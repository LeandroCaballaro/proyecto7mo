<?php

require_once __DIR__ . '/../services/AuthService.php';

class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    // -------------------------------------------------------------------------
    // POST /auth/register
    // Body JSON: { "name": "...", "email": "...", "password": "..." }
    // -------------------------------------------------------------------------
    public function register(): void
    {
        $data = $this->body();

        $result = $this->service->register(
            $data['name']     ?? '',
            $data['email']    ?? '',
            $data['password'] ?? ''
        );

        if (isset($result['error'])) {
            $this->json($result, 422);
        }

        $this->json($result, 201);
    }

    // -------------------------------------------------------------------------
    // POST /auth/login
    // Body JSON: { "email": "...", "password": "..." }
    // -------------------------------------------------------------------------
    public function login(): void
    {
        $data = $this->body();

        $result = $this->service->login(
            $data['email']    ?? '',
            $data['password'] ?? ''
        );

        if (isset($result['error'])) {
            $this->json($result, 401);
        }

        $this->json($result);
    }

    // -------------------------------------------------------------------------
    // POST /auth/logout
    // Header: Authorization: Bearer <token>
    // -------------------------------------------------------------------------
    public function logout(): void
    {
        $result = $this->service->logout($this->bearerToken());
        $code   = isset($result['error']) ? 400 : 200;
        $this->json($result, $code);
    }

    // -------------------------------------------------------------------------
    // GET /auth/me
    // Header: Authorization: Bearer <token>
    // -------------------------------------------------------------------------
    public function me(): void
    {
        $user = $this->service->me($this->bearerToken());

        if (!$user) {
            $this->json(['error' => 'No autorizado. Token inválido o expirado.'], 401);
        }

        $this->json(['user' => $user]);
    }

    // -------------------------------------------------------------------------
    // Helpers privados
    // -------------------------------------------------------------------------

    /** Lee el cuerpo de la petición como JSON o form-data */
    private function body(): array
    {
        $raw  = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : ($_POST ?: []);
    }

    /** Extrae el token del header Authorization: Bearer <token> */
    private function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
        if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /** Responde con JSON y termina la ejecución */
    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
