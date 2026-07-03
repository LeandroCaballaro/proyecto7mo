<?php

require_once __DIR__ . '/../services/AuthService.php';

class AuthController
{
    private AuthService $service;

    public function __construct()
    {
        $this->service = new AuthService();
    }

    public function register(): void
    {
        $data = $this->body();
        $result = $this->service->register(
            $data['name'] ?? '',
            $data['email'] ?? '',
            $data['password'] ?? '',
            $data['confirm_password'] ?? ''
        );

        if (isset($result['error'])) {
            $this->json($result, 422);
        }

        $this->json($result, 201);
    }

    public function login(): void
    {
        $data = $this->body();
        $result = $this->service->login(
            $data['email'] ?? '',
            $data['password'] ?? ''
        );

        if (isset($result['error'])) {
            $this->json($result, 401);
        }

        $this->json($result);
    }

    public function logout(): void
    {
        $result = $this->service->logout($this->bearerToken());
        $this->json($result, isset($result['error']) ? 400 : 200);
    }

    public function me(): void
    {
        $user = $this->service->me($this->bearerToken());

        if (!$user) {
            $this->json(['error' => 'No autorizado. Token invalido o expirado.'], 401);
        }

        $this->json(['user' => $user]);
    }

    private function body(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw, true);
        return is_array($data) ? $data : ($_POST ?: []);
    }

    private function bearerToken(): ?string
    {
        $header = $_SERVER['HTTP_AUTHORIZATION'] ?? $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] ?? '';
        if (!$header && function_exists('apache_request_headers')) {
            $headers = apache_request_headers();
            $header = $headers['Authorization'] ?? $headers['authorization'] ?? $header;
        }

        if (preg_match('/Bearer\s+(\S+)/i', $header, $matches)) {
            return $matches[1];
        }

        return null;
    }

    private function json(array $data, int $code = 200): void
    {
        http_response_code($code);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
}
