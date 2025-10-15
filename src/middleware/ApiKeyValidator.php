<?php

namespace Gyro\Middleware;

/**
 * API Key Validator Middleware
 * 
 * Validates API requests by checking for a valid API key
 */
class ApiKeyValidator
{
    /**
     * Validates the provided API key against the environment variable
     *
     * @param string|null $apiKey The API key to validate
     * @return bool True if valid, false otherwise
     */
    public static function validate(?string $apiKey): bool
    {
        echo "DEBUG: ApiKeyValidator::validate() started\n";
        echo "DEBUG: Provided API key: " . ($apiKey ? substr($apiKey, 0, 4) . "..." : "null") . "\n";
        
        // Debug: Check if $_ENV is populated
        echo "DEBUG: Checking if \$_ENV is populated\n";
        echo "DEBUG: \$_ENV keys available: " . implode(', ', array_keys($_ENV)) . "\n";
        
        // Get expected API key from environment
        $expectedApiKey = $_ENV['API_KEY'] ?? null;
        echo "DEBUG: Expected API key from env: " . ($expectedApiKey ? substr($expectedApiKey, 0, 4) . "..." : "null") . "\n";
        
        // Debug: Check if we can access other environment variables
        echo "DEBUG: DB_HOST from env: " . ($_ENV['DB_HOST'] ?? 'NOT SET') . "\n";
        echo "DEBUG: ENV from env: " . ($_ENV['ENV'] ?? 'NOT SET') . "\n";
        
        // Validate API key presence and correctness
        if (empty($expectedApiKey)) {
            echo "DEBUG: API_KEY not set in environment variables\n";
            error_log('API_KEY not set in environment variables');
            return false;
        }
        
        if (empty($apiKey)) {
            echo "DEBUG: No API key provided\n";
            return false;
        }
        
        // Compare API keys using constant time comparison to prevent timing attacks
        $result = hash_equals($expectedApiKey, $apiKey);
        echo "DEBUG: API key comparison result: " . ($result ? "MATCH" : "NO MATCH") . "\n";
        return $result;
    }
}

