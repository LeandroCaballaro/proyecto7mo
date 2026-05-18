<?php
require_once __DIR__ . '/../services/MovieService.php';

class MoviesController {
    private $service;

    public function __construct()
    {
        $this->service = new MovieService();
    }

    public function index()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->service->getAll());
    }

    public function featured()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->service->getFeatured());
    }

    public function genres()
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->service->getGenres());
    }

    public function byGenre($genre)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($this->service->getByGenre($genre));
    }
}
