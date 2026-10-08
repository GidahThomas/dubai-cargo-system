<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * Database migrations.
 *
 *   C:\xampp\php\php.exe tools\migrate.php           apply pending migrations
 *   C:\xampp\php\php.exe tools\migrate.php status    list applied and pending migrations
 *
 * New install: import database/schema.sql (or schema_hosting.sql), then run this script.
 */

require __DIR__ . '/bootstrap.php';

$migrator = new Migrator(Database::connect());

if (($argv[1] ?? '') === 'status') {
    foreach ($migrator->status() as $name => $appliedAt) {
        printf("%-10s %s%s\n", $appliedAt ? 'applied' : 'PENDING', $name, $appliedAt ? "  ({$appliedAt})" : '');
    }
    exit(0);
}

try {
    $ran = $migrator->migrate();
} catch (Throwable $exception) {
    fwrite(STDERR, 'Migration failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

echo $ran ? 'Applied: ' . implode(', ', $ran) . "\n" : "Database is up to date.\n";
