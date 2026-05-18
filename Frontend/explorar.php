<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar Películas - NexoHub</title>
    <meta name="description" content="Explora películas por género y reseñas confiables">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="style/styles.css" rel="stylesheet">

</head>
<body>
    <?php include 'header.php'; ?>
    <main class="flex-1">
        <section class="py-12">
            <div class="container mx-auto px-4">
                <div class="mb-8">
                    <h1 class="text-3xl font-bold text-foreground md:text-4xl">Explorar Películas</h1>
                    <p class="mt-2 text-muted-foreground">Descubre películas recomendadas por expertos en cada género</p>
                </div>

                <!-- Filters -->
                <div class="mb-8 flex flex-wrap gap-4">
                    <select class="rounded-lg border border-border bg-card px-4 py-2 text-foreground">
                        <option>Todos los Géneros</option>
                        <option>Acción</option>
                        <option>Terror</option>
                        <option>Comedia</option>
                        <option>Drama</option>
                        <option>Ciencia Ficción</option>
                    </select>
                    <select class="rounded-lg border border-border bg-card px-4 py-2 text-foreground">
                        <option>Ordenar por: Rating</option>
                        <option>Ordenar por: Año</option>
                        <option>Ordenar por: Popularidad</option>
                    </select>
                    <button class="rounded-lg border border-border bg-card px-4 py-2 text-foreground hover:bg-secondary">
                        <svg class="mr-2 inline h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"></path>
                        </svg>
                        Filtros
                    </button>
                </div>

                <!-- Movies Grid -->
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <div class="rounded-xl border border-border bg-card p-6">
                        <div class="aspect-[2/3] mb-4 rounded-lg bg-secondary"></div>
                        <h3 class="text-xl font-semibold text-foreground">Dune: Parte Dos</h3>
                        <p class="text-muted-foreground">Ciencia Ficción • 2024</p>
                        <div class="mt-2 flex items-center gap-1">
                            <span class="text-primary">★★★★★</span>
                            <span class="text-sm text-muted-foreground">4.8 (1250 reseñas)</span>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">Paul Atreides se une a los Fremen mientras busca venganza.</p>
                        <button class="mt-4 w-full rounded-lg bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90">Ver Detalles</button>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-6">
                        <div class="aspect-[2/3] mb-4 rounded-lg bg-secondary"></div>
                        <h3 class="text-xl font-semibold text-foreground">Oppenheimer</h3>
                        <p class="text-muted-foreground">Drama • 2023</p>
                        <div class="mt-2 flex items-center gap-1">
                            <span class="text-primary">★★★★★</span>
                            <span class="text-sm text-muted-foreground">4.9 (2340 reseñas)</span>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">La historia del físico J. Robert Oppenheimer.</p>
                        <button class="mt-4 w-full rounded-lg bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90">Ver Detalles</button>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-6">
                        <div class="aspect-[2/3] mb-4 rounded-lg bg-secondary"></div>
                        <h3 class="text-xl font-semibold text-foreground">Spider-Man: Across the Spider-Verse</h3>
                        <p class="text-muted-foreground">Animación • 2023</p>
                        <div class="mt-2 flex items-center gap-1">
                            <span class="text-primary">★★★★★</span>
                            <span class="text-sm text-muted-foreground">4.7 (1890 reseñas)</span>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">Miles Morales viaja a través del Multiverso.</p>
                        <button class="mt-4 w-full rounded-lg bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90">Ver Detalles</button>
                    </div>
                    <div class="rounded-xl border border-border bg-card p-6">
                        <div class="aspect-[2/3] mb-4 rounded-lg bg-secondary"></div>
                        <h3 class="text-xl font-semibold text-foreground">Barbie</h3>
                        <p class="text-muted-foreground">Comedia • 2023</p>
                        <div class="mt-2 flex items-center gap-1">
                            <span class="text-primary">★★★★☆</span>
                            <span class="text-sm text-muted-foreground">4.5 (3120 reseñas)</span>
                        </div>
                        <p class="mt-2 text-sm text-muted-foreground">Barbie sufre una crisis existencial.</p>
                        <button class="mt-4 w-full rounded-lg bg-primary px-4 py-2 text-primary-foreground hover:bg-primary/90">Ver Detalles</button>
                    </div>
                </div>
            </div>
        </section>
    </main>
    <?php include 'footer.php'; ?>
</body>
</html>