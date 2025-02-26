<?php
use Tribe\API;
use Gyro\DockerService;

$api = new API();
$docker = new DockerService();

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
 * app_name: string, whitespaces allowed
 * app_uid: alphanumeric string without spaces, must be unique and random
 * junction_secret: secret key for junction
 * domain: service's base domain
 */
post:
    $req = $api->requestBody;

    $app_name = preg_replace('/[^a-zA-Z0-9]/s', '', $req['app_name']); // allow only alphanumeric characters
    $app_name = preg_replace('!\s+!', '_', $app_name); // replace spaces with underscore
    // $app_name .= "_{$req['app_uid']}";

    $ports = $docker->getPorts();

    $res = $docker->spawnService(
        $req['app_name'],
        $app_name,
        $req['app_uid'],
        $req['junction_secret'],
        $req['domain'],
        $ports
    );

    $api->json($res)->send();

get:
    $res = $docker->getJobStatus($_GET['job_id']);

    $api->json($res)->send();
