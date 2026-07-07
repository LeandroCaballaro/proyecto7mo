<?php
$peliculas = api_get('movies/featured') ?: [];

function movieCoverRelativePathFeatured(int $movieId): ?string
{
    foreach (['png', 'jpg', 'jpeg', 'webp', 'gif'] as $ext) {
        $relative = 'public/covers/' . $movieId . '.' . $ext;
        if (file_exists(__DIR__ . '/../' . $relative)) {
            return $relative;
        }
    }

    return null;
}
?>
<section class="bg-card py-20">
    <div class="container mx-auto px-4">
        <div class="mb-12 text-center">
            <h2 class="text-3xl font-bold text-foreground md:text-4xl">Películas Destacadas</h2>
            <p class="mx-auto mt-4 max-w-2xl text-muted-foreground">
                Las películas más valoradas por nuestra comunidad de expertos
            </p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php if (empty($peliculas)): ?>
                <p class="text-muted-foreground col-span-full text-center">No hay películas destacadas disponibles.</p>
            <?php else: ?>
                <?php foreach ($peliculas as $p): ?>
                <div class="rounded-xl border border-border bg-background p-6 hover:shadow-lg transition-shadow">
                    <div class="aspect-[2/3] mb-4 rounded-lg bg-card flex items-center justify-center relative overflow-hidden">
                        <?php 
                        $coverPath = movieCoverRelativePathFeatured((int) $p['id']);
                        $hasCover = $coverPath !== null;
                        if ($hasCover): 
                        ?>
                            <img src="/proyecto7mo/<?= htmlspecialchars($coverPath) ?>" alt="<?= htmlspecialchars($p['title']) ?>" class="w-full h-full object-cover" loading="lazy" decoding="async">
                        <?php else: ?>
                            <div class="w-full h-full bg-gradient-to-br from-primary/20 to-secondary/30 flex items-center justify-center">
                                <span class="text-4xl">🎥</span>
                            </div>
                        <?php endif; ?>
                    </div>
                    <h3 class="text-xl font-semibold text-foreground"><?= htmlspecialchars($p['title']) ?></h3>
                    <p class="text-muted-foreground text-sm mt-1"><?= htmlspecialchars($p['genre'] ?? 'General') ?> • <?= (int) ($p['year'] ?? 2024) ?></p>
                    <div class="mt-2 flex items-center gap-1">
                        <span class="text-primary">★★★★★</span>
                        <span class="text-sm text-muted-foreground">4.8</span>
                    </div>
                    <?php if (!empty($p['description'])): ?>
                        <p class="text-sm text-muted-foreground mt-2 line-clamp-3"><?= htmlspecialchars($p['description']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
