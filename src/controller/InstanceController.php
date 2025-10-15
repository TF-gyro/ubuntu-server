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
        echo "DEBUG: InstanceController::handlePost() started\n";
        
        // Initialize database and Redis connections
        echo "DEBUG: Initializing database connection\n";
        $pdo = Database::getInstance()->getConnection();
        echo "DEBUG: Database connection established\n";
        
        echo "DEBUG: Initializing Redis connection\n";
        $redis = Redis::getInstance()->getClient();
        echo "DEBUG: Redis connection established\n";
        
        // Define required fields and validate their presence
        $requiredFields = ['app_name', 'app_uid', 'junction_secret', 'domain', 'server'];
        $errors = [];

        echo "DEBUG: Starting validation of required fields\n";
        // Validate presence of required fields
        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $errors[] = "$field is required";
                echo "DEBUG: Missing required field: $field\n";
            }
        }

        // Validate app name format (alphanumeric + underscores)
        echo "DEBUG: Validating app_name format\n";
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $data['app_name'])) {
            $errors[] = "app_name must contain only alphanumeric characters and underscores";
            echo "DEBUG: app_name validation FAILED\n";
        } else {
            echo "DEBUG: app_name validation PASSED\n";
        }

        // Validate app_uid format (alphanumeric only)
        echo "DEBUG: Validating app_uid format\n";
        if (!preg_match('/^[a-zA-Z0-9]+$/', $data['app_uid'])) {
            $errors[] = "app_uid must contain only alphanumeric characters";
            echo "DEBUG: app_uid validation FAILED\n";
        } else {
            echo "DEBUG: app_uid validation PASSED\n";
        }

        // Validate junction_secret length
        echo "DEBUG: Validating junction_secret length\n";
        if (strlen($data['junction_secret']) < 5) {
            $errors[] = "junction_secret must be at least 5 characters long";
            echo "DEBUG: junction_secret validation FAILED (too short)\n";
        } else {
            echo "DEBUG: junction_secret validation PASSED\n";
        }

        // Validate domain format
        echo "DEBUG: Validating domain format\n";
        if (!preg_match('/^[a-zA-Z0-9][a-zA-Z0-9-]{1,61}[a-zA-Z0-9]\.[a-zA-Z]{2,}$/', $data['domain'])) {
            $errors[] = "domain must be a valid domain name";
            echo "DEBUG: domain validation FAILED\n";
        } else {
            echo "DEBUG: domain validation PASSED\n";
        }

        // Validate server format (hostname or IP)
        echo "DEBUG: Validating server format\n";
        if (!preg_match('/^([a-zA-Z0-9][a-zA-Z0-9-]{0,61}[a-zA-Z0-9]\.)+[a-zA-Z]{2,}$|^(\d{1,3}\.){3}\d{1,3}$/', $data['server'])) {
            $errors[] = "server must be a valid hostname or IP address";
            echo "DEBUG: server validation FAILED\n";
        } else {
            echo "DEBUG: server validation PASSED\n";
        }

        // Return validation errors if any
        if (count($errors) > 0) {
            echo "DEBUG: Validation errors found: " . implode(', ', $errors) . "\n";
            return [
                'code' => 400,
                'body' => ['ok' => false, 'errors' => $errors]
            ];
        }

        echo "DEBUG: All validations PASSED, proceeding with instance creation\n";

        // Begin database transaction for atomic operations
        try {
            echo "DEBUG: Starting database transaction\n";
            
            // Lock the dockers table to prevent race conditions during port allocation
            echo "DEBUG: Locking dockers table\n";
            $pdo->exec("LOCK TABLES dockers WRITE");
            
            // Start transaction after locking tables
            $pdo->beginTransaction();

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
            echo "DEBUG: Creating DockerService\n";
            $dockerService = new DockerService($pdo, $redis);
            echo "DEBUG: Calling spawnService\n";
            $dockerService->spawnService($instance);
            echo "DEBUG: Docker service spawned successfully\n";
            
            $instance->setStatus(DockerStatus::PENDING);
            echo "DEBUG: Instance status set to PENDING\n";

            // Commit transaction
            echo "DEBUG: Committing database transaction\n";
            $pdo->commit();
            
            // Unlock tables after successful commit
            echo "DEBUG: Unlocking dockers table\n";
            $pdo->exec("UNLOCK TABLES");
            echo "DEBUG: Database operations completed successfully\n";

        } catch (\Exception $e) {
            echo "DEBUG: Database operation FAILED: " . $e->getMessage() . "\n";
            
            // Rollback transaction on error
            if ($pdo->inTransaction()) {
                echo "DEBUG: Rolling back transaction\n";
                $pdo->rollBack();
            }
            
            // Always unlock tables, even on error
            echo "DEBUG: Unlocking dockers table after error\n";
            $pdo->exec("UNLOCK TABLES");
            
            return [
                'code' => 500,
                'body' => ['ok' => false, 'errors' => $e->getMessage()]
            ];
        }

        // Create Cloudflare DNS records in a separate try-catch
        try {
            echo "DEBUG: Starting Cloudflare DNS setup\n";
            // Initialize Cloudflare service
            echo "DEBUG: Creating CloudflareService\n";
            $cloudflareService = new CloudflareService([
                'apiToken' => $_ENV['CLOUDFLARE_API_TOKEN']
            ]);
            echo "DEBUG: CloudflareService created successfully\n";

            // Create CNAME records for the instance
            echo "DEBUG: Creating CNAME records\n";
            $record1 = new Record(
                type: RecordType::CNAME,
                name: $instance->getAppName(),
                content: $data['server'],
                ttl: 3600,
                proxied: true
            );
            echo "DEBUG: First CNAME record created\n";

            $record2 = new Record(
                type: RecordType::CNAME,
                name: $instance->getAppName() . '.tribe',
                content: $data['server'],
                ttl: 3600,
                proxied: false
            );
            echo "DEBUG: Second CNAME record created\n";

            // Create and execute batch operation for DNS records
            echo "DEBUG: Creating batch operation\n";
            $batch = new Batch();
            $batch->addCreate($record1)
                  ->addCreate($record2);
            echo "DEBUG: Batch operation prepared\n";

            echo "DEBUG: Executing Cloudflare batch operation\n";
            $result = $cloudflareService->executeBatch($_ENV['CLOUDFLARE_ZONE_ID'], $batch);
            echo "DEBUG: Cloudflare batch operation completed: " . json_encode($result) . "\n";

            // Check for Cloudflare API errors
            if (!empty($result['errors'])) {
                echo "DEBUG: Cloudflare API errors found: " . json_encode($result['errors']) . "\n";
                throw new \Exception('Failed to create DNS records: ' . json_encode($result['errors']));
            }

            echo "DEBUG: DNS records created successfully\n";
            // Return success response
            echo "DEBUG: Returning success response\n";
            return [
                'code' => 200,
                'body' => [
                    'ok' => true,
                    'message' => 'Instance created and DNS records added successfully',
                    'instance' => $instance->toArray()
                ]
            ];

        } catch (\Exception $e) {
            echo "DEBUG: DNS setup FAILED: " . $e->getMessage() . "\n";
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

    /**
     * Handle GET requests to retrieve instance information by app_uid.
     * 
     * @param array $params Query parameters containing app_uid
     * @return array Response array with code and body
     */
    public static function handleGet($params)
    {
        echo "DEBUG: InstanceController::handleGet() started\n";
        
        // Initialize database connection
        echo "DEBUG: Initializing database connection\n";
        $pdo = Database::getInstance()->getConnection();
        echo "DEBUG: Database connection established\n";
        
        // Validate required parameter
        if (!isset($params['app_uid']) || empty($params['app_uid'])) {
            echo "DEBUG: Missing required parameter: app_uid\n";
            return [
                'code' => 400,
                'body' => ['ok' => false, 'error' => 'app_uid parameter is required']
            ];
        }
        
        $appUid = $params['app_uid'];
        echo "DEBUG: Looking for instance with app_uid: $appUid\n";
        
        try {
            // Query the database for the instance
            echo "DEBUG: Querying database for instance\n";
            $stmt = $pdo->prepare("SELECT * FROM dockers WHERE slug = :app_uid");
            $stmt->bindParam(':app_uid', $appUid);
            $stmt->execute();
            $instance = $stmt->fetch();
            
            if (!$instance) {
                echo "DEBUG: Instance not found with app_uid: $appUid\n";
                return [
                    'code' => 404,
                    'body' => ['ok' => false, 'error' => 'Instance not found']
                ];
            }
            
            echo "DEBUG: Instance found: " . json_encode($instance) . "\n";
            
            // Format the response
            $response = [
                'id' => $instance['id'],
                'app_name' => $instance['app_name'],
                'app_uid' => $instance['slug'], // slug is the app_uid
                'tribe_port' => $instance['tribe_port'],
                'junction_port' => $instance['junction_port'],
                'status' => $instance['status']
            ];
            
            echo "DEBUG: Returning instance data\n";
            return [
                'code' => 200,
                'body' => [
                    'ok' => true,
                    'instance' => $response
                ]
            ];
            
        } catch (\Exception $e) {
            echo "DEBUG: Database query FAILED: " . $e->getMessage() . "\n";
            return [
                'code' => 500,
                'body' => ['ok' => false, 'error' => 'Database error: ' . $e->getMessage()]
            ];
        }
    }
}   