<?php

/**
 * Pieces that let the app run on Vercel (no disk): Cloudinary images, database sessions, base path.
 */
class CloudHostingTest extends TestCase
{
    private array $savedEnv = [];
    private array $createdFiles = [];

    protected function setUp(): void
    {
        $this->savedEnv = $_ENV;
        unset($_ENV['CLOUDINARY_URL'], $_ENV['APP_BASE_PATH']);
    }

    protected function tearDown(): void
    {
        $_ENV = $this->savedEnv;
        foreach ($this->createdFiles as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
    }

    public function testCloudinarySignatureMatchesDocumentedExample(): void
    {
        // Example from Cloudinary's "Generating authentication signatures" documentation.
        $signature = MediaStorage::cloudinarySignature([
            'eager' => 'w_400,h_300,c_pad|w_260,h_200,c_crop',
            'public_id' => 'sample_image',
            'timestamp' => '1315060510',
        ], 'abcd');

        $this->assertEquals('bfd09f95f331f558cbd1320e67aa8d488770583e', $signature);
    }

    public function testCloudinaryVariantInsertsTransformation(): void
    {
        $url = 'https://res.cloudinary.com/demo/image/upload/v1712/dubai-tech-plaza/product-1.jpg';

        $this->assertEquals(
            'https://res.cloudinary.com/demo/image/upload/c_limit,w_480,f_auto,q_auto/v1712/dubai-tech-plaza/product-1.jpg',
            Thumbnail::url($url)
        );
        $this->assertEquals('uploads/a.jpg', MediaStorage::cloudinaryVariant('uploads/a.jpg', 'w_10'));
    }

    public function testOnlyCloudinaryHostIsTrusted(): void
    {
        $this->assertTrue(MediaStorage::isCloudinaryUrl('https://res.cloudinary.com/demo/image/upload/a.jpg'));
        $this->assertFalse(MediaStorage::isCloudinaryUrl('https://res.cloudinary.com.evil.example/image/upload/a.jpg'));
        $this->assertFalse(MediaStorage::isCloudinaryUrl('https://evil.example/?res.cloudinary.com'));
        $this->assertFalse(MediaStorage::isCloudinaryUrl('uploads/a.jpg'));
    }

    public function testWithoutCloudinaryUploadsStayOnThisServer(): void
    {
        $this->assertFalse(MediaStorage::usesCloudinary());

        $source = tempnam(sys_get_temp_dir(), 'dcf');
        // 1x1 PNG (the command-line PHP may not have the GD image extension).
        file_put_contents($source, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNkYAAAAAYAAjCB0C8AAAAASUVORK5CYII='));
        $this->createdFiles[] = $source;

        $stored = MediaStorage::storeUpload($source, 'unit-test', 'png');
        $this->assertNotNull($stored);
        $this->createdFiles[] = ROOT_PATH . '/public/' . $stored;
        $this->createdFiles[] = ROOT_PATH . '/public/uploads/thumbs/' . basename((string) $stored, '.png') . '.webp';

        $this->assertTrue(str_starts_with((string) $stored, 'uploads/unit-test-'));
        $this->assertTrue(is_file(ROOT_PATH . '/public/' . $stored));
        $this->assertNull(MediaStorage::storeUpload($source, 'unit-test', 'php'));
    }

    public function testCloudinaryUrlSettingIsParsed(): void
    {
        $_ENV['CLOUDINARY_URL'] = 'cloudinary://123456:secretXYZ@my-cloud';
        $this->assertTrue(MediaStorage::usesCloudinary());

        $_ENV['CLOUDINARY_URL'] = 'not-a-cloudinary-url';
        $this->assertFalse(MediaStorage::usesCloudinary());
    }

    public function testBasePathOverride(): void
    {
        $_ENV['APP_BASE_PATH'] = '/';
        $this->assertEquals('', base_url());
        $this->assertEquals('/index.php?url=shop', url('shop'));
        $this->assertEquals('/uploads/a.jpg', public_url('uploads/a.jpg'));

        $_ENV['APP_BASE_PATH'] = '/shop/';
        $this->assertEquals('/shop', base_url());
    }

    public function testDatabaseSessionsRoundTrip(): void
    {
        $handler = new DatabaseSessionHandler(Database::connect());
        $id = 'unit' . bin2hex(random_bytes(8));

        $this->assertEquals('', $handler->read($id));
        $this->assertTrue($handler->write($id, 'user_id|i:7;'));
        $this->assertEquals('user_id|i:7;', $handler->read($id));
        $this->assertTrue($handler->write($id, 'user_id|i:8;'));
        $this->assertEquals('user_id|i:8;', $handler->read($id));

        $this->assertTrue($handler->destroy($id));
        $this->assertEquals('', $handler->read($id));
    }

    public function testExpiredSessionsAreNotReadAndAreCollected(): void
    {
        $db = Database::connect();
        $handler = new DatabaseSessionHandler($db);
        $id = 'old' . bin2hex(random_bytes(8));
        $handler->write($id, 'stale');
        $db->prepare('UPDATE sessions SET last_activity = ? WHERE id = ?')->execute([time() - 99999, $id]);

        $this->assertEquals('', $handler->read($id));
        $this->assertTrue($handler->gc(7200) >= 1);
    }
}
