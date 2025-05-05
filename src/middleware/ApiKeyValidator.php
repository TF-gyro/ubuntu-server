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
        // Get expected API key from environment
        $expectedApiKey = $_ENV['API_KEY'] ?? null;
        
        // Validate API key presence and correctness
        if (empty($expectedApiKey)) {
            error_log('API_KEY not set in environment variables');
            return false;
        }
        
        if (empty($apiKey)) {
            return false;
        }
        
        // Compare API keys using constant time comparison to prevent timing attacks
        return hash_equals($expectedApiKey, $apiKey);
    }
}

