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

    public function getById($id)
    {
        return Movie::find($id);
    }

    public function createMovie($userId, $title, $genre, $year, $description, $movieAuthor = '', bool $autoApproved = false)
    {
        $title = trim((string) $title);
        $genre = trim((string) $genre);
        $description = trim((string) $description);
        $movieAuthor = trim((string) $movieAuthor);
        $year = (int) $year;
        $allowedGenres = ['Accion', 'Aventura', 'Animacion', 'Comedia', 'Crimen', 'Documental', 'Drama', 'Fantasia', 'Terror', 'Misterio', 'Romance', 'Ciencia ficcion', 'Thriller'];

        if ($title === '') {
            return ['error' => 'El titulo es obligatorio'];
        }
        if (!in_array($genre, $allowedGenres, true)) {
            return ['error' => 'El genero no es valido'];
        }
        if ($description === '') {
            return ['error' => 'La descripcion es obligatoria'];
        }
        if ($movieAuthor === '') {
            return ['error' => 'El autor de la pelicula es obligatorio'];
        }
        if ($year < 1800 || $year > (int) date('Y') + 1) {
            return ['error' => 'El año de la pelicula no es valido'];
        }

        $duplicate = $this->pdo->prepare("SELECT id FROM movies WHERE LOWER(title) = LOWER(?) AND year = ? AND approval_status <> 'rejected' LIMIT 1");
        $duplicate->execute([$title, $year]);
        if ($duplicate->fetch(PDO::FETCH_ASSOC)) {
            return ['error' => 'Esta pelicula ya existe'];
        }

        $status = $autoApproved ? 'approved' : 'pending';
        $stmt = $this->pdo->prepare(
            "INSERT INTO movies (title, genre, featured, description, year, movie_author, approval_status, user_id)
             VALUES (?, ?, 0, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([$title, $genre, $description, $year, $movieAuthor, $status, (int) $userId]);

        return ['ok' => true, 'movie_id' => (int) $this->pdo->lastInsertId(), 'approval_status' => $status];
    }

    public function getReviewers($limit = 10)
    {
        $limit = max(1, (int) $limit);
        $stmt = $this->pdo->prepare("
            SELECT ranked.user_id AS id, ranked.name, SUM(ranked.reputation) AS reputation
            FROM (
                SELECT u.id AS user_id, u.name, m.genre, FLOOR(COUNT(rl.id) / 10) AS reputation
                FROM review_likes rl
                JOIN reviews r ON r.id = rl.review_id
                JOIN users u ON u.id = r.user_id
                JOIN movies m ON m.id = r.movie_id
                GROUP BY u.id, u.name, m.genre
                HAVING reputation >= 1
            ) ranked
            GROUP BY ranked.user_id, ranked.name
            ORDER BY reputation DESC, ranked.name ASC
            LIMIT {$limit}
        ");
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function register($name, $email, $password)
    {
        $name = trim((string) $name);
        $email = trim((string) $email);
        if ($name === '' || $email === '' || strlen((string) $password) < 6) {
            return ['error' => 'Datos invalidos'];
        }
        $check = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            return ['error' => 'El email ya esta registrado'];
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
        $stmt->execute([trim((string) $email)]);
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
            "SELECT u.id, u.name, u.username, u.email, u.role
             FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token = ? AND t.expires_at > NOW()"
        );
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    public function getGenreReputation($userId)
    {
        $stmt = $this->pdo->prepare("
            SELECT m.genre, FLOOR(COUNT(rl.id) / 10) AS points
            FROM review_likes rl
            JOIN reviews r ON r.id = rl.review_id
            JOIN movies m ON m.id = r.movie_id
            WHERE r.user_id = ?
            GROUP BY m.genre
            HAVING points >= 1
            ORDER BY points DESC, m.genre ASC
        ");
        $stmt->execute([(int) $userId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewsForMovie($movieId, string $sort = 'recent')
    {
        $order = "r.created_at DESC, r.id DESC";
        if ($sort === 'oldest') {
            $order = "r.created_at ASC, r.id ASC";
        } elseif ($sort === 'rating') {
            $order = "r.rating DESC, r.created_at DESC, r.id DESC";
        }

        $stmt = $this->pdo->prepare(
            "SELECT r.id, r.user_id, r.rating, r.comment, r.created_at, u.name AS user_name,
                    COUNT(DISTINCT rr.id) AS responses_count,
                    COUNT(DISTINCT rl.id) AS hearts_count,
                    CASE WHEN m.user_id IS NOT NULL AND r.user_id = m.user_id THEN 1 ELSE 0 END AS is_author_review
             FROM reviews r
             JOIN users u ON u.id = r.user_id
             JOIN movies m ON m.id = r.movie_id
             LEFT JOIN review_responses rr ON rr.review_id = r.id
             LEFT JOIN review_likes rl ON rl.review_id = r.id
             WHERE r.movie_id = ?
             GROUP BY r.id, r.user_id, r.rating, r.comment, r.created_at, u.name, m.user_id
             ORDER BY is_author_review DESC, {$order}"
        );
        $stmt->execute([(int) $movieId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getReviewResponses($reviewId)
    {
        $stmt = $this->pdo->prepare(
            "SELECT rr.id, rr.comment, rr.created_at, u.name AS user_name
             FROM review_responses rr
             JOIN users u ON u.id = rr.user_id
             WHERE rr.review_id = ?
             ORDER BY rr.created_at ASC"
        );
        $stmt->execute([(int) $reviewId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function addReviewResponse($userId, $reviewId, $rating, $comment)
    {
        $comment = trim((string) $comment);
        if ($comment === '') {
            return ['error' => 'La respuesta no puede estar vacia'];
        }

        $stmt = $this->pdo->prepare("SELECT user_id FROM reviews WHERE id = ?");
        $stmt->execute([(int) $reviewId]);
        $review = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$review) {
            return ['error' => 'Reseña no encontrada'];
        }
        if ((int) $review['user_id'] === (int) $userId) {
            return ['error' => 'No puedes responder tu propia reseña'];
        }

        $this->pdo->prepare(
            "INSERT INTO review_responses (review_id, user_id, rating, comment) VALUES (?, ?, NULL, ?)"
        )->execute([(int) $reviewId, (int) $userId, $comment]);

        return ['ok' => true];
    }

    public function addReview($userId, $movieId, $rating, $comment)
    {
        $rating = (int) $rating;
        $comment = trim((string) $comment);

        if ($rating < 1 || $rating > 5) {
            return ['error' => 'La calificacion debe ser de 1 a 5'];
        }
        if ($comment === '') {
            return ['error' => 'La reseña no puede estar vacia'];
        }

        $movie = Movie::find($movieId);
        if (!$movie) {
            return ['error' => 'Pelicula no encontrada'];
        }

        $countStmt = $this->pdo->prepare("SELECT COUNT(*) FROM reviews WHERE user_id = ? AND movie_id = ?");
        $countStmt->execute([(int) $userId, (int) $movieId]);
        if ((int) $countStmt->fetchColumn() >= 3) {
            return ['error' => 'Ya alcanzaste el maximo de 3 reseñas para esta pelicula'];
        }

        $this->pdo->prepare("INSERT INTO reviews (user_id, movie_id, rating, comment) VALUES (?, ?, ?, ?)")
            ->execute([(int) $userId, (int) $movieId, $rating, $comment]);

        return ['ok' => true, 'genre' => $movie['genre'] ?: 'General'];
    }

    public function toggleReviewHeart($userId, $reviewId)
    {
        $stmt = $this->pdo->prepare("SELECT id FROM reviews WHERE id = ? LIMIT 1");
        $stmt->execute([(int) $reviewId]);
        if (!$stmt->fetchColumn()) {
            return ['error' => 'Reseña no encontrada'];
        }

        $exists = $this->pdo->prepare("SELECT id FROM review_likes WHERE review_id = ? AND user_id = ? LIMIT 1");
        $exists->execute([(int) $reviewId, (int) $userId]);
        $likeId = (int) $exists->fetchColumn();

        if ($likeId) {
            $this->pdo->prepare("DELETE FROM review_likes WHERE id = ?")->execute([$likeId]);
            $hearted = false;
        } else {
            $this->pdo->prepare("INSERT INTO review_likes (review_id, user_id) VALUES (?, ?)")
                ->execute([(int) $reviewId, (int) $userId]);
            $hearted = true;
        }

        $count = $this->pdo->prepare("SELECT COUNT(*) FROM review_likes WHERE review_id = ?");
        $count->execute([(int) $reviewId]);

        return ['ok' => true, 'hearted' => $hearted, 'hearts_count' => (int) $count->fetchColumn()];
    }

    public function getReputationRanking()
    {
        $stmt = $this->pdo->query("
            SELECT u.id AS user_id, u.name, m.genre, FLOOR(COUNT(rl.id) / 10) AS reputation
            FROM review_likes rl
            JOIN reviews r ON r.id = rl.review_id
            JOIN users u ON u.id = r.user_id
            JOIN movies m ON m.id = r.movie_id
            GROUP BY u.id, u.name, m.genre
            HAVING reputation >= 1
            ORDER BY reputation DESC, u.name ASC
        ");

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
