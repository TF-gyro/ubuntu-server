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

    public function __construct($db, $redis) {
        $this->redis = $redis;
        $this->db = $db;
    }

    public function spawnService(InstanceDTO $instance) {
        // Validate image name to prevent invalid Docker commands
        if (!preg_match('/^[a-zA-Z0-9-_]+(:[a-zA-Z0-9._-]+)?$/', $instance->getAppName())) {
            return ['error' => 'Invalid Docker image name.'];
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

        $stmt = $this->db->prepare("INSERT INTO job_logs (job_id, status) VALUES (:jobId, 'pending')");
        $stmt->bindParam(':jobId', $jobId);
        $stmt->execute();

        $stmt = $this->db->prepare("INSERT INTO dockers (slug, app_name, tribe_port, junction_port) VALUES (:slug, :app_name, :tribe_port, :junction_port)");
        $stmt->bindParam(':slug', $instance->getAppUid());
        $stmt->bindParam(':app_name', $instance->getAppName());
        $stmt->bindParam(':tribe_port', $instance->getTribePort());
        $stmt->bindParam(':junction_port', $instance->getJunctionPort());
        $stmt->execute();

        $this->redis->lpush('docker_jobs', json_encode($spawnJob));

        return ['job_id' => $jobId, 'message' => 'Job queued successfully'];
    }

    public function getJobStatus($jobId) {
        $stmt = $this->db->prepare("SELECT * FROM job_logs WHERE job_id = ?");
        $stmt->execute([$jobId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

}
