<?php
// Simple API router for Backend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../controllers/MoviesController.php';

$route = isset($_GET['route']) ? $_GET['route'] : '';
$parts = explode('/', trim($route, '/'));

$movies = new MoviesController();

if ($parts[0] === '' || $parts[0] === null) {
    echo json_encode(['status' => 'ok', 'message' => 'API disponible']);
    exit;
}

if ($parts[0] === 'movies') {
    if (!isset($parts[1]) || $parts[1] === '') {
        $movies->index();
        exit;
    }
    switch ($parts[1]) {
        case 'featured':
            $movies->featured();
            break;
        case 'genres':
            $movies->genres();
            break;
        case 'genre':
            if (isset($parts[2])) {
                $movies->byGenre(urldecode($parts[2]));
            } else {
                http_response_code(400);
                echo json_encode(['error' => 'Falta nombre de género']);
            }
            break;
        default:
            http_response_code(404);
            echo json_encode(['error' => 'Endpoint no encontrado']);
            break;
    }
    exit;
}

http_response_code(404);
echo json_encode(['error' => 'Recurso no encontrado']);
