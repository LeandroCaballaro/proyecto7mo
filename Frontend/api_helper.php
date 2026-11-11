<?php
/**
 * NexoHub - Direct Service & API Bridge
 * Prevents HTTP loopback deadlocks in local Apache/PHP environments
 */

if (!defined('API_URL')) {
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    define('API_URL', $scheme . '://' . $host . '/proyecto7mo/Backend/public/api.php');
}

require_once __DIR__ . '/../Backend/models/Database.php';
require_once __DIR__ . '/../Backend/services/MovieService.php';
require_once __DIR__ . '/../Backend/services/AuthService.php';

function api_get($route, $extra = [])
{
    try {
        static $movieService = null;
        if ($movieService === null) {
            $movieService = new MovieService();
        }

        if ($route === 'movies/featured') {
            return $movieService->getFeatured();
        }
        if ($route === 'movies') {
            return $movieService->getAll();
        }
        if ($route === 'movies/genres') {
            return $movieService->getGenres();
        }
        if (str_starts_with($route, 'movies/genre/')) {
            $genre = substr($route, strlen('movies/genre/'));
            return $movieService->getByGenre(urldecode($genre));
        }
        if (preg_match('#^movies/(\d+)/reviews$#', $route, $m)) {
            return $movieService->getReviewsForMovie((int) $m[1], $extra['sort'] ?? 'recent');
        }
        if (preg_match('#^movies/(\d+)$#', $route, $m)) {
            return $movieService->getById((int) $m[1]);
        }
        if ($route === 'ranking') {
            return $movieService->getReputationRanking();
        }
        if ($route === 'reviewers') {
            return $movieService->getReviewers((int) ($extra['limit'] ?? 10));
        }
    } catch (Throwable $e) {
        error_log('api_get local error: ' . $e->getMessage());
    }

    $url = API_URL . '?' . http_build_query(array_merge(['route' => $route], $extra));
    $ctx = stream_context_create(['http' => ['ignore_errors' => true, 'timeout' => 3]]);
    $raw = @file_get_contents($url, false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

function api_post($route, $data, $token = null)
{
    try {
        static $authService = null;
        static $movieService = null;
        if ($authService === null) {
            $authService = new AuthService();
        }
        if ($movieService === null) {
            $movieService = new MovieService();
        }

        if ($route === 'auth/login') {
            return $authService->login($data['identifier'] ?? $data['email'] ?? '', $data['password'] ?? '');
        }
        if ($route === 'auth/register') {
            return $authService->register(
                $data['name'] ?? '',
                $data['username'] ?? ($data['name'] ?? ''),
                $data['email'] ?? '',
                $data['password'] ?? '',
                $data['confirm_password'] ?? ($data['password'] ?? '')
            );
        }
        if ($route === 'auth/logout') {
            return $authService->logout($token);
        }
        if ($route === 'reviews' && $token) {
            $user = $movieService->userFromToken($token);
            if ($user) {
                return $movieService->addReview($user['id'], (int) ($data['movie_id'] ?? 0), (int) ($data['rating'] ?? 0), $data['comment'] ?? '');
            }
        }
    } catch (Throwable $e) {
        error_log('api_post local error: ' . $e->getMessage());
    }

    $h = "Content-Type: application/json\r\n";
    if ($token) {
        $h .= "Authorization: Bearer $token\r\n";
    }
    $ctx = stream_context_create(['http' => [
        'method'        => 'POST',
        'header'        => $h,
        'content'       => json_encode($data),
        'ignore_errors' => true,
        'timeout'       => 3,
    ]]);
    $raw = @file_get_contents(API_URL . '?route=' . urlencode($route), false, $ctx);
    return $raw ? json_decode($raw, true) : null;
}

