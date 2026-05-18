<?php $generos = api_get('movies/genres') ?: []; ?>
<section id="generos" class="py-12">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold">Géneros populares</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-4">
            <?php if (empty($generos)): ?>
                <p class="text-muted-foreground">No hay géneros cargados.</p>
            <?php else: ?>
                <?php foreach ($generos as $g): ?>
                <a href="explorar.php?genre=<?= urlencode($g) ?>" class="bg-card p-4 rounded block hover:border-primary">
                    <?= htmlspecialchars($g) ?>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        </div>
</section>
