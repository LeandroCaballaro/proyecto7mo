<?php
session_start();
header('Content-Type: application/json');

define('API_URL', 'http://localhost/proyecto7mo/Backend/public/api.php');

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['credential'])) {

    // Decodificar payload JWT (parte del medio)
    $parts = explode(".", $data['credential']);
    $payload = json_decode(base64_decode($parts[1]), true);

    $name = $payload['name'] ?? 'Usuario';
    $email = $payload['email'] ?? '';

    // Enviar petición al backend para autenticar/registrar
    $ctx = stream_context_create([
        'http' => [
            'method'        => 'POST',
            'header'        => "Content-Type: application/json\r\n",
            'content'       => json_encode(['name' => $name, 'email' => $email]),
            'ignore_errors' => true,
        ],
    ]);
    $raw = @file_get_contents(API_URL . '?route=auth/google', false, $ctx);
    $res = $raw ? json_decode($raw, true) : null;

    if ($res && !empty($res['token'])) {
        $_SESSION['token'] = $res['token'];
        $_SESSION['user'] = $res['user'];

        echo json_encode([
            'success' => true,
            'user'    => $_SESSION['user']
        ]);
        exit;
    }
}

echo json_encode([
    'success' => false
]);