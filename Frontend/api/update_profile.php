<?php

session_start();

header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../Backend/models/Database.php';

function respond_profile(array $payload): void
{
    echo json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

if (empty($_SESSION['user']['id'])) {
    respond_profile([
        'success' => false,
        'message' => 'Tu sesión expiró. Iniciá sesión nuevamente.',
        'redirect' => '/proyecto7mo/Frontend/login.php',
    ]);
}

$raw = file_get_contents('php://input') ?: '';
$jsonInput = json_decode($raw, true);
$input = is_array($jsonInput) ? $jsonInput : $_POST;
$action = $input['action'] ?? '';
$userId = (int) $_SESSION['user']['id'];

try {
    $db = Database::getInstance()->getConnection();

    if ($action === 'update_profile') {
        $name = trim((string) ($input['name'] ?? ''));
        $username = trim((string) ($input['username'] ?? ''));

        if (!preg_match('/^[\p{L}\p{N} ]{1,40}$/u', $name)) {
            respond_profile([
                'success' => false,
                'message' => 'El nombre solo puede tener letras, números y espacios. Máximo 40 caracteres.',
            ]);
        }

        if (!preg_match('/^[A-Za-z0-9]{1,20}$/', $username)) {
            respond_profile([
                'success' => false,
                'message' => 'El nombre de usuario solo puede tener letras y números. Máximo 20 caracteres.',
            ]);
        }

        $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
        $stmt->execute([$username, $userId]);
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            respond_profile([
                'success' => false,
                'message' => 'El nombre de usuario ya está en uso.',
            ]);
        }

        $stmt = $db->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
        $stmt->execute([$name, $username, $userId]);

        $stmt = $db->prepare("UPDATE reviewers SET name = ? WHERE user_id = ?");
        $stmt->execute([$name, $userId]);

        $_SESSION['user']['name'] = $name;
        $_SESSION['user']['username'] = $username;

        respond_profile([
            'success' => true,
            'name' => $name,
            'username' => $username,
        ]);
    }

    if (($input['field'] ?? '') === 'description') {
        $description = trim((string) ($input['value'] ?? ''));
        $stmt = $db->prepare("UPDATE users SET description = ? WHERE id = ?");
        $stmt->execute([$description, $userId]);
        respond_profile([
            'success' => true,
            'message' => 'Perfil actualizado correctamente',
        ]);
    }

    respond_profile([
        'success' => false,
        'message' => 'Acción no permitida.',
    ]);
} catch (Throwable $e) {
    respond_profile([
        'success' => false,
        'message' => 'No se pudo actualizar el perfil. Intentá de nuevo.',
    ]);
}
