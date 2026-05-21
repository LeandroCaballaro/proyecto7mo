<?php

require_once __DIR__ . '/../models/UserModel.php';

class AuthService
{
    private UserModel $userModel;

    // Duración del token (7 días por defecto)
    private int $tokenTtlDays = 7;

    public function __construct()
    {
        $this->userModel = new UserModel();
    }

    // -------------------------------------------------------------------------
    // REGISTRO
    // -------------------------------------------------------------------------

    /**
     * Registra un nuevo usuario.
     *
     * @return array  En éxito: ['user' => [...], 'token' => '...']
     *                En error:  ['error' => 'mensaje']
     */
    public function register(string $name, string $email, string $password): array
    {
        // Validaciones básicas
        $name     = trim($name);
        $email    = trim($email);

        if ($name === '') {
            return ['error' => 'El nombre es obligatorio'];
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'El email no es válido'];
        }

        if (strlen($password) < 6) {
            return ['error' => 'La contraseña debe tener al menos 6 caracteres'];
        }

        // Verificar que el email no esté en uso
        if ($this->userModel->findByEmail($email)) {
            return ['error' => 'El email ya está registrado'];
        }

        // Crear usuario
        $hash   = password_hash($password, PASSWORD_BCRYPT);
        $userId = $this->userModel->create($name, $email, $hash);

        // Crear reviewer asociado
        $this->userModel->ensureReviewer($userId, $name);

        // Generar token y devolver sesión
        return $this->buildSession($userId, $name, $email);
    }

    // -------------------------------------------------------------------------
    // LOGIN
    // -------------------------------------------------------------------------

    /**
     * Autentica un usuario con email y contraseña.
     *
     * @return array  En éxito: ['user' => [...], 'token' => '...']
     *                En error:  ['error' => 'mensaje']
     */
    public function login(string $email, string $password): array
    {
        $user = $this->userModel->findByEmail($email);

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return ['error' => 'Credenciales incorrectas'];
        }

        return $this->buildSession((int) $user['id'], $user['name'], $user['email']);
    }

    // -------------------------------------------------------------------------
    // LOGOUT
    // -------------------------------------------------------------------------

    /**
     * Invalida el token Bearer recibido.
     */
    public function logout(?string $token): array
    {
        if (!$token) {
            return ['error' => 'No hay sesión activa'];
        }

        $this->userModel->deleteToken($token);
        return ['message' => 'Sesión cerrada correctamente'];
    }

    // -------------------------------------------------------------------------
    // USUARIO ACTUAL (ME)
    // -------------------------------------------------------------------------

    /**
     * Retorna los datos del usuario asociado al token.
     */
    public function me(?string $token): ?array
    {
        if (!$token) {
            return null;
        }

        return $this->userModel->findByToken($token);
    }

<<<<<<< HEAD
    /**
     * Autentica o registra un usuario que inicia sesión mediante Google.
     */
    public function googleLogin(string $name, string $email): array
    {
        $email = trim($email);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['error' => 'El email no es válido'];
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            // Registrar usuario nuevo con contraseña aleatoria
            $randomPassword = bin2hex(random_bytes(16));
            $hash = password_hash($randomPassword, PASSWORD_BCRYPT);
            $userId = $this->userModel->create($name, $email, $hash);

            // Asegurar reviewer asociado
            $this->userModel->ensureReviewer($userId, $name);
        } else {
            $userId = (int) $user['id'];
            $name = $user['name'];
        }

        return $this->buildSession($userId, $name, $email);
    }

=======
>>>>>>> 7f18f1ab49d683819ce880bd7457e3da0465fd73
    // -------------------------------------------------------------------------
    // HELPER PRIVADO
    // -------------------------------------------------------------------------

    /**
     * Genera un token seguro, lo persiste en BD y devuelve la respuesta de sesión.
     */
    private function buildSession(int $userId, string $name, string $email): array
    {
        $token     = bin2hex(random_bytes(32));                         // 64 hex chars
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$this->tokenTtlDays} days"));

        $this->userModel->createToken($userId, $token, $expiresAt);

        return [
            'user'       => [
                'id'    => $userId,
                'name'  => $name,
                'email' => $email,
            ],
            'token'      => $token,
            'expires_at' => $expiresAt,
        ];
    }
}
