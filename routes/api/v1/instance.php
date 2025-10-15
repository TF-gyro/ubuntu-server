<?php
use Tribe\API;
use Gyro\Controller\InstanceController;
use Gyro\Middleware\ApiKeyValidator;

// Debug: API endpoint started
echo "DEBUG: API endpoint /api/v1/instance started\n";

// Debug: Show raw request method
echo "DEBUG: Raw REQUEST_METHOD: " . ($_SERVER['REQUEST_METHOD'] ?? 'NOT SET') . "\n";
echo "DEBUG: Raw SERVER data for method detection:\n";
echo "DEBUG: HTTP_X_HTTP_METHOD_OVERRIDE: " . ($_SERVER['HTTP_X_HTTP_METHOD_OVERRIDE'] ?? 'NOT SET') . "\n";
echo "DEBUG: Content-Type: " . ($_SERVER['CONTENT_TYPE'] ?? 'NOT SET') . "\n";

$api = new API();

// Debug: API object created
echo "DEBUG: API object created successfully\n";

// Extract API key from headers
$apiKey = null;

// Check various header formats for API key
if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
    $auth = $_SERVER['HTTP_AUTHORIZATION'];
    if (strpos($auth, 'Bearer ') === 0) {
        $apiKey = substr($auth, 7);
        echo "DEBUG: API key extracted from Authorization header: " . substr($apiKey, 0, 4) . "...\n";
    }
} elseif (isset($_SERVER['HTTP_API_KEY'])) {
    $apiKey = $_SERVER['HTTP_API_KEY'];
    echo "DEBUG: API key extracted from API-Key header: " . substr($apiKey, 0, 4) . "...\n";
}

// Debug: API key extraction completed
echo "DEBUG: API key extraction completed, key present: " . ($apiKey ? 'yes' : 'no') . "\n";

// Validate API key
echo "DEBUG: About to call ApiKeyValidator::validate()\n";
if (!ApiKeyValidator::validate($apiKey)) {
    echo "DEBUG: API key validation FAILED\n";
    header('HTTP/1.1 401 Unauthorized');
    $api->json(['ok' => false, 'error' => 'Invalid or missing API key'])->send();
    exit;
}

// Debug: API key validation passed
echo "DEBUG: API key validation PASSED\n";

// Debug: HTTP method
echo "DEBUG: HTTP method: " . $api->method() . "\n";

switch ($api->method()) {
    case 'post':
        echo "DEBUG: Routing to POST handler\n";
        goto post;

    case 'get':
        echo "DEBUG: Routing to GET handler\n";
        goto get;

    default:
        echo "DEBUG: Unsupported HTTP method: " . $api->method() . "\n";
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
    echo "DEBUG: POST handler started\n";
    
    // Get request body data
    $req = $api->requestBody;
    echo "DEBUG: Request body received: " . json_encode($req) . "\n";
    
    // Process request through controller
    echo "DEBUG: Calling InstanceController::handlePost()\n";
    $result = InstanceController::handlePost($req);
    echo "DEBUG: InstanceController::handlePost() completed, result: " . json_encode($result) . "\n";
    
    // Send response
    echo "DEBUG: Sending response with code: " . $result['code'] . "\n";
    $api->json($result['body'])->send($result['code']);
    echo "DEBUG: Response sent successfully\n";

/**
 * GET endpoint for retrieving instance information by app_uid.
 * 
 * This endpoint handles instance retrieval requests with the following flow:
 * 1. Validates API key from request headers
 * 2. Processes query parameters
 * 3. Delegates to InstanceController for business logic
 * 4. Returns appropriate HTTP response
 * 
 * Required Headers:
 * - Authorization: Bearer <api_key> or
 * - API-Key: <api_key>
 * 
 * Required Query Parameters:
 * - app_uid: string (alphanumeric, unique identifier for the instance)
 * 
 * Example Usage:
 * GET /api/v1/instance?app_uid=app12345
 * 
 * Response Format:
 * {
 *   "ok": true,
 *   "instance": {
 *     "id": 1,
 *     "app_name": "my_application",
 *     "app_uid": "app12345",
 *     "tribe_port": 8080,
 *     "junction_port": 8081,
 *     "status": "pending"
 *   }
 * }
 */
get:
    echo "DEBUG: GET handler started\n";
    
    // Get query parameters
    $params = $_GET;
    echo "DEBUG: Query parameters received: " . json_encode($params) . "\n";
    
    // Process request through controller
    echo "DEBUG: Calling InstanceController::handleGet()\n";
    $result = InstanceController::handleGet($params);
    echo "DEBUG: InstanceController::handleGet() completed, result: " . json_encode($result) . "\n";
    
    // Send response
    echo "DEBUG: Sending response with code: " . $result['code'] . "\n";
    $api->json($result['body'])->send($result['code']);
    echo "DEBUG: GET response sent successfully\n";
