<?php
session_start();
define('API_URL', 'http://proyecto7mo.test/Backend/public/api.php');

function api_get($route, $extra = [])
{
    $url = API_URL . '?' . http_build_query(array_merge(['route' => $route], $extra));
    $raw = @file_get_contents($url);
    return $raw ? json_decode($raw, true) : null;
}

function api_post($route, $data, $token = null)
{
    $h = "Content-Type: application/json\r\n";
    if ($token) {
        $h .= "Authorization: Bearer $token\r\n";
    }
    $ctx = stream_context_create(['http' => ['method' => 'POST', 'header' => $h, 'content' => json_encode($data)]]);
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
<?php include 'header.php'; ?>
<main class="py-12">
    <div class="container mx-auto px-4">
        <h1 class="text-2xl font-bold">Explorar películas</h1>
        <?php if ($genre): ?><p class="text-muted-foreground">Género: <?= htmlspecialchars($genre) ?></p><?php endif; ?>
        <?php if ($msg): ?><p class="mt-4 p-3 bg-card rounded"><?= htmlspecialchars($msg) ?></p><?php endif; ?>

        <div class="mt-8 grid gap-6 md:grid-cols-2">
            <?php foreach ($peliculas ?: [] as $p): ?>
            <div class="bg-card p-4 rounded">
                <h3 class="font-semibold"><?= htmlspecialchars($p['title']) ?></h3>
                <p class="text-sm"><?= htmlspecialchars($p['genre'] ?? '') ?> · <?= (int) ($p['year'] ?? 0) ?></p>
                <?php if (!empty($_SESSION['user'])): ?>
                <form method="post" class="mt-3 space-y-2">
                    <input type="hidden" name="movie_id" value="<?= (int) $p['id'] ?>">
                    <select name="rating" class="border rounded px-2 py-1">
                        <?php for ($i = 1; $i <= 5; $i++): ?><option value="<?= $i ?>"><?= $i ?> estrellas</option><?php endfor; ?>
                    </select>
                    <input type="text" name="comment" placeholder="Tu reseña" class="w-full border rounded px-2 py-1">
                    <button type="submit" class="bg-primary text-white px-3 py-1 rounded text-sm">Publicar</button>
                </form>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</main>
<?php include 'footer.php'; ?>
