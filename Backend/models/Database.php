<?php

class Database
{
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $cfg = require __DIR__ . '/../config/config.php';
        $dsn = "mysql:host={$cfg['host']};dbname={$cfg['dbname']};charset={$cfg['charset']}";
        $this->pdo = new PDO($dsn, $cfg['user'], $cfg['pass']);
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->ensureSchema();
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    private function ensureSchema()
    {
        $this->pdo->exec("CREATE TABLE IF NOT EXISTS movies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255) NOT NULL,
            genre VARCHAR(100),
            featured TINYINT(1) DEFAULT 0,
            description TEXT,
            year INT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS reviewers (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NULL,
            name VARCHAR(255) NOT NULL,
            reputation INT DEFAULT 0
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS reviews (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            movie_id INT NOT NULL,
            rating TINYINT NOT NULL,
            comment TEXT,
            UNIQUE KEY uk_user_movie (user_id, movie_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS genre_reputation (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            genre VARCHAR(100) NOT NULL,
            points INT DEFAULT 0,
            UNIQUE KEY uk_user_genre (user_id, genre)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS api_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        if ((int) $this->pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn() === 0) {
            $s = $this->pdo->prepare("INSERT INTO movies (title, genre, featured, description, year) VALUES (?, ?, ?, ?, ?)");
            $s->execute(['El Gran Viaje', 'Aventura', 1, 'Una épica aventura entre mundos.', 2021]);
            $s->execute(['Amor en París', 'Romance', 0, 'Drama romántico en París.', 2019]);
            $s->execute(['Risa Mortal', 'Comedia', 1, 'Comedia negra sobre la fama.', 2022]);
        }

        if ((int) $this->pdo->query("SELECT COUNT(*) FROM reviewers")->fetchColumn() === 0) {
            $s = $this->pdo->prepare("INSERT INTO reviewers (user_id, name, reputation) VALUES (NULL, ?, ?)");
            $s->execute(['Carlos Pérez', 120]);
            $s->execute(['Ana Gómez', 95]);
            $s->execute(['María López', 80]);
        }
    }
}
