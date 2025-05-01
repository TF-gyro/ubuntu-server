<?php

namespace Gyro;

use Predis\Client;
use Exception;

/**
 * Singleton class for managing Redis connections.
 * 
 * This class provides a single point of access to the Redis connection
 * throughout the application, ensuring only one connection is maintained.
 */
class Redis
{
    /** @var Redis|null The single instance of this class */
    private static ?Redis $instance = null;

    /** @var Client The Redis client */
    private Client $client;

    /**
     * Private constructor to prevent direct instantiation.
     */
    private function __construct()
    {
        try {
            $this->client = new Client([
                'scheme' => 'tcp',
                'host'   => $_ENV['REDIS_HOST'],
                'port'   => $_ENV['REDIS_PORT'],
            ]);

            // Authenticate if password is set
            if (!empty($_ENV['REDIS_PASSWORD'])) {
                if (!$this->client->auth($_ENV['REDIS_PASSWORD'])) {
                    throw new Exception('Redis authentication failed');
                }
            }
        } catch (Exception $e) {
            throw new Exception("Redis connection failed: " . $e->getMessage());
        }
    }

    /**
     * Get the singleton instance of Redis.
     *
     * @return Redis
     */
    public static function getInstance(): Redis
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Get the Redis client.
     *
     * @return Client
     */
    public function getClient(): Client
    {
        return $this->client;
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
        throw new Exception("Cannot unserialize singleton");
    }
} 