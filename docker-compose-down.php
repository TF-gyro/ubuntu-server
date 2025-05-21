<?php
/**
 * REQUIRED OPTIONS:
 * app_uid: unique name of app
 */

if (!isset($_SERVER['HTTP_HOST'])) {
    parse_str($argv[1], $_POST);
}

$slug = $_POST['app_uid'];

chdir("/mnt/junctions/$slug");

exec("docker compose down"); // bring down composer stack
exec("bash ./install/docker-scripts/setup-network.sh"); // remove network of this stack

chdir("/mnt/junctions");
exec("rm -rf $slug");
