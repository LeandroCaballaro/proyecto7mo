<?php $ranking = api_get('reviewers', ['limit' => 10]) ?: []; ?>
<section id="ranking" class="py-12">
    <div class="container mx-auto px-4">
        <h2 class="text-2xl font-bold">Top reseñadores</h2>
        <div class="mt-6 grid gap-4 md:grid-cols-3">
            <?php if (empty($ranking)): ?>
                <p class="text-muted-foreground">Sin datos de ranking.</p>
            <?php else: ?>
                <?php foreach ($ranking as $r): ?>
                <div class="bg-card p-4 rounded">
                    <span class="font-semibold"><?= htmlspecialchars($r['name']) ?></span>
                    <span class="text-primary"> — <?= (int) $r['reputation'] ?> pts</span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
