<?php

namespace Gyro;

/**
 * Enum representing possible job statuses in the system.
 * 
 * These statuses are used to track the progress and state of asynchronous jobs
 * throughout their lifecycle.
 */
enum JobStatus: string
{
    /**
     * Job has been created but not yet started
     */
    case PENDING = 'pending';

    /**
     * Job is currently being processed
     */
    case RUNNING = 'running';

    /**
     * Job has completed successfully
     */
    case COMPLETED = 'completed';

    /**
     * Job has failed during execution
     */
    case FAILED = 'failed';

    /**
     * Get all possible job statuses
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
