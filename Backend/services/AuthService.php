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

    public function register(string $name, string $email, string $password, string $confirmPassword = ''): array
    {
        $name = trim($name);
        $email = trim($email);

        if ($name === '') {
            return ['error' => 'El nombre es obligatorio'];
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

        $hash = password_hash($password, PASSWORD_BCRYPT);
        $userId = $this->userModel->create($name, $email, $hash);
        $this->userModel->ensureReviewer($userId, $name);

        return $this->buildSession($userId, $name, $email);
    }

    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['error' => 'Credenciales incorrectas'];
        }

        return $this->buildSession((int) $user['id'], $user['name'], $user['email']);
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

    private function buildSession(int $userId, string $name, string $email): array
    {
        $token = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->tokenTtlDays} days"));

        $this->userModel->createToken($userId, $token, $expiresAt);

        return [
            'user' => [
                'id' => $userId,
                'name' => $name,
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
}
