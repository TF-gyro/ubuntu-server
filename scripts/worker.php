<?php
require_once __DIR__ . "/../vendor/autoload.php";

use Gyro\Database;
use Gyro\Redis;

$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '/../.env');
$dotenv->load();

$redis = Redis::getInstance()->getClient();
$db = Database::getInstance()->getConnection();

while (true) {
    // get job from redis
    $job = $redis->brpop('docker_jobs', 5);
    if (!$job) continue;

    $job = json_decode($job[1], true);

    $job_id = $job['job_id'];

    // update job_log's status to running
    $db->prepare("UPDATE job_logs SET status = 'running' WHERE job_id = ?")
       ->execute([$job_id]);

    // run docker setup
    $args = "app_name={$job['app_name']}&";
    $args .= "app_uid={$job['app_uid']}&";
    $args .= "secret={$job['secret']}&";
    $args .= "domain={$job['domain']}&";
    $args .= "tribe_port={$job['tribe_port']}&";
    $args .= "junction_port={$job['junction_port']}";
    $args .= "title={$job['title']}";

    exec("php docker-tribe-setup.php '$args'", $output, $status);

    if ($status === 0) {
        $db->prepare("UPDATE job_logs SET status = 'completed' WHERE job_id = ?")
           ->execute([$job_id]);

        $db->prepare("UPDATE dockers SET status = 'success' WHERE slug = ?")
            ->execute([$job['app_uid']]);
    } else {
        $errorMessage = implode("\n", $output);
        error_log("Docker spawn failed for job $job_id: $errorMessage");
        $db->prepare("UPDATE job_logs SET status = 'failed', output = ? WHERE job_id = ?")
           ->execute([$errorMessage, $job_id]);

        $db->prepare("UPDATE dockers SET status = 'failed' WHERE slug = ?")
           ->execute([$job['app_uid']]);
        continue;
    }

    sleep(2);
}
