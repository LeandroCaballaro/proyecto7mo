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
            year INT,
            movie_author VARCHAR(255) NULL,
            external_source VARCHAR(50) NULL,
            external_id VARCHAR(100) NULL,
            poster_url VARCHAR(500) NULL,
            approval_status VARCHAR(20) NOT NULL DEFAULT 'approved',
            rejection_reason TEXT NULL,
            user_id INT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $movieColumns = [
            'movie_author' => "ALTER TABLE movies ADD COLUMN movie_author VARCHAR(255) NULL AFTER year",
            'external_source' => "ALTER TABLE movies ADD COLUMN external_source VARCHAR(50) NULL AFTER movie_author",
            'external_id' => "ALTER TABLE movies ADD COLUMN external_id VARCHAR(100) NULL AFTER external_source",
            'poster_url' => "ALTER TABLE movies ADD COLUMN poster_url VARCHAR(500) NULL AFTER external_id",
            'approval_status' => "ALTER TABLE movies ADD COLUMN approval_status VARCHAR(20) NOT NULL DEFAULT 'approved' AFTER poster_url",
            'rejection_reason' => "ALTER TABLE movies ADD COLUMN rejection_reason TEXT NULL AFTER approval_status",
            'user_id' => "ALTER TABLE movies ADD COLUMN user_id INT NULL AFTER rejection_reason",
        ];
        foreach ($movieColumns as $column => $sql) {
            $exists = $this->pdo->query("SHOW COLUMNS FROM movies LIKE " . $this->pdo->quote($column))->fetch(PDO::FETCH_ASSOC);
            if (!$exists) {
                $this->pdo->exec($sql);
            }
        }

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255) NOT NULL,
            username VARCHAR(20) NULL UNIQUE,
            email VARCHAR(255) NOT NULL UNIQUE,
            password_hash VARCHAR(255) NOT NULL,
            description VARCHAR(100) NULL,
            profile_image VARCHAR(255) NULL,
            is_public TINYINT(1) NOT NULL DEFAULT 1,
            role VARCHAR(20) NOT NULL DEFAULT 'user'
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $userColumns = [
            'username' => "ALTER TABLE users ADD COLUMN username VARCHAR(20) NULL UNIQUE AFTER name",
            'description' => "ALTER TABLE users ADD COLUMN description VARCHAR(100) NULL AFTER password_hash",
            'profile_image' => "ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NULL AFTER description",
            'is_public' => "ALTER TABLE users ADD COLUMN is_public TINYINT(1) NOT NULL DEFAULT 1 AFTER profile_image",
            'role' => "ALTER TABLE users ADD COLUMN role VARCHAR(20) NOT NULL DEFAULT 'user' AFTER is_public",
        ];
        foreach ($userColumns as $column => $sql) {
            $exists = $this->pdo->query("SHOW COLUMNS FROM users LIKE " . $this->pdo->quote($column))->fetch(PDO::FETCH_ASSOC);
            if (!$exists) {
                $this->pdo->exec($sql);
            }
        }

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS password_reset_tokens (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            token VARCHAR(64) NOT NULL UNIQUE,
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
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
            image_url VARCHAR(255) NULL,
            comment TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            KEY idx_reviews_user_movie (user_id, movie_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $reviewImageColumn = $this->pdo->query("SHOW COLUMNS FROM reviews LIKE 'image_url'")->fetch(PDO::FETCH_ASSOC);
        if (!$reviewImageColumn) {
            $this->pdo->exec("ALTER TABLE reviews ADD COLUMN image_url VARCHAR(255) NULL AFTER rating");
        }
        $reviewCreatedColumn = $this->pdo->query("SHOW COLUMNS FROM reviews LIKE 'created_at'")->fetch(PDO::FETCH_ASSOC);
        if (!$reviewCreatedColumn) {
            $this->pdo->exec("ALTER TABLE reviews ADD COLUMN created_at DATETIME DEFAULT CURRENT_TIMESTAMP AFTER comment");
        }
        $reviewUnique = $this->pdo->query("SHOW INDEX FROM reviews WHERE Key_name = 'uk_user_movie'")->fetch(PDO::FETCH_ASSOC);
        if ($reviewUnique) {
            $this->pdo->exec("ALTER TABLE reviews DROP INDEX uk_user_movie");
        }

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS favorite_movies (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            movie_id INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_user_movie_favorite (user_id, movie_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS review_responses (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            user_id INT NOT NULL,
            rating TINYINT NULL,
            comment TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
        $responseRatingColumn = $this->pdo->query("SHOW COLUMNS FROM review_responses LIKE 'rating'")->fetch(PDO::FETCH_ASSOC);
        if ($responseRatingColumn && strtoupper((string) ($responseRatingColumn['Null'] ?? '')) === 'NO') {
            $this->pdo->exec("ALTER TABLE review_responses MODIFY rating TINYINT NULL");
        }

        $this->pdo->exec("CREATE TABLE IF NOT EXISTS review_likes (
            id INT AUTO_INCREMENT PRIMARY KEY,
            review_id INT NOT NULL,
            user_id INT NOT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY uk_review_like (review_id, user_id),
            KEY idx_review_likes_user_id (user_id)
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

        if ((int) $this->pdo->query("SELECT COUNT(*) FROM reviewers")->fetchColumn() === 0) {
            $s = $this->pdo->prepare("INSERT INTO reviewers (user_id, name, reputation) VALUES (NULL, ?, ?)");
            $s->execute(['Carlos Perez', 120]);
            $s->execute(['Ana Gomez', 95]);
            $s->execute(['Maria Lopez', 80]);
        }

        if ((int) $this->pdo->query("SELECT COUNT(*) FROM users WHERE role = 'admin'")->fetchColumn() === 0) {
            $firstUserId = $this->pdo->query("SELECT id FROM users ORDER BY id ASC LIMIT 1")->fetchColumn();
            if ($firstUserId) {
                $this->pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([(int) $firstUserId]);
            }
        }
    }
}
