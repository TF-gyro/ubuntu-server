<?php

namespace Gyro\Controller;
use Gyro\Database;
use Gyro\Dto\InstanceDTO;
use Gyro\Service\PortService;
use Gyro\Service\DockerService;
use Gyro\Redis;
class InstanceController {
    public static function handlePost($data){
        $pdo = Database::getInstance()->getConnection();
        $redis = Redis::getInstance()->getClient();
        $requiredFields = ['app_name', 'app_uid', 'junction_secret', 'domain'];
        $errors = [];

        foreach ($requiredFields as $field) {
            if (!isset($data[$field])) {
                $errors[] = "$field is required";
            }
        }

        if (count($errors) > 0) {
            return [
                'code' => 400,
                'body' => ['ok' => false, 'errors' => $errors]
            ];
        }

        //Sanatize and prepare data         
        $app_name = preg_replace('/[^a-zA-Z0-9]/s', '', $data['app_name']); // allow only alphanumeric characters
        $app_name = preg_replace('!\s+!', '_', $app_name); // replace spaces with underscore

        // get ports and lock table. find free ports and spawn service. Then update table with ports and release lock.
        // lock dockers table
        // get ports 
        // pass to dockerService to spawn services
        // update table with ports and release lock

        try {
            $pdo->beginTransaction();

            // lock dockers table to avoid race condition
            $pdo->exec("LOCK TABLES dockers");

            $portService = new PortService($pdo);
            $ports = $portService->getAvailablePorts();

            $instance = new InstanceDTO(
                $app_name,
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
            $pdo->rollBack();
            $pdo->exec("UNLOCK TABLES");
            return [
                'code' => 500,
                'body' => ['ok' => false, 'errors' => $e->getMessage()]
            ];
        }

        // call cloudflare service and return response
    }
}   