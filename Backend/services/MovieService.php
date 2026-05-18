<?php
require_once __DIR__ . '/../models/Movie.php';

class MovieService {
    public function getAll()
    {
        return Movie::all();
    }

    public function getFeatured()
    {
        return Movie::featured();
    }

    public function getByGenre($genre)
    {
        return Movie::byGenre($genre);
    }

    public function getGenres()
    {
        return Movie::genres();
    }
}
