<?php

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

require_once __DIR__ . '/../controllers/AuthController.php';
require_once __DIR__ . '/../controllers/MoviesController.php';

$route = isset($_GET['route']) ? trim($_GET['route'], '/') : '';
$parts = $route === '' ? [] : explode('/', $route);
$method = $_SERVER['REQUEST_METHOD'];

if ($parts === []) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['status' => 'ok', 'message' => 'API disponible']);
    exit;
}

if ($parts[0] === 'auth') {
    $auth = new AuthController();
    $sub = $parts[1] ?? '';

    if ($sub === 'register' && $method === 'POST') {
        $auth->register();
    }

    if ($sub === 'login' && $method === 'POST') {
        $auth->login();
    }

    if ($sub === 'logout' && $method === 'POST') {
        $auth->logout();
    }

    if ($sub === 'me' && $method === 'GET') {
        $auth->me();
    }

    http_response_code(404);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => 'Ruta de auth no encontrada']);
    exit;
}

$api = new MoviesController();

if ($parts[0] === 'reviewers') {
    $api->reviewers();
}

if ($parts[0] === 'reviews') {
    if ($method === 'POST' && isset($parts[1], $parts[2]) && $parts[2] === 'responses' && is_numeric($parts[1])) {
        $api->addReviewResponse((int) $parts[1]);
    }
    if ($method === 'GET' && isset($parts[1], $parts[2]) && $parts[2] === 'responses' && is_numeric($parts[1])) {
        $api->reviewResponses((int) $parts[1]);
    }
    if ($method === 'POST' && count($parts) === 1) {
        $api->addReview();
    }
}

if ($parts[0] === 'movies') {
    if ($method === 'POST' && count($parts) === 1) {
        $api->createMovie();
    }
    if (isset($parts[1], $parts[2]) && $parts[2] === 'reviews' && is_numeric($parts[1])) {
        $api->movieReviews((int) $parts[1]);
    }
    if (isset($parts[1]) && is_numeric($parts[1]) && !isset($parts[2])) {
        $api->showMovie((int) $parts[1]);
    }
    if (!isset($parts[1]) || $parts[1] === '') {
        $api->index();
    }
    if (($parts[1] ?? '') === 'featured') {
        $api->featured();
    }
    if (($parts[1] ?? '') === 'genres') {
        $api->genres();
    }
    if (($parts[1] ?? '') === 'genre' && isset($parts[2])) {
        $api->byGenre(urldecode($parts[2]));
    }
}

http_response_code(404);
header('Content-Type: application/json; charset=utf-8');
echo json_encode(['error' => 'Recurso no encontrado']);
