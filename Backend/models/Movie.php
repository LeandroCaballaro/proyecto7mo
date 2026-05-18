<?php
require_once __DIR__ . '/Database.php';

class Movie {
    public static function all()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM movies ORDER BY year DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE id = ?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function featured()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT * FROM movies WHERE featured = 1 ORDER BY year DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function byGenre($genre)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare("SELECT * FROM movies WHERE genre = ? ORDER BY year DESC");
        $stmt->execute([$genre]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function genres()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT DISTINCT genre FROM movies");
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'genre');
    }
}
