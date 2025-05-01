<?php

namespace Gyro;

use PDO;
use PDOException;

/**
 * Singleton class for managing database connections.
 * 
 * This class provides a single point of access to the database connection
 * throughout the application, ensuring only one connection is maintained.
 */
class Database
{
    /** @var Database|null The single instance of this class */
    private static ?Database $instance = null;

    /** @var PDO The database connection */
    private PDO $connection;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        try {
            $this->connection = new PDO(
                "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}",
                $_ENV['DB_USER'],
                $_ENV['DB_PASS'],
                [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                ]
            );
        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get the singleton instance of Database.
     *
     * @return Database
     */
    public static function getInstance(): Database
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the database connection.
     *
     * @return PDO
     */
    public function getConnection(): PDO
    {
        return $this->connection;
    }

    /**
     * Prevent cloning of the instance.
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserializing of the instance.
     */
    public function __wakeup()
    {
        throw new \Exception("Cannot unserialize singleton");
    }
} 