<?php
session_start();
define('API_URL', 'http://localhost/proyecto7mo/Backend/public/api.php');
require_once __DIR__ . '/../Backend/models/Database.php';
require_once __DIR__ . '/../Backend/services/MovieService.php';

function api_get($route, $extra = [])
{
    $url = API_URL . '?' . http_build_query(array_merge(['route' => $route], $extra));
    $ctx = stream_context_create(['http' => ['ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

function api_post($route, $data, $token = null)
{
    $h = "Content-Type: application/json\r\n";
    if ($token) {
        $h .= "Authorization: Bearer $token\r\n";
    }
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => $h,
        'content'       => json_encode($data),
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents(API_URL . '?route=' . urlencode($route), false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

function json_response(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function js_json($value): string
{
    return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
}

$genre = $_GET['genre'] ?? '';
$q = trim($_GET['q'] ?? '');
$msg = '';
$active_movie_id = max(0, (int) ($_GET['movie_id'] ?? 0));
$favoriteMovieIds = [];
$allowedMovieGenres = ['Accion', 'Aventura', 'Animacion', 'Comedia', 'Crimen', 'Documental', 'Drama', 'Fantasia', 'Terror', 'Misterio', 'Romance', 'Ciencia ficcion', 'Thriller'];

function mapMovieApiGenre(?string $genre): string
{
    $genre = trim((string) $genre);
    $map = [
        'Action' => 'Accion',
        'Adventure' => 'Aventura',
        'Animation' => 'Animacion',
        'Comedy' => 'Comedia',
        'Crime' => 'Crimen',
        'Documentary' => 'Documental',
        'Drama' => 'Drama',
        'Fantasy' => 'Fantasia',
        'Horror' => 'Terror',
        'Mystery' => 'Misterio',
        'Romance' => 'Romance',
        'Sci-Fi' => 'Ciencia ficcion',
        'Science Fiction' => 'Ciencia ficcion',
        'Thriller' => 'Thriller',
    ];

    return $map[$genre] ?? 'Drama';
}

function movieApiJson(string $url): ?array
{
    $context = stream_context_create(['http' => [
        'timeout' => 8,
        'ignore_errors' => true,
        'header' => "User-Agent: NexoHub/1.0\r\n",
    ]]);
    $raw = @file_get_contents($url, false, $context);
    $data = $raw ? json_decode($raw, true) : null;

    return is_array($data) ? $data : null;
}

function movieApiSearch(string $query): array
{
    $query = trim($query);
    if ($query === '') {
        return [];
    }

    $url = 'https://v3-cinemeta.strem.io/catalog/movie/top/search=' . rawurlencode($query) . '.json';
    $data = movieApiJson($url);
    $metas = is_array($data['metas'] ?? null) ? $data['metas'] : [];

    return array_values(array_slice(array_map(function ($movie) {
        $release = (string) ($movie['releaseInfo'] ?? '');
        preg_match('/\d{4}/', $release, $yearMatch);

        return [
            'id' => $movie['id'] ?? '',
            'title' => $movie['name'] ?? 'Sin titulo',
            'year' => isset($yearMatch[0]) ? (int) $yearMatch[0] : null,
            'poster' => $movie['poster'] ?? '',
            'description' => $movie['description'] ?? '',
        ];
    }, $metas), 0, 10));
}

function movieApiMeta(string $id): ?array
{
    if (!preg_match('/^tt\d+$/', $id)) {
        return null;
    }

    $url = 'https://v3-cinemeta.strem.io/meta/movie/' . rawurlencode($id) . '.json';
    $data = movieApiJson($url);
    $meta = is_array($data['meta'] ?? null) ? $data['meta'] : null;
    if (!$meta) {
        return null;
    }

    $release = (string) ($meta['releaseInfo'] ?? '');
    preg_match('/\d{4}/', $release, $yearMatch);
    $genres = is_array($meta['genres'] ?? null) ? $meta['genres'] : [];
    $directorValue = $meta['director'] ?? [];
    $directors = is_array($directorValue) ? $directorValue : [$directorValue];

    return [
        'id' => $meta['id'] ?? $id,
        'title' => $meta['name'] ?? '',
        'movie_author' => trim((string) ($directors[0] ?? '')),
        'genre' => mapMovieApiGenre($genres[0] ?? ''),
        'year' => isset($yearMatch[0]) ? (int) $yearMatch[0] : (int) date('Y'),
        'description' => $meta['description'] ?? '',
        'cover_image_url' => $meta['poster'] ?? '',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'movie_api_search') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['results' => movieApiSearch($_GET['q'] ?? '')], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'GET' && ($_GET['action'] ?? '') === 'movie_api_meta') {
    header('Content-Type: application/json; charset=utf-8');
    $meta = movieApiMeta($_GET['id'] ?? '');
    echo json_encode($meta ? ['movie' => $meta] : ['error' => 'No se encontro la película'], JSON_UNESCAPED_UNICODE);
    exit;
}

function ensure_favorites_table(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS favorite_movies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            movie_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_movie_favorite (user_id, movie_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

function movieCoverRelativePath(int $movieId): ?string
{
    foreach (['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'] as $ext) {
        $file = __DIR__ . '/public/covers/' . $movieId . '.' . $ext;
        if (file_exists($file)) {
            return 'Frontend/public/covers/' . $movieId . '.' . $ext;
        }
    }

    return null;
}

function clearMovieCoverFiles(int $movieId): void
{
    $files = glob(__DIR__ . '/public/covers/' . $movieId . '.*');
    if (!is_array($files)) {
        return;
    }

    foreach ($files as $file) {
        if (is_file($file)) {
            @unlink($file);
        }
    }
}

function saveMovieCoverFromUpload(array $file, int $movieId): array
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return ['ok' => true, 'saved' => false];
    }

    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        return ['ok' => false, 'error' => 'No se pudo subir la portada del archivo'];
    }


    $tmpPath = $file['tmp_name'] ?? '';
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        return ['ok' => false, 'error' => 'Archivo de portada inválido'];
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = $finfo ? finfo_file($finfo, $tmpPath) : '';
    if ($finfo) {
        finfo_close($finfo);
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'Formato de portada no permitido (JPG, PNG, WEBP o GIF)'];
    }

    $coverDir = __DIR__ . '/public/covers';
    if (!is_dir($coverDir) && !mkdir($coverDir, 0755, true)) {
        return ['ok' => false, 'error' => 'No se pudo preparar la carpeta de portadas'];
    }

    clearMovieCoverFiles($movieId);
    $destination = $coverDir . '/' . $movieId . '.' . $allowed[$mime];

    if (!move_uploaded_file($tmpPath, $destination)) {
        return ['ok' => false, 'error' => 'No se pudo guardar la portada subida'];
    }

    return ['ok' => true, 'saved' => true];
}

function saveMovieCoverFromUrl(string $url, int $movieId): array
{
    $url = trim($url);
    if ($url === '') {
        return ['ok' => true, 'saved' => false];
    }

    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        return ['ok' => false, 'error' => 'La URL de la portada no es válida'];
    }

    $context = stream_context_create(['http' => ['timeout' => 10, 'ignore_errors' => true]]);
    $content = @file_get_contents($url, false, $context);
    if ($content === false) {
        return ['ok' => false, 'error' => 'No se pudo descargar la portada desde la URL'];
    }

    $mime = '';
    foreach ($http_response_header ?? [] as $header) {
        if (stripos($header, 'Content-Type:') === 0) {
            $mime = trim(strtolower(explode(';', substr($header, 13))[0]));
            break;
        }
    }

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
        'image/gif' => 'gif',
    ];

    if (!isset($allowed[$mime])) {
        return ['ok' => false, 'error' => 'La URL no apunta a una imagen válida (JPG, PNG, WEBP o GIF)'];
    }

    $coverDir = __DIR__ . '/public/covers';
    if (!is_dir($coverDir) && !mkdir($coverDir, 0755, true)) {
        return ['ok' => false, 'error' => 'No se pudo preparar la carpeta de portadas'];
    }

    clearMovieCoverFiles($movieId);
    $destination = $coverDir . '/' . $movieId . '.' . $allowed[$mime];
    if (file_put_contents($destination, $content) === false) {
        return ['ok' => false, 'error' => 'No se pudo guardar la portada descargada'];
    }

    return ['ok' => true, 'saved' => true];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'submit_review';

    if (empty($_SESSION['user'])) {
        $msg = 'Debes iniciar sesión para publicar películas o responder reseñas.';
    } else {
        if ($action === 'toggle_favorite') {
            $userId = (int) ($_SESSION['user']['id'] ?? 0);
            $movieId = (int) ($_POST['movie_id'] ?? 0);

            if (!$userId || !$movieId) {
                json_response(['success' => false, 'message' => 'No se pudo guardar el favorito'], 400);
            }

            try {
                $db = Database::getInstance()->getConnection();
                ensure_favorites_table($db);

                $movieExists = $db->prepare("SELECT id FROM movies WHERE id = ? LIMIT 1");
                $movieExists->execute([$movieId]);
                if (!$movieExists->fetchColumn()) {
                    json_response(['success' => false, 'message' => 'La película no existe'], 404);
                }

                $stmt = $db->prepare("SELECT id FROM favorite_movies WHERE user_id = ? AND movie_id = ? LIMIT 1");
                $stmt->execute([$userId, $movieId]);

                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $db->prepare("DELETE FROM favorite_movies WHERE user_id = ? AND movie_id = ?")->execute([$userId, $movieId]);
                    json_response(['success' => true, 'favorite' => false]);
                } else {
                    $db->prepare("INSERT INTO favorite_movies (user_id, movie_id) VALUES (?, ?)")->execute([$userId, $movieId]);
                    json_response(['success' => true, 'favorite' => true]);
                }
            } catch (Exception $e) {
                json_response(['success' => false, 'message' => 'No se pudo guardar el favorito'], 500);
            }
        }

        if ($action === 'create_movie') {
            $movieGenre = trim((string) ($_POST['genre'] ?? ''));
            $movieDescription = trim((string) ($_POST['description'] ?? ''));
            $coverUrlInput = trim((string) ($_POST['cover_image_url'] ?? ''));
            $externalMovieId = trim((string) ($_POST['external_movie_id'] ?? ''));
            $hasCoverFile = (($_FILES['cover_image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);
            $autoApproved = $externalMovieId !== '' && $coverUrlInput !== '' && !$hasCoverFile;

            if (!in_array($movieGenre, $allowedMovieGenres, true)) {
                $msg = 'Debes seleccionar un género válido.';
                goto end_create_movie;
            }

            if ($movieDescription === '') {
                $msg = 'La descripción es obligatoria.';
                goto end_create_movie;
            }

            if ($coverUrlInput === '' && !$hasCoverFile) {
                $msg = 'Debes completar la URL de imagen o seleccionar un archivo de portada.';
                goto end_create_movie;
            }

            $service = new MovieService();
            $res = $service->createMovie(
                (int) ($_SESSION['user']['id'] ?? 0),
                $_POST['title'] ?? '',
                $movieGenre,
                (int) ($_POST['year'] ?? 0),
                $movieDescription,
                $_POST['movie_author'] ?? '',
                $autoApproved
            );
            if (isset($res['ok'], $res['movie_id'])) {
                $movieId = (int) $res['movie_id'];
                $coverResult = saveMovieCoverFromUpload($_FILES['cover_image_file'] ?? [], $movieId);

                if (empty($coverResult['ok'])) {
                    $msg = 'Película publicada, pero la portada no se pudo guardar: ' . ($coverResult['error'] ?? 'error desconocido');
                } elseif (!empty($coverResult['saved'])) {
                    $msg = 'Película publicada correctamente con portada.';
                } else {
                    $coverUrlResult = saveMovieCoverFromUrl($coverUrlInput, $movieId);
                    if (empty($coverUrlResult['ok'])) {
                        $msg = 'Película publicada, pero la portada por URL falló: ' . ($coverUrlResult['error'] ?? 'error desconocido');
                    } elseif (!empty($coverUrlResult['saved'])) {
                        $msg = 'Película publicada correctamente con portada.';
                    } else {
                        $msg = $autoApproved ? 'Película publicada correctamente.' : 'Película enviada a revisión.';
                    }
                }
            } else {
                $msg = $res['error'] ?? 'Error al publicar película';
            }
            end_create_movie:
        } elseif ($action === 'respond_review') {
            if (empty($_SESSION['token'])) {
                $msg = 'Debes iniciar sesión para responder reseñas.';
                goto end_post_actions;
            }
            $res = api_post('reviews/' . urlencode((int) ($_POST['review_id'] ?? 0)) . '/responses', [
                'comment' => $_POST['comment'] ?? '',
            ], $_SESSION['token']);
            $msg = isset($res['ok']) ? 'Respuesta enviada correctamente.' : ($res['error'] ?? 'Error al responder la reseña');
            if (isset($res['ok'])) {
                $active_movie_id = (int) ($_POST['movie_id'] ?? 0);
            }
        } else {
            if (empty($_SESSION['token'])) {
                $msg = 'Debes iniciar sesión para publicar reseñas.';
                goto end_post_actions;
            }
            $movie_id = (int) ($_POST['movie_id'] ?? 0);
            $res = api_post('reviews', [
                'movie_id' => $movie_id,
                'rating' => (int) ($_POST['rating'] ?? 0),
                'comment' => $_POST['comment'] ?? '',
            ], $_SESSION['token']);
            $msg = isset($res['ok']) ? 'Reseña publicada (+reputación por género)' : ($res['error'] ?? 'Error');
            if (isset($res['ok'])) {
                $active_movie_id = $movie_id;
            }
        }
    }
    end_post_actions:
}

$películas = $genre ? api_get('movies/genre/' . urlencode($genre)) : api_get('movies');
$películas = is_array($películas) ? $películas : [];

$seenMovies = [];
$películas = array_values(array_filter($películas, function ($movie) use (&$seenMovies) {
    $key = mb_strtolower(trim((string) ($movie['title'] ?? '')), 'UTF-8') . '|' . (int) ($movie['year'] ?? 0);
    if (isset($seenMovies[$key])) {
        return false;
    }
    $seenMovies[$key] = true;
    return true;
}));

if ($q !== '') {
    $películas = array_values(array_filter($películas, function ($movie) use ($q) {
        return stripos($movie['title'] ?? '', $q) !== false;
    }));
}

$sort = $_GET['sort'] ?? 'alphabetical';
$validSorts = ['alphabetical', 'recent', 'reviews', 'rating'];
if (!in_array($sort, $validSorts, true)) {
    $sort = 'alphabetical';
}

usort($películas, function ($a, $b) use ($sort) {
    return match ($sort) {
        'recent' => ((int) ($b['year'] ?? 0) <=> (int) ($a['year'] ?? 0)) ?: strcasecmp($a['title'] ?? '', $b['title'] ?? ''),
        'reviews' => ((int) ($b['reviews_count'] ?? 0) <=> (int) ($a['reviews_count'] ?? 0)) ?: strcasecmp($a['title'] ?? '', $b['title'] ?? ''),
        'rating' => ((float) ($b['average_rating'] ?? 0) <=> (float) ($a['average_rating'] ?? 0)) ?: ((int) ($b['reviews_count'] ?? 0) <=> (int) ($a['reviews_count'] ?? 0)),
        default => strcasecmp($a['title'] ?? '', $b['title'] ?? ''),
    };
});

$showGenreRows = $genre === '' && $q === '';
$moviesPerPage = $showGenreRows ? max(1, count($películas)) : 12;
$page = max(1, (int) ($_GET['page'] ?? 1));
$totalMovies = count($películas);
$totalPages = max(1, (int) ceil($totalMovies / $moviesPerPage));
$page = min($page, $totalPages);
$visiblePelículas = array_slice($películas, ($page - 1) * $moviesPerPage, $moviesPerPage);

if ($active_movie_id > 0 && !in_array($active_movie_id, array_map(fn($movie) => (int) ($movie['id'] ?? 0), $visiblePelículas), true)) {
    foreach ($películas as $movie) {
        if ((int) ($movie['id'] ?? 0) === $active_movie_id) {
            array_unshift($visiblePelículas, $movie);
            break;
        }
    }
}

foreach ($visiblePelículas as &$movie) {
    $coverPath = movieCoverRelativePath((int) $movie['id']);
    $posterUrl = trim((string) ($movie['poster_url'] ?? ''));
    $movie['_cover_path'] = $coverPath;
    $movie['_cover_url'] = $coverPath !== null ? '/proyecto7mo/' . $coverPath : $posterUrl;
    $movie['_has_cover'] = $coverPath !== null || $posterUrl !== '';
}
unset($movie);
$moviesByGenre = [];
foreach ($visiblePelículas as $movie) {
    $moviesByGenre[$movie['genre'] ?? 'General'][] = $movie;
}

$nextPageUrl = '';
if ($page < $totalPages) {
    $nextPageUrl = '/proyecto7mo/Frontend/explorar.php?' . http_build_query(array_filter([
        'genre' => $genre ?: null,
        'q' => $q ?: null,
        'page' => $page + 1,
    ], fn($value) => $value !== null && $value !== ''));
}

if (!empty($_SESSION['user']['id'])) {
    try {
        $db = Database::getInstance()->getConnection();
        ensure_favorites_table($db);
        $stmt = $db->prepare("SELECT movie_id FROM favorite_movies WHERE user_id = ?");
        $stmt->execute([(int) $_SESSION['user']['id']]);
        $favoriteMovieIds = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));
    } catch (Exception $e) {
        $favoriteMovieIds = [];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar Películas - NexoHub</title>
    <meta name="description" content="Explora películas por género y reseñas de la comunidad">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/explorar.css">
    <link href="assets/css/styles.css" rel="stylesheet">
    <link rel="icon" href="public/nhlogo.png" type="image/png">
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
    <?php include 'components/header.php'; ?>

    <main class="flex-1 py-12">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold md:text-4xl text-foreground">
                    Explorar Películas
                </h1>
                <?php if ($genre): ?>
                    <p class="mt-2 text-muted-foreground">Filtrado por género: <strong class="text-primary"><?= htmlspecialchars($genre) ?></strong></p>
                <?php else: ?>
                    <p class="mt-2 text-muted-foreground">Descubre películas recomendadas por expertos en cada género</p>
                <?php endif; ?>
            </div>

            <!-- Alerta de Respuesta del Formulario -->
            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-lg bg-card border border-border text-foreground flex items-center gap-2">
                    <span class="text-primary font-bold">📢</span>
                    <span><?= htmlspecialchars($msg) ?></span>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="mb-8 flex flex-wrap gap-4">
                <?php if (!empty($_SESSION['user'])): ?>
                    <button type="button" class="add-movie-btn" onclick="openCreateMovieModal()">
                        Agregar película
                    </button>
                <?php endif; ?>
                <form method="get" action="/proyecto7mo/Frontend/explorar.php" class="explore-search-form">
                    <?php if ($genre): ?>
                        <input type="hidden" name="genre" value="<?= htmlspecialchars($genre) ?>">
                    <?php endif; ?>
                    <input id="exploreSearchInput" type="search" name="q" value="<?= htmlspecialchars($q) ?>" class="explore-search-input" placeholder="Buscar película por nombre">
                    <button type="submit" class="explore-search-button" aria-label="Buscar película">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                        </svg>
                    </button>
                </form>
                <select onchange="location = this.value;" class="rounded-lg border border-border bg-card px-4 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="explorar.php">Todos los Géneros</option>
                    <?php foreach ($allowedMovieGenres as $g): ?>
                        <option value="explorar.php?genre=<?= urlencode($g) ?>" <?= $genre === $g ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select onchange="location = this.value;" class="rounded-lg border border-border bg-card px-4 py-2 text-foreground focus:outline-none" aria-label="Filtrar películas">
                    <?php
                    $sortBase = ['genre' => $genre ?: null, 'q' => $q ?: null];
                    $sortLabels = [
                        'alphabetical' => 'Orden alfabético',
                        'recent' => 'Más reciente',
                        'reviews' => 'Más reseñas',
                        'rating' => 'Mejor valoración',
                    ];
                    ?>
                    <?php foreach ($sortLabels as $sortValue => $sortLabel): ?>
                        <option value="explorar.php?<?= http_build_query(array_filter($sortBase + ['sort' => $sortValue], fn($value) => $value !== null && $value !== '')) ?>" <?= $sort === $sortValue ? 'selected' : '' ?>>
                            🔎 <?= htmlspecialchars($sortLabel) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <?php if (!empty($_SESSION['user'])): ?>
                <div id="createMovieModalBackdrop" class="create-movie-modal-backdrop" onclick="closeCreateMovieModalOutside(event)">
                    <div class="create-movie-modal" onclick="event.stopPropagation()">
                        <div class="create-movie-modal-header">
                            <h2>Publicar nueva película</h2>
                            <button type="button" class="create-movie-close-btn" onclick="closeCreateMovieModal()" aria-label="Cerrar">&times;</button>
                        </div>
                    <form method="post" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-2 create-movie-form" onsubmit="return validateCreateMovieForm(this)">
                        <input type="hidden" name="action" value="create_movie">
                        <input type="hidden" name="external_movie_id" value="">
                        <div class="movie-picker-panel lg:col-span-2">
                            <button type="button" class="movie-picker-toggle" onclick="toggleMoviePicker()">
                                Seleccionar película
                            </button>
                            <div id="moviePickerBox" class="movie-picker-box">
                                <div class="movie-picker-search-row">
                                    <input id="movieApiSearchInput" type="search" class="movie-picker-search" placeholder="Buscar en IMDb">
                                    <button type="button" class="movie-picker-search-btn" onclick="searchExternalMovies()">Buscar</button>
                                </div>
                                <div id="movieApiResults" class="movie-picker-results">
                                    <button type="button" class="movie-picker-result movie-picker-new" onclick="useManualMovieEntry()">
                                        <span>Nueva Película</span>
                                        <small>Completar los datos manualmente</small>
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground">Título</label>
                            <input type="text" name="title" required class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nombre de la película">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground">Nombre del autor</label>
                            <input type="text" name="movie_author" required maxlength="255" class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Autor de la película">
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground">Género</label>
                            <select name="genre" required class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary">
                                <option value="">Selecciona un género</option>
                                <?php foreach ($allowedMovieGenres as $validGenre): ?>
                                    <option value="<?= htmlspecialchars($validGenre) ?>"><?= htmlspecialchars($validGenre) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground">Año</label>
                            <input type="number" name="year" min="1880" max="2100" required class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="<?= date('Y') ?>" value="<?= date('Y') ?>">
                        </div>
                        <div class="lg:col-start-2 lg:row-start-3">
                            <label class="text-xs font-semibold text-muted-foreground">Descripción</label>
                            <input type="text" name="description" required class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Un breve resumen">
                        </div>
                        <div class="lg:col-start-1 lg:row-start-3 w-full">
                            <label class="text-xs font-semibold text-muted-foreground">Portada (URL o seleccionar archivo)</label>
                            <div class="mt-2 flex items-center gap-2 rounded-lg border border-border bg-background px-2 py-1.5 focus-within:ring-1 focus-within:ring-primary">
                                <input type="url" name="cover_image_url" class="w-full border-0 bg-transparent px-2 py-1 text-foreground focus:outline-none js-cover-url" placeholder="https://ejemplo.com/portada.jpg" oninput="syncCoverRequired(this.form)">
                                <input type="file" id="cover_image_file" name="cover_image_file" accept="image/png,image/jpeg,image/webp,image/gif" class="hidden js-cover-file" onchange="syncCoverRequired(this.form); updateCoverFileLabel(this)">
                                <label for="cover_image_file" class="shrink-0 cursor-pointer rounded-md border border-border bg-card px-3 py-1.5 text-xs font-semibold text-foreground hover:bg-primary/10 transition-colors">
                                    Seleccionar archivo
                                </label>
                            </div>
                            <p id="cover-file-label" class="mt-1 text-center text-xs text-muted-foreground">Ningún archivo seleccionado</p>
                        </div>
                        <div class="lg:col-span-2 flex justify-center">
                            <button type="submit" class="rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition-colors">
                                Publicar película
                            </button>
                        </div>
                    </form>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Películas -->
            <?php if (empty($películas)): ?>
                <div class="text-center py-20 bg-card rounded-xl border border-border">
                    <span class="text-4xl">🎬</span>
                    <p class="mt-4 text-muted-foreground">No se encontraron películas en esta categoría.</p>
                </div>
            <?php elseif ($showGenreRows): ?>
                <div class="explore-genre-sections">
                    <?php foreach ($moviesByGenre as $sectionGenre => $sectionMovies): ?>
                        <section class="explore-genre-section">
                            <div class="explore-genre-header">
                                <h2><?= htmlspecialchars($sectionGenre) ?></h2>
                                <div class="explore-row-controls">
                                    <button type="button" onclick="scrollMovieSection('genre-<?= md5($sectionGenre) ?>', -1)" aria-label="Ver anteriores">‹</button>
                                    <button type="button" onclick="scrollMovieSection('genre-<?= md5($sectionGenre) ?>', 1)" aria-label="Ver siguientes">›</button>
                                </div>
                            </div>
                            <div id="genre-<?= md5($sectionGenre) ?>" class="explore-movie-row">
                                <?php foreach ($sectionMovies as $p): ?>
                                    <div class="movie-card-container" onclick="openMovieModal(<?= (int)$p['id'] ?>)">
                                        <div class="movie-flip-card">
                                            <div class="movie-flip-card-front relative flex flex-col justify-between">
                                                <?php if (!empty($p['_cover_url'])): ?>
                                                    <img src="<?= htmlspecialchars($p['_cover_url']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="w-full h-full object-cover" loading="lazy" decoding="async">
                                                <?php else: ?>
                                                    <div class="w-full h-full bg-gradient-to-br from-primary/20 to-secondary/30 flex flex-col items-center justify-center p-4">
                                                        <span class="text-5xl mb-4">🎥</span>
                                                    </div>
                                                <?php endif; ?>
                                                <div class="movie-poster-overlay">
                                                    <h3 class="text-xl font-bold text-foreground line-clamp-2"><?= htmlspecialchars($p['title']) ?></h3>
                                                    <p class="text-xs text-muted-foreground mt-1"><?= htmlspecialchars($p['genre'] ?? 'General') ?> • <?= (int) ($p['year'] ?? 2024) ?><span class="click-here">Click Aquí</span></p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </section>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <?php foreach ($visiblePelículas as $p): ?>
                        <div class="movie-card-container" onclick="openMovieModal(<?= (int)$p['id'] ?>)">
                            <div class="movie-flip-card">
                                <!-- FRONT SIDE -->
                                <div class="movie-flip-card-front relative flex flex-col justify-between">
                                    <?php 
                                    $coverUrl = $p['_cover_url'] ?? '';
                                    $hasCover = $coverUrl !== '';
                                    if ($hasCover): 
                                    ?>
                                        <img src="<?= htmlspecialchars($p['_cover_url']) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="w-full h-full object-cover" loading="lazy" decoding="async">
                                    <?php else: ?>
                                        <!-- Fallback gradient design -->
                                        <div class="w-full h-full bg-gradient-to-br from-primary/20 to-secondary/30 flex flex-col items-center justify-center p-4">
                                            <span class="text-5xl mb-4">🎥</span>
                                        </div>
                                    <?php endif; ?>
                                    
                                    <!-- Cover detail overlay -->
                                    <div class="movie-poster-overlay">
                                        <h3 class="text-xl font-bold text-foreground line-clamp-2"><?= htmlspecialchars($p['title']) ?></h3>
                                        <p class="text-xs text-muted-foreground mt-1"><?= htmlspecialchars($p['genre'] ?? 'General') ?> • <?= (int) ($p['year'] ?? 2024) ?><span class="click-here">Click Aquí</span></p>
                                    </div>
                                </div>
                                
                                <!-- BACK SIDE -->
                               
                                
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
                <?php if ($nextPageUrl): ?>
                    <div class="mt-8 flex justify-center">
                        <a href="<?= htmlspecialchars($nextPageUrl) ?>" class="load-more-movies-btn">Ver más películas</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Backdrop -->
    <div id="movieModalBackdrop" class="modal-backdrop" onclick="closeMovieModalOutside(event)">
        <div class="modal-container" onclick="event.stopPropagation()">
            <!-- LEFT COLUMN: Movie Poster -->
            <div class="modal-left">
                <img id="modalMoviePoster" src="" alt="Portada" class="modal-poster-img">
                <button type="button" id="favoriteMovieBtn" class="favorite-movie-btn" aria-label="Agregar a favoritos">&#9734;</button>
                <div class="modal-poster-overlay">
                    <span id="modalMovieGenreYear" class="text-xs font-bold text-primary tracking-wide uppercase">GÉNERO &bull; AÑO</span>
                    <h2 id="modalMovieTitle" class="text-2xl md:text-3xl font-extrabold text-foreground mt-1">Título de la Película</h2>
                    <p id="modalMovieAuthor" class="modal-movie-author"></p>
                    <p id="modalMovieDesc" class="text-sm text-muted-foreground mt-3 line-clamp-4">Descripción de la película...</p>
                </div>
            </div>
            
            <!-- RIGHT COLUMN: Reviews Feed -->
            <div class="modal-right">
                <div class="modal-header">
                    <span class="text-sm font-bold text-muted-foreground uppercase tracking-widest">Reseñas</span>
                    <div class="modal-close-btn" onclick="closeMovieModal()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </div>
                </div>
                
                <div class="modal-body custom-scrollbar" id="modalReviewsBody">
                    <!-- Reviews and replies loaded dynamically here -->
                </div>
            </div>
        </div>
    </div>

    <?php include 'components/footer.php'; ?>

    <script>
    // Prepare all movie data on the client side
    const moviesData = {
        <?php foreach ($visiblePelículas as $p): ?>
            "<?= (int)$p['id'] ?>": {
                id: <?= (int)$p['id'] ?>,
                author_user_id: <?= isset($p['user_id']) ? (int) $p['user_id'] : 0 ?>,
                author_name: <?= js_json($p['author_name'] ?? '') ?>,
                movie_author: <?= js_json($p['movie_author'] ?? '') ?>,
                title: <?= js_json($p['title']) ?>,
                genre: <?= js_json($p['genre'] ?? 'General') ?>,
                year: <?= (int)($p['year'] ?? 2024) ?>,
                description: <?= js_json($p['description'] ?? '') ?>,
                coverUrl: <?= js_json($p['_cover_url'] ?? '') ?>,
                hasCover: <?= !empty($p['_has_cover']) ? 'true' : 'false' ?>,
                favorite: <?= in_array((int) $p['id'], $favoriteMovieIds, true) ? 'true' : 'false' ?>,
                reviews: null
            },
        <?php endforeach; ?>
    };

    const currentUser = <?= isset($_SESSION['user']) ? js_json($_SESSION['user']) : 'null' ?>;
    const activeMovieId = <?= $active_movie_id ?>;
    const activeReviewId = <?= max(0, (int) ($_GET['review_id'] ?? 0)) ?>;
    let currentModalMovie = null;

    // Helper to safely escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function reviewStars(rating) {
        const value = Math.max(0, Math.min(5, Number(rating) || 0));
        return `${'\u2605'.repeat(value)}${'\u2606'.repeat(5 - value)}`;
    }

    function userInitial(name) {
        return escapeHtml(String(name || '?').trim().charAt(0).toUpperCase() || '?');
    }

    function validateCreateMovieForm(form) {
        if (!form) return false;
        syncCoverRequired(form);
        return form.checkValidity();
    }

    function syncCoverRequired(form) {
        if (!form) return;

        const urlInput = form.querySelector('.js-cover-url');
        const fileInput = form.querySelector('.js-cover-file');
        if (!urlInput || !fileInput) return;

        const hasUrl = urlInput.value.trim() !== '';
        const hasFile = (fileInput.files?.length || 0) > 0;
        const needsOne = !hasUrl && !hasFile;

        // Trigger native required message instead of custom alert.
        urlInput.required = needsOne;
        fileInput.required = false;

        const message = needsOne ? 'Completa la URL de imagen o selecciona un archivo.' : '';
        urlInput.setCustomValidity(message);
        fileInput.setCustomValidity('');
    }

    function updateCoverFileLabel(fileInput) {
        const label = document.getElementById('cover-file-label');
        if (!label) return;
        label.textContent = fileInput?.files?.[0]?.name || 'Ningún archivo seleccionado';
    }

    function getCreateMovieForm() {
        return document.querySelector('form input[name="action"][value="create_movie"]')?.form || null;
    }

    function toggleMoviePicker() {
        const box = document.getElementById('moviePickerBox');
        if (!box) return;
        box.classList.toggle('open');
        const input = document.getElementById('movieApiSearchInput');
        if (box.classList.contains('open') && input) input.focus();
    }

    function renderMovieApiResults(results, message = '') {
        const container = document.getElementById('movieApiResults');
        if (!container) return;

        const newMovieButton = `
            <button type="button" class="movie-picker-result movie-picker-new" onclick="useManualMovieEntry()">
                <span>Nueva Película</span>
                <small>Completar los datos manualmente</small>
            </button>
        `;

        if (message) {
            container.innerHTML = `<p class="movie-picker-message">${escapeHtml(message)}</p>${newMovieButton}`;
            return;
        }

        container.innerHTML = results.map(movie => `
            <button type="button" class="movie-picker-result" onclick="selectExternalMovie('${escapeHtml(movie.id)}')">
                ${movie.poster ? `<img src="${escapeHtml(movie.poster)}" alt="">` : '<span class="movie-picker-poster-placeholder">🎬</span>'}
                <span>
                    <strong>${escapeHtml(movie.title)}</strong>
                    <small>${movie.year || 'Sin año'}</small>
                </span>
            </button>
        `).join('') + newMovieButton;
    }

    async function searchExternalMovies() {
        const input = document.getElementById('movieApiSearchInput');
        const query = input?.value.trim() || '';
        if (query.length < 2) {
            renderMovieApiResults([], 'Escribe al menos 2 caracteres para buscar.');
            return;
        }

        renderMovieApiResults([], 'Buscando películas...');

        try {
            const response = await fetch(`/proyecto7mo/Frontend/explorar.php?action=movie_api_search&q=${encodeURIComponent(query)}`);
            const data = await response.json();
            const results = Array.isArray(data.results) ? data.results : [];
            renderMovieApiResults(results, results.length ? '' : 'No encontramos resultados en la API.');
        } catch (error) {
            renderMovieApiResults([], 'No se pudo consultar la API. Puedes usar Nueva Película.');
        }
    }

    async function selectExternalMovie(movieId) {
        if (!movieId) return;
        renderMovieApiResults([], 'Cargando datos de la película...');

        try {
            const response = await fetch(`/proyecto7mo/Frontend/explorar.php?action=movie_api_meta&id=${encodeURIComponent(movieId)}`);
            const data = await response.json();
            if (!data.movie) {
                renderMovieApiResults([], 'No se pudieron cargar los detalles. Puedes usar Nueva Película.');
                return;
            }

            fillCreateMovieForm(data.movie);
            const box = document.getElementById('moviePickerBox');
            if (box) box.classList.remove('open');
        } catch (error) {
            renderMovieApiResults([], 'No se pudieron cargar los detalles. Puedes usar Nueva Película.');
        }
    }

    function fillCreateMovieForm(movie) {
        const form = getCreateMovieForm();
        if (!form) return;

        form.elements.title.value = movie.title || '';
        form.elements.external_movie_id.value = movie.id || '';
        form.elements.movie_author.value = movie.movie_author || 'Desconocido';
        form.elements.genre.value = movie.genre || '';
        form.elements.year.value = movie.year || new Date().getFullYear();
        form.elements.description.value = movie.description || '';
        form.elements.cover_image_url.value = movie.cover_image_url || '';

        syncCoverRequired(form);
        updateCoverFileLabel(form.querySelector('.js-cover-file'));
    }

    function useManualMovieEntry() {
        const form = getCreateMovieForm();
        if (!form) return;

        form.reset();
        form.querySelector('input[name="action"]').value = 'create_movie';
        form.elements.external_movie_id.value = '';
        form.elements.year.value = new Date().getFullYear();
        updateCoverFileLabel(form.querySelector('.js-cover-file'));
        syncCoverRequired(form);

        const box = document.getElementById('moviePickerBox');
        if (box) box.classList.remove('open');
        form.elements.title.focus();
    }

    function openCreateMovieModal() {
        const modal = document.getElementById('createMovieModalBackdrop');
        if (!modal) return;
        modal.classList.add('active');
        document.body.style.overflow = 'hidden';
    }

    function closeCreateMovieModal() {
        const modal = document.getElementById('createMovieModalBackdrop');
        if (!modal) return;
        modal.classList.remove('active');
        document.body.style.overflow = '';
    }

    function closeCreateMovieModalOutside(event) {
        if (event.target === document.getElementById('createMovieModalBackdrop')) {
            closeCreateMovieModal();
        }
    }

    window.addEventListener('DOMContentLoaded', () => {
        const createMovieForm = getCreateMovieForm();
        if (createMovieForm) {
            syncCoverRequired(createMovieForm);
        }

        const movieApiSearchInput = document.getElementById('movieApiSearchInput');
        if (movieApiSearchInput) {
            movieApiSearchInput.addEventListener('keydown', (event) => {
                if (event.key === 'Enter') {
                    event.preventDefault();
                    searchExternalMovies();
                }
            });
        }
    });

    // Modal Control Functions
    function openMovieModal(movieId) {
        const movie = moviesData[movieId];
        if (!movie) return;

        // Set left side details
        const posterImg = document.getElementById('modalMoviePoster');
        if (movie.hasCover) {
            posterImg.src = movie.coverUrl;
            posterImg.style.display = 'block';
        } else {
            // Simulated generic background if no poster image
            posterImg.src = 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="100" height="150" viewBox="0 0 100 150"><rect width="100" height="150" fill="%231a1a2e"/><circle cx="50" cy="65" r="20" fill="%23e94560" opacity="0.3"/><text x="50" y="70" font-family="sans-serif" font-size="20" fill="%23f5f5f5" text-anchor="middle">🎥</text></svg>';
        }

        document.getElementById('modalMovieGenreYear').innerText = `${movie.genre} • ${movie.year}`;
        document.getElementById('modalMovieTitle').innerText = movie.title;
        const authorEl = document.getElementById('modalMovieAuthor');
        const visibleAuthorName = movie.movie_author || movie.author_name;
        if (visibleAuthorName) {
            authorEl.innerText = `Producido por: ${visibleAuthorName}`;
            authorEl.style.display = 'block';
        } else {
            authorEl.innerText = '';
            authorEl.style.display = 'none';
        }
        document.getElementById('modalMovieDesc').innerText = movie.description || 'Sin descripción disponible.';

        updateFavoriteButton(movie);

        renderReviewsLoading();
        loadMovieReviews(movie, movie.id === activeMovieId).then(() => {
            renderReviewsList(movie);
        });

        // Show Modal
        document.getElementById('movieModalBackdrop').classList.add('active');
        document.body.style.overflow = 'hidden'; // Disable background scroll
    }

    function closeMovieModal() {
        document.getElementById('movieModalBackdrop').classList.remove('active');
        document.body.style.overflow = ''; // Restore scroll
    }

    function closeMovieModalOutside(event) {
        if (event.target === document.getElementById('movieModalBackdrop')) {
            closeMovieModal();
        }
    }

    function updateFavoriteButton(movie) {
        const btn = document.getElementById('favoriteMovieBtn');
        if (!btn) return;

        btn.dataset.movieId = movie.id;
        btn.textContent = movie.favorite ? '★' : '☆';
        btn.classList.toggle('active', !!movie.favorite);
        btn.title = movie.favorite ? 'Quitar de favoritos' : 'Agregar a favoritos';
        btn.setAttribute('aria-label', btn.title);
    }

    async function toggleFavorite(movieId) {
        if (!currentUser) {
            window.location.href = '/proyecto7mo/Frontend/login.php';
            return;
        }

        const movie = moviesData[movieId];
        if (!movie) return;
        currentModalMovie = movie;

        const btn = document.getElementById('favoriteMovieBtn');
        if (btn) btn.disabled = true;

        let data = null;
        try {
            const response = await fetch('/proyecto7mo/Frontend/api/favorite_movie.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/x-www-form-urlencoded'},
                credentials: 'same-origin',
                body: new URLSearchParams({
                    movie_id: movieId
                })
            });
            data = await response.json();
        } catch (error) {
            data = {success: false, message: 'No se pudo guardar el favorito'};
        } finally {
            if (btn) btn.disabled = false;
        }

        if (data.success) {
            movie.favorite = !!data.favorite;
            updateFavoriteButton(movie);
        } else if (data.message) {
            alert(data.message);
        }
    }

    const favoriteMovieBtn = document.getElementById('favoriteMovieBtn');
    if (favoriteMovieBtn) {
        favoriteMovieBtn.addEventListener('click', (event) => {
            event.preventDefault();
            event.stopPropagation();
            toggleFavorite(favoriteMovieBtn.dataset.movieId);
        });
    }

    async function toggleReviewHeart(reviewId) {
        if (!currentUser) {
            window.location.href = '/proyecto7mo/Frontend/login.php';
            return;
        }

        const btn = document.getElementById(`review-heart-${reviewId}`);
        if (btn) btn.disabled = true;

        try {
            const response = await fetch(`/proyecto7mo/Backend/public/api.php?route=${encodeURIComponent(`reviews/${reviewId}/heart`)}`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', 'Authorization': `Bearer <?= htmlspecialchars($_SESSION['token'] ?? '') ?>`}
            });
            const data = await response.json();
            if (!data.ok) {
                alert(data.error || 'No se pudo guardar el corazon.');
                return;
            }
            const countEl = document.getElementById(`review-heart-count-${reviewId}`);
            if (countEl) countEl.textContent = data.hearts_count;
            if (btn) btn.classList.toggle('active', !!data.hearted);
            if (currentModalMovie && Array.isArray(currentModalMovie.reviews)) {
                const review = currentModalMovie.reviews.find(item => Number(item.id) === Number(reviewId));
                if (review) review.hearts_count = Number(data.hearts_count || 0);
            }
        } catch (error) {
            alert('No se pudo guardar el corazon.');
        } finally {
            if (btn) btn.disabled = false;
        }
    }

    // Escape key closes modal
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeMovieModal();
            closeCreateMovieModal();
        }
    });

    // Expand/collapse review replies
    function toggleReplies(reviewId) {
        const container = document.getElementById(`replies-container-${reviewId}`);
        const btn = document.getElementById(`toggle-replies-btn-${reviewId}`);
        if (!container) return;

        container.classList.toggle('open');
        btn.classList.toggle('open');

        if (container.classList.contains('open')) {
            loadReviewResponses(reviewId);
        }
    }

    // Render responses markup
    function renderResponses(responses) {
        if (responses.length === 0) {
            return `<p class="review-reply-empty">Todavía no hay respuestas.</p>`;
        }
        return responses.map(resp => `
            <article class="review-reply-card">
                <div class="review-reply-avatar">${userInitial(resp.user_name)}</div>
                <div class="review-reply-content">
                    <div class="review-reply-header">
                        <strong>${escapeHtml(resp.user_name)}</strong>
                    </div>
                    <p>${escapeHtml(resp.comment)}</p>
                </div>
            </article>
        `).join('');
    }

    function reviewsCacheKey(movieId) {        return `nexohub_movie_reviews_${movieId}`;
    }

    function getCachedReviews(movieId) {
        try {
            const cached = JSON.parse(sessionStorage.getItem(reviewsCacheKey(movieId)) || 'null');
            if (!cached || !Array.isArray(cached.reviews)) return null;
            if (Date.now() - cached.savedAt > 5 * 60 * 1000) return null;
            return cached.reviews;
        } catch (error) {
            return null;
        }
    }

    function setCachedReviews(movieId, reviews) {
        try {
            sessionStorage.setItem(reviewsCacheKey(movieId), JSON.stringify({
                savedAt: Date.now(),
                reviews
            }));
        } catch (error) {
            // Storage can be unavailable; the lazy load still works without cache.
        }
    }

    function responsesCacheKey(reviewId) {
        return `nexohub_review_responses_${reviewId}`;
    }

    function getCachedResponses(reviewId) {
        try {
            const cached = JSON.parse(sessionStorage.getItem(responsesCacheKey(reviewId)) || 'null');
            if (!cached || !Array.isArray(cached.responses)) return null;
            if (Date.now() - cached.savedAt > 5 * 60 * 1000) return null;
            return cached.responses;
        } catch (error) {
            return null;
        }
    }

    function setCachedResponses(reviewId, responses) {
        try {
            sessionStorage.setItem(responsesCacheKey(reviewId), JSON.stringify({
                savedAt: Date.now(),
                responses
            }));
        } catch (error) {
            // Cache is optional.
        }
    }

    async function loadReviewResponses(reviewId) {
        const target = document.getElementById(`responses-list-${reviewId}`);
        if (!target || target.dataset.loaded === '1') return;

        const cached = getCachedResponses(reviewId);
        if (cached) {
            target.innerHTML = renderResponses(cached);
            target.dataset.loaded = '1';
            updateRepliesCount(reviewId, cached.length);
            return;
        }

        target.innerHTML = `<p class="text-xs text-muted-foreground italic pl-4 opacity-75">Cargando respuestas...</p>`;

        try {
            const response = await fetch(`/proyecto7mo/Backend/public/api.php?route=${encodeURIComponent(`reviews/${reviewId}/responses`)}`);
            const responses = await response.json();
            const normalizedResponses = Array.isArray(responses) ? responses : [];
            setCachedResponses(reviewId, normalizedResponses);
            target.innerHTML = renderResponses(normalizedResponses);
            target.dataset.loaded = '1';
            updateRepliesCount(reviewId, normalizedResponses.length);
        } catch (error) {
            target.innerHTML = `<p class="text-xs text-muted-foreground italic pl-4 opacity-75">No se pudieron cargar las respuestas.</p>`;
        }
    }

    function updateRepliesCount(reviewId, count) {
        const countEl = document.getElementById(`replies-count-${reviewId}`);
        if (countEl) countEl.textContent = count;
    }

    async function loadMovieReviews(movie, forceRefresh = false) {
        if (!forceRefresh && Array.isArray(movie.reviews)) return movie.reviews;

        const cached = forceRefresh ? null : getCachedReviews(movie.id);
        if (cached) {
            movie.reviews = cached;
            return cached;
        }

        try {
            const sort = document.getElementById('reviewSortSelect')?.value || 'recent';
            const reviewsResponse = await fetch(`/proyecto7mo/Backend/public/api.php?route=${encodeURIComponent(`movies/${movie.id}/reviews`)}&sort=${encodeURIComponent(sort)}`);
            const reviews = await reviewsResponse.json();
            const normalizedReviews = Array.isArray(reviews) ? reviews.map(review => ({
                ...review,
                responses_count: Number(review.responses_count || 0),
                hearts_count: Number(review.hearts_count || 0),
                is_author_review: Number(review.is_author_review || 0)
            })) : [];

            movie.reviews = normalizedReviews;
            setCachedReviews(movie.id, normalizedReviews);
            return normalizedReviews;
        } catch (error) {
            movie.reviews = [];
            return [];
        }
    }

    function renderReviewsLoading() {
        const container = document.getElementById('modalReviewsBody');
        if (!container) return;
        container.innerHTML = `
            <div class="reviews-state">
                <span class="reviews-state-kicker">Cargando</span>
                <p>Buscando reseñas de esta película...</p>
            </div>
        `;
    }

    async function refreshCurrentMovieReviews() {
        if (!currentModalMovie) return;
        renderReviewsLoading();
        await loadMovieReviews(currentModalMovie, true);
        renderReviewsList(currentModalMovie);
    }

    function scrollMovieSection(sectionId, direction) {
        const row = document.getElementById(sectionId);
        if (!row) return;
        row.scrollBy({
            left: direction * Math.max(280, row.clientWidth * 0.8),
            behavior: 'smooth'
        });
    }

    function renderReplyForm(rev, movieId) {
        if (!currentUser) return '';
        if (currentUser.id === rev.user_id) {
            return `
                <div class="review-note">
                    No puedes responder tu propia reseña.
                </div>
            `;
        }

        return `
            <form method="post" class="review-reply-form">
                <input type="hidden" name="action" value="respond_review">
                <input type="hidden" name="review_id" value="${rev.id}">
                <input type="hidden" name="movie_id" value="${movieId}">
                <div class="review-form-title">
                    <span>Responder reseña</span>
                    <small>Responde sin volver a calificar la película.</small>
                </div>
                <div class="review-form-grid">
                    <label>
                        Respuesta
                        <textarea name="comment" placeholder="Escribe una respuesta breve..." required rows="2"></textarea>
                    </label>
                </div>
                <button type="submit">Publicar respuesta</button>
            </form>
        `;
    }

    function renderReviewSummary(movie, reviews) {
        const count = reviews.length;
        if (count === 0) {
            return `
                <section class="reviews-summary">
                    <div>
                        <span class="reviews-summary-kicker">Reseñas</span>
                        <h3>Sin reseñas todavía</h3>
                    </div>
                    <p>Sé el primero en dejar una opinión clara sobre esta película.</p>
                    <label class="review-sort-control">
                        Orden
                        <select id="reviewSortSelect" onchange="refreshCurrentMovieReviews()">
                            <option value="recent">Más reciente</option>
                            <option value="oldest">Más antiguo</option>
                            <option value="rating">Mejor valoración</option>
                        </select>
                    </label>
                </section>
            `;
        }

        const average = reviews.reduce((total, review) => total + Number(review.rating || 0), 0) / count;
        return `
            <section class="reviews-summary">
                <div>
                    <span class="reviews-summary-kicker">Reseñas</span>
                    <h3>${count} opinión${count === 1 ? '' : 'es'}</h3>
                </div>
                <div class="reviews-summary-score">
                    <strong>${average.toFixed(1)}</strong>
                    <span>${reviewStars(Math.round(average))}</span>
                </div>
                <label class="review-sort-control">
                    Orden
                    <select id="reviewSortSelect" onchange="refreshCurrentMovieReviews()">
                        <option value="recent">Más reciente</option>
                        <option value="oldest">Más antiguo</option>
                        <option value="rating">Mejor valoración</option>
                    </select>
                </label>
            </section>
        `;
    }

    function renderReviewsList(movie) {
        const container = document.getElementById('modalReviewsBody');
        if (!container) return;

        const reviews = Array.isArray(movie.reviews) ? movie.reviews : [];
        let htmlContent = renderReviewSummary(movie, reviews);

        if (reviews.length === 0) {
            htmlContent += `
                <div class="reviews-state reviews-state-empty">
                    <span class="reviews-state-kicker">Aún no hay actividad</span>
                    <p>Cuando alguien escriba una reseña, aparecerá aca con sus respuestas.</p>
                </div>
            `;
        } else {
            htmlContent += '<div class="reviews-list">';
            reviews.forEach(rev => {
                const isAuthorReview = Number(rev.is_author_review || 0) === 1 || (Number(movie.author_user_id) > 0 && Number(rev.user_id) === Number(movie.author_user_id));
                const authorBadge = isAuthorReview ? '<span class="review-author-badge">Autor</span>' : '';
                htmlContent += `
                    <article class="review-card" id="review-${rev.id}">
                        <header class="review-card-header">
                            <div class="review-avatar">${userInitial(rev.user_name)}</div>
                            <div class="review-card-title">
                                <div class="review-author-line">
                                    <strong>${escapeHtml(rev.user_name)}</strong>
                                    ${authorBadge}
                                </div>
                                <span class="review-stars">${reviewStars(rev.rating)}</span>
                            </div>
                        </header>
                        <p class="review-text">${escapeHtml(rev.comment)}</p>
                        <div class="review-actions">
                            <button type="button" class="review-heart-btn" id="review-heart-${rev.id}" onclick="toggleReviewHeart(${rev.id})" aria-label="Dar corazon">
                                <span>♥</span>
                                <strong id="review-heart-count-${rev.id}">${Number(rev.hearts_count || 0)}</strong>
                            </button>
                            <button type="button" class="toggle-replies-btn" id="toggle-replies-btn-${rev.id}" onclick="toggleReplies(${rev.id})">
                                <span>Ver respuestas</span>
                                <strong id="replies-count-${rev.id}">${Number(rev.responses_count || 0)}</strong>
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </button>
                        </div>
                        <div class="replies-container" id="replies-container-${rev.id}">
                            <div class="replies-panel">
                                <div id="responses-list-${rev.id}" data-loaded="0">
                                    <p class="review-reply-empty">Abre para cargar respuestas.</p>
                                </div>
                                ${renderReplyForm(rev, movie.id)}
                            </div>
                        </div>
                    </article>
                `;
            });
            htmlContent += '</div>';
        }

        if (currentUser) {
            const userReviewCount = reviews.filter(rev => Number(rev.user_id) === Number(currentUser.id)).length;

            if (userReviewCount >= 3) {
                htmlContent += `
                    <div class="review-current-user-note disabled">
                        Alcanzaste el máximo de 3 reseñas para esta película.
                    </div>
                `;
            } else {
                htmlContent += `
                    <form method="post" class="review-submit-form">
                        <input type="hidden" name="action" value="submit_review">
                        <input type="hidden" name="movie_id" value="${movie.id}">
                        <div class="review-form-title">
                            <span>Escribir reseña</span>
                            <small>Tu calificación ayuda al resto a decidir.</small>
                        </div>
                        <label>
                            Calificación
                            <select name="rating" required>
                                <option value="5">&#9733;&#9733;&#9733;&#9733;&#9733; - Excelente</option>
                                <option value="4">&#9733;&#9733;&#9733;&#9733;&#9734; - Muy buena</option>
                                <option value="3">&#9733;&#9733;&#9733;&#9734;&#9734; - Correcta</option>
                                <option value="2">&#9733;&#9733;&#9734;&#9734;&#9734; - Floja</option>
                                <option value="1">&#9733;&#9734;&#9734;&#9734;&#9734; - Mala</option>
                            </select>
                        </label>
                        <label>
                            Reseña
                            <textarea name="comment" placeholder="Conta que te parecio en una o dos frases..." required rows="3"></textarea>
                        </label>
                        <button type="submit">Publicar reseña</button>
                    </form>
                `;
            }
        } else {
            htmlContent += `
                <div class="review-login-note">
                    <a href="/proyecto7mo/Frontend/login.php">Inicia sesión</a> para calificar esta película.
                </div>
            `;
        }

        container.innerHTML = htmlContent;

        if (activeReviewId > 0) {
            window.setTimeout(() => {
                const targetReview = document.getElementById(`review-${activeReviewId}`);
                if (!targetReview) return;
                targetReview.scrollIntoView({ behavior: 'smooth', block: 'center' });
                targetReview.classList.add('review-card-highlight');
                window.setTimeout(() => targetReview.classList.remove('review-card-highlight'), 1800);
            }, 120);
        }
    }

    // Auto-open modal on load if activeMovieId is set
    window.addEventListener('DOMContentLoaded', () => {
        const params = new URLSearchParams(window.location.search);
        if (params.get('focus') === 'search') {
            const searchInput = document.getElementById('exploreSearchInput');
            if (searchInput) searchInput.focus();
        }

        if (activeMovieId > 0) {
            openMovieModal(activeMovieId);
        }
    });
    </script>
</body>
</html>
