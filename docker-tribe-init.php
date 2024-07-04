<?php
$_POST = file_get_contents("php://input");
shell_exec("php docker-tribe-setup.php \"$_POST\"");
?>