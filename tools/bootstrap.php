<?php

/**
 * Shared start-up for command-line tools: loads .env, the database, helpers and the class autoloader.
 */

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('ROOT_PATH', dirname(__DIR__));

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

require_once ROOT_PATH . '/app/helpers/Env.php';

// --env=.env.tidb runs the tool against another set of settings (e.g. the cloud database);
// anything that file leaves out still comes from .env.
foreach ($argv ?? [] as $argument) {
    if (str_starts_with($argument, '--env=')) {
        $envFile = substr($argument, 6);
        $envPath = is_file($envFile) ? $envFile : ROOT_PATH . '/' . $envFile;
        if (!is_file($envPath)) {
            fwrite(STDERR, "Settings file not found: {$envFile}\n");
            exit(1);
        }
        load_env($envPath);
    }
}
load_env(ROOT_PATH . '/.env');

if (!defined('APP_DEBUG')) {
    define('APP_DEBUG', true);
}

date_default_timezone_set('Africa/Dar_es_Salaam');

require_once ROOT_PATH . '/app/config/database.php';
require_once ROOT_PATH . '/app/helpers/Auth.php';
require_once ROOT_PATH . '/app/helpers/autoload.php';
