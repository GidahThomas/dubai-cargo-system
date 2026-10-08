<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * Creates missing thumbnails for product photos and gallery images.
 * Needs PHP's GD extension; XAMPP ships it switched off, so run with it enabled:
 *
 *   C:\xampp\php\php.exe -d extension=gd tools\make_thumbnails.php
 *
 * Scheduled every 30 minutes by tools/schedule_tasks.ps1.
 */

require __DIR__ . '/bootstrap.php';

if (!Thumbnail::available()) {
    fwrite(STDERR, "GD is not enabled. Run: php -d extension=gd tools/make_thumbnails.php\n");
    exit(1);
}

$db = Database::connect();
$paths = array_merge(
    $db->query('SELECT image FROM products WHERE image IS NOT NULL AND image != ""')->fetchAll(PDO::FETCH_COLUMN),
    $db->query('SELECT image_path FROM product_images')->fetchAll(PDO::FETCH_COLUMN),
    $db->query('SELECT media_path FROM instagram_post_media WHERE media_type = "image"')->fetchAll(PDO::FETCH_COLUMN)
);

$made = 0;
$failed = 0;
foreach (array_unique($paths) as $path) {
    if (preg_match('#^https?://#i', $path)) {
        continue;
    }
    Thumbnail::make($path) ? $made++ : $failed++;
}

if ($made || $failed) {
    echo date('Y-m-d H:i:s') . " thumbnails ready: {$made}" . ($failed ? ", could not make {$failed}" : '') . "\n";
}
