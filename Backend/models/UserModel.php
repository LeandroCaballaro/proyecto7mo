<?php

require_once __DIR__ . '/Database.php';

class UserModel
{
    private $pdo;

    public function __construct()
    {
        $this->pdo = Database::getInstance()->getConnection();
    }

    /**
     * Busca un usuario por email.
     * Retorna el array con todos los campos (incluyendo password_hash) o false.
     */
    public function findByEmail(string $email)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, username, email, password_hash FROM users WHERE email = ? LIMIT 1"
        );
        $stmt->execute([trim($email)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findByUsername(string $username)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, username, email FROM users WHERE username = ? LIMIT 1"
        );
        $stmt->execute([trim($username)]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Busca un usuario por ID.
     */
    public function findById(int $id)
    {
        $stmt = $this->pdo->prepare(
            "SELECT id, name, username, email FROM users WHERE id = ? LIMIT 1"
        );
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Crea un nuevo usuario. Retorna el ID insertado.
     */
    public function create(string $name, string $username, string $email, string $passwordHash): int
    {
        $stmt = $this->pdo->prepare(
            "INSERT INTO users (name, username, email, password_hash) VALUES (?, ?, ?, ?)"
        );
        $stmt->execute([$name, $username, $email, $passwordHash]);
        return (int) $this->pdo->lastInsertId();
    }

    /**
     * Crea un token de sesión para el usuario.
     */
    public function createToken(int $userId, string $token, string $expiresAt): void
    {
        $this->pdo->prepare(
            "INSERT INTO api_tokens (user_id, token, expires_at) VALUES (?, ?, ?)"
        )->execute([$userId, $token, $expiresAt]);
    }

    /**
     * Busca usuario a partir de un token Bearer válido (no expirado).
     */
    public function findByToken(string $token)
    {
        $stmt = $this->pdo->prepare(
            "SELECT u.id, u.name, u.username, u.email
             FROM api_tokens t
             JOIN users u ON u.id = t.user_id
             WHERE t.token = ? AND t.expires_at > NOW()
             LIMIT 1"
        );
        $stmt->execute([$token]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
    }

    /**
     * Elimina todos los tokens vencidos (limpieza opcional).
     */
    public function purgeExpiredTokens(): void
    {
        $this->pdo->exec("DELETE FROM api_tokens WHERE expires_at <= NOW()");
    }

    /**
     * Elimina un token específico (logout).
     */
    public function deleteToken(string $token): void
    {
        $this->pdo->prepare("DELETE FROM api_tokens WHERE token = ?")
            ->execute([$token]);
    }

    /**
     * Agrega al usuario como reviewer si no existe ya.
     */
    public function ensureReviewer(int $userId, string $name): void
    {
        $check = $this->pdo->prepare("SELECT id FROM reviewers WHERE user_id = ? LIMIT 1");
        $check->execute([$userId]);
        if (!$check->fetch()) {
            $this->pdo->prepare(
                "INSERT INTO reviewers (user_id, name, reputation) VALUES (?, ?, 0)"
            )->execute([$userId, $name]);
        }
    }

    public function updateProfile(int $userId, string $name, string $username): void
    {
        $this->pdo->prepare(
            "UPDATE users SET name = ?, username = ? WHERE id = ?"
        )->execute([$name, $username, $userId]);

        $this->pdo->prepare(
            "UPDATE reviewers SET name = ? WHERE user_id = ?"
        )->execute([$name, $userId]);
    }
}
