<?php
use Tribe\API;
use Gyro\Controller\InstanceController;

$api = new API();

switch ($api->method()) {
    case 'post':
        goto post;
        break;

    case 'get':
        goto get;
        break;

    default:
        $api->send(405);
        break;
}

/**
 * POST method expects the following params:
 * app_name: string, must contain only alphanumeric characters and underscores
 * app_uid: alphanumeric string without spaces, must be unique
 * junction_secret: secret key for junction
 * domain: service's base domain
 * server: hostname or IP for the server
 */
post:
    $req = $api->requestBody;
    
    // Pass request to controller
    $result = InstanceController::handlePost($req);
    
    //TODO: Send response with appropriate HTTP code
    //$api->status($result['code'])->json($result['body'])->send();

    $api->json($result['body'])->send();

get:
    $res = $docker->getJobStatus($_GET['job_id']);

    $api->json($res)->send();
