<?php
session_start();

require_once __DIR__ . '/../../Backend/models/Database.php';

function favorite_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function ensure_favorites_table_for_endpoint(PDO $db): void
{
    $db->exec("
        CREATE TABLE IF NOT EXISTS favorite_movies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            movie_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_movie_favorite (user_id, movie_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    favorite_json(['success' => false, 'message' => 'Metodo no permitido'], 405);
}

$userId = (int) ($_SESSION['user']['id'] ?? 0);
$movieId = (int) ($_POST['movie_id'] ?? 0);

if (!$userId) {
    favorite_json(['success' => false, 'message' => 'Debes iniciar sesion para guardar favoritos'], 401);
}

if (!$movieId) {
    favorite_json(['success' => false, 'message' => 'Pelicula invalida'], 400);
}

try {
    $db = Database::getInstance()->getConnection();
    ensure_favorites_table_for_endpoint($db);

    $movieExists = $db->prepare("SELECT id FROM movies WHERE id = ? LIMIT 1");
    $movieExists->execute([$movieId]);
    if (!$movieExists->fetchColumn()) {
        favorite_json(['success' => false, 'message' => 'La pelicula no existe'], 404);
    }

    $favorite = $db->prepare("SELECT id FROM favorite_movies WHERE user_id = ? AND movie_id = ? LIMIT 1");
    $favorite->execute([$userId, $movieId]);

    if ($favorite->fetch(PDO::FETCH_ASSOC)) {
        $db->prepare("DELETE FROM favorite_movies WHERE user_id = ? AND movie_id = ?")
            ->execute([$userId, $movieId]);
        favorite_json(['success' => true, 'favorite' => false]);
    }

    $db->prepare("INSERT INTO favorite_movies (user_id, movie_id) VALUES (?, ?)")
        ->execute([$userId, $movieId]);
    favorite_json(['success' => true, 'favorite' => true]);
} catch (Exception $e) {
    favorite_json(['success' => false, 'message' => 'No se pudo guardar el favorito'], 500);
}
