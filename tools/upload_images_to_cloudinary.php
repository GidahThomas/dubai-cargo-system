<?php

/**
 * Moves images that are stored on this server (public/uploads/...) to Cloudinary and saves the
 * Cloudinary address in the database instead. Needed before hosting on Vercel, which has no
 * permanent disk. Safe to run more than once: rows that already hold a web address are skipped.
 *
 * Needs CLOUDINARY_URL in .env, and the .env database settings pointing at the database to update.
 *
 *   C:\xampp\php\php.exe tools\upload_images_to_cloudinary.php --dry-run   (only list what would move)
 *   C:\xampp\php\php.exe tools\upload_images_to_cloudinary.php
 */

require __DIR__ . '/bootstrap.php';

$dryRun = in_array('--dry-run', $argv, true);

if (!MediaStorage::usesCloudinary()) {
    fwrite(STDERR, "CLOUDINARY_URL is not set in .env (format: cloudinary://API_KEY:API_SECRET@CLOUD_NAME).\n");
    exit(1);
}

$db = Database::connect();

// table => image column (all hold either "uploads/..." or a full https address)
$columns = [
    'products' => 'image',
    'product_images' => 'image_path',
    'quotation_items' => 'product_image',
    'invoice_items' => 'product_image',
    'invoice_settings' => 'logo_path',
    'instagram_post_media' => 'media_path',
];

$uploaded = [];   // local path => Cloudinary URL, so a file used in several rows is uploaded once
$moved = 0;
$missing = 0;
$failed = 0;

foreach ($columns as $table => $column) {
    try {
        $paths = $db->query("SELECT DISTINCT {$column} FROM {$table} WHERE {$column} IS NOT NULL AND {$column} <> '' AND {$column} NOT LIKE 'http%'")
            ->fetchAll(PDO::FETCH_COLUMN);
    } catch (PDOException) {
        echo "- {$table}: table not found, skipped\n";
        continue;
    }

    foreach ($paths as $path) {
        $file = ROOT_PATH . '/public/' . ltrim((string) $path, '/');
        $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));

        if (!in_array($extension, MediaStorage::IMAGE_EXTENSIONS, true)) {
            echo "- {$table}: {$path} is not a photo (kept on the server)\n";
            continue;
        }
        if (!is_file($file)) {
            echo "! {$table}: {$path} - file not found\n";
            $missing++;
            continue;
        }

        if ($dryRun) {
            echo "  {$table}: {$path} would be uploaded\n";
            continue;
        }

        if (!isset($uploaded[$path])) {
            $prefix = pathinfo($file, PATHINFO_FILENAME);
            $url = MediaStorage::storeUpload($file, $prefix, $extension);
            if ($url === null) {
                echo "! {$table}: {$path} - " . MediaStorage::$lastError . "\n";
                $failed++;
                continue;
            }
            $uploaded[$path] = $url;
        }

        $update = $db->prepare("UPDATE {$table} SET {$column} = ? WHERE {$column} = ?");
        $update->execute([$uploaded[$path], $path]);
        echo "  {$table}: {$path} -> {$uploaded[$path]}\n";
        $moved++;
    }
}

echo $dryRun
    ? "\nDry run only. Run again without --dry-run to upload.\n"
    : "\nDone: {$moved} updated, " . count($uploaded) . " files uploaded, {$missing} missing, {$failed} failed.\n";

exit($failed > 0 ? 1 : 0);
