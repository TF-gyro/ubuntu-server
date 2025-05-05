<?php
// require 'vendor/autoload.php';
namespace Gyro\Service;

use Gyro\Dto\InstanceDTO;

// Ensure Redis client is installed before using it
if (!class_exists('Predis\Client')) {
    die("Predis client not found. Please install predis/predis via composer.");
}


class DockerService {
    private $redis;
    private $db;
    private $jobService;

    public function __construct($db, $redis) {
        $this->redis = $redis;
        $this->db = $db;
        $this->jobService = new JobService($db);
    }

    public function spawnService(InstanceDTO $instance) {
        // Validate image name to prevent invalid Docker commands
        if (!preg_match('/^[a-zA-Z0-9-_]+(:[a-zA-Z0-9._-]+)?$/', $instance->getAppName())) {
            throw new \InvalidArgumentException('Invalid Docker image name');
        }

        $jobId = uniqid();
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

        // Create job log entry
        $this->jobService->createJob($jobId);

        // Insert docker instance with pending status
        $stmt = $this->db->prepare("INSERT INTO dockers (slug, app_name, tribe_port, junction_port, status) VALUES (:slug, :app_name, :tribe_port, :junction_port, 'pending')");
        $stmt->bindParam(':slug', $instance->getAppUid());
        $stmt->bindParam(':app_name', $instance->getAppName());
        $stmt->bindParam(':tribe_port', $instance->getTribePort());
        $stmt->bindParam(':junction_port', $instance->getJunctionPort());
        $stmt->execute();

        // Push job to Redis queue
        $this->redis->lpush('docker_jobs', json_encode($spawnJob));

        return ['job_id' => $jobId, 'message' => 'Job queued successfully'];
    }

}
