<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $data = json_decode(file_get_contents("php://input"), true);

    if (!empty($data['credential'])) {

        $_SESSION['google_user'] = $data['credential'];

        echo json_encode([
            'success' => true
        ]);

        exit;
    }
}

echo json_encode([
    'success' => false
]);