<?php

/**
 * Imports posts from an Instagram "Download your information" export (JSON format).
 *
 * Point it at the extracted export folder. It reads posts_*.json and reels.json,
 * copies the photos/videos into public/uploads/instagram/, and stores each post with
 * its caption. Re-running is safe: posts already imported are skipped.
 * Optionally each post with at least one photo also becomes a hidden draft product.
 */
class InstagramImporter
{
    public const DEFAULT_EXPORT_DIR = ROOT_PATH . '/storage/instagram-export';

    private const IMAGE_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
    private const VIDEO_EXTENSIONS = ['mp4', 'mov', 'm4v', 'webm'];

    private const CATEGORY_KEYWORDS = [
        'Gaming PCs' => ['gaming', 'rtx', 'gtx'],
        'Workstations' => ['workstation', 'zbook', 'precision'],
        'All-in-One PCs' => ['all in one', 'all-in-one', 'aio', 'imac'],
        'Chromebooks' => ['chromebook'],
        'Mini PCs' => ['mini pc', 'tiny', 'nuc'],
        'Servers' => ['server', 'poweredge', 'proliant'],
        'Printers' => ['printer', 'laserjet', 'deskjet', 'scanner'],
        'Monitors' => ['monitor', 'display', 'screen'],
        'Desktops' => ['desktop', 'optiplex', 'prodesk', 'elitedesk', 'thinkcentre', 'tower'],
        'Laptops' => ['laptop', 'notebook', 'macbook', 'elitebook', 'probook', 'thinkpad', 'latitude', 'inspiron', 'vostro', 'pavilion', 'ideapad', 'zenbook', 'vivobook', 'spectre', 'envy', 'x360'],
        'Accessories' => ['mouse', 'keyboard', 'charger', 'bag', 'headphone', 'speaker', 'ssd', 'ram', 'flash', 'hdd', 'cable', 'adapter'],
    ];

    private const BRANDS = [
        'HP' => ['hp ', 'hp-', 'elitebook', 'probook', 'zbook', 'pavilion', 'spectre', 'envy', 'prodesk', 'elitedesk', 'laserjet'],
        'Dell' => ['dell', 'latitude', 'inspiron', 'vostro', 'optiplex', 'precision', 'xps', 'alienware'],
        'Apple' => ['apple', 'macbook', 'imac', 'mac mini', 'iphone', 'ipad'],
        'Lenovo' => ['lenovo', 'thinkpad', 'ideapad', 'thinkcentre', 'yoga', 'legion'],
        'Asus' => ['asus', 'zenbook', 'vivobook', 'rog '],
        'Acer' => ['acer', 'aspire', 'predator', 'nitro'],
        'Microsoft' => ['surface'],
        'Samsung' => ['samsung'],
        'Toshiba' => ['toshiba', 'dynabook'],
    ];

    private array $stats = [];

    public function __construct(private bool $createProducts = true, private ?int $userId = null)
    {
    }

    /**
     * @return array{posts_found: int, imported: int, skipped_existing: int, skipped_no_media: int, products_created: int, media_copied: int, warnings: string[]}
     */
    public function import(string $exportDir = self::DEFAULT_EXPORT_DIR): array
    {
        $this->stats = ['posts_found' => 0, 'imported' => 0, 'skipped_existing' => 0, 'skipped_no_media' => 0, 'products_created' => 0, 'media_copied' => 0, 'warnings' => []];

        $exportDir = rtrim(str_replace('\\', '/', $exportDir), '/');
        if (!is_dir($exportDir)) {
            throw new RuntimeException('Export folder not found: ' . $exportDir);
        }

        $jsonFiles = $this->findPostFiles($exportDir);
        if (!$jsonFiles) {
            $hasHtml = $this->findFiles($exportDir, '/^posts_\d+\.html$|^reels\.html$/i') !== [];
            throw new RuntimeException($hasHtml
                ? 'This export is in HTML format. Request a new export from Instagram and choose Format: JSON.'
                : 'No posts_1.json or reels.json found in ' . $exportDir . '. Make sure the export ZIP is extracted into that folder.');
        }

        $postModel = new InstagramPost();

        foreach ($jsonFiles as $jsonFile) {
            foreach ($this->readPosts($jsonFile) as $post) {
                $this->stats['posts_found']++;
                $this->importPost($postModel, $post, $exportDir, dirname($jsonFile));
            }
        }

        return $this->stats;
    }

    private function importPost(InstagramPost $postModel, array $post, string $exportDir, string $jsonDir): void
    {
        $mediaItems = $post['media'] ?? [];
        if (!$mediaItems) {
            $this->stats['skipped_no_media']++;
            return;
        }

        $timestamp = (int) ($post['creation_timestamp'] ?? $mediaItems[0]['creation_timestamp'] ?? 0);
        $caption = $this->fixEncoding((string) ($post['title'] ?? ''));
        if ($caption === '' && count($mediaItems) >= 1) {
            $caption = $this->fixEncoding((string) ($mediaItems[0]['title'] ?? ''));
        }

        $sourceKey = sha1(basename((string) ($mediaItems[0]['uri'] ?? '')) . '|' . $timestamp);
        if ($postModel->existsBySourceKey($sourceKey)) {
            $this->stats['skipped_existing']++;
            return;
        }

        $media = [];
        foreach ($mediaItems as $item) {
            $copied = $this->copyMedia((string) ($item['uri'] ?? ''), $exportDir, $jsonDir, $timestamp);
            if ($copied !== null) {
                $media[] = $copied;
            }
        }

        if (!$media) {
            $this->stats['skipped_no_media']++;
            return;
        }

        $postId = $postModel->create($sourceKey, $caption !== '' ? $caption : null, $timestamp > 0 ? date('Y-m-d H:i:s', $timestamp) : null, $media);
        $this->stats['imported']++;

        if ($this->createProducts) {
            $images = array_values(array_filter($media, static fn (array $m): bool => $m['type'] === 'image'));
            if ($images) {
                $productId = $this->createDraftProduct($caption, $images, $timestamp);
                $postModel->linkProduct($postId, $productId);
                $this->stats['products_created']++;
            }
        }
    }

    private function createDraftProduct(string $caption, array $images, int $timestamp): int
    {
        $text = ' ' . strtolower($caption) . ' ';

        return (new Product())->create([
            'name' => $this->productName($caption, $timestamp),
            'category' => $this->match($text, self::CATEGORY_KEYWORDS) ?? 'Laptops',
            'brand' => $this->match($text, self::BRANDS) ?? 'Other',
            'description' => $caption !== '' ? $caption : null,
            'price' => $this->guessPrice($caption),
            'image' => $images[0]['path'],
            'gallery_images' => array_column(array_slice($images, 1), 'path'),
            'primary_image_choice' => 'primary',
            'status' => 'inactive',
            'quantity' => 0,
        ], $this->userId ?? $this->defaultUserId());
    }

    /**
     * First meaningful line of the caption, without hashtags, mentions or emoji.
     */
    public function productName(string $caption, int $timestamp = 0): string
    {
        foreach (preg_split('/\R/u', $caption) ?: [] as $line) {
            $line = preg_replace('/[#@][\p{L}\p{N}_.]+/u', '', $line);
            $line = preg_replace('/\b(?:tsh|tzs|tshs|bei|price)\s*[:.\-]?\s*\d[\d,\.]*\s*(?:m|k|million)?\b|\d[\d,\.]{3,}\s*(?:m|k)?\s*(?:\/=|tshs|tsh|tzs)/iu', '', (string) $line);
            $line = preg_replace('/[^\p{L}\p{N}\s\-\/.,&+()"\']+/u', ' ', (string) $line);
            $line = trim(preg_replace('/\s+/u', ' ', (string) $line), " \t-.,");

            if (mb_strlen($line) >= 3) {
                return mb_strlen($line) > 120 ? rtrim(mb_substr($line, 0, 117)) . '...' : $line;
            }
        }

        return 'Instagram post' . ($timestamp > 0 ? ' ' . date('Y-m-d', $timestamp) : '');
    }

    /**
     * Picks up prices written like "Tsh 1,250,000", "TZS 850000", "1.2M", "850k", "Bei 900,000/=".
     */
    public function guessPrice(string $caption): float
    {
        $patterns = [
            '/(?:tsh|tzs|tshs|bei|price)\s*[:.\-]?\s*(\d[\d,\.]*)\s*(m|k|million)?\b/iu',
            '/([\d][\d,\.]{3,})\s*(m|k)?\s*(?:\/=|tsh|tzs|tshs)/iu',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $caption, $match)) {
                $suffix = strtolower($match[2] ?? '');
                $number = str_replace(',', '', rtrim($match[1], '.,'));
                if ($suffix === '') {
                    $number = str_replace('.', '', $number);
                }
                $value = (float) $number;
                if ($suffix === 'k') {
                    $value *= 1000;
                } elseif ($suffix === 'm' || $suffix === 'million') {
                    $value *= 1000000;
                }
                if ($value >= 1000) {
                    return round($value);
                }
            }
        }

        return 0.0;
    }

    /**
     * Instagram exports UTF-8 text escaped byte-by-byte (e.g. "ð\u009f\u0094¥" for an emoji),
     * which decodes to mojibake. Re-interpret those bytes as UTF-8 when that is what happened.
     */
    public function fixEncoding(string $text): string
    {
        if ($text === '' || preg_match('/[^\x00-\x{FF}]/u', $text)) {
            return trim($text);
        }

        $bytes = mb_convert_encoding($text, 'ISO-8859-1', 'UTF-8');

        return trim(mb_check_encoding($bytes, 'UTF-8') ? $bytes : $text);
    }

    private function match(string $text, array $map): ?string
    {
        foreach ($map as $value => $keywords) {
            foreach ($keywords as $keyword) {
                if (str_contains($text, $keyword)) {
                    return $value;
                }
            }
        }

        return null;
    }

    private function copyMedia(string $uri, string $exportDir, string $jsonDir, int $timestamp): ?array
    {
        if ($uri === '' || preg_match('#^https?://#i', $uri)) {
            return null;
        }

        $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
        $type = in_array($extension, self::IMAGE_EXTENSIONS, true) ? 'image' : (in_array($extension, self::VIDEO_EXTENSIONS, true) ? 'video' : null);
        if ($type === null) {
            return null;
        }

        $source = null;
        foreach ([$exportDir . '/' . $uri, $jsonDir . '/' . $uri, dirname($jsonDir) . '/' . $uri, dirname($jsonDir, 2) . '/' . $uri] as $candidate) {
            if (is_file($candidate)) {
                $source = $candidate;
                break;
            }
        }

        if ($source === null) {
            $this->stats['warnings'][] = 'Missing file in export: ' . $uri;
            return null;
        }

        $folder = 'uploads/instagram/' . ($timestamp > 0 ? date('Ym', $timestamp) : 'undated');
        $targetDir = ROOT_PATH . '/public/' . $folder;
        if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
            throw new RuntimeException('Cannot create folder ' . $targetDir);
        }

        $fileName = substr(sha1_file($source), 0, 16) . '.' . $extension;
        $target = $targetDir . '/' . $fileName;
        if (!is_file($target)) {
            if (!copy($source, $target)) {
                throw new RuntimeException('Cannot copy ' . $source);
            }
            $this->stats['media_copied']++;
        }

        return ['path' => $folder . '/' . $fileName, 'type' => $type];
    }

    /**
     * Normalises every known export layout to a list of ['media' => [...], 'title' => ?, 'creation_timestamp' => ?].
     */
    private function readPosts(string $jsonFile): array
    {
        $data = json_decode((string) file_get_contents($jsonFile), true);
        if (!is_array($data)) {
            $this->stats['warnings'][] = 'Could not read ' . basename($jsonFile);
            return [];
        }

        if (isset($data['ig_reels_media'])) {
            $data = $data['ig_reels_media'];
        } elseif (isset($data['photos']) || isset($data['videos'])) {
            $data = array_merge($data['photos'] ?? [], $data['videos'] ?? []);
        }

        $posts = [];
        foreach ($data as $item) {
            if (!is_array($item)) {
                continue;
            }
            if (isset($item['uri']) || isset($item['path'])) {
                $item['uri'] ??= $item['path'];
                $item['creation_timestamp'] ??= isset($item['taken_at']) ? strtotime((string) $item['taken_at']) : null;
                $item['title'] ??= $item['caption'] ?? '';
                $posts[] = ['media' => [$item], 'title' => $item['title'], 'creation_timestamp' => $item['creation_timestamp']];
            } elseif (isset($item['media']) && is_array($item['media'])) {
                $posts[] = $item;
            }
        }

        return $posts;
    }

    private function findPostFiles(string $exportDir): array
    {
        return $this->findFiles($exportDir, '/^(posts_\d+|reels|media)\.json$/i');
    }

    private function findFiles(string $dir, string $pattern): array
    {
        $found = [];
        $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile() && preg_match($pattern, $file->getFilename())) {
                $found[] = str_replace('\\', '/', $file->getPathname());
            }
        }
        sort($found);

        return $found;
    }

    private function defaultUserId(): int
    {
        $admins = (new User())->byRoles(['admin']);

        return (int) ($admins[0]['id'] ?? 1);
    }
}
