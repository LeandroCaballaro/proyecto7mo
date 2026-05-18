<?php
class Database {
    private static $instance = null;
    private $pdo;

    private function __construct()
    {
        $cfg = require __DIR__ . '/../config/config.php';
        if ($cfg['driver'] === 'sqlite') {
            $dsn = $cfg['dsn'];
            $dir = dirname($cfg['path']);
            if (!is_dir($dir)) {
                mkdir($dir, 0755, true);
            }
            $this->pdo = new PDO($dsn);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->ensureSchema();
        } elseif ($cfg['driver'] === 'mysql') {
            $host = $cfg['host'];
            $dbname = $cfg['dbname'];
            $charset = isset($cfg['charset']) ? $cfg['charset'] : 'utf8mb4';
            $dsn = "mysql:host={$host};dbname={$dbname};charset={$charset}";
            $this->pdo = new PDO($dsn, $cfg['user'], $cfg['pass']);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->ensureSchema();
        } else {
            // Fallback using provided dsn
            $this->pdo = new PDO($cfg['dsn'], $cfg['user'] ?? null, $cfg['pass'] ?? null);
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        }
    }

    public static function getInstance()
    {
        if (!self::$instance) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    public function getConnection()
    {
        return $this->pdo;
    }

    private function ensureSchema()
    {
        // Create basic tables if they don't exist and seed minimal data
        // Use SQL compatible with both SQLite and MySQL where possible
        $driver = $this->pdo->getAttribute(PDO::ATTR_DRIVER_NAME);
        if ($driver === 'sqlite') {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS movies (id INTEGER PRIMARY KEY AUTOINCREMENT, title TEXT NOT NULL, genre TEXT, featured INTEGER DEFAULT 0, description TEXT, year INTEGER);");
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS reviewers (id INTEGER PRIMARY KEY AUTOINCREMENT, name TEXT NOT NULL, reputation INTEGER DEFAULT 0);");
        } else {
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS movies (id INT AUTO_INCREMENT PRIMARY KEY, title VARCHAR(255) NOT NULL, genre VARCHAR(100), featured TINYINT(1) DEFAULT 0, description TEXT, year INT) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
            $this->pdo->exec("CREATE TABLE IF NOT EXISTS reviewers (id INT AUTO_INCREMENT PRIMARY KEY, name VARCHAR(255) NOT NULL, reputation INT DEFAULT 0) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");
        }

        $count = (int)$this->pdo->query("SELECT COUNT(*) FROM movies")->fetchColumn();
        if ($count === 0) {
            $stmt = $this->pdo->prepare("INSERT INTO movies (title, genre, featured, description, year) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute(['El Gran Viaje', 'Aventura', 1, 'Una épica aventura entre mundos.', 2021]);
            $stmt->execute(['Amor en París', 'Romance', 0, 'Drama romántico en París.', 2019]);
            $stmt->execute(['Risa Mortal', 'Comedia', 1, 'Comedia negra sobre la fama.', 2022]);
        }

        $countR = (int)$this->pdo->query("SELECT COUNT(*) FROM reviewers")->fetchColumn();
        if ($countR === 0) {
            $stmt = $this->pdo->prepare("INSERT INTO reviewers (name, reputation) VALUES (?, ?)");
            $stmt->execute(['Carlos Pérez', 120]);
            $stmt->execute(['Ana Gómez', 95]);
        }
    }
}
