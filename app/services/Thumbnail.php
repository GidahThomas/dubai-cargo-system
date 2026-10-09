<?php

/**
 * Small WebP copies of uploaded images for listing pages (catalogue, gallery, cart), so a page of
 * products loads ~30-60 KB per image instead of full-size photos.
 *
 * Thumbnails live in public/uploads/thumbs/ and are made by tools/make_thumbnails.php (scheduled),
 * or straight after upload when PHP's GD extension is enabled for the web server.
 */
final class Thumbnail
{
    public const MAX_SIZE = 480;
    private const QUALITY = 78;
    private const DIR = 'uploads/thumbs';

    /**
     * Public URL of the thumbnail when it exists, otherwise of the original image.
     */
    public static function url(?string $path): string
    {
        $path = (string) $path;
        if (MediaStorage::isCloudinaryUrl($path)) {
            // Cloudinary resizes on request: same 480px limit, best format for the browser.
            return MediaStorage::cloudinaryVariant($path, 'c_limit,w_' . self::MAX_SIZE . ',f_auto,q_auto');
        }
        if ($path === '' || preg_match('#^https?://#i', $path)) {
            return public_url($path);
        }

        $thumb = self::thumbPath($path);

        return is_file(ROOT_PATH . '/public/' . $thumb) ? public_url($thumb) : public_url($path);
    }

    public static function available(): bool
    {
        return extension_loaded('gd') && function_exists('imagewebp');
    }

    /**
     * Creates the thumbnail if it is missing or older than the original. Returns true when one exists afterwards.
     */
    public static function make(string $path): bool
    {
        $source = ROOT_PATH . '/public/' . ltrim($path, '/');
        $target = ROOT_PATH . '/public/' . self::thumbPath($path);

        if (!is_file($source)) {
            return false;
        }
        if (is_file($target) && filemtime($target) >= filemtime($source)) {
            return true;
        }
        if (!self::available()) {
            return false;
        }

        $info = @getimagesize($source);
        if (!$info) {
            return false;
        }

        $image = match ($info[2]) {
            IMAGETYPE_JPEG => @imagecreatefromjpeg($source),
            IMAGETYPE_PNG => @imagecreatefrompng($source),
            IMAGETYPE_WEBP => @imagecreatefromwebp($source),
            IMAGETYPE_GIF => @imagecreatefromgif($source),
            default => false,
        };
        if (!$image) {
            return false;
        }

        [$width, $height] = [$info[0], $info[1]];
        $scale = min(1, self::MAX_SIZE / max($width, $height));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $thumb = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($thumb, false);
        imagesavealpha($thumb, true);
        imagecopyresampled($thumb, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        if (!is_dir(dirname($target))) {
            mkdir(dirname($target), 0775, true);
        }
        $saved = imagewebp($thumb, $target, self::QUALITY);
        imagedestroy($image);
        imagedestroy($thumb);

        return $saved;
    }

    private static function thumbPath(string $path): string
    {
        $relative = preg_replace('#^/?uploads/#', '', ltrim($path, '/'));

        return self::DIR . '/' . preg_replace('/\.[A-Za-z0-9]+$/', '', (string) $relative) . '.webp';
    }
}
