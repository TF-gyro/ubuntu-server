<?php

namespace Gyro;

/**
 * Enum representing possible Docker instance statuses in the system.
 * 
 * These statuses are used to track the state of Docker containers
 * and their deployment process.
 */
enum DockerStatus: string
{
    /**
     * Docker instance is pending deployment
     */
    case PENDING = 'pending';

    /**
     * Docker instance is currently running
     */
    case RUNNING = 'running';

    /**
     * Docker instance has been stopped
     */
    case STOPPED = 'stopped';

    /**
     * Docker instance deployment has failed
     */
    case FAILED = 'failed';

    /**
     * Get all possible Docker statuses
     * 
     * @return array<string> Array of all possible status values
     */
    public static function getAll(): array
    {
        return array_column(self::cases(), 'value');
    }

    /**
     * Check if a given status is valid
     * 
     * @param string $status Status to validate
     * @return bool True if status is valid
     */
    public static function isValid(string $status): bool
    {
        return in_array($status, self::getAll());
    }
}
