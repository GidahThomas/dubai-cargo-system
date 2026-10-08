<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * Backs up the database and public/uploads, keeps the newest BACKUP_KEEP sets, and optionally
 * copies each set to a second place (BACKUP_COPY_DIR, e.g. a OneDrive folder or USB drive).
 *
 *   C:\xampp\php\php.exe tools\backup.php            make a backup
 *   C:\xampp\php\php.exe tools\backup.php verify     restore the newest backup into a scratch
 *                                                    database and compare row counts with live
 *
 * Settings (.env): BACKUP_DIR (default storage/backups), BACKUP_KEEP (default 14),
 * BACKUP_COPY_DIR (optional), MYSQL_BIN_DIR (default C:\xampp\mysql\bin).
 */

require __DIR__ . '/bootstrap.php';

$backupDir = rtrim($_ENV['BACKUP_DIR'] ?? '', '\\/') ?: ROOT_PATH . '/storage/backups';
$keep = max(1, (int) ($_ENV['BACKUP_KEEP'] ?? 14));
$copyDir = rtrim(trim($_ENV['BACKUP_COPY_DIR'] ?? ''), '\\/');
$binDir = rtrim($_ENV['MYSQL_BIN_DIR'] ?? 'C:\\xampp\\mysql\\bin', '\\/');
$database = $_ENV['DB_NAME'] ?? 'dubai_computer_fast_cargo';

function fail(string $message): never
{
    fwrite(STDERR, 'Backup error: ' . $message . "\n");
    error_log('Backup error: ' . $message);
    exit(1);
}

function mysqlCredentials(): string
{
    $args = '--host=' . escapeshellarg($_ENV['DB_HOST'] ?? '127.0.0.1') . ' --user=' . escapeshellarg($_ENV['DB_USER'] ?? 'root');
    if (($_ENV['DB_PASS'] ?? '') !== '') {
        $args .= ' --password=' . escapeshellarg($_ENV['DB_PASS']);
    }

    return $args;
}

function tool(string $binDir, string $name): string
{
    foreach ([$binDir . DIRECTORY_SEPARATOR . $name . '.exe', $binDir . DIRECTORY_SEPARATOR . $name] as $path) {
        if (is_file($path)) {
            return escapeshellarg($path);
        }
    }

    return $name;
}

if (($argv[1] ?? '') === 'verify') {
    $dumps = glob($backupDir . '/*/database.sql.gz') ?: [];
    rsort($dumps, SORT_STRING);
    $latest = $dumps[0] ?? fail('No backups found in ' . $backupDir);

    $scratch = $database . '_restore_check';
    $server = new PDO(sprintf('mysql:host=%s;charset=utf8mb4', $_ENV['DB_HOST'] ?? '127.0.0.1'), $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $server->exec("DROP DATABASE IF EXISTS `{$scratch}`");
    $server->exec("CREATE DATABASE `{$scratch}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

    $plain = tempnam(sys_get_temp_dir(), 'restore');
    file_put_contents($plain, gzdecode((string) file_get_contents($latest)));
    $command = tool($binDir, 'mysql') . ' ' . mysqlCredentials() . ' ' . escapeshellarg($scratch) . ' < ' . escapeshellarg($plain) . ' 2>&1';
    exec($command, $output, $code);
    unlink($plain);
    if ($code !== 0) {
        fail('Restore failed: ' . implode(' ', $output));
    }

    $mismatches = 0;
    $tables = $server->query("SELECT table_name FROM information_schema.tables WHERE table_schema = '{$database}' AND table_type = 'BASE TABLE' ORDER BY table_name")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $table) {
        $live = (int) $server->query("SELECT COUNT(*) FROM `{$database}`.`{$table}`")->fetchColumn();
        $restored = (int) $server->query("SELECT COUNT(*) FROM `{$scratch}`.`{$table}`")->fetchColumn();
        if ($live !== $restored) {
            $mismatches++;
            echo "  differs  {$table}: live {$live}, backup {$restored}\n";
        }
    }
    $server->exec("DROP DATABASE `{$scratch}`");

    echo 'Restored ' . basename(dirname($latest)) . ' into a scratch database: ' . count($tables) . ' tables, ';
    echo $mismatches === 0 ? "row counts match the live database.\n" : "{$mismatches} differ (normal if data changed since the backup).\n";
    exit(0);
}

$stamp = date('Y-m-d_His');
$target = $backupDir . '/' . $stamp;
if (!is_dir($target) && !mkdir($target, 0775, true)) {
    fail('Cannot create ' . $target);
}

// 1. Database: consistent snapshot without locking the shop.
$sqlFile = $target . '/database.sql';
$command = tool($binDir, 'mysqldump') . ' ' . mysqlCredentials()
    . ' --single-transaction --routines --triggers --default-character-set=utf8mb4 '
    . escapeshellarg($database) . ' > ' . escapeshellarg($sqlFile) . ' 2>&1';
exec($command, $output, $code);
if ($code !== 0 || !is_file($sqlFile) || filesize($sqlFile) < 1000) {
    fail('mysqldump failed: ' . (is_file($sqlFile) ? substr((string) file_get_contents($sqlFile), 0, 300) : implode(' ', $output)));
}
file_put_contents($sqlFile . '.gz', gzencode((string) file_get_contents($sqlFile), 9));
unlink($sqlFile);

// 2. Uploaded images and logos.
$uploadsArchive = $target . '/uploads.tar';
$tar = new PharData($uploadsArchive);
$tar->buildFromDirectory(ROOT_PATH . '/public/uploads');
$tar->compress(Phar::GZ);
unset($tar);
unlink($uploadsArchive);

$size = array_sum(array_map('filesize', glob($target . '/*') ?: []));
echo sprintf("Backup %s written to %s (%.1f MB).\n", $stamp, $target, $size / 1048576);

// 3. Optional second copy, off this machine.
if ($copyDir !== '') {
    $copyTarget = $copyDir . '/' . $stamp;
    if (!is_dir($copyTarget) && !mkdir($copyTarget, 0775, true)) {
        fail('Cannot create copy folder ' . $copyTarget);
    }
    foreach (glob($target . '/*') ?: [] as $file) {
        copy($file, $copyTarget . '/' . basename($file)) || fail('Copy to ' . $copyTarget . ' failed');
    }
    echo "Copied to {$copyTarget}.\n";
}

// 4. Retention: keep the newest $keep sets in each location.
foreach (array_filter([$backupDir, $copyDir]) as $dir) {
    $sets = array_filter(glob($dir . '/*') ?: [], 'is_dir');
    rsort($sets, SORT_STRING);
    foreach (array_slice($sets, $keep) as $old) {
        array_map('unlink', glob($old . '/*') ?: []);
        rmdir($old);
    }
}

(new AuditLog())->create(null, 'backup_created', null, null, $stamp . ' (' . round($size / 1048576, 1) . ' MB)');
