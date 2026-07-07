<?php
session_start();
define('API_URL', 'http://localhost/proyecto7mo/Backend/public/api.php');
require_once __DIR__ . '/../Backend/models/Database.php';

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

$genre = $_GET['genre'] ?? '';
$q = trim($_GET['q'] ?? '');
$msg = '';
$active_movie_id = 0;
$favoriteMovieIds = [];
$allowedMovieGenres = ['Accion', 'Aventura', 'Animacion', 'Comedia', 'Crimen', 'Documental', 'Drama', 'Fantasia', 'Terror', 'Misterio', 'Romance', 'Ciencia ficcion', 'Thriller'];

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
    foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $ext) {
        $relative = 'public/covers/' . $movieId . '.' . $ext;
        if (file_exists(__DIR__ . '/../' . $relative)) {
            return $relative;
        }
    }

    return null;
}

function clearMovieCoverFiles(int $movieId): void
{
    $files = glob(__DIR__ . '/../public/covers/' . $movieId . '.*');
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

    $coverDir = __DIR__ . '/../public/covers';
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

    $coverDir = __DIR__ . '/../public/covers';
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
    if (empty($_SESSION['token'])) {
        $msg = 'Debes iniciar sesión para publicar películas o responder reseñas.';
    } else {
        $action = $_POST['action'] ?? 'submit_review';

        if ($action === 'toggle_favorite') {
            header('Content-Type: application/json');
            $userId = (int) ($_SESSION['user']['id'] ?? 0);
            $movieId = (int) ($_POST['movie_id'] ?? 0);

            if (!$userId || !$movieId) {
                echo json_encode(['success' => false, 'message' => 'No se pudo guardar el favorito']);
                exit;
            }

            try {
                $db = Database::getInstance()->getConnection();
                ensure_favorites_table($db);
                $stmt = $db->prepare("SELECT id FROM favorite_movies WHERE user_id = ? AND movie_id = ? LIMIT 1");
                $stmt->execute([$userId, $movieId]);

                if ($stmt->fetch(PDO::FETCH_ASSOC)) {
                    $db->prepare("DELETE FROM favorite_movies WHERE user_id = ? AND movie_id = ?")->execute([$userId, $movieId]);
                    echo json_encode(['success' => true, 'favorite' => false]);
                } else {
                    $db->prepare("INSERT INTO favorite_movies (user_id, movie_id) VALUES (?, ?)")->execute([$userId, $movieId]);
                    echo json_encode(['success' => true, 'favorite' => true]);
                }
            } catch (Exception $e) {
                echo json_encode(['success' => false, 'message' => 'No se pudo guardar el favorito']);
            }
            exit;
        }

        if ($action === 'create_movie') {
            $movieGenre = trim((string) ($_POST['genre'] ?? ''));
            $movieDescription = trim((string) ($_POST['description'] ?? ''));
            $coverUrlInput = trim((string) ($_POST['cover_image_url'] ?? ''));
            $hasCoverFile = (($_FILES['cover_image_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE);

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

            $res = api_post('movies', [
                'title' => $_POST['title'] ?? '',
                'genre' => $movieGenre,
                'year' => (int) ($_POST['year'] ?? 0),
                'description' => $movieDescription,
            ], $_SESSION['token']);
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
                        $msg = 'Película publicada correctamente.';
                    }
                }
            } else {
                $msg = $res['error'] ?? 'Error al publicar película';
            }
            end_create_movie:
        } elseif ($action === 'respond_review') {
            $res = api_post('reviews/' . urlencode((int) ($_POST['review_id'] ?? 0)) . '/responses', [
                'rating' => (int) ($_POST['rating'] ?? 0),
                'comment' => $_POST['comment'] ?? '',
            ], $_SESSION['token']);
            $msg = isset($res['ok']) ? 'Respuesta enviada correctamente.' : ($res['error'] ?? 'Error al responder la reseña');
            if (isset($res['ok'])) {
                $active_movie_id = (int) ($_POST['movie_id'] ?? 0);
            }
        } else {
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
}

$peliculas = $genre ? api_get('movies/genre/' . urlencode($genre)) : api_get('movies');
$peliculas = is_array($peliculas) ? $peliculas : [];

if ($q !== '') {
    $peliculas = array_values(array_filter($peliculas, function ($movie) use ($q) {
        return stripos($movie['title'] ?? '', $q) !== false;
    }));
}

usort($peliculas, function ($a, $b) {
    return strcasecmp($a['title'] ?? '', $b['title'] ?? '');
});

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
    <link rel="stylesheet" href="style/explorar.css">
    <link href="style/styles.css" rel="stylesheet">
    <link rel="icon" href="../public/nhlogo.png" type="image/png">
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
    <?php include 'header.php'; ?>

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
                <form method="get" action="/proyecto7mo/Frontend/explorar.php" class="explore-search-form">
                    <?php if ($genre): ?>
                        <input type="hidden" name="genre" value="<?= htmlspecialchars($genre) ?>">
                    <?php endif; ?>
                    <input id="exploreSearchInput" type="search" name="q" value="<?= htmlspecialchars($q) ?>" class="explore-search-input" placeholder="Buscar pelicula por nombre">
                    <button type="submit" class="explore-search-button" aria-label="Buscar pelicula">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 1 1-14 0 7 7 0 0 1 14 0z"></path>
                        </svg>
                    </button>
                </form>
                <select onchange="location = this.value;" class="rounded-lg border border-border bg-card px-4 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="explorar.php">Todos los Géneros</option>
                    <?php foreach (api_get('movies/genres') ?: [] as $g): ?>
                        <option value="explorar.php?genre=<?= urlencode($g) ?>" <?= $genre === $g ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="rounded-lg border border-border bg-card px-4 py-2 text-foreground focus:outline-none" style="display:none">
                    <option>Ordenar por: Rating</option>
                    <option>Ordenar por: Año</option>
                    <option>Ordenar por: Popularidad</option>
                </select>
                <span class="alphabetical-badge">Orden alfabetico</span>
            </div>

            <?php if (!empty($_SESSION['user'])): ?>
                <div class="mb-8 rounded-xl border border-border bg-card p-6">
                    <h2 class="text-xl font-semibold text-foreground mb-4">Publicar nueva película</h2>
                    <form method="post" enctype="multipart/form-data" class="grid gap-4 lg:grid-cols-2" onsubmit="return validateCreateMovieForm(this)">
                        <input type="hidden" name="action" value="create_movie">
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground">Título</label>
                            <input type="text" name="title" required class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Nombre de la película">
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
                        <div>
                            <label class="text-xs font-semibold text-muted-foreground">Descripción</label>
                            <input type="text" name="description" required class="mt-2 w-full rounded-lg border border-border bg-background px-3 py-2 text-foreground focus:outline-none focus:ring-1 focus:ring-primary" placeholder="Un breve resumen">
                        </div>
                        <div class="lg:col-span-2 w-full max-w-2xl mx-auto">
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
                        <div class="lg:col-span-2">
                            <button type="submit" class="rounded-lg bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground hover:bg-primary/90 transition-colors">
                                Publicar película
                            </button>
                        </div>
                    </form>
                </div>
            <?php endif; ?>

            <!-- Películas Grid -->
            <?php if (empty($peliculas)): ?>
                <div class="text-center py-20 bg-card rounded-xl border border-border">
                    <span class="text-4xl">🎬</span>
                    <p class="mt-4 text-muted-foreground">No se encontraron películas en esta categoría.</p>
                </div>
            <?php else: ?>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <?php foreach ($peliculas as $p): ?>
                        <div class="movie-card-container" onclick="openMovieModal(<?= (int)$p['id'] ?>)">
                            <div class="movie-flip-card">
                                <!-- FRONT SIDE -->
                                <div class="movie-flip-card-front relative flex flex-col justify-between">
                                    <?php 
                                    $coverPath = movieCoverRelativePath((int) $p['id']);
                                    $hasCover = $coverPath !== null;
                                    if ($hasCover): 
                                    ?>
                                        <img src="/proyecto7mo/<?= htmlspecialchars($coverPath) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="w-full h-full object-cover">
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
            <?php endif; ?>
        </div>
    </main>

    <!-- Modal Backdrop -->
    <div id="movieModalBackdrop" class="modal-backdrop" onclick="closeMovieModalOutside(event)">
        <div class="modal-container" onclick="event.stopPropagation()">
            <!-- LEFT COLUMN: Movie Poster -->
            <div class="modal-left">
                <img id="modalMoviePoster" src="" alt="Portada" class="modal-poster-img">
                <button type="button" id="favoriteMovieBtn" class="favorite-movie-btn" aria-label="Agregar a favoritos">☆</button>
                <div class="modal-poster-overlay">
                    <span id="modalMovieGenreYear" class="text-xs font-bold text-primary tracking-wide uppercase">GÉNERO • AÑO</span>
                    <h2 id="modalMovieTitle" class="text-2xl md:text-3xl font-extrabold text-foreground mt-1">Título de la Película</h2>
                    <p id="modalMovieDesc" class="text-sm text-muted-foreground mt-3 line-clamp-4">Descripción de la película...</p>
                </div>
            </div>
            
            <!-- RIGHT COLUMN: Reviews Feed -->
            <div class="modal-right">
                <div class="modal-header">
                    <span class="text-sm font-bold text-muted-foreground uppercase tracking-widest">Feed de Reseñas</span>
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

    <?php include 'footer.php'; ?>

    <script>
    // Prepare all movie data on the client side
    const moviesData = {
        <?php foreach ($peliculas as $p): ?>
            "<?= (int)$p['id'] ?>": {
                id: <?= (int)$p['id'] ?>,
                author_user_id: <?= isset($p['user_id']) ? (int) $p['user_id'] : 0 ?>,
                title: <?= json_encode($p['title']) ?>,
                genre: <?= json_encode($p['genre'] ?? 'General') ?>,
                year: <?= (int)($p['year'] ?? 2024) ?>,
                description: <?= json_encode($p['description'] ?? '') ?>,
                coverUrl: <?= json_encode((($cp = movieCoverRelativePath((int) $p['id'])) !== null) ? ('/proyecto7mo/' . $cp) : '') ?>,
                hasCover: <?= (($cp = movieCoverRelativePath((int) $p['id'])) !== null) ? 'true' : 'false' ?>,
                favorite: <?= in_array((int) $p['id'], $favoriteMovieIds, true) ? 'true' : 'false' ?>,
                reviews: [
                    <?php 
                    $reviews = api_get('movies/' . urlencode((int) $p['id']) . '/reviews') ?: [];
                    foreach ($reviews as $rev): 
                        $responses = api_get('reviews/' . urlencode((int) $rev['id']) . '/responses') ?: [];
                    ?>
                    {
                        id: <?= (int)$rev['id'] ?>,
                        user_id: <?= (int)$rev['user_id'] ?>,
                        user_name: <?= json_encode($rev['user_name']) ?>,
                        rating: <?= (int)$rev['rating'] ?>,
                        comment: <?= json_encode($rev['comment'] ?: 'Sin comentario') ?>,
                        responses: <?= json_encode($responses) ?>
                    },
                    <?php endforeach; ?>
                ]
            },
        <?php endforeach; ?>
    };

    const currentUser = <?= isset($_SESSION['user']) ? json_encode($_SESSION['user']) : 'null' ?>;
    const activeMovieId = <?= $active_movie_id ?>;

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

    window.addEventListener('DOMContentLoaded', () => {
        const createMovieForm = document.querySelector('form input[name="action"][value="create_movie"]')?.form;
        if (createMovieForm) {
            syncCoverRequired(createMovieForm);
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
        document.getElementById('modalMovieDesc').innerText = movie.description || 'Sin descripción disponible.';

        updateFavoriteButton(movie);

        // Render reviews list on the right side
        renderReviewsList(movie);

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
    }

    async function toggleFavorite(movieId) {
        if (!currentUser) {
            window.location.href = '/proyecto7mo/Frontend/login.php';
            return;
        }

        const movie = moviesData[movieId];
        if (!movie) return;

        const response = await fetch('/proyecto7mo/Frontend/explorar.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({
                action: 'toggle_favorite',
                movie_id: movieId
            })
        });
        const data = await response.json();

        if (data.success) {
            movie.favorite = !!data.favorite;
            updateFavoriteButton(movie);
        }
    }

    const favoriteMovieBtn = document.getElementById('favoriteMovieBtn');
    if (favoriteMovieBtn) {
        favoriteMovieBtn.addEventListener('click', () => {
            toggleFavorite(favoriteMovieBtn.dataset.movieId);
        });
    }

    // Escape key closes modal
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') {
            closeMovieModal();
        }
    });

    // Expand/collapse review replies
    function toggleReplies(reviewId) {
        const container = document.getElementById(`replies-container-${reviewId}`);
        const btn = document.getElementById(`toggle-replies-btn-${reviewId}`);
        if (!container) return;

        container.classList.toggle('open');
        btn.classList.toggle('open');
    }

    // Render responses markup
    function renderResponses(responses) {
        if (responses.length === 0) {
            return `<p class="text-xs text-muted-foreground italic pl-4 opacity-75">Sé el primero en responder...</p>`;
        }
        return responses.map(resp => `
            <div class="pl-4 border-l-2 border-primary/30 py-1.5 review-item">
                <div class="flex items-center justify-between gap-3 text-xs mb-1">
                    <span class="font-bold text-foreground">${escapeHtml(resp.user_name)}</span>
                    <span class="text-yellow-400 font-normal">${'★'.repeat(resp.rating)}${'☆'.repeat(5 - resp.rating)}</span>
                </div>
                <p class="text-muted-foreground text-xs leading-relaxed">${escapeHtml(resp.comment)}</p>
            </div>
        `).join('');
    }

    // Render response reply form markup
    function renderReplyForm(rev, movieId) {
        if (!currentUser) return '';
        if (currentUser.id === rev.user_id) {
            return `
                <div class="rounded border border-secondary/20 bg-secondary/5 p-2.5 text-xs text-muted-foreground mt-3 text-center">
                    ℹ️ No puedes responder tu propia reseña
                </div>
            `;
        }

        return `
            <form method="post" class="space-y-3 rounded-lg border border-primary/10 bg-primary/5 p-4 mt-3">
                <input type="hidden" name="action" value="respond_review">
                <input type="hidden" name="review_id" value="${rev.id}">
                <input type="hidden" name="movie_id" value="${movieId}">
                <div class="flex items-center gap-2 mb-1">
                    <span class="text-sm">💬</span>
                    <label class="block text-[11px] font-bold text-primary uppercase tracking-wide">Responde esta reseña</label>
                </div>
                <div class="space-y-1">
                    <label class="text-[10px] uppercase font-bold text-muted-foreground">Tu calificación:</label>
                    <select name="rating" required class="rounded border border-border bg-background text-foreground text-xs px-2 py-1 focus:ring-1 focus:ring-primary focus:outline-none w-full">
                        <option value="">-- Selecciona una calificación --</option>
                        <option value="5">★★★★★ - 5 estrellas</option>
                        <option value="4">★★★★☆ - 4 estrellas</option>
                        <option value="3">★★★☆☆ - 3 estrellas</option>
                        <option value="2">★★☆☆☆ - 2 estrellas</option>
                        <option value="1">★☆☆☆☆ - 1 estrella</option>
                    </select>
                </div>
                <div class="space-y-1">
                    <label class="block text-[10px] uppercase font-bold text-muted-foreground">Tu respuesta:</label>
                    <textarea name="comment" placeholder="Comparte tu opinión..." required rows="2" class="w-full text-xs rounded border border-border bg-background px-3 py-2 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary"></textarea>
                </div>
                <button type="submit" class="w-full rounded bg-primary text-primary-foreground hover:bg-primary/90 py-1.5 text-xs font-bold transition-colors">
                    ✓ Publicar respuesta
                </button>
            </form>
        `;
    }

    // Render right column contents
    function renderReviewsList(movie) {
        const container = document.getElementById('modalReviewsBody');
        let htmlContent = '';

        if (movie.reviews.length === 0) {
            htmlContent += `
                <div class="text-center py-16 opacity-75">
                    <span class="text-4xl">💬</span>
                    <p class="mt-3 text-sm text-muted-foreground">Aún no hay reseñas para esta película.</p>
                </div>
            `;
        } else {
            movie.reviews.forEach(rev => {
                const isAuthorReview = Number(movie.author_user_id) > 0 && Number(rev.user_id) === Number(movie.author_user_id);
                const authorBadge = isAuthorReview ? '<span class="review-author-badge">Autor</span>' : '';
                htmlContent += `
                    <div class="rounded-xl border border-border bg-background/30 p-4 mb-4 review-item transition-all duration-200">
                        <div class="flex items-center justify-between gap-3">
                            <div class="review-author-line">
                                <span class="font-extrabold text-foreground text-sm">${escapeHtml(rev.user_name)}</span>
                                ${authorBadge}
                                <span class="text-yellow-400 text-xs ml-2">${'★'.repeat(rev.rating)}${'☆'.repeat(5 - rev.rating)}</span>
                            </div>
                        </div>
                        <p class="text-muted-foreground text-xs leading-relaxed mt-2">${escapeHtml(rev.comment)}</p>
                        
                        <!-- Replies section toggle -->
                        <div class="mt-3 flex items-center justify-between">
                            <div class="toggle-replies-btn" id="toggle-replies-btn-${rev.id}" onclick="toggleReplies(${rev.id})">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3 transform transition-transform duration-200" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7" />
                                </svg>
                                <span>Respuestas (${rev.responses.length})</span>
                            </div>
                        </div>
                        
                        <!-- Collapsible Replies Container -->
                        <div class="replies-container" id="replies-container-${rev.id}">
                            <div class="border-t border-border mt-3 pt-3 space-y-3">
                                ${renderResponses(rev.responses)}
                                ${renderReplyForm(rev, movie.id)}
                            </div>
                        </div>
                    </div>
                `;
            });
        }

        // Add Review Section at the bottom of the right column
        if (currentUser) {
            // Check if user already reviewed this movie
            const alreadyReviewed = movie.reviews.some(rev => rev.user_id === currentUser.id);

            if (alreadyReviewed) {
                htmlContent += `
                    <div class="mt-6 pt-4 border-t border-border text-center">
                        <p class="text-xs text-muted-foreground bg-primary/5 border border-primary/10 rounded-lg py-3 px-4">
                            ✨ Ya has reseñado esta película. ¡Gracias por tu opinión!
                        </p>
                    </div>
                `;
            } else {
                htmlContent += `
                    <form method="post" class="mt-6 pt-6 border-t border-border space-y-3">
                        <input type="hidden" name="action" value="submit_review">
                        <input type="hidden" name="movie_id" value="${movie.id}">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-bold text-muted-foreground uppercase">Calificación:</label>
                            <select name="rating" class="rounded border border-border bg-background text-foreground text-xs px-2 py-1 focus:ring-1 focus:ring-primary focus:outline-none">
                                <option value="5">★★★★★</option>
                                <option value="4">★★★★☆</option>
                                <option value="3">★★★☆☆</option>
                                <option value="2">★★☆☆☆</option>
                                <option value="1">★☆☆☆☆</option>
                            </select>
                        </div>
                        <input type="text" name="comment" placeholder="Escribe tu reseña..." required class="w-full text-xs rounded border border-border bg-background px-3 py-2 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary">
                        <button type="submit" class="w-full rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 py-2.5 text-xs font-bold transition-colors">
                            Publicar Reseña
                        </button>
                    </form>
                `;
            }
        } else {
            htmlContent += `
                <div class="mt-6 pt-4 border-t border-border text-center">
                    <p class="text-xs text-muted-foreground">
                        <a href="/proyecto7mo/Frontend/login.php" class="text-primary hover:underline font-bold">Inicia sesión</a> para calificar esta película.
                    </p>
                </div>
            `;
        }

        container.innerHTML = htmlContent;
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
