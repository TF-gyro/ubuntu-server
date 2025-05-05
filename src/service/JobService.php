<?php

namespace Gyro\Service;

use PDO;

/**
 * Service class for managing job status and tracking.
 * 
 * This service handles the creation, updating, and retrieval of job statuses
 * for various operations in the system.
 */
class JobService
{
    /** @var PDO Database connection */
    private PDO $db;

    /** @var array Valid job statuses */
    private const ALLOWED_STATUSES = ['pending', 'running', 'completed', 'failed'];

    /**
     * JobService constructor.
     *
     * @param PDO $db Database connection
     */
    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    /**
     * Create a new job log entry.
     *
     * @param string $jobId Unique identifier for the job
     * @return bool True if successful
     */
    public function createJob(string $jobId): bool
    {
        $stmt = $this->db->prepare("INSERT INTO job_logs (job_id, status) VALUES (:jobId, 'pending')");
        $stmt->bindParam(':jobId', $jobId);
        return $stmt->execute();
    }

    /**
     * Update job status and output.
     *
     * @param string $jobId Job identifier
     * @param string $status New status
     * @param string|null $output Optional output message
     * @return bool True if successful
     * @throws \InvalidArgumentException If status is invalid
     */
    public function updateJobStatus(string $jobId, string $status, ?string $output = null): bool
    {
        if (!in_array($status, self::ALLOWED_STATUSES)) {
            throw new \InvalidArgumentException('Invalid job status. Must be one of: ' . implode(', ', self::ALLOWED_STATUSES));
        }

        $stmt = $this->db->prepare("UPDATE job_logs SET status = :status, output = :output WHERE job_id = :jobId");
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':output', $output);
        $stmt->bindParam(':jobId', $jobId);
        return $stmt->execute();
    }

    /**
     * Get job status and output.
     *
     * @param string $jobId Job identifier
     * @return array{status: string, output: string|null} Job status and output
     * @throws \InvalidArgumentException If job is not found
     */
    public function getJobStatus(string $jobId): array
    {
        $stmt = $this->db->prepare("SELECT status, output FROM job_logs WHERE job_id = :jobId");
        $stmt->bindParam(':jobId', $jobId);
        $stmt->execute();
        $result = $stmt->fetch();

        if (!$result) {
            throw new \InvalidArgumentException('Job not found');
        }

        return $result;
    }

    /**
     * Get all jobs with a specific status.
     *
     * @param string|null $status Optional status filter
     * @return array Array of jobs
     * @throws \InvalidArgumentException If status is invalid
     */
    public function getJobs(?string $status = null): array
    {
        if ($status !== null && !in_array($status, self::ALLOWED_STATUSES)) {
            throw new \InvalidArgumentException('Invalid job status. Must be one of: ' . implode(', ', self::ALLOWED_STATUSES));
        }

        $sql = "SELECT * FROM job_logs";
        if ($status !== null) {
            $sql .= " WHERE status = :status";
        }
        $sql .= " ORDER BY created_at DESC";

        $stmt = $this->db->prepare($sql);
        if ($status !== null) {
            $stmt->bindParam(':status', $status);
        }
        $stmt->execute();
        return $stmt->fetchAll();
    }
}
