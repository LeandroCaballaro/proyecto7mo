<?php

require_once __DIR__ . '/../services/MovieService.php';

class MoviesController
{
    private $service;

    public function __construct()
    {
        $this->service = new MovieService();
    }

    private function json($data, $code = 200)
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE);
        exit;
    }

    private function body()
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : ($_POST ?: []);
    }

    private function token()
    {
        $h = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/', $h, $m)) {
            return $m[1];
        }
        return null;
    }

    public function index()
    {
        $this->json($this->service->getAll());
    }

    public function featured()
    {
        $this->json($this->service->getFeatured());
    }

    public function genres()
    {
        $this->json($this->service->getGenres());
    }

    public function byGenre($genre)
    {
        $this->json($this->service->getByGenre($genre));
    }

    public function reviewers()
    {
        $limit = isset($_GET['limit']) ? (int) $_GET['limit'] : 10;
        $this->json($this->service->getReviewers($limit));
    }

    public function register()
    {
        $d = $this->body();
        $result = $this->service->register($d['name'] ?? '', $d['email'] ?? '', $d['password'] ?? '');
        if (isset($result['error'])) {
            $this->json($result, 400);
        }
        $this->json($result, 201);
    }

    public function login()
    {
        $d = $this->body();
        $result = $this->service->login($d['email'] ?? '', $d['password'] ?? '');
        if (isset($result['error'])) {
            $this->json($result, 401);
        }
        $this->json($result);
    }

    public function me()
    {
        $user = $this->service->userFromToken($this->token());
        if (!$user) {
            $this->json(['error' => 'No autorizado'], 401);
        }
        $this->json([
            'user' => $user,
            'genre_reputation' => $this->service->getGenreReputation($user['id']),
        ]);
    }

    public function movieReviews($movieId)
    {
        $this->json($this->service->getReviewsForMovie($movieId));
    }

    public function addReview()
    {
        $user = $this->service->userFromToken($this->token());
        if (!$user) {
            $this->json(['error' => 'No autorizado'], 401);
        }
        $d = $this->body();
        $result = $this->service->addReview(
            $user['id'],
            $d['movie_id'] ?? 0,
            $d['rating'] ?? 0,
            $d['comment'] ?? ''
        );
        if (isset($result['error'])) {
            $this->json($result, 400);
        }
        $this->json($result, 201);
    }
}
