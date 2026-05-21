<?php
session_start();
header('Content-Type: application/json');

$data = json_decode(file_get_contents("php://input"), true);

if (!empty($data['credential'])) {

    // Decodificar payload JWT (parte del medio)
    $parts = explode(".", $data['credential']);
    $payload = json_decode(base64_decode($parts[1]), true);

    $name = $payload['name'] ?? 'Usuario';
    $email = $payload['email'] ?? '';

    $_SESSION['user'] = [
        'name' => $name,
        'email' => $email
    ];

    $_SESSION['token'] = $data['credential'];

    echo json_encode([
        'success' => true,
        'user' => $_SESSION['user']
    ]);
    exit;
}

echo json_encode([
    'success' => false
]);