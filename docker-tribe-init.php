<?php
$_POST = file_get_contents("php://input");
shell_exec("echo \"$_POST\" >> /var/www/html/docker-tribe-init.txt");
parse_str($_POST, $_POST);
shell_exec("echo ".$_POST['app_uid']." >> /var/www/html/docker-tribe-slugs.txt");
shell_exec("echo ".$_POST['tribe_port']." >> /var/www/html/docker-tribe-ports.txt");
shell_exec("echo ".$_POST['junction_port']." >> /var/www/html/docker-junction-ports.txt");
?>