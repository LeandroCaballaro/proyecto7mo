<?php $ranking = api_get('reviewers', ['limit' => 10]) ?: []; ?>
<section class="py-20" id="ranking">
    <div class="container mx-auto px-4">
        <div class="mb-12 text-center">
            <h2 class="text-3xl font-bold text-foreground md:text-4xl">Top Reseñadores</h2>
            <p class="mx-auto mt-4 max-w-2xl text-muted-foreground">
                Los críticos más influyentes en cada género
            </p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php if (empty($ranking)): ?>
                <p class="text-muted-foreground col-span-full text-center">Sin datos de ranking actualmente.</p>
            <?php else: ?>
                <?php foreach ($ranking as $r): ?>
                <div class="rounded-xl border border-border bg-card p-6 text-center hover:shadow-lg transition-shadow">
                    <div class="mx-auto mb-4 h-16 w-16 rounded-full bg-primary/10 flex items-center justify-center">
                        <span class="text-2xl">👤</span>
                    </div>
                    <h3 class="text-xl font-semibold text-foreground"><?= htmlspecialchars($r['name']) ?></h3>
                    <p class="text-muted-foreground text-sm">Crítico Destacado</p>
                    <div class="mt-2">
                        <span class="text-primary font-bold"><?= (int) $r['reputation'] ?> pts de Reputación</span>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
