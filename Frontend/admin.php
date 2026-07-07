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

if ($currentRole !== 'admin') {
    http_response_code(403);
    echo 'No autorizado';
    exit;
}

$message = '';
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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    try {
        if ($action === 'update_user') {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $name = trim($_POST['name'] ?? '');
            $username = trim($_POST['username'] ?? '');
            $email = trim($_POST['email'] ?? '');
            $role = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';
            $password = $_POST['password'] ?? '';

            if ($userId && $name !== '' && $username !== '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
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
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        $message = 'No se pudo completar la accion.';
    }
}

$users = $db->query("SELECT id, name, username, email, role, is_public FROM users ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$movies = $db->query("SELECT id, title, movie_author, genre, year, featured, description FROM movies ORDER BY title ASC")->fetchAll(PDO::FETCH_ASSOC);
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
    <title>Administracion - NexoHub</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style/styles.css">
    <link rel="stylesheet" href="style/admin.css">
</head>
<body class="admin-body">
<?php include 'header.php'; ?>
<main class="admin-main">
    <header class="admin-hero">
        <h1>Administracion</h1>
        <p>Gestiona usuarios, contraseñas, peliculas y comentarios.</p>
        <?php if ($message): ?><div class="admin-message"><?= htmlspecialchars($message) ?></div><?php endif; ?>
    </header>

    <section class="admin-section">
        <h2>Usuarios</h2>
        <div class="admin-grid">
            <?php foreach ($users as $item): ?>
                <form method="post" class="admin-card">
                    <input type="hidden" name="action" value="update_user">
                    <input type="hidden" name="user_id" value="<?= (int) $item['id'] ?>">
                    <label>Nombre <input name="name" value="<?= htmlspecialchars($item['name']) ?>" required></label>
                    <label>Usuario <input name="username" value="<?= htmlspecialchars($item['username'] ?? '') ?>" required></label>
                    <label>Email <input type="email" name="email" value="<?= htmlspecialchars($item['email']) ?>" required></label>
                    <label>Rol
                        <select name="role">
                            <option value="user" <?= $item['role'] === 'user' ? 'selected' : '' ?>>Usuario</option>
                            <option value="admin" <?= $item['role'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                        </select>
                    </label>
                    <label>Nueva contraseña <input type="password" name="password" placeholder="Dejar vacio para no cambiar"></label>
                    <button type="submit">Guardar usuario</button>
                    <?php if ((int) $item['id'] !== (int) $_SESSION['user']['id']): ?>
                        <button type="submit" name="action" value="delete_user" class="danger-btn" onclick="return confirm('Eliminar este usuario?')">Eliminar usuario</button>
                    <?php endif; ?>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-section">
        <h2>Peliculas</h2>
        <div class="admin-grid">
            <?php foreach ($movies as $movie): ?>
                <form method="post" class="admin-card">
                    <input type="hidden" name="action" value="update_movie">
                    <input type="hidden" name="movie_id" value="<?= (int) $movie['id'] ?>">
                    <label>Titulo <input name="title" value="<?= htmlspecialchars($movie['title']) ?>" required></label>
                    <label>Autor <input name="movie_author" value="<?= htmlspecialchars($movie['movie_author'] ?? '') ?>" required></label>
                    <label>Genero
                        <select name="genre">
                            <?php foreach ($genres as $genre): ?>
                                <option value="<?= htmlspecialchars($genre) ?>" <?= $movie['genre'] === $genre ? 'selected' : '' ?>><?= htmlspecialchars($genre) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Año <input type="number" name="year" value="<?= (int) $movie['year'] ?>" required></label>
                    <label>Descripcion <textarea name="description"><?= htmlspecialchars($movie['description'] ?? '') ?></textarea></label>
                    <label class="inline-check"><input type="checkbox" name="featured" <?= $movie['featured'] ? 'checked' : '' ?>> Destacada</label>
                    <button type="submit">Guardar pelicula</button>
                    <button type="submit" name="action" value="delete_movie" class="danger-btn" onclick="return confirm('Eliminar esta pelicula?')">Eliminar pelicula</button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>

    <section class="admin-section">
        <h2>Comentarios</h2>
        <div class="admin-list">
            <?php foreach ($reviews as $review): ?>
                <form method="post" class="admin-row">
                    <input type="hidden" name="action" value="delete_review">
                    <input type="hidden" name="review_id" value="<?= (int) $review['id'] ?>">
                    <span><strong><?= htmlspecialchars($review['user_name'] ?? 'Usuario eliminado') ?></strong> en <?= htmlspecialchars($review['movie_title'] ?? 'Pelicula eliminada') ?>: <?= htmlspecialchars($review['comment'] ?? '') ?></span>
                    <button class="danger-btn" onclick="return confirm('Eliminar reseña?')">Eliminar</button>
                </form>
            <?php endforeach; ?>
            <?php foreach ($responses as $response): ?>
                <form method="post" class="admin-row">
                    <input type="hidden" name="action" value="delete_response">
                    <input type="hidden" name="response_id" value="<?= (int) $response['id'] ?>">
                    <span><strong><?= htmlspecialchars($response['user_name'] ?? 'Usuario eliminado') ?></strong> respondio: <?= htmlspecialchars($response['comment'] ?? '') ?></span>
                    <button class="danger-btn" onclick="return confirm('Eliminar respuesta?')">Eliminar</button>
                </form>
            <?php endforeach; ?>
        </div>
    </section>
</main>
</body>
</html>
