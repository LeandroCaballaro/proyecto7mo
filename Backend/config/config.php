<?php

// Carga Backend/.env en variables de entorno (PHP no lo hace solo)
$envFile = __DIR__ . '/../.env';
if (is_readable($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') {
            continue;
        }
        if (strpos($line, '=') === false) {
            continue;
        }
        list($key, $val) = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val, " \t\"'");
        if ($key !== '' && getenv($key) === false) {
            putenv("$key=$val");
        }
    }
}

return [
    'driver' => 'mysql',
    'host' => getenv('DB_HOST') ?: '127.0.0.1',
    'dbname' => getenv('DB_NAME') ?: getenv('DB_DATABASE') ?: 'proyecto7mo',
    'user' => getenv('DB_USER') ?: getenv('DB_USERNAME') ?: 'root',
    'pass' => getenv('DB_PASS') ?: getenv('DB_PASSWORD') ?: '',
    'charset' => 'utf8mb4',
];
