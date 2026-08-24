<?php

declare(strict_types=1);

define('ROOT_PATH', dirname(__DIR__));
define('APP_DEBUG', true);

date_default_timezone_set('Africa/Dar_es_Salaam');

require ROOT_PATH . '/app/config/database.php';
require ROOT_PATH . '/app/helpers/Auth.php';

Auth::startSecureSession();

spl_autoload_register(function (string $class): void {
    $folders = [
        ROOT_PATH . '/app/core/',
        ROOT_PATH . '/app/controllers/',
        ROOT_PATH . '/app/models/',
        ROOT_PATH . '/app/services/',
        ROOT_PATH . '/app/middleware/',
    ];

    foreach ($folders as $folder) {
        $file = $folder . $class . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

$app = new App();
$app->dispatch();
