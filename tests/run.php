<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/*
 * Runs every tests/*Test.php against a throwaway database (DB_TEST_NAME, default "<DB_NAME>_test")
 * that is rebuilt from database/schema.sql plus all migrations on every run. The real
 * database is never touched.
 */

define('APP_DEBUG', true);
require dirname(__DIR__) . '/tools/bootstrap.php';

spl_autoload_register(static function (string $class): void {
    $file = ROOT_PATH . '/tests/' . $class . '.php';
    if (is_file($file)) {
        require $file;
    }
});

$liveDatabase = ($_ENV['DB_NAME'] ?? '') ?: 'dubai_computer_fast_cargo';
$testDatabase = ($_ENV['DB_TEST_NAME'] ?? '') ?: $liveDatabase . '_test';

if (!preg_match('/^[A-Za-z0-9_]+$/', $testDatabase) || $testDatabase === $liveDatabase) {
    fwrite(STDERR, "Refusing to run: the test database must be a separate, simple name (got \"{$testDatabase}\").\n");
    exit(1);
}

$server = new PDO(
    sprintf('mysql:host=%s;charset=utf8mb4', $_ENV['DB_HOST'] ?? '127.0.0.1'),
    $_ENV['DB_USER'] ?? 'root',
    $_ENV['DB_PASS'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);
$server->exec("DROP DATABASE IF EXISTS `{$testDatabase}`");
$server->exec("CREATE DATABASE `{$testDatabase}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
$server->exec("USE `{$testDatabase}`");

$schema = (string) file_get_contents(ROOT_PATH . '/database/schema.sql');
$schema = preg_replace('/^\s*(CREATE DATABASE|USE)\b[^;]*;/mi', '', $schema);
Migrator::runSql($server, (string) $schema);

$_ENV['DB_NAME'] = $testDatabase;
(new Migrator(Database::connect()))->migrate();
echo "Test database {$testDatabase} rebuilt from schema.sql and migrations.\n\n";

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
