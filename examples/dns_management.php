<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Gyro\Cloudflare\Record;
use Gyro\Cloudflare\CloudflareService;
use Gyro\Cloudflare\RecordType;

// Replace with your actual Cloudflare API token and Zone ID
$apiToken = 'your-api-token';
$zoneId = 'your-zone-id';

// Create a CloudflareService instance
$cloudflareService = new CloudflareService([
    'apiToken' => $apiToken
]);

try {
    // List all A records
    echo "Listing all A records:\n";
    $aRecords = $cloudflareService->listRecords($zoneId, RecordType::A);
    foreach ($aRecords as $record) {
        echo "ID: {$record->getId()}, Name: {$record->getName()}, Content: {$record->getContent()}\n";
    }

    // List all CNAME records
    echo "\nListing all CNAME records:\n";
    $cnameRecords = $cloudflareService->listRecords($zoneId, RecordType::CNAME);
    foreach ($cnameRecords as $record) {
        echo "ID: {$record->getId()}, Name: {$record->getName()}, Content: {$record->getContent()}\n";
    }

    // Create a new A record
    echo "\nCreating a new A record:\n";
    $newRecord = new Record(
        type: RecordType::A,
        name: 'example.com',
        content: '192.168.1.1',
        proxied: true
    );
    
    $createdRecord = $cloudflareService->createRecord($zoneId, $newRecord);
    echo "Created record: ID: {$createdRecord->getId()}, Name: {$createdRecord->getName()}, Content: {$createdRecord->getContent()}\n";

    // Update the record
    echo "\nUpdating the record:\n";
    $createdRecord->setContent('192.168.1.2');
    $updatedRecord = $cloudflareService->updateRecord($zoneId, $createdRecord->getId(), $createdRecord);
    echo "Updated record: ID: {$updatedRecord->getId()}, Name: {$updatedRecord->getName()}, Content: {$updatedRecord->getContent()}\n";

    // Delete the record
    echo "\nDeleting the record:\n";
    $success = $cloudflareService->deleteRecord($zoneId, $updatedRecord->getId());
    echo $success ? "Record deleted successfully\n" : "Failed to delete record\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
} 