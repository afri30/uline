<?php

namespace App\Core;

use PDO;
use PDOException;
use Exception;

class Database
{
    private static ?PDO $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     */
    private function __construct()
    {
    }

    /**
     * Get singleton PDO instance
     *
     * @return PDO
     * @throws Exception
     */
    public static function getConnection(): PDO
    {
        if (self::$instance === null) {
            $configPath = __DIR__ . '/../Config/database.php';
            
            if (!file_exists($configPath)) {
                throw new Exception("Database configuration file not found at {$configPath}");
            }
            
            $config = require $configPath;
            
            $dsn = sprintf(
                'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                $config['host'] ?? '127.0.0.1',
                $config['port'] ?? '3306',
                $config['database'] ?? 'uline_db',
                $config['charset'] ?? 'utf8mb4'
            );
            
            try {
                self::$instance = new PDO(
                    $dsn,
                    $config['username'] ?? 'root',
                    $config['password'] ?? '',
                    $config['options'] ?? [
                        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                        PDO::ATTR_EMULATE_PREPARES => false,
                    ]
                );
            } catch (PDOException $e) {
                // In production, we log the error instead of exposing raw database credentials/details
                throw new Exception("Database connection failed: " . $e->getMessage(), (int)$e->getCode(), $e);
            }
        }

        return self::$instance;
    }

    /**
     * Prevent cloning of instance
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserializing of instance
     */
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize singleton");
    }
}
