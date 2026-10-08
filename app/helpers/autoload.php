<?php

/**
 * Class loader shared by the website, the tests and the command-line tools.
 * When Composer is installed (vendor/autoload.php), its classmap loads classes first
 * and this loader only covers anything not in the map.
 */
spl_autoload_register(static function (string $class): void {
    foreach (['core', 'controllers', 'models', 'services', 'middleware'] as $folder) {
        $file = ROOT_PATH . '/app/' . $folder . '/' . $class . '.php';
        if (is_file($file)) {
            require $file;
            return;
        }
    }
});
