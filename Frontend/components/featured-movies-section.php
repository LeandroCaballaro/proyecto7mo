<?php
function movieCoverRelativePathFeatured(int $movieId): ?string
{
    foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $ext) {
        $file = __DIR__ . '/../public/covers/' . $movieId . '.' . $ext;
        if (file_exists($file)) {
            return 'Frontend/public/covers/' . $movieId . '.' . $ext;
        }
    }

    return null;
}

$peliculas = api_get('movies/featured') ?: [];
$peliculasUnicas = [];

foreach ($peliculas as $pelicula) {
    $key = mb_strtolower(trim((string) ($pelicula['title'] ?? '')), 'UTF-8') . '|' . (int) ($pelicula['year'] ?? 0);
    $currentCover = movieCoverRelativePathFeatured((int) ($pelicula['id'] ?? 0));

    if (!isset($peliculasUnicas[$key])) {
        $pelicula['_cover_path'] = $currentCover;
        $peliculasUnicas[$key] = $pelicula;
        continue;
    }

    $existingCover = $peliculasUnicas[$key]['_cover_path'] ?? null;
    if (!$existingCover && $currentCover) {
        $pelicula['_cover_path'] = $currentCover;
        $peliculasUnicas[$key] = $pelicula;
    }
}

$peliculas = array_slice(array_values($peliculasUnicas), 0, 10);
?>
<section class="bg-card py-14">
    <div class="container mx-auto px-4">
        <div class="mb-8 text-center">
            <h2 class="text-3xl font-bold text-foreground md:text-4xl">Películas Destacadas</h2>
            <p class="mx-auto mt-4 max-w-2xl text-muted-foreground">
                Las películas más valoradas por nuestra comunidad de expertos
            </p>
        </div>
        <div class="featured-movies-grid">
            <?php if (empty($peliculas)): ?>
                <p class="text-muted-foreground col-span-full text-center">No hay películas destacadas disponibles.</p>
            <?php else: ?>
                <?php foreach ($peliculas as $p): ?>
                    <?php $coverPath = $p['_cover_path'] ?? movieCoverRelativePathFeatured((int) $p['id']); ?>
                    <a href="/proyecto7mo/Frontend/explorar.php?movie_id=<?= (int) $p['id'] ?>" class="featured-movie-card" aria-label="Ver <?= htmlspecialchars($p['title']) ?>">
                        <div class="featured-movie-poster">
                            <?php if ($coverPath): ?>
                                <img src="/proyecto7mo/<?= htmlspecialchars($coverPath) ?>" alt="<?= htmlspecialchars($p['title']) ?>" loading="lazy" decoding="async">
                            <?php else: ?>
                                <div class="featured-movie-placeholder">
                                    <span>🎬</span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="featured-movie-body">
                            <h3><?= htmlspecialchars($p['title']) ?></h3>
                            <p><?= htmlspecialchars($p['genre'] ?? 'General') ?> • <?= (int) ($p['year'] ?? 2024) ?></p>
                            <div class="featured-movie-rating">
                                <?php
                                $avg = max(0, min(5, (float) ($p['average_rating'] ?? 0)));
                                $rounded = (int) round($avg);
                                ?>
                                <span><?= str_repeat('★', $rounded) ?><?= str_repeat('☆', 5 - $rounded) ?></span>
                                <small><?= $avg > 0 ? number_format($avg, 1) : 'Sin valorar' ?></small>
                            </div>
                            <?php if (!empty($p['description'])): ?>
                                <p class="featured-movie-description"><?= htmlspecialchars($p['description']) ?></p>
                            <?php endif; ?>
                        </div>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
