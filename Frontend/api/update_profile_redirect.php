<?php

session_start();

require_once __DIR__ . '/../../Backend/models/Database.php';

$redirect = '/proyecto7mo/Frontend/user.php';

if (empty($_SESSION['user']['id'])) {
    header('Location: /proyecto7mo/Frontend/login.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . $redirect);
    exit;
}

$name = trim((string) ($_POST['name'] ?? ''));
$username = trim((string) ($_POST['username'] ?? ''));
$userId = (int) $_SESSION['user']['id'];

try {
    if (!preg_match('/^[\p{L}\p{N} ]{1,40}$/u', $name)) {
        header('Location: ' . $redirect . '?profile_error=' . urlencode('El nombre no es valido.'));
        exit;
    }

    if (!preg_match('/^[A-Za-z0-9]{1,20}$/', $username)) {
        header('Location: ' . $redirect . '?profile_error=' . urlencode('El nombre de usuario no es valido.'));
        exit;
    }

    $db = Database::getInstance()->getConnection();

    $stmt = $db->prepare("SELECT id FROM users WHERE username = ? AND id <> ? LIMIT 1");
    $stmt->execute([$username, $userId]);
    if ($stmt->fetch(PDO::FETCH_ASSOC)) {
        header('Location: ' . $redirect . '?profile_error=' . urlencode('El nombre de usuario ya esta en uso.'));
        exit;
    }

    $stmt = $db->prepare("UPDATE users SET name = ?, username = ? WHERE id = ?");
    $stmt->execute([$name, $username, $userId]);

    $reviewerUserIdColumn = $db->query("SHOW COLUMNS FROM reviewers LIKE 'user_id'")->fetch(PDO::FETCH_ASSOC);
    if ($reviewerUserIdColumn) {
        $stmt = $db->prepare("UPDATE reviewers SET name = ? WHERE user_id = ?");
        $stmt->execute([$name, $userId]);
    }

    $_SESSION['user']['name'] = $name;
    $_SESSION['user']['username'] = $username;

    header('Location: ' . $redirect . '?profile_updated=1');
    exit;
} catch (Throwable $e) {
    header('Location: ' . $redirect . '?profile_error=' . urlencode('No se pudo actualizar el perfil.'));
    exit;
}
