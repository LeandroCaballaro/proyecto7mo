<?php

require_once __DIR__ . '/../models/UserModel.php';

class AuthService
{
    private UserModel $userModel;
    private int $tokenTtlDays = 7;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    private array $allowedEmailDomains = [
        'gmail.com',
        'hotmail.com',
        'live.com',
        'outlook.com',
        'yahoo.com',
        'icloud.com',
        'proton.me',
    ];

    public function register(string $name, string $username, string $email, string $password, string $confirmPassword = ''): array
    {
        $name = trim($name);
        $username = trim($username);
        $email = trim($email);

        $nameError = $this->validateDisplayName($name);
        if ($nameError) {
            return ['error' => $nameError];
        }

        $usernameError = $this->validateUsername($username);
        if ($usernameError) {
            return ['error' => $usernameError];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'El email no es valido'];
        }

        if (!$this->hasAllowedEmailDomain($email)) {
            return ['error' => 'Solo se permiten correos de dominios conocidos como Gmail, Hotmail, Live, Outlook, Yahoo, iCloud o Proton'];
        }

        if ($password !== $confirmPassword) {
            return ['error' => 'Las contrasenas no coinciden'];
        }

        if (!$this->isStrongPassword($password)) {
            return ['error' => 'La contrasena debe tener al menos 8 caracteres, una mayuscula, una minuscula y un numero'];
        }

        if ($this->userModel->findByEmail($email)) {
            return ['error' => 'El email ya esta registrado'];
        }

        if ($this->userModel->findByUsername($username)) {
            return ['error' => 'El nombre de usuario ya esta en uso'];
        }

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $userId = $this->userModel->create($name, $username, $email, $hash);
        $this->userModel->ensureReviewer($userId, $name);

        return $this->buildSession($userId, $name, $username, $email);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['error' => 'Credenciales incorrectas'];
        }

        return $this->buildSession((int) $user['id'], $user['name'], $user['username'] ?? '', $user['email']);
    }

    public function logout(?string $token): array
    {
        if (!$token) {
            return ['error' => 'No hay sesion activa'];
        }

        $this->userModel->deleteToken($token);
        return ['message' => 'Sesion cerrada correctamente'];
    }

    public function me(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        return $this->userModel->findByToken($token);
    }

    private function buildSession(int $userId, string $name, string $username, string $email): array
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->tokenTtlDays} days"));

        $this->userModel->createToken($userId, $token, $expiresAt);

        return [
            'user' => [
                'id' => $userId,
                'name' => $name,
                'username' => $username,
                'email' => $email,
            ],
            'token' => $token,
            'expires_at' => $expiresAt,
        ];
    }

    private function hasAllowedEmailDomain(string $email): bool
    {
        $domain = strtolower(substr(strrchr($email, '@') ?: '', 1));
        return in_array($domain, $this->allowedEmailDomains, true);
    }

    private function isStrongPassword(string $password): bool
    {
        return strlen($password) >= 8
            && preg_match('/[A-Z]/', $password)
            && preg_match('/[a-z]/', $password)
            && preg_match('/[0-9]/', $password);
    }

    private function validateDisplayName(string $name): ?string
    {
        if ($name === '') {
            return 'El nombre es obligatorio';
        }

        if (mb_strlen($name, 'UTF-8') > 40) {
            return 'El nombre no puede superar los 40 caracteres';
        }

        if (!preg_match('/^[\p{L}\p{N} ]+$/u', $name)) {
            return 'El nombre no puede tener caracteres especiales';
        }

        return null;
    }

    private function validateUsername(string $username): ?string
    {
        if ($username === '') {
            return 'El nombre de usuario es obligatorio';
        }

        if (strlen($username) > 20) {
            return 'El nombre de usuario no puede superar los 20 caracteres';
        }

        if (!preg_match('/^[A-Za-z0-9]+$/', $username)) {
            return 'El nombre de usuario solo puede tener letras y numeros';
        }

        return null;
    }
}
