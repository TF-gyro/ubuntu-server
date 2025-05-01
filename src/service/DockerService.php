<?php
// require 'vendor/autoload.php';
namespace Gyro\Service;

// Ensure Redis client is installed before using it
if (!class_exists('Predis\Client')) {
    die("Predis client not found. Please install predis/predis via composer.");
}

use \Predis\Client;

class DockerService {
    public $redis;
    public $db;

    public function __construct() {
        $this->redis = new Client();

        try {
            // TODO: use PDO in MySQL class
            // connect to MYSQL
            $this->db = new \PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_NAME']}", $_ENV['DB_USER'], $_ENV['DB_PASS']);

            // connect to Redis
            $this->redis->connect($_ENV['REDIS_HOST'], $_ENV['REDIS_PORT']);

            // Authenticate
            if (!empty($_ENV['REDIS_PASSWORD']) && !$this->redis->auth($_ENV['REDIS_PASSWORD'])) {
                throw new \Exception('Redis authentication failed');
            }

            // echo "Connected to Redis successfully!\n";
        } catch (\Exception $e) {
            echo "Error: " . $e->getMessage();
        }
    }

    public function spawnService(string $title, string $app_name, string $app_uid, string $secret, string $domain, array $ports) {
        // Validate image name to prevent invalid Docker commands
        if (!preg_match('/^[a-zA-Z0-9-_]+(:[a-zA-Z0-9._-]+)?$/', $app_name)) {
            return ['error' => 'Invalid Docker image name.'];
        }

        $jobId = uniqid();
        $spawnJob = [
            'job_id' => $jobId,
            'title' => $title,
            'app_name' => $app_name,
            'app_uid' => $app_uid,
            'secret' => $secret,
            'domain' => $domain,
            'tribe_port' => $ports['tribe_port'],
            'junction_port' => $ports['junction_port']
        ];

        $stmt = $this->db->prepare("INSERT INTO job_logs (job_id, status) VALUES (:jobId, 'pending')");
        $stmt->bindParam(':jobId', $jobId);
        $stmt->execute();

        $stmt = $this->db->prepare("INSERT INTO dockers (slug, app_name, tribe_port, junction_port) VALUES (:slug, :app_name, :tribe_port, :junction_port)");
        $stmt->bindParam(':slug', $app_uid);
        $stmt->bindParam(':app_name', $app_name);
        $stmt->bindParam(':tribe_port', $ports['tribe_port']);
        $stmt->bindParam(':junction_port', $ports['junction_port']);
        $stmt->execute();

        $this->redis->lpush('docker_jobs', json_encode($spawnJob));

        return ['job_id' => $jobId, 'message' => 'Job queued successfully'];
    }

    public function getJobStatus($jobId) {
        $stmt = $this->db->prepare("SELECT * FROM job_logs WHERE job_id = ?");
        $stmt->execute([$jobId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC);
    }

    public function getPorts() {
        $row = $this->db
                    ->query("SELECT * FROM dockers ORDER BY id DESC LIMIT 1")
                    ->fetch(\PDO::FETCH_ASSOC);

        // default ports for initialisation
        $tribe_port = 8080;
        $junction_port = 8081;

        if ($row) {
            // increase by 1 based on junction's, since it is supposed to be higher than tribe's
            $tribe_port = $junction_port = $row['junction_port'] + 1;
        }

        // find free system port for tribe
        $status = false;
        while(!$status) {
            $status = $this->isPortAvailable($tribe_port);
            $tribe_port = $status ? $tribe_port : ++$tribe_port;
        }

        $junction_port = $tribe_port + 1;
        $status = false;
        while(!$status) { // find free system port for junction
            $status = $this->isPortAvailable($junction_port);
            $junction_port = $status ? $junction_port : ++$junction_port;
        }

        return [
            'tribe_port' => $tribe_port,
            'junction_port' => $junction_port
        ];
    }

    private function isPortAvailable($port) {
        exec("netstat -tnlp | grep $port", $output, $status);

        return $status !== 0;
    }
}
