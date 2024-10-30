<?php
/**
 * This script prepares all files to initiate a docker junction instance
 */
$_POST = file_get_contents("php://input");
parse_str($_POST, $or);
shell_exec("echo \"$_POST\" >> /var/www/html/logs/".$or['app_uid']."-tribe-init.txt");
shell_exec("echo ".$or['tribe_port']." >> /var/www/html/logs/".$or['app_uid']."-tribe-port.txt");
shell_exec("echo ".$or['junction_port']." >> /var/www/html/logs/".$or['app_uid']."-junction-port.txt");
shell_exec("echo ".$or['app_uid']." >> /var/www/html/docker-tribe-slugs.txt");
?>
