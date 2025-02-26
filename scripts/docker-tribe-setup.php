<?php
/**
 * REQUIRED OPTIONS:
 * app_name: webapp name
 * app_uid: unique name of app
 * tribe_port: port to be attached to tribe's docker
 * junction_port: port to be attached to junction's docker
 * db_user: non-root user for database
 * db_pass: mysqldb password
 * db_name: mysqldb name
 * junction_pass: password for junction
 * enable_ssl: bool true/false
 * allow_cross_origin: bool true/false
 * web_url: http/s based domain name
 * junction_url: http/s based url for junction app
 * junction_slug: junction's slug
 */

if (!isset($_SERVER['HTTP_HOST'])) {
    parse_str($argv[1], $_POST);
}

function generateUniqueString($length = 16) {
    // Generate random bytes and convert to hexadecimal
    return bin2hex(random_bytes($length / 2));
}

$APP_NAME= $_POST['app_name'] ?? null;
$APP_UID= $_POST['app_uid'] ?? null;

$TRIBE_PORT= $_POST['tribe_port'] ?? null;
$JUNCTION_PORT= $_POST['junction_port'] ?? null;

$JUNCTION_PASS= $_POST['secret'] ?? null;

$JUNCTION_URL= "{$APP_UID}.{$_POST['domain']}";
$WEB_URL= "{$APP_UID}.tribe.{$_POST['domain']}";

$DB_USER= $_POST['app_name'] ?? null;
$DB_PASS= generateUniqueString(16);
$DB_NAME= $_POST['app_name'] ?? null;
$DB_HOST= $APP_UID."-db";

$TRIBE_API_SECRET_KEY= generateUniqueString(16);

if (!$APP_UID) {
    die("'app_uid' is required");
}

exec("sudo ./nginx-tribe-setup.sh --jport=$JUNCTION_PORT --tport=$TRIBE_PORT --slug=$APP_UID --root=/var/www/gyro --ssl-dir=/var/www/gyro/ssl");

$BASE_DIR = "/mnt/junctions";

if (!is_dir($BASE_DIR)) {
    mkdir($BASE_DIR);
}

chdir($BASE_DIR);

try {
    exec("git clone https://github.com/tf-gyro/docker-tribe-template.git {$APP_UID}");
} catch (\Exception $e) {
    echo "<pre style='color: red;'>";
    var_dump($e);
    echo "</pre>";
    die();
}

$APP_PATH = "{$BASE_DIR}/{$APP_UID}";
chdir($APP_PATH);

$docker_vars = array(
    'APP_UID' => $APP_NAME,
    'DB_USER' => $DB_USER,
    'DB_NAME' => $DB_NAME,
    'DB_PASS' => $DB_PASS,
    'TRIBE_PORT' => $TRIBE_PORT,
    'JUNCTION_PORT' => $JUNCTION_PORT
);

// update docker-compose variables
$docker_compose = file_get_contents("{$APP_PATH}/docker-compose.yml");

foreach ($docker_vars as $key => $value) {
    $docker_compose = str_replace("\${$key}", $value, $docker_compose);
}

file_put_contents("{$APP_PATH}/docker-compose.yml", $docker_compose);

// update .env
copy("{$APP_PATH}/tribe/.env.sample", "{$APP_PATH}/tribe/.env");
$env_file = file_get_contents("{$APP_PATH}/tribe/.env");

$env_vars = array(
    'JUNCTION_PASS' => $JUNCTION_PASS,
    'JUNCTION_URL' => $JUNCTION_URL,
    'APP_UID' => $APP_UID,
    'APP_NAME' => $APP_NAME,
    'WEB_URL' => $WEB_URL,
    'DOCKER_EXTERNAL_TRIBE_URL' => "localhost:$TRIBE_PORT",
    'DOCKER_EXTERNAL_JUNCTION_URL' => "localhost:$JUNCTION_PORT",
    'TRIBE_API_SECRET_KEY' => $TRIBE_API_SECRET_KEY,
    'DB_NAME' => $DB_NAME,
    'DB_USER' => $DB_USER,
    'DB_PASS' => $DB_PASS,
    'DB_HOST' => $DB_HOST
);

foreach ($env_vars as $key => $value) {
    $env_file = str_replace("\${$key}", $value, $env_file);
}

file_put_contents("{$APP_PATH}/tribe/.env", $env_file); // write changes to .env

// update PMA configuration
$pma_config = file_get_contents("{$APP_PATH}/tribe/config.inc.php");
$pma_config = str_replace('$DB_HOST', $DB_HOST, $pma_config);

file_put_contents("{$APP_PATH}/tribe/config.inc.php", $pma_config); // write changes to pma_config

// exec("chown -R www-data: $APP_PATH"); // transfer ownership of app to www-data

exec("docker compose up -d", $output, $status); // start docker app
if ($status !== 0) {
    exit(1); // exit script with failure code when starting docker fails
}

// wait for process to start before importing and applying database structure
sleep(30);
$status = null;

// if status == 0, it was success
while ($status !== 0) {
    exec ("docker exec -i {$DB_HOST} mysql -u{$DB_USER} -p{$DB_PASS} {$DB_NAME} < {$APP_PATH}/tribe/install/db.sql", $output, $status);
    sleep(5);
}
