<?php
session_start();
define('API_URL', 'http://localhost/proyecto7mo/Backend/public/api.php');

function api_get($route, $extra = [])
{
    $url = API_URL . '?' . http_build_query(array_merge(['route' => $route], $extra));
    $ctx = stream_context_create(['http' => ['ignore_errors' => true]]);
    $raw = @file_get_contents($url, false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

function api_post($route, $data, $token = null)
{
    $h = "Content-Type: application/json\r\n";
    if ($token) {
        $h .= "Authorization: Bearer $token\r\n";
    }
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => $h,
        'content'       => json_encode($data),
        'ignore_errors' => true,
    ]]);
    $raw = @file_get_contents(API_URL . '?route=' . urlencode($route), false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

$genre = $_GET['genre'] ?? '';
$peliculas = $genre ? api_get('movies/genre/' . urlencode($genre)) : api_get('movies');
$msg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SESSION['token'])) {
    $res = api_post('reviews', [
        'movie_id' => (int) ($_POST['movie_id'] ?? 0),
        'rating' => (int) ($_POST['rating'] ?? 0),
        'comment' => $_POST['comment'] ?? '',
    ], $_SESSION['token']);
    $msg = isset($res['ok']) ? 'Reseña publicada (+reputación por género)' : ($res['error'] ?? 'Error');
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explorar Películas - NexoHub</title>
    <meta name="description" content="Explora películas por género y reseñas de la comunidad">
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="style/styles.css" rel="stylesheet">
</head>
<body class="bg-background text-foreground min-h-screen flex flex-col">
    <?php include 'header.php'; ?>

    <main class="flex-1 py-12">
        <div class="container mx-auto px-4">
            <div class="mb-8">
                <h1 class="text-3xl font-bold md:text-4xl text-foreground">
                    Explorar Películas
                </h1>
                <?php if ($genre): ?>
                    <p class="mt-2 text-muted-foreground">Filtrado por género: <strong class="text-primary"><?= htmlspecialchars($genre) ?></strong></p>
                <?php else: ?>
                    <p class="mt-2 text-muted-foreground">Descubre películas recomendadas por expertos en cada género</p>
                <?php endif; ?>
            </div>

            <!-- Alerta de Respuesta del Formulario -->
            <?php if ($msg): ?>
                <div class="mb-6 p-4 rounded-lg bg-card border border-border text-foreground flex items-center gap-2">
                    <span class="text-primary font-bold">📢</span>
                    <span><?= htmlspecialchars($msg) ?></span>
                </div>
            <?php endif; ?>

            <!-- Filtros -->
            <div class="mb-8 flex flex-wrap gap-4">
                <select onchange="location = this.value;" class="rounded-lg border border-border bg-card px-4 py-2 text-foreground focus:outline-none focus:ring-2 focus:ring-primary">
                    <option value="explorar.php">Todos los Géneros</option>
                    <?php foreach (api_get('movies/genres') ?: [] as $g): ?>
                        <option value="explorar.php?genre=<?= urlencode($g) ?>" <?= $genre === $g ? 'selected' : '' ?>>
                            <?= htmlspecialchars($g) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <select class="rounded-lg border border-border bg-card px-4 py-2 text-foreground focus:outline-none">
                    <option>Ordenar por: Rating</option>
                    <option>Ordenar por: Año</option>
                    <option>Ordenar por: Popularidad</option>
                </select>
            </div>

            <!-- Películas Grid -->
            <?php if (empty($peliculas)): ?>
                <div class="text-center py-20 bg-card rounded-xl border border-border">
                    <span class="text-4xl">🎬</span>
                    <p class="mt-4 text-muted-foreground">No se encontraron películas en esta categoría.</p>
                </div>
            <?php else: ?>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                    <?php foreach ($peliculas as $p): ?>
                        <div class="rounded-xl border border-border bg-card p-6 flex flex-col justify-between hover:shadow-lg transition-shadow">
                            <div>
                                <!-- Cartel de Película Simulado con Gradiente Moderno -->
                                <div class="aspect-[2/3] mb-4 rounded-lg bg-gradient-to-br from-primary/20 to-secondary/30 flex items-center justify-center relative overflow-hidden">
                                    <span class="text-4xl">🎥</span>
                                </div>
                                <h3 class="text-xl font-semibold text-foreground"><?= htmlspecialchars($p['title']) ?></h3>
                                <p class="text-muted-foreground text-sm mt-1"><?= htmlspecialchars($p['genre'] ?? 'General') ?> • <?= (int) ($p['year'] ?? 2024) ?></p>
                                
                                <?php if (!empty($p['description'])): ?>
                                    <p class="mt-2 text-sm text-muted-foreground line-clamp-3"><?= htmlspecialchars($p['description']) ?></p>
                                <?php endif; ?>
                            </div>

                            <div class="mt-4">
                                <?php if (!empty($_SESSION['user'])): ?>
                                    <form method="post" class="mt-4 pt-4 border-t border-border space-y-3">
                                        <input type="hidden" name="movie_id" value="<?= (int) $p['id'] ?>">
                                        <div class="flex items-center justify-between">
                                            <label class="text-xs font-semibold text-muted-foreground">Calificación:</label>
                                            <select name="rating" class="rounded border border-border bg-background text-foreground text-xs px-2 py-1 focus:ring-1 focus:ring-primary focus:outline-none">
                                                <?php for ($i = 5; $i >= 1; $i--): ?>
                                                    <option value="<?= $i ?>"><?= str_repeat('★', $i) . str_repeat('☆', 5 - $i) ?></option>
                                                <?php endfor; ?>
                                            </select>
                                        </div>
                                        <input type="text" name="comment" placeholder="Escribe tu reseña..." required class="w-full text-xs rounded border border-border bg-background px-3 py-2 text-foreground placeholder-muted-foreground focus:outline-none focus:ring-1 focus:ring-primary">
                                        <button type="submit" class="w-full rounded-lg bg-primary text-primary-foreground hover:bg-primary/90 py-2 text-xs font-semibold transition-colors">
                                            Publicar Reseña
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <div class="mt-4 pt-4 border-t border-border text-center">
                                        <p class="text-xs text-muted-foreground">
                                            <a href="/proyecto7mo/Frontend/login.php" class="text-primary hover:underline font-semibold">Inicia sesión</a> para calificar esta película.
                                        </p>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <?php include 'footer.php'; ?>
</body>
</html>
