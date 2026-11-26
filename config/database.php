<?php
/**
 * FORT EXPLORER - Database Connection Handler
 * Uses PDO for secure prepared statements and SQL injection prevention.
 */

require_once __DIR__ . '/config.php';

class Database {
    private static ?PDO $instance = null;
    private static bool $connectionAttempted = false;
    private static ?string $errorMessage = null;

    /**
     * Get singleton PDO connection instance
     */
    public static function getConnection(): ?PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$connectionAttempted = true;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
        ];

        $portsToTry = DB_PORT ? [(int)DB_PORT] : [3307, 3306];
        foreach ($portsToTry as $port) {
            $dsn = "mysql:host=" . DB_HOST . ";port=" . $port . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, $options);
                return self::$instance;
            } catch (PDOException $e) {
                self::$errorMessage = $e->getMessage();
                // Check if database does not exist on this port
                if ($e->getCode() === 1049 || strpos($e->getMessage(), 'Unknown database') !== false) {
                    $currentScript = basename($_SERVER['PHP_SELF'] ?? '');
                    if ($currentScript !== 'install.php') {
                        header("Location: " . BASE_URL . "install.php?notice=db_missing");
                        exit;
                    }
                }
            }
        }
        return null;
    }

    /**
     * Check if database connection is currently healthy
     */
    public static function isConnected(): bool {
        if (self::$instance === null) {
            self::getConnection();
        }
        return self::$instance !== null;
    }

    /**
     * Get last connection error message
     */
    public static function getErrorMessage(): ?string {
        return self::$errorMessage;
    }
}

// Convenience global function for getting DB instance
function getDB(): ?PDO {
    return Database::getConnection();
}
