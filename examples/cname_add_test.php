<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use Gyro\Cloudflare\Record;
use Gyro\Cloudflare\CloudflareService;
use Gyro\Cloudflare\RecordType;
use Gyro\Cloudflare\Batch;

// Load environment variables from .env file
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->load();

// Replace with your actual Cloudflare API token and Zone ID
$apiToken = $_ENV['CLOUDFLARE_API_TOKEN'];
$zoneId = $_ENV['CLOUDFLARE_ZONE_ID'];

echo "Using Zone ID: {$zoneId}\n";

// Create a CloudflareService instance
$cloudflareService = new CloudflareService([
    'apiToken' => $apiToken
]);

try {
    // Create first CNAME record
    $record1 = new Record(
        type: RecordType::CNAME,
        name: 'subdomaintest',
        content: 'truearch.io',
        ttl: 3600,
        proxied: true
    );

    // Create second CNAME record
    $record2 = new Record(
        type: RecordType::CNAME,
        name: 'subdomaintest.tribe',
        content: 'truearch.io',
        ttl: 3600,
        proxied: false
    );

    // Create a new batch and add both records
    $batch = new Batch();
    $batch->addCreate($record1)
          ->addCreate($record2);

    // Debug: Print the operations that will be sent
    echo "\nBatch operations to be sent:\n";
    print_r($batch->getOperationsArray());
    echo "\n";

    // Execute the batch
    echo "Executing batch operation...\n";
    $result = $cloudflareService->executeBatch($zoneId, $batch);

    // Process results
    echo "\nAPI Response:\n";
    print_r($result);
    
    // Check if we have a success_count in the response
    if (isset($result['success_count'])) {
        echo "\nSummary:\n";
        echo "Success count: " . $result['success_count'] . "\n";
        echo "Failure count: " . ($result['error_count'] ?? 0) . "\n";
    }

    // If there are errors, display them
    if (!empty($result['errors'])) {
        echo "\nErrors encountered:\n";
        foreach ($result['errors'] as $error) {
            echo "- " . ($error['message'] ?? 'Unknown error') . "\n";
            if (isset($error['code'])) {
                echo "  Code: " . $error['code'] . "\n";
            }
        }
    }

    // If there are warnings, display them
    if (!empty($result['warnings'])) {
        echo "\nWarnings:\n";
        foreach ($result['warnings'] as $warning) {
            echo "- " . ($warning['message'] ?? 'Unknown warning') . "\n";
        }
    }

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
} 