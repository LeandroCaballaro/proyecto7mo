<?php
session_start();
require_once __DIR__ . '/../Backend/models/Database.php';

if (empty($_SESSION['user']['id'])) {
    header('Location: /proyecto7mo/Frontend/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$roleStmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$roleStmt->execute([(int) $_SESSION['user']['id']]);
$currentRole = $roleStmt->fetchColumn() ?: 'user';
$_SESSION['user']['role'] = $currentRole;

if (!in_array($currentRole, ['admin', 'superadmin'], true)) {
    http_response_code(403);
    echo 'No autorizado';
    exit;
}

$genres = ['Accion', 'Aventura', 'Animacion', 'Comedia', 'Crimen', 'Documental', 'Drama', 'Fantasia', 'Terror', 'Misterio', 'Romance', 'Ciencia ficcion', 'Thriller'];
$message = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'review_pending_movie') {
    try {
        $movieId = (int) ($_POST['movie_id'] ?? 0);
        $title = trim($_POST['title'] ?? '');
        $movieAuthor = trim($_POST['movie_author'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        $year = (int) ($_POST['year'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $decision = $_POST['decision'] ?? '';

        if ($movieId && $title !== '' && $movieAuthor !== '' && in_array($genre, $genres, true) && $year >= 1800) {
            if ($decision === 'approve') {
                $db->prepare("UPDATE movies SET title = ?, movie_author = ?, genre = ?, year = ?, description = ?, approval_status = 'approved', rejection_reason = NULL WHERE id = ?")
                    ->execute([$title, $movieAuthor, $genre, $year, $description, $movieId]);
                $message = 'Película aprobada.';
            } elseif ($decision === 'reject') {
                $db->prepare("UPDATE movies SET title = ?, movie_author = ?, genre = ?, year = ?, description = ?, approval_status = 'rejected', rejection_reason = ? WHERE id = ?")
                    ->execute([$title, $movieAuthor, $genre, $year, $description, 'Rechazada por administración', $movieId]);
                $message = 'Película rechazada.';
            }
        } else {
            $message = 'Revisá los datos antes de continuar.';
        }
    } catch (Throwable $e) {
        $message = 'No se pudo completar la revisión.';
    }
}

$pendingMovies = $db->query("
    SELECT m.id, m.title, m.movie_author, m.genre, m.year, m.description, u.name AS requester_name, u.username AS requester_username
    FROM movies m
    LEFT JOIN users u ON u.id = m.user_id
    WHERE m.approval_status = 'pending'
    ORDER BY m.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprobar Películas - NexoHub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/admin.css">
    <link rel="stylesheet" href="assets/css/directory-pages.css">
</head>
<body class="admin-body">
<?php include 'components/header.php'; ?>
<main class="directory-page approval-page">
    <header class="directory-hero approval-hero">
        <span class="directory-kicker">Administración</span>
        <h1>Aprobar Películas</h1>
        <p>Revisá las películas cargadas manualmente, corregí sus datos y decidí si se publican o se rechazan.</p>
        <?php if ($message): ?><div class="admin-message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    </header>

    <?php if (empty($pendingMovies)): ?>
        <section class="approval-empty">
            <h2>No hay películas pendientes</h2>
            <p>Cuando un usuario cargue una película manualmente, aparecerá acá para revisión.</p>
        </section>
    <?php else: ?>
        <section class="approval-grid">
            <?php foreach ($pendingMovies as $movie): ?>
                <form method="post" class="approval-card">
                    <input type="hidden" name="action" value="review_pending_movie">
                    <input type="hidden" name="movie_id" value="<?= (int) $movie['id'] ?>">
                    <div class="approval-card-head">
                        <span>Solicitud #<?= (int) $movie['id'] ?></span>
                        <small><?= htmlspecialchars($movie['requester_username'] ?: ($movie['requester_name'] ?? 'Usuario')) ?></small>
                    </div>
                    <label>Título <input name="title" value="<?= htmlspecialchars($movie['title']) ?>" required></label>
                    <label>Producido por <input name="movie_author" value="<?= htmlspecialchars($movie['movie_author'] ?? '') ?>" required></label>
                    <label>Género
                        <select name="genre">
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?= htmlspecialchars($genre) ?>" <?= $movie['genre'] === $genre ? 'selected' : '' ?>><?= htmlspecialchars($genre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Año <input type="number" name="year" value="<?= (int) $movie['year'] ?>" required></label>
                    <label>Descripción <textarea name="description"><?= htmlspecialchars($movie['description'] ?? '') ?></textarea></label>
                    <div class="approval-actions">
                        <button type="submit" name="decision" value="approve">Aprobar</button>
                        <button type="submit" name="decision" value="reject" class="danger-btn">Rechazar</button>
                    </div>
                </form>
            <?php endforeach; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
