<?php

require_once __DIR__ . '/../models/Movie.php';
require_once __DIR__ . '/../models/Database.php';

class MovieService
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

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

    public function getReviewers($limit = 10)
    {
        $limit = (int) $limit;
        $stmt = $this->pdo->prepare("SELECT id, name, reputation FROM reviewers ORDER BY reputation DESC LIMIT $limit");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function register($name, $email, $password)
    {
        $name = trim($name);
        $email = trim($email);
        if ($name === '' || $email === '' || strlen($password) < 6) {
            return ['error' => 'Datos inválidos (contraseña mínimo 6 caracteres)'];
        }
        $check = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            return ['error' => 'El email ya está registrado'];
        }

        $hash = password_hash($password, PASSWORD_DEFAULT);
        $this->pdo->prepare("INSERT INTO users (name, email, password_hash) VALUES (?, ?, ?)")
            ->execute([$name, $email, $hash]);
        $userId = (int) $this->pdo->lastInsertId();
        $this->pdo->prepare("INSERT INTO reviewers (user_id, name, reputation) VALUES (?, ?, 0)")
            ->execute([$userId, $name]);

        return $this->makeSession($userId, $name, $email);
    }

    public function login($email, $password)
    {
        $stmt = $this->pdo->prepare("SELECT id, name, email, password_hash FROM users WHERE email = ?");
        $stmt->execute([trim($email)]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['error' => 'Credenciales incorrectas'];
        }
        return $this->makeSession((int) $user['id'], $user['name'], $user['email']);
    }

    private function makeSession($userId, $name, $email)
    {
        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', strtotime('+7 days'));
        $this->pdo->prepare("INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)")
            ->execute([$userId, $token, $expires]);
        return [
            'user' => ['id' => $userId, 'name' => $name, 'email' => $email],
            'token' => $token,
        ];
    }

    public function userFromToken($token)
    {
        if (!$token) {
            return null;
        }
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.name, u.email FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token = ? AND t.expires_at > NOW()"
        );
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getGenreReputation($userId)
    {
        $stmt = $this->pdo->prepare("SELECT genre, points FROM genre_reputation WHERE user_id = ? ORDER BY points DESC");
        $stmt->execute([(int) $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewsForMovie($movieId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT r.rating, r.comment, u.name AS user_name FROM reviews r
             JOIN users u ON u.id = r.user_id WHERE r.movie_id = ? ORDER BY r.id DESC"
        );
        $stmt->execute([(int) $movieId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addReview($userId, $movieId, $rating, $comment)
    {
        $rating = (int) $rating;
        if ($rating < 1 || $rating > 5) {
            return ['error' => 'La calificación debe ser de 1 a 5'];
        }

        $movie = Movie::find($movieId);
        if (!$movie) {
            return ['error' => 'Película no encontrada'];
        }

        try {
            $this->pdo->prepare("INSERT INTO reviews (user_id, movie_id, rating, comment) VALUES (?, ?, ?, ?)")
                ->execute([(int) $userId, (int) $movieId, $rating, $comment]);
        } catch (PDOException $e) {
            return ['error' => 'Ya reseñaste esta película'];
        }

        $points = $rating * 2;
        $genre = $movie['genre'] ?: 'General';
        $this->pdo->prepare(
            "INSERT INTO genre_reputation (user_id, genre, points) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE points = points + ?"
        )->execute([(int) $userId, $genre, $points, $points]);

        $this->pdo->prepare("UPDATE reviewers SET reputation = reputation + ? WHERE user_id = ?")
            ->execute([$points, (int) $userId]);

        return ['ok' => true, 'points' => $points, 'genre' => $genre];
    }
}
