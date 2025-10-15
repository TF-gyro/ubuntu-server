<?php
require_once __DIR__ . "/../vendor/autoload.php";

use Gyro\JobStatus;
use Gyro\DockerStatus;
use Gyro\Database;
use Gyro\Redis;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '/../.env');
$dotenv->load();

$redis = Redis::getInstance()->getClient();

while (true) {
    // get job from redis
    $job = $redis->brpop('docker_jobs', 5);
    if (!$job) continue;

    $job = json_decode($job[1], true);

    $job_id = $job['job_id'];

    // Get fresh database connection for this operation
    $db = Database::getInstance()->getConnection();
    
    // update job_log's status to running
    $stmt = $db->prepare("UPDATE job_logs SET status = :status WHERE job_id = :job_id");
    $stmt->bindValue(':status', JobStatus::RUNNING->value);
    $stmt->bindValue(':job_id', $job_id);
    $stmt->execute();

    // run docker setup
    $args = "app_name={$job['app_name']}&";
    $args .= "app_uid={$job['app_uid']}&";
    $args .= "secret={$job['secret']}&";
    $args .= "domain={$job['domain']}&";
    $args .= "tribe_port={$job['tribe_port']}&";
    $args .= "junction_port={$job['junction_port']}&";
    $args .= "title={$job['title']}";

    echo "Processing job $job_id: {$job['app_name']}\n";
    echo "Running: php " . __DIR__ . "/docker-tribe-setup.php '$args'\n";
    
    exec("php " . __DIR__ . "/docker-tribe-setup.php '$args'", $output, $status);
    
    echo "Docker setup exit status: $status\n";
    if (!empty($output)) {
        echo "Docker setup output: " . implode("\n", $output) . "\n";
    }

    if ($status === 0) {
        // Get fresh database connection for these operations
        $db = Database::getInstance()->getConnection();
        
        // Update job status to completed
        $stmt = $db->prepare("UPDATE job_logs SET status = :status WHERE job_id = :job_id");
        $stmt->bindValue(':status', JobStatus::COMPLETED->value);
        $stmt->bindValue(':job_id', $job_id);
        $stmt->execute();

        // Update docker status to running
        $stmt = $db->prepare("UPDATE dockers SET status = :status WHERE slug = :slug");
        $stmt->bindValue(':status', DockerStatus::RUNNING->value);
        $stmt->bindValue(':slug', $job['app_uid']);
        $stmt->execute();
    } else {
        $errorMessage = implode("\n", $output);
        error_log("Docker spawn failed for job $job_id: $errorMessage");
        
        // Get fresh database connection for these operations
        $db = Database::getInstance()->getConnection();
        
        // Update job status to failed
        $stmt = $db->prepare("UPDATE job_logs SET status = :status, output = :output WHERE job_id = :job_id");
        $stmt->bindValue(':status', JobStatus::FAILED->value);
        $stmt->bindValue(':output', $errorMessage);
        $stmt->bindValue(':job_id', $job_id);
        $stmt->execute();

        // Update docker status to failed
        $stmt = $db->prepare("UPDATE dockers SET status = :status WHERE slug = :slug");
        $stmt->bindValue(':status', DockerStatus::FAILED->value);
        $stmt->bindValue(':slug', $job['app_uid']);
        $stmt->execute();
        
        continue;
    }

    sleep(2);
}
