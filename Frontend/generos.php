<?php
session_start();
define('API_URL', 'http://localhost/proyecto7mo/Backend/public/api.php');

function api_get($route, $extra = [])
{
    $url = API_URL . '?' . http_build_query(array_merge(['route' => $route], $extra));
    $raw = @file_get_contents($url);
    return $raw ? json_decode($raw, true) : null;
}

$genres = api_get('movies/genres') ?: [];
$movies = api_get('movies') ?: [];
$genreStats = [];
foreach ($movies as $movie) {
    $genre = $movie['genre'] ?? 'General';
    if (!isset($genreStats[$genre])) {
        $genreStats[$genre] = ['movies' => 0, 'reviews' => 0, 'rating' => 0.0];
    }
    $genreStats[$genre]['movies']++;
    $genreStats[$genre]['reviews'] += (int) ($movie['reviews_count'] ?? 0);
    $genreStats[$genre]['rating'] += (float) ($movie['average_rating'] ?? 0);
}
sort($genres);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Géneros - NexoHub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/directory-pages.css">
</head>
<body class="bg-background text-foreground min-h-screen">
<?php include 'components/header.php'; ?>
<main class="directory-page">
    <header class="directory-hero">
        <span class="directory-kicker">Catálogo</span>
        <h1>Géneros</h1>
        <p>Explorá las categorías disponibles y entrá directo a las películas cargadas en cada una.</p>
    </header>

    <section class="genre-directory-grid">
        <?php if (empty($genres)): ?>
            <p class="directory-empty">Todavía no hay géneros cargados.</p>
        <?php else: ?>
            <?php foreach ($genres as $genre): ?>
                <?php
                $stats = $genreStats[$genre] ?? ['movies' => 0, 'reviews' => 0, 'rating' => 0.0];
                $avg = $stats['movies'] > 0 ? $stats['rating'] / $stats['movies'] : 0;
                ?>
                <a class="genre-directory-card" href="/proyecto7mo/Frontend/explorar.php?genre=<?= urlencode($genre) ?>">
                    <span class="genre-directory-icon"><?= htmlspecialchars(mb_strtoupper(mb_substr($genre, 0, 1, 'UTF-8'))) ?></span>
                    <div>
                        <h2><?= htmlspecialchars($genre) ?></h2>
                        <p><?= (int) $stats['movies'] ?> películas · <?= (int) $stats['reviews'] ?> reseñas</p>
                    </div>
                    <strong><?= $avg > 0 ? number_format($avg, 1) : 'Sin rating' ?></strong>
                </a>
            <?php endforeach; ?>
        <?php endif; ?>
    </section>
</main>
<?php include 'components/footer.php'; ?>
</body>
</html>
