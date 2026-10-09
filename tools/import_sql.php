<?php

/**
 * Runs a mysqldump file against the database in the settings, over the app's own connection
 * (so TLS for cloud databases such as TiDB Cloud works without extra client tools).
 *
 *   C:\xampp\php\php.exe tools\import_sql.php deploy\database-cloud.sql --env=.env.tidb
 *
 * The target database should be empty: the dump drops and recreates every table it contains.
 */

require __DIR__ . '/bootstrap.php';

$file = null;
foreach (array_slice($argv, 1) as $argument) {
    if (!str_starts_with($argument, '--')) {
        $file = $argument;
    }
}

if ($file === null || !is_file($file)) {
    fwrite(STDERR, "Usage: php tools/import_sql.php <dump.sql> [--env=.env.tidb]\n");
    exit(1);
}

$db = Database::connect();
echo 'Importing into ' . ($_ENV['DB_NAME'] ?? '?') . ' on ' . ($_ENV['DB_HOST'] ?? '?') . "\n";

$handle = fopen($file, 'rb');
$statement = '';
$count = 0;
$lineNumber = 0;

// mysqldump writes one statement per line or ends it with ";" at a line end; newlines inside
// values are escaped as \n, so a line ending in ";" always ends a statement.
while (($line = fgets($handle)) !== false) {
    $lineNumber++;
    if ($lineNumber === 1) {
        $line = preg_replace('/^\xEF\xBB\xBF/', '', $line);
    }
    $trimmed = trim($line);
    if ($statement === '' && ($trimmed === '' || str_starts_with($trimmed, '--'))) {
        continue;
    }

    $statement .= $line;
    if (!str_ends_with($trimmed, ';')) {
        continue;
    }

    try {
        $db->exec($statement);
        $count++;
        if ($count % 50 === 0) {
            echo "  {$count} statements...\n";
        }
    } catch (PDOException $exception) {
        // Session-variable lines from the dump header/footer are optional on other servers.
        if (!preg_match('/^\/\*!\d+\s+SET\b/i', ltrim($statement))) {
            fwrite(STDERR, "Failed near line {$lineNumber}: " . $exception->getMessage() . "\n" . substr($statement, 0, 200) . "\n");
            exit(1);
        }
    }
    $statement = '';
}

fclose($handle);
echo "Done: {$count} statements.\n";
