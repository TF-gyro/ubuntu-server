<?php
$_POST = file_get_contents("php://input");
parse_str($_POST, $or);
shell_exec("echo ".$or['app_uid']." >> /var/www/html/docker-tribe-destroy-slugs.txt");
?>