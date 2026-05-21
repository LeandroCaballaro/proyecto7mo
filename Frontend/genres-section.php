<?php 
$generos = api_get('movies/genres') ?: []; 
$genreInfo = [
    'Acción' => ['emoji' => '🎬', 'desc' => 'Películas llenas de adrenalina, combates y emoción.'],
    'Terror' => ['emoji' => '👻', 'desc' => 'Para los amantes del suspenso, lo sobrenatural y el miedo.'],
    'Comedia' => ['emoji' => '😂', 'desc' => 'Risas garantizadas y momentos divertidos con los mejores elencos.'],
    'Drama' => ['emoji' => '🎭', 'desc' => 'Historias profundas, emotivas y personajes memorables.'],
    'Ciencia Ficción' => ['emoji' => '🚀', 'desc' => 'Viajes espaciales, tecnología del futuro y realidades alternas.'],
    'Romance' => ['emoji' => '❤️', 'desc' => 'Historias de amor, pasión y romance inolvidables.'],
];
?>
<section class="py-20" id="generos">
    <div class="container mx-auto px-4">
        <div class="mb-12 text-center">
            <h2 class="text-3xl font-bold text-foreground md:text-4xl">Explora por Género</h2>
            <p class="mx-auto mt-4 max-w-2xl text-muted-foreground">
                Descubre películas en tu género favorito con reseñas de expertos especializados
            </p>
        </div>
        <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            <?php if (empty($generos)): ?>
                <p class="text-muted-foreground col-span-full text-center">No hay géneros cargados en el sistema.</p>
            <?php else: ?>
                <?php foreach ($generos as $g): ?>
                    <?php 
                    $info = $genreInfo[$g] ?? ['emoji' => '🎥', 'desc' => "Descubre las mejores películas clasificadas en el género $g."];
                    ?>
                    <a href="Frontend/explorar.php?genre=<?= urlencode($g) ?>" class="group rounded-xl border border-border bg-card p-6 transition-all hover:border-primary hover:shadow-lg hover:-translate-y-1 transform duration-300 block">
                        <div class="mb-4 flex h-12 w-12 items-center justify-center rounded-lg bg-primary/10 group-hover:bg-primary/20 transition-colors">
                            <span class="text-2xl"><?= $info['emoji'] ?></span>
                        </div>
                        <h3 class="mb-2 text-xl font-semibold text-foreground"><?= htmlspecialchars($g) ?></h3>
                        <p class="text-muted-foreground text-sm"><?= htmlspecialchars($info['desc']) ?></p>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</section>
