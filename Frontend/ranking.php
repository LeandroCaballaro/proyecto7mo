<?php
session_start();
define('API_URL', 'http://localhost/proyecto7mo/Backend/public/api.php');

function api_get($route, $extra = [])
{
    $url = API_URL . '?' . http_build_query(array_merge(['route' => $route], $extra));
    $raw = @file_get_contents($url);
    return $raw ? json_decode($raw, true) : null;
}

$rankingRows = api_get('ranking') ?: [];
$byGenre = [];
$overall = [];
foreach ($rankingRows as $row) {
    $genre = $row['genre'] ?? 'General';
    $byGenre[$genre][] = $row;
    $userId = (int) ($row['user_id'] ?? 0);
    if (!isset($overall[$userId])) {
        $overall[$userId] = ['name' => $row['name'] ?? 'Usuario', 'reputation' => 0];
    }
    $overall[$userId]['reputation'] += (int) ($row['reputation'] ?? 0);
}
uasort($overall, fn($a, $b) => $b['reputation'] <=> $a['reputation'] ?: strcmp($a['name'], $b['name']));
ksort($byGenre);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ranking - NexoHub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/ranking.css">
</head>
<body class="bg-background text-foreground min-h-screen">
<?php include 'components/header.php'; ?>
<main class="ranking-page">
    <header class="ranking-hero">
        <h1>Ranking de reputación</h1>
        <p>La reputación se calcula por categoría: cada 10 corazones recibidos en reseñas suman 1 punto.</p>
    </header>

    <section class="ranking-section">
        <div class="ranking-section-header">
            <h2>Top reseñadores</h2>
            <div class="ranking-arrows">
                <button type="button" onclick="scrollRanking('ranking-overall', -1)">‹</button>
                <button type="button" onclick="scrollRanking('ranking-overall', 1)">›</button>
            </div>
        </div>
        <div class="ranking-row" id="ranking-overall">
            <?php if (empty($overall)): ?>
                <p class="ranking-empty">Todavía no hay usuarios con reputación.</p>
            <?php else: ?>
                <?php foreach ($overall as $item): ?>
                    <article class="ranking-card">
                        <span class="ranking-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($item['name'], 0, 1, 'UTF-8'))) ?></span>
                        <h3><?= htmlspecialchars($item['name']) ?></h3>
                        <strong><?= (int) $item['reputation'] ?> pts</strong>
                    </article>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <?php foreach ($byGenre as $genre => $rows): ?>
        <section class="ranking-section">
            <div class="ranking-section-header">
                <h2><?= htmlspecialchars($genre) ?></h2>
                <div class="ranking-arrows">
                    <button type="button" onclick="scrollRanking('ranking-<?= md5($genre) ?>', -1)">‹</button>
                    <button type="button" onclick="scrollRanking('ranking-<?= md5($genre) ?>', 1)">›</button>
                </div>
            </div>
            <div class="ranking-row" id="ranking-<?= md5($genre) ?>">
                <?php foreach ($rows as $item): ?>
                    <article class="ranking-card">
                        <span class="ranking-avatar"><?= htmlspecialchars(mb_strtoupper(mb_substr($item['name'] ?? 'U', 0, 1, 'UTF-8'))) ?></span>
                        <h3><?= htmlspecialchars($item['name'] ?? 'Usuario') ?></h3>
                        <strong><?= (int) ($item['reputation'] ?? 0) ?> pts</strong>
                    </article>
                <?php endforeach; ?>
            </div>
        </section>
    <?php endforeach; ?>
</main>
<?php include 'components/footer.php'; ?>
<script>
function scrollRanking(id, direction) {
    const row = document.getElementById(id);
    if (!row) return;
    row.scrollBy({ left: direction * Math.max(260, row.clientWidth * 0.75), behavior: 'smooth' });
}
</script>
</body>
</html>
