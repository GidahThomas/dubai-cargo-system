<?php

/**
 * Where uploaded images (product photos, company logo) are kept.
 *
 * - Default: public/uploads on this server; the stored value is a relative path like "uploads/x.jpg".
 * - CLOUDINARY_URL set (cloudinary://API_KEY:API_SECRET@CLOUD_NAME): images go to Cloudinary and
 *   the stored value is the full https URL. Needed on hosts without a permanent disk (Vercel).
 *
 * Everything that displays images already accepts both forms (public_url, Thumbnail::url, PDFs).
 */
final class MediaStorage
{
    public const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];

    public static ?string $lastError = null;

    public static function usesCloudinary(): bool
    {
        return self::cloudinaryConfig() !== null;
    }

    /**
     * Stores one uploaded file. Returns the value to save in the database, or null on failure.
     */
    public static function storeUpload(string $tmpPath, string $prefix, string $extension): ?string
    {
        self::$lastError = null;
        $extension = strtolower($extension);
        if (!in_array($extension, self::IMAGE_EXTENSIONS, true) || !is_uploaded_file($tmpPath) && !is_file($tmpPath)) {
            self::$lastError = 'Unsupported or missing file.';
            return null;
        }

        $safePrefix = strtolower(preg_replace('/[^a-z0-9-]+/i', '-', $prefix) ?: 'image');
        $name = $safePrefix . '-' . time() . '-' . random_int(1000, 9999);

        if (self::usesCloudinary()) {
            return self::uploadToCloudinary($tmpPath, $name);
        }

        $dir = ROOT_PATH . '/public/uploads';
        if (!is_dir($dir) && !mkdir($dir, 0775, true) && !is_dir($dir)) {
            self::$lastError = 'The uploads folder is not writable.';
            return null;
        }

        $fileName = $name . '.' . $extension;
        $moved = is_uploaded_file($tmpPath) ? move_uploaded_file($tmpPath, $dir . '/' . $fileName) : copy($tmpPath, $dir . '/' . $fileName);
        if (!$moved) {
            self::$lastError = 'Could not save the file.';
            return null;
        }

        Thumbnail::make('uploads/' . $fileName);

        return 'uploads/' . $fileName;
    }

    /**
     * Cloudinary signed-upload signature: SHA-1 of the sorted "key=value" parameters joined with
     * "&", followed by the API secret (https://cloudinary.com/documentation/authentication_signatures).
     */
    public static function cloudinarySignature(array $params, string $apiSecret): string
    {
        ksort($params);
        $pairs = [];
        foreach ($params as $key => $value) {
            if ($value !== null && $value !== '') {
                $pairs[] = $key . '=' . $value;
            }
        }

        return sha1(implode('&', $pairs) . $apiSecret);
    }

    /**
     * A resized/converted delivery URL for a Cloudinary image, e.g. "c_limit,w_480,f_auto,q_auto".
     * Non-Cloudinary values are returned unchanged.
     */
    public static function cloudinaryVariant(string $url, string $transformation): string
    {
        if (!self::isCloudinaryUrl($url) || !str_contains($url, '/image/upload/')) {
            return $url;
        }

        return preg_replace('#/image/upload/#', '/image/upload/' . $transformation . '/', $url, 1) ?? $url;
    }

    public static function isCloudinaryUrl(string $url): bool
    {
        return (string) parse_url($url, PHP_URL_HOST) === 'res.cloudinary.com';
    }

    private static function uploadToCloudinary(string $tmpPath, string $publicId): ?string
    {
        $config = self::cloudinaryConfig();
        $params = [
            'folder' => trim((string) ($_ENV['CLOUDINARY_FOLDER'] ?? 'dubai-tech-plaza'), '/') ?: 'dubai-tech-plaza',
            'public_id' => $publicId,
            'timestamp' => (string) time(),
        ];
        $params['signature'] = self::cloudinarySignature($params, $config['secret']);
        $params['api_key'] = $config['key'];
        $params['file'] = new CURLFile($tmpPath);

        $curl = curl_init('https://api.cloudinary.com/v1_1/' . rawurlencode($config['cloud']) . '/image/upload');
        curl_setopt_array($curl, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 45,
            CURLOPT_POSTFIELDS => $params,
        ]);
        $raw = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
        curl_close($curl);

        $response = is_string($raw) ? json_decode($raw, true) : null;
        if ($status !== 200 || empty($response['secure_url'])) {
            self::$lastError = 'Cloudinary upload failed' . (isset($response['error']['message']) ? ': ' . $response['error']['message'] : ' (HTTP ' . $status . ')');
            error_log(self::$lastError);
            return null;
        }

        return (string) $response['secure_url'];
    }

    /**
     * @return array{key: string, secret: string, cloud: string}|null
     */
    private static function cloudinaryConfig(): ?array
    {
        $url = trim((string) ($_ENV['CLOUDINARY_URL'] ?? ''));
        if (!preg_match('#^cloudinary://([^:]+):([^@]+)@([a-z0-9_-]+)#i', $url, $match)) {
            return null;
        }

        return ['key' => $match[1], 'secret' => $match[2], 'cloud' => $match[3]];
    }
}
