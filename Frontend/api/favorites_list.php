<?php
session_start();

require_once __DIR__ . '/../../Backend/models/Database.php';

function favorites_json(array $payload, int $statusCode = 200): void
{
    http_response_code($statusCode);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function favorite_cover_url(int $movieId, ?string $posterUrl = null): string
{
    foreach (['png', 'jpg', 'jpeg', 'webp', 'gif', 'svg'] as $ext) {
        $file = __DIR__ . '/../public/covers/' . $movieId . '.' . $ext;
        if (is_file($file)) {
            return '/proyecto7mo/Frontend/public/covers/' . $movieId . '.' . $ext;
        }
    }

    $posterUrl = trim((string) $posterUrl);
    if ($posterUrl !== '') {
        return $posterUrl;
    }

    return 'data:image/svg+xml;utf8,<svg xmlns="http://www.w3.org/2000/svg" width="240" height="360" viewBox="0 0 240 360"><rect width="240" height="360" fill="%231a1a2e"/><circle cx="120" cy="150" r="42" fill="%23e94560" opacity="0.35"/><text x="120" y="230" font-family="Arial,sans-serif" font-size="20" fill="%23f5f5f5" text-anchor="middle">NexoHub</text></svg>';
}

$userId = (int) ($_SESSION['user']['id'] ?? 0);
if (!$userId) {
    favorites_json(['success' => false, 'message' => 'No autorizado'], 401);
}

try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->prepare("
        SELECT m.id AS movie_id, m.title, m.poster_url
        FROM favorite_movies fm
        INNER JOIN movies m ON fm.movie_id = m.id
        WHERE fm.user_id = ?
        ORDER BY fm.created_at DESC, m.title ASC
    ");
    $stmt->execute([$userId]);
    $favorites = array_map(function (array $movie): array {
        $movieId = (int) $movie['movie_id'];
        return [
            'movie_id' => $movieId,
            'title' => $movie['title'] ?? 'Pelicula',
            'poster' => favorite_cover_url($movieId, $movie['poster_url'] ?? null),
        ];
    }, $stmt->fetchAll(PDO::FETCH_ASSOC));

    favorites_json(['success' => true, 'favorites' => $favorites]);
} catch (Exception $e) {
    favorites_json(['success' => false, 'message' => 'No se pudieron cargar los favoritos'], 500);
}
