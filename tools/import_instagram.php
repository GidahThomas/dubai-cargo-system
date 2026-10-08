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

require __DIR__ . '/bootstrap.php';

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
