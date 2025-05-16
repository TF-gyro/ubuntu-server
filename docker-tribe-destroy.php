<?php
$_POST = file_get_contents("php://input");

$slug = explode("&", $_POST);
$slug = array_pop($slug);

parse_str($slug, $or);

shell_exec("echo {$or['app_uid']} >> /var/www/html/docker-tribe-destroy-slugs.txt");
?>
