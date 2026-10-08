<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * Import an Instagram "Download your information" export (JSON) into the gallery.
 *
 * Usage (from the project folder):
 *   C:\xampp\php\php.exe tools\import_instagram.php [export-folder] [--no-products]
 *
 * The export folder defaults to storage/instagram-export.
 */

define('ROOT_PATH', dirname(__DIR__));

require ROOT_PATH . '/app/helpers/Env.php';
load_env(ROOT_PATH . '/.env');
define('APP_DEBUG', true);

date_default_timezone_set('Africa/Dar_es_Salaam');

require ROOT_PATH . '/app/config/database.php';
require ROOT_PATH . '/app/helpers/Auth.php';

spl_autoload_register(function (string $class): void {
    foreach (['core', 'controllers', 'models', 'services'] as $folder) {
        $file = ROOT_PATH . '/app/' . $folder . '/' . $class . '.php';
        if (file_exists($file)) {
            require $file;
            return;
        }
    }
});

$arguments = array_slice($argv, 1);
$createProducts = !in_array('--no-products', $arguments, true);
$folders = array_values(array_filter($arguments, static fn (string $arg): bool => !str_starts_with($arg, '--')));
$exportDir = $folders[0] ?? InstagramImporter::DEFAULT_EXPORT_DIR;

echo "Importing from {$exportDir}" . ($createProducts ? ' (with draft products)' : '') . "...\n";

try {
    $stats = (new InstagramImporter($createProducts))->import($exportDir);
} catch (Throwable $exception) {
    fwrite(STDERR, 'Import failed: ' . $exception->getMessage() . "\n");
    exit(1);
}

printf(
    "Posts found: %d\nImported: %d\nAlready imported (skipped): %d\nWithout usable media (skipped): %d\nFiles copied: %d\nDraft products created: %d\n",
    $stats['posts_found'],
    $stats['imported'],
    $stats['skipped_existing'],
    $stats['skipped_no_media'],
    $stats['media_copied'],
    $stats['products_created']
);

foreach (array_slice($stats['warnings'], 0, 20) as $warning) {
    echo "Warning: {$warning}\n";
}
