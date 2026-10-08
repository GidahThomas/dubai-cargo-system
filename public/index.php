<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));

if (file_exists(ROOT_PATH . '/vendor/autoload.php')) {
    require ROOT_PATH . '/vendor/autoload.php';
}

require ROOT_PATH . '/app/helpers/Env.php';
load_env(ROOT_PATH . '/.env');

// Off unless explicitly enabled, so a server without .env never shows technical errors.
define('APP_DEBUG', filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN));

date_default_timezone_set('Africa/Dar_es_Salaam');

require ROOT_PATH . '/app/config/database.php';
require ROOT_PATH . '/app/helpers/Auth.php';
require ROOT_PATH . '/app/helpers/autoload.php';

ErrorReporter::register();

// FORCE_HTTPS=true (once the site has an SSL certificate): redirect plain HTTP and tell browsers to stay on HTTPS.
if (filter_var($_ENV['FORCE_HTTPS'] ?? false, FILTER_VALIDATE_BOOLEAN)) {
    if (!Auth::isHttpsRequest()) {
        header('Location: https://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
        exit;
    }
    header('Strict-Transport-Security: max-age=31536000');
}

Auth::startSecureSession();

$app = new App();
$app->dispatch();
