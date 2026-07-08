<?php
session_start();
require_once __DIR__ . '/../Backend/models/Database.php';

if (empty($_SESSION['user']['id'])) {
    header('Location: /proyecto7mo/Frontend/login.php');
    exit;
}

$db = Database::getInstance()->getConnection();
$db->exec("
    CREATE TABLE IF NOT EXISTS favorite_movies (
        id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        movie_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY uk_user_movie_favorite (user_id, movie_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");
$stmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
$stmt->execute([(int) $_SESSION['user']['id']]);
$currentRole = $stmt->fetchColumn() ?: 'user';
$_SESSION['user']['role'] = $currentRole;

$isSuperAdmin = $currentRole === 'superadmin';
$canManageAdmin = in_array($currentRole, ['admin', 'superadmin'], true);

if (!$canManageAdmin) {
    http_response_code(403);
    echo 'No autorizado';
    exit;
}

$message = '';
$movieSearch = trim((string) ($_GET['q_movie'] ?? ''));
$userSearch = trim((string) ($_GET['q_user'] ?? ''));
$commentUserSearch = trim((string) ($_GET['q_comment_user'] ?? ''));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['q_movie'])) {
    $movieSearch = trim((string) $_POST['q_movie']);
}
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['q_user'])) {
    $userSearch = trim((string) $_POST['q_user']);
}
$commentUserPayload = [];
$genres = ['Accion', 'Aventura', 'Animacion', 'Comedia', 'Crimen', 'Documental', 'Drama', 'Fantasia', 'Terror', 'Misterio', 'Romance', 'Ciencia ficcion', 'Thriller'];

function cleanup_user(PDO $db, int $userId): void
{
    $db->prepare("DELETE rr FROM review_responses rr INNER JOIN reviews r ON r.id = rr.review_id WHERE r.user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM review_responses WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM reviews WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM favorite_movies WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM genre_reputation WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM reviewers WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM api_tokens WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM password_reset_tokens WHERE user_id = ?")->execute([$userId]);
    $db->prepare("UPDATE movies SET user_id = NULL WHERE user_id = ?")->execute([$userId]);
    $db->prepare("DELETE FROM users WHERE id = ?")->execute([$userId]);
}

function render_user_form(array $item, bool $isSuperAdmin, int $currentUserId, string $userSearch): void
{
    ?>
    <form method="post" class="admin-card">
        <input type="hidden" name="action" value="update_user">
        <input type="hidden" name="user_id" value="<?= (int) $item['id'] ?>">
        <input type="hidden" name="q_user" value="<?= htmlspecialchars($userSearch) ?>">
        <label>Nombre <input name="name" value="<?= htmlspecialchars($item['name']) ?>" required></label>
        <label>Usuario <input name="username" value="<?= htmlspecialchars($item['username'] ?? '') ?>" required></label>
        <label>Email <input type="email" name="email" value="<?= htmlspecialchars($item['email']) ?>" required></label>
        <label>Rol
            <select name="role">
                <option value="user" <?= $item['role'] === 'user' ? 'selected' : '' ?>>Usuario</option>
                <option value="admin" <?= $item['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                <?php if ($isSuperAdmin || $item['role'] === 'superadmin'): ?>
                    <option value="superadmin" <?= $item['role'] === 'superadmin' ? 'selected' : '' ?> <?= !$isSuperAdmin ? 'disabled' : '' ?>>Superadmin</option>
                <?php endif; ?>
            </select>
        </label>
        <label>Nueva contraseña <input type="password" name="password" placeholder="Dejar vacío para no cambiar"></label>
        <button type="submit">Guardar usuario</button>
        <?php if ((int) $item['id'] !== $currentUserId && ($isSuperAdmin || !in_array($item['role'], ['admin', 'superadmin'], true))): ?>
            <button type="submit" name="action" value="delete_user" class="danger-btn" onclick="return confirm('Eliminar este usuario?')">Eliminar usuario</button>
        <?php endif; ?>
    </form>
    <?php
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $requestedRole = $_POST['role'] ?? 'user';
            $role = in_array($requestedRole, ['user', 'admin'], true) ? $requestedRole : 'user';
            if ($isSuperAdmin && $requestedRole === 'superadmin') {
                $role = 'superadmin';
            }
            $password = $_POST['password'] ?? '';

            if ($userId && $name !== '' && $username !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $targetRoleStmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
                $targetRoleStmt->execute([$userId]);
                $targetRole = $targetRoleStmt->fetchColumn() ?: 'user';
                if ($targetRole === 'superadmin' && !$isSuperAdmin) {
                    $message = 'Solo un superadmin puede editar otro superadmin.';
                    goto end_post_actions;
                }
                $db->prepare("UPDATE users SET name = ?, username = ?, email = ?, role = ? WHERE id = ?")
                    ->execute([$name, $username, $email, $role, $userId]);
                $db->prepare("UPDATE reviewers SET name = ? WHERE user_id = ?")->execute([$name, $userId]);

                if ($password !== '') {
                    $db->prepare("UPDATE users SET password_hash = ? WHERE id = ?")
                        ->execute([password_hash($password, PASSWORD_BCRYPT), $userId]);
                    $db->prepare("DELETE FROM api_tokens WHERE user_id = ?")->execute([$userId]);
                }
                $message = 'Usuario actualizado.';
            }
        }

        if ($action === 'delete_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            if ($userId && $userId !== (int) $_SESSION['user']['id']) {
                $targetRoleStmt = $db->prepare("SELECT role FROM users WHERE id = ? LIMIT 1");
                $targetRoleStmt->execute([$userId]);
                $targetRole = $targetRoleStmt->fetchColumn() ?: 'user';
                if (in_array($targetRole, ['admin', 'superadmin'], true) && !$isSuperAdmin) {
                    $message = 'Solo un superadmin puede eliminar cuentas administradoras.';
                    goto end_post_actions;
                }
                $db->beginTransaction();
                cleanup_user($db, $userId);
                $db->commit();
                $message = 'Usuario eliminado.';
            } else {
                $message = 'No puedes eliminar tu propia cuenta desde administracion.';
            }
        }

        if ($action === 'update_movie') {
            $movieId = (int) ($_POST['movie_id'] ?? 0);
            $title = trim($_POST['title'] ?? '');
            $movieAuthor = trim($_POST['movie_author'] ?? '');
            $genre = trim($_POST['genre'] ?? '');
            $year = (int) ($_POST['year'] ?? 0);
            $description = trim($_POST['description'] ?? '');
            $featured = isset($_POST['featured']) ? 1 : 0;

            if ($movieId && $title !== '' && $movieAuthor !== '' && in_array($genre, $genres, true) && $year >= 1800) {
                $db->prepare("UPDATE movies SET title = ?, movie_author = ?, genre = ?, year = ?, description = ?, featured = ? WHERE id = ?")
                    ->execute([$title, $movieAuthor, $genre, $year, $description, $featured, $movieId]);
                $message = 'Pelicula actualizada.';
            }
        }

        if ($action === 'delete_movie') {
            $movieId = (int) ($_POST['movie_id'] ?? 0);
            if ($movieId) {
                $reviewIds = $db->prepare("SELECT id FROM reviews WHERE movie_id = ?");
                $reviewIds->execute([$movieId]);
                $ids = $reviewIds->fetchAll(PDO::FETCH_COLUMN);
                if ($ids) {
                    $placeholders = implode(',', array_fill(0, count($ids), '?'));
                    $db->prepare("DELETE FROM review_responses WHERE review_id IN ($placeholders)")->execute($ids);
                }
                $db->prepare("DELETE FROM reviews WHERE movie_id = ?")->execute([$movieId]);
                $db->prepare("DELETE FROM favorite_movies WHERE movie_id = ?")->execute([$movieId]);
                $db->prepare("DELETE FROM movies WHERE id = ?")->execute([$movieId]);
                $message = 'Pelicula eliminada.';
            }
        }

        if ($action === 'delete_review') {
            $reviewId = (int) ($_POST['review_id'] ?? 0);
            if ($reviewId) {
                $db->prepare("DELETE FROM review_responses WHERE review_id = ?")->execute([$reviewId]);
                $db->prepare("DELETE FROM reviews WHERE id = ?")->execute([$reviewId]);
                $message = 'Reseña eliminada.';
            }
        }

        if ($action === 'delete_response') {
            $responseId = (int) ($_POST['response_id'] ?? 0);
            if ($responseId) {
                $db->prepare("DELETE FROM review_responses WHERE id = ?")->execute([$responseId]);
                $message = 'Respuesta eliminada.';
            }
        }

        if ($action === 'review_pending_movie') {
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
                    $message = 'Pelicula aprobada.';
                } elseif ($decision === 'reject') {
                    $db->prepare("UPDATE movies SET title = ?, movie_author = ?, genre = ?, year = ?, description = ?, approval_status = 'rejected', rejection_reason = ? WHERE id = ?")
                        ->execute([$title, $movieAuthor, $genre, $year, $description, 'Rechazada por administracion', $movieId]);
                    $message = 'Pelicula rechazada.';
                }
            }
        }
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $message = 'No se pudo completar la accion.';
    }
    end_post_actions:
}

$admins = $db->query("
    SELECT id, name, username, email, role, is_public
    FROM users
    WHERE role IN ('admin', 'superadmin')
    ORDER BY CASE role WHEN 'superadmin' THEN 0 ELSE 1 END, username ASC, id ASC
")->fetchAll(PDO::FETCH_ASSOC);
$users = [];
if ($userSearch !== '') {
    $userStmt = $db->prepare("
        SELECT id, name, username, email, role, is_public
        FROM users
        WHERE role = 'user' AND username LIKE ?
        ORDER BY username ASC, id ASC
        LIMIT 24
    ");
    $userStmt->execute(['%' . $userSearch . '%']);
    $users = $userStmt->fetchAll(PDO::FETCH_ASSOC);
}
$movies = [];
if ($movieSearch !== '') {
    $movieStmt = $db->prepare("
        SELECT id, title, movie_author, genre, year, featured, description
        FROM movies
        WHERE title LIKE ? OR movie_author LIKE ? OR genre LIKE ?
        ORDER BY title ASC
        LIMIT 24
    ");
    $likeMovieSearch = '%' . $movieSearch . '%';
    $movieStmt->execute([$likeMovieSearch, $likeMovieSearch, $likeMovieSearch]);
    $movies = $movieStmt->fetchAll(PDO::FETCH_ASSOC);
}
$pendingMovies = $db->query("
    SELECT m.id, m.title, m.movie_author, m.genre, m.year, m.description, u.name AS requester_name
    FROM movies m
    LEFT JOIN users u ON u.id = m.user_id
    WHERE m.approval_status = 'pending'
    ORDER BY m.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
$commentUsers = [];
if ($commentUserSearch !== '') {
    $commentUserStmt = $db->prepare("
        SELECT id, name, username
        FROM users
        WHERE username LIKE ?
        ORDER BY username ASC
        LIMIT 10
    ");
    $commentUserStmt->execute(['%' . $commentUserSearch . '%']);
    $commentUsers = $commentUserStmt->fetchAll(PDO::FETCH_ASSOC);

    $commentReviewsStmt = $db->prepare("
        SELECT r.id, r.comment, r.rating, r.created_at, m.title AS movie_title
        FROM reviews r
        LEFT JOIN movies m ON m.id = r.movie_id
        WHERE r.user_id = ?
        ORDER BY r.created_at DESC, r.id DESC
    ");
    $commentResponsesStmt = $db->prepare("
        SELECT rr.id, rr.comment, rr.created_at, m.title AS movie_title
        FROM review_responses rr
        LEFT JOIN reviews r ON r.id = rr.review_id
        LEFT JOIN movies m ON m.id = r.movie_id
        WHERE rr.user_id = ?
        ORDER BY rr.created_at DESC, rr.id DESC
    ");

    foreach ($commentUsers as $commentUser) {
        $commentReviewsStmt->execute([(int) $commentUser['id']]);
        $commentResponsesStmt->execute([(int) $commentUser['id']]);
        $commentUserPayload[(int) $commentUser['id']] = [
            'name' => $commentUser['name'],
            'username' => $commentUser['username'],
            'reviews' => $commentReviewsStmt->fetchAll(PDO::FETCH_ASSOC),
            'responses' => $commentResponsesStmt->fetchAll(PDO::FETCH_ASSOC),
        ];
    }
}
$reviews = $db->query("
    SELECT r.id, r.comment, r.rating, u.name AS user_name, m.title AS movie_title
    FROM reviews r
    LEFT JOIN users u ON u.id = r.user_id
    LEFT JOIN movies m ON m.id = r.movie_id
    ORDER BY r.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
$responses = $db->query("
    SELECT rr.id, rr.comment, rr.rating, u.name AS user_name, r.comment AS review_comment
    FROM review_responses rr
    LEFT JOIN users u ON u.id = rr.user_id
    LEFT JOIN reviews r ON r.id = rr.review_id
    ORDER BY rr.id DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Administración - NexoHub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link rel="stylesheet" href="assets/css/admin.css">
</head>
<body class="admin-body">
<?php include 'components/header.php'; ?>
<main class="admin-main">
    <header class="admin-hero">
        <h1>Administración</h1>
        <p>Gestiona usuarios, contraseñas, películas y comentarios.</p>
        <?php if ($message): ?><div class="admin-message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    </header>

    <section class="admin-section">
        <h2>Administradores</h2>
        <div class="admin-grid">
            <?php foreach ($admins as $item): ?>
                <form method="post" class="admin-card">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="<?= (int) $item['id'] ?>">
                    <input type="hidden" name="q_user" value="<?= htmlspecialchars($userSearch) ?>">
                    <label>Nombre <input name="name" value="<?= htmlspecialchars($item['name']) ?>" required></label>
                    <label>Usuario <input name="username" value="<?= htmlspecialchars($item['username'] ?? '') ?>" required></label>
                    <label>Email <input type="email" name="email" value="<?= htmlspecialchars($item['email']) ?>" required></label>
                    <label>Rol
                        <select name="role">
                            <option value="user" <?= $item['role'] === 'user' ? 'selected' : '' ?>>Usuario</option>
                            <option value="admin" <?= $item['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                            <?php if ($isSuperAdmin || $item['role'] === 'superadmin'): ?>
                                <option value="superadmin" <?= $item['role'] === 'superadmin' ? 'selected' : '' ?> <?= !$isSuperAdmin ? 'disabled' : '' ?>>Superadmin</option>
                            <?php endif; ?>
                        </select>
                    </label>
                    <label>Nueva contraseña <input type="password" name="password" placeholder="Dejar vacio para no cambiar"></label>
                    <button type="submit">Guardar usuario</button>
                    <?php if ((int) $item['id'] !== (int) $_SESSION['user']['id'] && ($isSuperAdmin || !in_array($item['role'], ['admin', 'superadmin'], true))): ?>
                        <button type="submit" name="action" value="delete_user" class="danger-btn" onclick="return confirm('Eliminar este usuario?')">Eliminar usuario</button>
                    <?php endif; ?>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-section">
        <h2>Usuarios</h2>
        <form method="get" class="admin-search-form">
            <label for="q_user">Buscar usuario cargado por nombre de usuario</label>
            <div class="admin-search-row">
                <input id="q_user" type="search" name="q_user" value="<?= htmlspecialchars($userSearch) ?>" placeholder="Ej: carlosp, anagomez o superadmin" required>
                <?php if ($movieSearch !== ''): ?>
                    <input type="hidden" name="q_movie" value="<?= htmlspecialchars($movieSearch) ?>">
                <?php endif; ?>
                <button type="submit">Buscar</button>
                <?php if ($userSearch !== ''): ?>
                    <a href="/proyecto7mo/Frontend/admin.php<?= $movieSearch !== '' ? '?q_movie=' . urlencode($movieSearch) : '' ?>" class="admin-clear-link">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($userSearch === ''): ?>
            <p class="admin-empty">Usa el buscador para encontrar un usuario por su username y editarlo.</p>
        <?php elseif (empty($users)): ?>
            <p class="admin-empty">No se encontraron usuarios con username "<?= htmlspecialchars($userSearch) ?>".</p>
        <?php else: ?>
            <p class="admin-results-count"><?= count($users) ?> resultado<?= count($users) === 1 ? '' : 's' ?> para username "<?= htmlspecialchars($userSearch) ?>".</p>
            <div class="admin-grid">
                <?php foreach ($users as $item): ?>
                    <?php render_user_form($item, $isSuperAdmin, (int) $_SESSION['user']['id'], $userSearch); ?>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-section">
        <h2>Películas</h2>
        <form method="get" class="admin-search-form">
            <label for="q_movie">Buscar película cargada</label>
            <div class="admin-search-row">
                <input id="q_movie" type="search" name="q_movie" value="<?= htmlspecialchars($movieSearch) ?>" placeholder="Título, autor o género" required>
                <?php if ($userSearch !== ''): ?>
                    <input type="hidden" name="q_user" value="<?= htmlspecialchars($userSearch) ?>">
                <?php endif; ?>
                <button type="submit">Buscar</button>
                <?php if ($movieSearch !== ''): ?>
                    <a href="/proyecto7mo/Frontend/admin.php<?= $userSearch !== '' ? '?q_user=' . urlencode($userSearch) : '' ?>" class="admin-clear-link">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($movieSearch === ''): ?>
            <p class="admin-empty">Usa el buscador para encontrar una película y editarla.</p>
        <?php elseif (empty($movies)): ?>
            <p class="admin-empty">No se encontraron películas para "<?= htmlspecialchars($movieSearch) ?>".</p>
        <?php else: ?>
            <p class="admin-results-count"><?= count($movies) ?> resultado<?= count($movies) === 1 ? '' : 's' ?> para "<?= htmlspecialchars($movieSearch) ?>".</p>
            <div class="admin-grid">
                <?php foreach ($movies as $movie): ?>
                <form method="post" class="admin-card">
                    <input type="hidden" name="action" value="update_movie">
                    <input type="hidden" name="q_movie" value="<?= htmlspecialchars($movieSearch) ?>">
                    <input type="hidden" name="movie_id" value="<?= (int) $movie['id'] ?>">
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
                    <label class="inline-check"><input type="checkbox" name="featured" <?= $movie['featured'] ? 'checked' : '' ?>> Destacada</label>
                    <button type="submit">Guardar película</button>
                    <button type="submit" name="action" value="delete_movie" class="danger-btn" onclick="return confirm('Eliminar esta película?')">Eliminar película</button>
                </form>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>

    <section class="admin-section">
        <h2>Reseñas y comentarios</h2>
        <form method="get" class="admin-search-form">
            <label for="q_comment_user">Buscar actividad por nombre de usuario</label>
            <div class="admin-search-row">
                <input id="q_comment_user" type="search" name="q_comment_user" value="<?= htmlspecialchars($commentUserSearch) ?>" placeholder="Escribe el username para revisar sus reseñas y respuestas" required>
                <button type="submit">Buscar</button>
                <?php if ($commentUserSearch !== ''): ?>
                    <a href="/proyecto7mo/Frontend/admin.php" class="admin-clear-link">Limpiar</a>
                <?php endif; ?>
            </div>
        </form>

        <?php if ($commentUserSearch === ''): ?>
            <p class="admin-empty">Busca un usuario por username para moderar sus reseñas y comentarios.</p>
        <?php elseif (empty($commentUsers)): ?>
            <p class="admin-empty">No se encontraron usuarios con username "<?= htmlspecialchars($commentUserSearch) ?>".</p>
        <?php else: ?>
            <div class="admin-list">
                <?php foreach ($commentUsers as $commentUser): ?>
                    <button type="button" class="admin-row admin-user-open" onclick="openCommentUserModal(<?= (int) $commentUser['id'] ?>)">
                        <span><strong><?= htmlspecialchars($commentUser['username'] ?? '') ?></strong> · <?= htmlspecialchars($commentUser['name'] ?? '') ?></span>
                        <span>Ver actividad</span>
                    </button>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </section>
</main>
<div id="commentUserModal" class="admin-modal" hidden>
    <div class="admin-modal-panel">
        <button type="button" class="admin-modal-close" onclick="closeCommentUserModal()">×</button>
        <h2 id="commentUserModalTitle">Actividad</h2>
        <div class="admin-modal-tabs">
            <button type="button" class="active" onclick="showCommentUserTab('reviews')">Reseñas</button>
            <button type="button" onclick="showCommentUserTab('responses')">Comentarios</button>
        </div>
        <div id="commentUserReviews" class="admin-list"></div>
        <div id="commentUserResponses" class="admin-list" hidden></div>
    </div>
</div>
<script>
const commentUsers = <?= json_encode($commentUserPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function escapeAdminHtml(value) {
    const div = document.createElement('div');
    div.textContent = value ?? '';
    return div.innerHTML;
}

function openCommentUserModal(userId) {
    const user = commentUsers[userId];
    if (!user) return;

    document.getElementById('commentUserModalTitle').textContent = `${user.username} · ${user.name}`;
    renderCommentItems('commentUserReviews', user.reviews, 'delete_review', 'review_id', 'Eliminar reseña');
    renderCommentItems('commentUserResponses', user.responses, 'delete_response', 'response_id', 'Eliminar comentario');
    showCommentUserTab('reviews');
    document.getElementById('commentUserModal').hidden = false;
}

function closeCommentUserModal() {
    document.getElementById('commentUserModal').hidden = true;
}

function showCommentUserTab(tab) {
    document.getElementById('commentUserReviews').hidden = tab !== 'reviews';
    document.getElementById('commentUserResponses').hidden = tab !== 'responses';
    document.querySelectorAll('.admin-modal-tabs button').forEach(button => {
        button.classList.toggle('active', button.textContent.toLowerCase().startsWith(tab === 'reviews' ? 'rese' : 'com'));
    });
}

function renderCommentItems(targetId, items, action, idName, label) {
    const target = document.getElementById(targetId);
    if (!items.length) {
        target.innerHTML = '<p class="admin-empty">No hay actividad en esta pestaña.</p>';
        return;
    }

    target.innerHTML = items.map(item => `
        <form method="post" class="admin-row">
            <input type="hidden" name="action" value="${action}">
            <input type="hidden" name="${idName}" value="${Number(item.id)}">
            <span><strong>${escapeAdminHtml(item.movie_title || 'Película eliminada')}</strong>: ${escapeAdminHtml(item.comment || '')}</span>
            <button class="danger-btn" onclick="return confirm('${label}?')">${label}</button>
        </form>
    `).join('');
}
</script>
</body>
</html>
