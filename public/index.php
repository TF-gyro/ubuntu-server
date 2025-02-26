<?php
require_once __DIR__ . "/../vendor/autoload.php";

//dotenv for loading variables in .env as $_ENV
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__, '/../.env');
$dotenv->load();

//php vars file
require_once __DIR__.'/../config.php';

// Simple router logic
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

require_once "router.php";

// Check if the requested route exists
if (array_key_exists($requestUri, $routes)) {
    $routes[$requestUri]();
} else {
    http_response_code(404);
    echo "404 Not Found";
}
?>
