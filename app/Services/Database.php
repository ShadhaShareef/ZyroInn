<?php
namespace App\Services;

use PDO;
use Exception;

class Database {
    private static ?PDO $instance = null;

    /**
     * Get the PDO database connection singleton.
     *
     * @return PDO
     * @throws Exception
     */
    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $configPath = __DIR__ . '/../../config/database.php';
            if (!file_exists($configPath)) {
                throw new Exception("Database configuration file not found at: " . $configPath);
            }
            $config = require $configPath;
            
            $dsn = "mysql:host={$config['host']};dbname={$config['database']};charset={$config['charset']}";
            self::$instance = new PDO($dsn, $config['username'], $config['password'], [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }
        return self::$instance;
    }
    
    /**
     * Get a PDO connection directly to the MySQL host (without a specific database).
     * Used for migrations/setup.
     *
     * @return PDO
     * @throws Exception
     */
    public static function getHostConnection(): PDO {
        $configPath = __DIR__ . '/../../config/database.php';
        if (!file_exists($configPath)) {
            throw new Exception("Database configuration file not found.");
        }
        $config = require $configPath;
        
        $dsn = "mysql:host={$config['host']};charset={$config['charset']}";
        return new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
}
