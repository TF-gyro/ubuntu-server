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
 * web_bare_url: domain name without http/s
 * web_url: http/s based domain name
 * junction_url: http/s based url for junction app
 */

if (!isset($_SERVER['HTTP_HOST'])) {
    parse_str($argv[1], $_POST);
}

$APP_NAME= $_POST['app_name'] ?? null;
$APP_UID= $_POST['app_uid'] ?? null;
$TRIBE_PORT= $_POST['tribe_port'] ?? null;

$JUNCTION_PORT= $_POST['junction_port'] ?? null;
$JUNCTION_PASS= $_POST['junction_pass'] ?? null;
$JUNCTION_URL= $_POST['junction_url'] ?? null;

$DB_USER= $_POST['db_user'] ?? null;
$DB_PASS= $_POST['db_pass'] ?? null;
$DB_NAME= $_POST['db_name'] ?? null;
$DB_HOST= $APP_UID."-db";

$ENABLE_SSL= $_POST['enable_ssl'] ?? true;
$ALLOW_CROSS_ORIGIN= $_POST['allow_cross_origin'] ?? false;
$WEB_BARE_URL= $_POST['web_bare_url'] ?? null;
$WEB_URL= $_POST['web_url'] ?? null;

$TRIBE_API_SECRET_KEY= $_POST['tribe_secret'] ?? null;

if (!$APP_UID) {
    die("'app_uid' is required");
}

$BASE_DIR = "/mnt/junctions";

if (!is_dir($BASE_DIR)) {
    mkdir($BASE_DIR);
}

chdir($BASE_DIR);

try {
    exec("git clone https://github.com/tribe-framework/docker-tribe-template.git {$APP_UID}");
} catch (\Exception $e) {
    echo "<pre style='color: red;'>";
    var_dump($e);
    echo "</pre>";
    die();
}

$APP_PATH = "{$BASE_DIR}/{$APP_UID}";
chdir($APP_PATH);

// update docker-compose variables
$docker_compose = file_get_contents("{$APP_PATH}/docker-compose.yml");
$docker_compose = str_replace("\$APP_UID", $APP_NAME, $docker_compose);
$docker_compose = str_replace("\$DB_USER", $DB_USER, $docker_compose);
$docker_compose = str_replace("\$DB_NAME", $DB_NAME, $docker_compose);
$docker_compose = str_replace("\$DB_PASS", $DB_PASS, $docker_compose);
$docker_compose = str_replace("\$APP_PORT", $APP_PORT, $docker_compose);
$docker_compose = str_replace("\$JUNCTION_PORT", $JUNCTION_PORT, $docker_compose);

file_put_contents("{$APP_PATH}/docker-compose.yml", $docker_compose);

// update .env
copy("{$APP_PATH}/tribe/.env.sample", "{$APP_PATH}/tribe/.env");
$env_file = file_get_contents("{$APP_PATH}/tribe/.env");
$env_file = str_replace("\$ALLOW_CROSS_ORIGIN", $ALLOW_CROSS_ORIGIN, $env_file);
$env_file = str_replace("\$ENABLE_SSL", $ENABLE_SSL, $env_file);

$env_file = str_replace("\$JUNCTION_PASS", $JUNCTION_PASS, $env_file);
$env_file = str_replace("\$JUNCTION_URL", $JUNCTION_URL, $env_file);

$env_file = str_replace("\$APP_NAME", $APP_NAME, $env_file);
$env_file = str_replace("\$WEB_BARE_URL", $WEB_BARE_URL, $env_file);
$env_file = str_replace("\$WEB_URL", $WEB_URL, $env_file);
$env_file = str_replace("\$DOCKER_EXTERNAL_TRIBE_URL", "localhost:$TRIBE_PORT", $env_file);
$env_file = str_replace("\$DOCKER_EXTERNAL_JUNCTION_URL", "localhost:$JUNCTION_PORT", $env_file);
$env_file = str_replace("\$TRIBE_API_SECRET_KEY", $TRIBE_API_SECRET_KEY, $env_file);

$env_file = str_replace("\$DB_NAME", $DB_NAME, $env_file);
$env_file = str_replace("\$DB_USER", $DB_USER, $env_file);
$env_file = str_replace("\$DB_PASS", $DB_PASS, $env_file);
$env_file = str_replace("\$DB_HOST", $DB_HOST, $env_file);

file_put_contents("{$APP_PATH}/tribe/.env", $env_file);

$pma_config = file_get_contents("{$APP_PATH}/tribe/config.inc.php");
$env_file = str_replace("\$DB_HOST", $DB_HOST, $pma_config);

file_put_contents("{$APP_PATH}/tribe/config.inc.php", $pma_config);

exec("docker compose up -d");

exec("sleep 10; docker exec -i {$DB_HOST} mysql -u{$DB_USER} -p{$DB_PASS} {$DB_NAME} < {$APP_PATH}/tribe/install/db.sql");
