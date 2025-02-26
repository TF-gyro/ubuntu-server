<?php
$_POST = file_get_contents("php://input");
parse_str($_POST, $or);

// check if sysinfo directory exists, else create a new directory
// if (
//     !file_exists("/mnt/junctions/{$or['app_uid']}/tribe/sysinfo") &&
//     !is_dir("/mnt/junctions/{$or['app_uid']}/tribe/sysinfo")
// ) {
//     mkdir("/mnt/junctions/{$or['app_uid']}/tribe/sysinfo");
// }

$size = shell_exec("du -sh /mnt/junctions/{$or['app_uid']} | awk '{print $1}'");
echo $size;
?>
