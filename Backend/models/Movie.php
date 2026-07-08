<?php
require_once __DIR__ . '/Database.php';

class Movie {
    private static function selectWithAuthor(): string
    {
        return "SELECT m.*, u.name AS author_name,
                COALESCE(AVG(r.rating), 0) AS average_rating,
                COUNT(r.id) AS reviews_count
                FROM movies m
                LEFT JOIN users u ON u.id = m.user_id
                LEFT JOIN reviews r ON r.movie_id = m.id";
    }

    private static function groupByMovie(): string
    {
        return " GROUP BY m.id, u.name";
    }

    public static function all()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query(self::selectWithAuthor() . " WHERE m.approval_status = 'approved'" . self::groupByMovie() . " ORDER BY m.genre ASC, m.year DESC, m.title ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function find($id)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(self::selectWithAuthor() . " WHERE m.id = ? AND m.approval_status = 'approved'" . self::groupByMovie());
        $stmt->execute([(int) $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public static function featured()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query(self::selectWithAuthor() . " WHERE m.approval_status = 'approved'" . self::groupByMovie() . " ORDER BY average_rating DESC, reviews_count DESC, m.year DESC, m.title ASC LIMIT 10");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function byGenre($genre)
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->prepare(self::selectWithAuthor() . " WHERE m.genre = ? AND m.approval_status = 'approved'" . self::groupByMovie() . " ORDER BY m.year DESC, m.title ASC");
        $stmt->execute([$genre]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public static function genres()
    {
        $pdo = Database::getInstance()->getConnection();
        $stmt = $pdo->query("SELECT DISTINCT genre FROM movies WHERE approval_status = 'approved' ORDER BY genre ASC");
        return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'genre');
    }
}
