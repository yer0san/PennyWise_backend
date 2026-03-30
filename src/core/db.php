<?php
function loadEnv($path) {
    if (!file_exists($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    foreach ($lines as $line) {
        // skip comments
        if (str_starts_with(trim($line), '#')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);

        $key = trim($key);
        $value = trim($value);

        $_ENV[$key] = $value;
        putenv("$key=$value");
    }
}

loadEnv(__DIR__ . '/../../.env');

function db() {
    static $pdo = null;

    if ($pdo === null) {
        $pdo = new PDO(
            "mysql:host=" . $_ENV['DB_HOST'] . ";dbname=" . $_ENV['DB_NAME'] . ";charset=utf8mb4",
            $_ENV['DB_USER'],
            $_ENV['DB_PASS'],
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION
            ]
        );
    }

    return $pdo;
}
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Log error and return generic message
            error_log("Database Connection Error: " . $e->getMessage());
            throw new Exception("Database connection failed. Please check your configuration.");
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

/**
 * Helper function to get database connection
 */
function getDB() {
    return Database::getInstance()->getConnection();
}
?>