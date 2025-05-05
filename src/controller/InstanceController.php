<?php

namespace Gyro\Controller;
use Gyro\Database;
use Gyro\Dto\InstanceDTO;
use Gyro\Service\PortService;
use Gyro\Service\DockerService;
use Gyro\Redis;
use Gyro\Cloudflare\Record;
use Gyro\Cloudflare\CloudflareService;
use Gyro\Cloudflare\RecordType;
use Gyro\Cloudflare\Batch;

class InstanceController {
    public static function handlePost($data){
        $pdo = Database::getInstance()->getConnection();
        $redis = Redis::getInstance()->getClient();
        $requiredFields = ['app_name', 'app_uid', 'junction_secret', 'domain', 'server'];
        $errors = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $errors[] = "$field is required";
            }
        }

        // Validate app name format
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['app_name'])) {
            $errors[] = "app_name must contain only alphanumeric characters and underscores";
        }

        // Validate app_uid format
        if (!preg_match('/^[a-zA-Z0-9]+$/', $data['app_uid'])) {
            $errors[] = "app_uid must contain only alphanumeric characters";
        }

        // Validate junction_secret format
        if (strlen($data['junction_secret']) > 5) {
            $errors[] = "junction_secret must be at least 5 characters long";
        }

        // Validate domain format
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $data['domain'])) {
            $errors[] = "domain must be a valid domain name";
        }

        // Validate server format (hostname or IP)
        if (!preg_match('/^([a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]\.)+[a-zA-Z]{2,}$|^(\d{1,3}\.){3}\d{1,3}$/', $data['server'])) {
            $errors[] = "server must be a valid hostname or IP address";
        }

        if (count($errors) > 0) {
            return [
                'code' => 400,
                'body' => ['ok' => false, 'errors' => $errors]
            ];
        }

        

        try {
            $pdo->beginTransaction();

            // lock dockers table to avoid race condition
            $pdo->exec("LOCK TABLES dockers");

            $portService = new PortService($pdo);
            $ports = $portService->getAvailablePorts();

            $instance = new InstanceDTO(
                $data['app_name'],
                $data['app_uid'],
                $data['junction_secret'],
                $data['domain'],
                $ports['tribe_port'],
                $ports['junction_port']
            );

            $dockerService = new DockerService($pdo, $redis);
            $dockerService->spawnService($instance);

            $pdo->commit();
            $pdo->exec("UNLOCK TABLES");

        } catch (\Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            $pdo->exec("UNLOCK TABLES");
            return [
                'code' => 500,
                'body' => ['ok' => false, 'errors' => $e->getMessage()]
            ];
        }

        // Create Cloudflare DNS records in a separate try-catch
        try {
            $cloudflareService = new CloudflareService([
                'apiToken' => $_ENV['CLOUDFLARE_API_TOKEN']
            ]);

            // Create CNAME records
            $record1 = new Record(
                type: RecordType::CNAME,
                name: $data['app_name'],
                content: $data['server'],
                ttl: 3600,
                proxied: true
            );

            $record2 = new Record(
                type: RecordType::CNAME,
                name: $data['app_name'] . '.tribe',
                content: $data['server'],
                ttl: 3600,
                proxied: false
            );

            // Create and execute batch
            $batch = new Batch();
            $batch->addCreate($record1)
                  ->addCreate($record2);

            $result = $cloudflareService->executeBatch($_ENV['CLOUDFLARE_ZONE_ID'], $batch);

            if (!empty($result['errors'])) {
                throw new \Exception('Failed to create DNS records: ' . json_encode($result['errors']));
            }

            return [
                'code' => 200,
                'body' => [
                    'ok' => true,
                    'message' => 'Instance created and DNS records added successfully'
                ]
            ];

        } catch (\Exception $e) {
            return [
                'code' => 500,
                'body' => [
                    'ok' => false,
                    'errors' => 'Instance created but DNS records failed: ' . $e->getMessage()
                ]
            ];
        }
    }
}   