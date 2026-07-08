<?php
$generos = api_get('movies/genres') ?: [];
$previewGenres = array_slice($generos, 0, 6);
?>
<section class="py-20" id="generos">
    <div class="container mx-auto px-4">
        <div class="mb-12 text-center">
            <h2 class="text-3xl font-bold text-foreground md:text-4xl">Explora por Género</h2>
            <p class="mx-auto mt-4 max-w-2xl text-muted-foreground">
                Entrá a una categoría y encontrá películas, reseñas y usuarios con reputación en ese género.
            </p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php if (empty($previewGenres)): ?>
                <p class="text-muted-foreground col-span-full text-center">No hay géneros cargados en el sistema.</p>
            <?php else: ?>
                <?php foreach ($previewGenres as $g): ?>
                    <a href="/proyecto7mo/Frontend/explorar.php?genre=<?= urlencode($g) ?>" class="group rounded-xl border border-border bg-card p-6 transition-all hover:border-primary hover:shadow-lg hover:-translate-y-1 transform duration-300 block">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 group-hover:bg-primary/20 transition-colors">
                            <span class="text-xl font-black text-primary"><?= htmlspecialchars(mb_strtoupper(mb_substr($g, 0, 1, 'UTF-8'))) ?></span>
                        </div>
                        <h3 class="mb-2 text-xl font-semibold text-foreground"><?= htmlspecialchars($g) ?></h3>
                        <p class="text-muted-foreground text-sm">Ver películas y reseñas de <?= htmlspecialchars($g) ?>.</p>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        <div class="mt-10 text-center">
            <a href="/proyecto7mo/Frontend/generos.php" class="inline-flex items-center justify-center rounded-lg bg-primary px-5 py-3 font-bold text-primary-foreground hover:bg-primary/90">
                Ver todos los géneros
            </a>
        </div>
    </div>
</section>
