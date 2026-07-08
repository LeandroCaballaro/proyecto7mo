<?php

require_once __DIR__ . '/../models/Database.php';

$pdo = Database::getInstance()->getConnection();
$limit = max(1, (int) (getenv('MOVIE_SEED_LIMIT') ?: 12));
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

function seed_download_cover(int $movieId, string $posterUrl): void
{
    if ($posterUrl === '') {
        return;
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
        return;
    }

    $path = parse_url($posterUrl, PHP_URL_PATH) ?: '';
    $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));
    if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
        $extension = 'jpg';
    }

    file_put_contents($coversDir . '/' . $movieId . '.' . $extension, $image);
}

function seed_fetch_movies(int $limit): array
{
    $data = seed_http_json('https://v3-cinemeta.strem.io/catalog/movie/top.json');
    $metas = is_array($data['metas'] ?? null) ? $data['metas'] : [];
    $movies = [];

    foreach ($metas as $summary) {
        if (count($movies) >= $limit) {
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
        $movies[$id] = [
            'external_id' => $id,
            'title' => trim((string) $meta['name']),
            'genre' => seed_map_genre($genres[0] ?? ''),
            'featured' => count($movies) < 5 ? 1 : 0,
            'description' => trim((string) ($meta['description'] ?? '')),
            'year' => seed_extract_year($meta['releaseInfo'] ?? ''),
            'movie_author' => seed_director($meta),
            'poster_url' => trim((string) ($meta['poster'] ?? '')),
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

    seed_download_cover($movieId, $movie['poster_url']);
}

echo "Peliculas desde API: {$inserted} insertadas, {$updated} actualizadas.\n";
