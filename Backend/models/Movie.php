<?php
require_once __DIR__ . '/Database.php';

class Movie {
    private static function selectWithAuthor(): string
    {
        return "SELECT m.*, u.name AS author_name FROM movies m LEFT JOIN users u ON u.id = m.user_id";
    }

    public static function all()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query(self::selectWithAuthor() . " ORDER BY m.year DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(self::selectWithAuthor() . " WHERE m.id = ?");
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function featured()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query(self::selectWithAuthor() . " WHERE m.featured = 1 ORDER BY m.year DESC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function byGenre($genre)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(self::selectWithAuthor() . " WHERE m.genre = ? ORDER BY m.year DESC");
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
