<?php
use Tribe\API;
use Gyro\Controller\InstanceController;
use Gyro\Middleware\ApiKeyValidator;

$api = new API();

// Extract API key from headers
$apiKey = null;

// Check various header formats for API key
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (strpos($auth, 'Bearer ') === 0) {
        $apiKey = substr($auth, 7);
    }
} elseif (isset($_SERVER['HTTP_API_KEY'])) {
    $apiKey = $_SERVER['HTTP_API_KEY'];
}

// Validate API key
if (!ApiKeyValidator::validate($apiKey)) {
    header('HTTP/1.1 401 Unauthorized');
    $api->json(['ok' => false, 'error' => 'Invalid or missing API key'])->send();
    exit;
}

switch ($api->method()) {
    case 'post':
        goto post;

    case 'get':
        goto get;

    default:
        $api->send(405);
        break;
}

/**
 * POST endpoint for creating new service instances.
 * 
 * This endpoint handles instance creation requests with the following flow:
 * 1. Validates API key from request headers
 * 2. Processes POST request data
 * 3. Delegates to InstanceController for business logic
 * 4. Returns appropriate HTTP response
 * 
 * Required Headers:
 * - Authorization: Bearer <api_key> or
 * - API-Key: <api_key>
 * 
 * Required POST Parameters:
 * - app_name: string (alphanumeric + underscores)
 * - app_uid: string (alphanumeric, unique)
 * - junction_secret: string (secret key for junction)
 * - domain: string (service's base domain)
 * - server: string (hostname or IP for the server)
 */
post:
    // Get request body data
    $req = $api->requestBody;
    
    // Process request through controller
    $result = InstanceController::handlePost($req);
    
    //TODO: Send response with appropriate HTTP code
    //$api->status($result['code'])->json($result['body'])->send();

    $api->json($result['body'])->send();

get:
    $res = $docker->getJobStatus($_GET['job_id']);

    $api->json($res)->send();
