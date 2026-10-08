<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

define('ROOT_PATH', dirname(__DIR__));
define('APP_DEBUG', true);

require ROOT_PATH . '/app/helpers/Env.php';
load_env(ROOT_PATH . '/.env');

date_default_timezone_set('Africa/Dar_es_Salaam');

require ROOT_PATH . '/app/config/database.php';
require ROOT_PATH . '/app/helpers/Auth.php';

spl_autoload_register(function (string $class): void {
    $folders = [
        ROOT_PATH . '/app/core/',
        ROOT_PATH . '/app/controllers/',
        ROOT_PATH . '/app/models/',
        ROOT_PATH . '/app/services/',
        ROOT_PATH . '/app/middleware/',
        ROOT_PATH . '/tests/',
    ];

    foreach ($folders as $folder) {
        $file = $folder . $class . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

$testFiles = glob(ROOT_PATH . '/tests/*Test.php');
sort($testFiles);

$totalPassed = 0;
$totalFailed = 0;
$totalAssertions = 0;

foreach ($testFiles as $file) {
    $className = basename($file, '.php');
    require_once $file;

    /** @var TestCase $instance */
    $instance = new $className();
    $results = $instance->run();

    foreach ($results as $result) {
        $totalAssertions += $result['assertions'];

        if ($result['passed']) {
            $totalPassed++;
            echo "  PASS  {$result['name']} ({$result['assertions']} assertions)\n";
            continue;
        }

        $totalFailed++;
        echo "  FAIL  {$result['name']}\n";

        foreach ($result['failures'] as $failure) {
            echo "        {$failure}\n";
        }
    }
}

$total = $totalPassed + $totalFailed;
echo "\n" . str_repeat('-', 60) . "\n";
echo "Tests: {$total}, Passed: {$totalPassed}, Failed: {$totalFailed}, Assertions: {$totalAssertions}\n";

exit($totalFailed > 0 ? 1 : 0);
