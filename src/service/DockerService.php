<?php
// require 'vendor/autoload.php';
namespace Gyro\Service;

use Gyro\Dto\InstanceDTO;

/**
 * DockerService handles Docker container operations and job management.
 * 
 * This service is responsible for:
 * - Validating Docker image names and port configurations
 * - Creating and managing Docker container jobs
 * - Interacting with Redis for job queuing
 * - Tracking job status through JobService
 */
class DockerService {
    /** @var \Predis\Client Redis client for job queuing */
    private $redis;

    /** @var \PDO Database connection for job logging */
    private $db;

    /** @var JobService Service for managing job status and tracking */
    private $jobService;

    /**
     * DockerService constructor.
     *
     * @param \PDO $db Database connection for job logging
     * @param \Predis\Client $redis Redis client for job queuing
     */
    public function __construct($db, $redis) {
        $this->redis = $redis;
        $this->db = $db;
        $this->jobService = new JobService($db);
    }

    /**
     * Spawns a new Docker service based on the provided instance configuration.
     * 
     * This method:
     * 1. Validates the Docker image name format
     * 2. Creates a unique job ID
     * 3. Prepares the job data structure
     * 4. Creates a job log entry
     * 5. Inserts the Docker instance record
     * 6. Queues the job in Redis
     *
     * @param InstanceDTO $instance The instance configuration containing:
     *                             - app_name: Docker image name
     *                             - app_uid: Unique identifier for the instance
     *                             - secret: Authentication secret
     *                             - domain: Domain name for the instance
     *                             - tribe_port: Port for tribe service
     *                             - junction_port: Port for junction service
     * 
     * @return array{job_id: string, message: string} Job information and status message
     * 
     * @throws \InvalidArgumentException If the Docker image name is invalid
     * @throws \PDOException If database operations fail
     * @throws \Predis\PredisException If Redis operations fail
     */
    public function spawnService(InstanceDTO $instance) {
        // Validate image name to prevent invalid Docker commands
        // Only allows alphanumeric characters, hyphens, underscores, and optional tag
        if (!preg_match('/^[a-zA-Z0-9-_]+(:[a-zA-Z0-9._-]+)?$/', $instance->getAppName())) {
            throw new \InvalidArgumentException('Invalid Docker image name');
        }

        // Generate a unique job ID for tracking
        $jobId = uniqid();

        // Prepare job data structure for Redis queue
        $spawnJob = [
            'title' => $instance->getAppName(),
            'job_id' => $jobId,
            'app_name' => $instance->getAppName(),
            'app_uid' => $instance->getAppUid(),
            'secret' => $instance->getSecret(),
            'domain' => $instance->getDomain(),
            'tribe_port' => $instance->getTribePort(),
            'junction_port' => $instance->getJunctionPort()
        ];

        // Create job log entry with pending status
        $this->jobService->createJob($jobId);

        // Insert Docker instance record with pending status
        $stmt = $this->db->prepare("
            INSERT INTO dockers (
                slug, 
                app_name, 
                tribe_port, 
                junction_port, 
                status
            ) VALUES (
                :slug, 
                :app_name, 
                :tribe_port, 
                :junction_port, 
                'pending'
            )
        ");
        $stmt->bindValue(':slug', $instance->getAppUid());
        $stmt->bindValue(':app_name', $instance->getAppName());
        $stmt->bindValue(':tribe_port', $instance->getTribePort());
        $stmt->bindValue(':junction_port', $instance->getJunctionPort());
        $stmt->execute();

        // Queue the job in Redis for processing
        $jobData = json_encode($spawnJob);
        $this->redis->lpush('docker_jobs', [$jobData]);

        return [
            'job_id' => $jobId, 
            'message' => 'Job queued successfully'
        ];
    }
}
