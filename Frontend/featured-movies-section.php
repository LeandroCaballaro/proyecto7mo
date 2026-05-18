<?php $peliculas = api_get('movies/featured') ?: []; ?>
<section id="destacadas" class="py-12">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold">Películas destacadas</h2>
        <div class="mt-6 grid gap-6 md:grid-cols-3">
            <?php if (empty($peliculas)): ?>
                <p class="text-muted-foreground">No hay películas disponibles.</p>
            <?php else: ?>
                <?php foreach ($peliculas as $p): ?>
                <div class="bg-card p-4 rounded">
                    <h3 class="font-semibold"><?= htmlspecialchars($p['title']) ?></h3>
                    <p class="text-sm text-muted-foreground"><?= htmlspecialchars($p['genre'] ?? '') ?> · <?= (int) ($p['year'] ?? 0) ?></p>
                    <?php if (!empty($p['description'])): ?>
                        <p class="text-sm mt-2"><?= htmlspecialchars($p['description']) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
