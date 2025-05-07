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
use Gyro\DockerStatus;
/**
 * InstanceController handles the creation and management of service instances.
 * This controller is responsible for:
 * - Validating instance creation requests
 * - Managing Docker container deployment
 * - Setting up Cloudflare DNS records
 * - Handling database transactions
 */
class InstanceController {
    /**
     * Handles POST requests for creating new service instances.
     * 
     * @param array $data Request data containing:
     *                    - app_name: string (alphanumeric + underscores)
     *                    - app_uid: string (alphanumeric)
     *                    - junction_secret: string (min 5 chars)
     *                    - domain: string (valid domain format)
     *                    - server: string (valid hostname or IP)
     * 
     * @return array Response containing:
     *               - code: int HTTP status code
     *               - body: array Response data
     * 
     * @throws \Exception On database or service operation failures
     */
    public static function handlePost($data){
        // Initialize database and Redis connections
        $pdo = Database::getInstance()->getConnection();
        $redis = Redis::getInstance()->getClient();
        
        // Define required fields and validate their presence
        $requiredFields = ['app_name', 'app_uid', 'junction_secret', 'domain', 'server'];
        $errors = [];

        // Validate presence of required fields
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $errors[] = "$field is required";
            }
        }

        // Validate app name format (alphanumeric + underscores)
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['app_name'])) {
            $errors[] = "app_name must contain only alphanumeric characters and underscores";
        }

        // Validate app_uid format (alphanumeric only)
        if (!preg_match('/^[a-zA-Z0-9]+$/', $data['app_uid'])) {
            $errors[] = "app_uid must contain only alphanumeric characters";
        }

        // Validate junction_secret length
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

        // Return validation errors if any
        if (count($errors) > 0) {
            return [
                'code' => 400,
                'body' => ['ok' => false, 'errors' => $errors]
            ];
        }

        // Begin database transaction for atomic operations
        try {
            $pdo->beginTransaction();

            // Lock dockers table to prevent race conditions
            $pdo->exec("LOCK TABLES dockers");

            // Get available ports for the new instance
            $portService = new PortService($pdo);
            $ports = $portService->getAvailablePorts();

            // Create instance DTO with validated data
            $instance = new InstanceDTO(
                $data['app_name'],
                $data['app_uid'],
                $data['junction_secret'],
                $data['domain'],
                $ports['tribe_port'],
                $ports['junction_port']
            );

            // Deploy Docker container
            $dockerService = new DockerService($pdo, $redis);
            $dockerService->spawnService($instance);
            $instance->setStatus(DockerStatus::PENDING);

            // Commit transaction and release table lock
            $pdo->commit();
            $pdo->exec("UNLOCK TABLES");

        } catch (\Exception $e) {
            // Rollback transaction and release lock on error
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
            // Initialize Cloudflare service
            $cloudflareService = new CloudflareService([
                'apiToken' => $_ENV['CLOUDFLARE_API_TOKEN']
            ]);

            // Create CNAME records for the instance
            $record1 = new Record(
                type: RecordType::CNAME,
                name: $instance->getAppName(),
                content: $data['server'],
                ttl: 3600,
                proxied: true
            );

            $record2 = new Record(
                type: RecordType::CNAME,
                name: $instance->getAppName() . '.tribe',
                content: $data['server'],
                ttl: 3600,
                proxied: false
            );

            // Create and execute batch operation for DNS records
            $batch = new Batch();
            $batch->addCreate($record1)
                  ->addCreate($record2);

            $result = $cloudflareService->executeBatch($_ENV['CLOUDFLARE_ZONE_ID'], $batch);

            // Check for Cloudflare API errors
            if (!empty($result['errors'])) {
                throw new \Exception('Failed to create DNS records: ' . json_encode($result['errors']));
            }

            // Return success response
            return [
                'code' => 200,
                'body' => [
                    'ok' => true,
                    'message' => 'Instance created and DNS records added successfully',
                    'instance' => $instance->toArray()
                ]
            ];

        } catch (\Exception $e) {
            // Return partial success response if DNS setup fails
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