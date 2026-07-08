<?php

require_once __DIR__ . '/../models/Database.php';

$pdo = Database::getInstance()->getConnection();
$limitEnv = getenv('MOVIE_SEED_LIMIT');
$limit = $limitEnv === false ? 12 : max(0, (int) $limitEnv);
$source = 'cinemeta';

function seed_http_json(string $url): ?array
{
    $context = stream_context_create(['http' => [
        'timeout' => 12,
        'ignore_errors' => true,
        'header' => "User-Agent: NexoHubSeeder/1.0\r\n",
    ]]);
    $raw = @file_get_contents($url, false, $context);
    $data = $raw ? json_decode($raw, true) : null;

    return is_array($data) ? $data : null;
}

function seed_map_genre(?string $genre): string
{
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

    return $map[trim((string) $genre)] ?? 'Drama';
}

function seed_extract_year(?string $releaseInfo): int
{
    preg_match('/\d{4}/', (string) $releaseInfo, $matches);
    return isset($matches[0]) ? (int) $matches[0] : (int) date('Y');
}

function seed_director(array $meta): string
{
    $directorValue = $meta['director'] ?? [];
    $directors = is_array($directorValue) ? $directorValue : [$directorValue];
    $director = trim((string) ($directors[0] ?? ''));

    return $director !== '' ? $director : 'Cinemeta';
}

function seed_write_placeholder_cover(int $movieId, string $title): void
{
    $coversDir = dirname(__DIR__, 2) . '/Frontend/public/covers';
    if (!is_dir($coversDir)) {
        mkdir($coversDir, 0755, true);
    }

    $safeTitle = preg_replace('/[^\p{L}\p{N}\s.-]+/u', '', trim($title)) ?: 'Pelicula';
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="600" height="900" viewBox="0 0 600 900">
  <rect width="600" height="900" fill="#141826"/>
  <rect x="36" y="36" width="528" height="828" rx="24" fill="#1f2639" stroke="#3b82f6" stroke-width="8"/>
  <circle cx="300" cy="320" r="120" fill="#3b82f6" opacity="0.25"/>
  <path d="M220 430h160c24 0 44 20 44 44v120H176v-120c0-24 20-44 44-44Z" fill="#f5f5f5" opacity="0.9"/>
  <text x="300" y="690" fill="#f5f5f5" font-family="Arial, sans-serif" font-size="30" text-anchor="middle">{$safeTitle}</text>
  <text x="300" y="742" fill="#8ea3c9" font-family="Arial, sans-serif" font-size="22" text-anchor="middle">NexoHub</text>
</svg>
SVG;

    file_put_contents($coversDir . '/' . $movieId . '.svg', $svg);
}

function seed_resolve_image_extension(string $image, string $posterUrl): string
{
    if (function_exists('finfo_buffer')) {
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mime = $finfo->buffer($image) ?: '';
        $map = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'image/gif' => 'gif',
        ];
        if (isset($map[$mime])) {
            return $map[$mime];
        }
    }

    $path = parse_url($posterUrl, PHP_URL_PATH) ?: '';
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if ($extension === 'jpeg') {
        $extension = 'jpg';
    }

    return in_array($extension, ['jpg', 'png', 'webp', 'gif'], true) ? $extension : 'jpg';
}

function seed_try_download_cover(int $movieId, string $posterUrl, string $title = ''): bool
{
    if ($posterUrl === '') {
        return false;
    }

    $coversDir = dirname(__DIR__, 2) . '/Frontend/public/covers';
    if (!is_dir($coversDir)) {
        mkdir($coversDir, 0755, true);
    }

    $context = stream_context_create(['http' => [
        'timeout' => 15,
        'ignore_errors' => true,
        'header' => "User-Agent: NexoHubSeeder/1.0\r\n",
    ]]);
    $image = @file_get_contents($posterUrl, false, $context);
    if (!$image) {
        return false;
    }

    $extension = seed_resolve_image_extension($image, $posterUrl);
    file_put_contents($coversDir . '/' . $movieId . '.' . $extension, $image);

    return true;
}

function seed_download_cover(int $movieId, array $imageUrls, string $title = ''): void
{
    foreach ($imageUrls as $posterUrl) {
        if (seed_try_download_cover($movieId, $posterUrl, $title)) {
            return;
        }
    }

    seed_write_placeholder_cover($movieId, $title);
}

function seed_download_missing_covers(PDO $pdo): void
{
    $stmt = $pdo->query("SELECT id, poster_url, title FROM movies WHERE external_source = 'cinemeta' AND poster_url IS NOT NULL AND poster_url != '' ORDER BY id ASC");
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $movie) {
        $movieId = (int) $movie['id'];
        $posterUrl = trim((string) ($movie['poster_url'] ?? ''));
        if ($posterUrl === '') {
            continue;
        }

        $coversDir = dirname(__DIR__, 2) . '/Frontend/public/covers';
        $hasFile = false;
        foreach (['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'] as $ext) {
            if (is_file($coversDir . '/' . $movieId . '.' . $ext)) {
                $hasFile = true;
                break;
            }
        }

        if (!$hasFile) {
            seed_download_cover($movieId, [$posterUrl], (string) ($movie['title'] ?? ''));
        }
    }
}

function seed_fetch_movies(int $limit): array
{
    $data = seed_http_json('https://v3-cinemeta.strem.io/catalog/movie/top.json');
    $metas = is_array($data['metas'] ?? null) ? $data['metas'] : [];
    $movies = [];

    foreach ($metas as $summary) {
        if ($limit > 0 && count($movies) >= $limit) {
            break;
        }

        $id = (string) ($summary['id'] ?? '');
        if (!preg_match('/^tt\d+$/', $id)) {
            continue;
        }

        $metaData = seed_http_json('https://v3-cinemeta.strem.io/meta/movie/' . rawurlencode($id) . '.json');
        $meta = is_array($metaData['meta'] ?? null) ? $metaData['meta'] : null;
        if (!$meta || trim((string) ($meta['name'] ?? '')) === '') {
            continue;
        }

        $genres = is_array($meta['genres'] ?? null) ? $meta['genres'] : [];
        $imageUrls = array_values(array_filter([
            trim((string) ($meta['poster'] ?? '')),
            trim((string) ($meta['background'] ?? '')),
            trim((string) ($meta['logo'] ?? '')),
        ], static fn($url) => $url !== ''));

        $movies[$id] = [
            'external_id' => $id,
            'title' => trim((string) $meta['name']),
            'genre' => seed_map_genre($genres[0] ?? ''),
            'featured' => count($movies) < 5 ? 1 : 0,
            'description' => trim((string) ($meta['description'] ?? '')),
            'year' => seed_extract_year($meta['releaseInfo'] ?? ''),
            'movie_author' => seed_director($meta),
            'poster_url' => $imageUrls[0] ?? '',
            'image_urls' => $imageUrls,
        ];
    }

    return array_values($movies);
}

$movies = seed_fetch_movies($limit);
if (!$movies) {
    fwrite(STDERR, "No se pudieron obtener peliculas desde la API.\n");
    exit(1);
}

$findByExternal = $pdo->prepare("SELECT id FROM movies WHERE external_source = ? AND external_id = ? LIMIT 1");
$findByTitle = $pdo->prepare("SELECT id FROM movies WHERE LOWER(title) = LOWER(?) AND year = ? LIMIT 1");
$insert = $pdo->prepare("
    INSERT INTO movies (title, genre, featured, description, year, movie_author, external_source, external_id, poster_url, approval_status, user_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'approved', 1)
");
$update = $pdo->prepare("
    UPDATE movies
    SET title = ?, genre = ?, featured = ?, description = ?, year = ?, movie_author = ?, poster_url = ?, approval_status = 'approved', user_id = COALESCE(user_id, 1)
    WHERE id = ?
");

$inserted = 0;
$updated = 0;

foreach ($movies as $movie) {
    $findByExternal->execute([$source, $movie['external_id']]);
    $movieId = (int) $findByExternal->fetchColumn();

    if (!$movieId) {
        $findByTitle->execute([$movie['title'], $movie['year']]);
        $movieId = (int) $findByTitle->fetchColumn();
    }

    if ($movieId) {
        $update->execute([
            $movie['title'],
            $movie['genre'],
            $movie['featured'],
            $movie['description'],
            $movie['year'],
            $movie['movie_author'],
            $movie['poster_url'],
            $movieId,
        ]);
        $pdo->prepare("UPDATE movies SET external_source = ?, external_id = ? WHERE id = ?")
            ->execute([$source, $movie['external_id'], $movieId]);
        $updated++;
    } else {
        $insert->execute([
            $movie['title'],
            $movie['genre'],
            $movie['featured'],
            $movie['description'],
            $movie['year'],
            $movie['movie_author'],
            $source,
            $movie['external_id'],
            $movie['poster_url'],
        ]);
        $movieId = (int) $pdo->lastInsertId();
        $inserted++;
    }

    seed_download_cover($movieId, $movie['image_urls'] ?? [$movie['poster_url']], $movie['title']);
}

seed_download_missing_covers($pdo);

echo "Peliculas desde API: {$inserted} insertadas, {$updated} actualizadas.\n";
