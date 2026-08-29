<?php

class EnvTest extends TestCase
{
    private string $tempFile = '';

    protected function setUp(): void
    {
        $this->tempFile = sys_get_temp_dir() . '/dcf_env_test_' . uniqid() . '.env';
    }

    protected function tearDown(): void
    {
        if ($this->tempFile !== '' && file_exists($this->tempFile)) {
            unlink($this->tempFile);
        }

        unset($_ENV['TEST_ENV_KEY'], $_ENV['TEST_ENV_QUOTED'], $_ENV['TEST_ENV_EMPTY']);
    }

    public function testLoadEnvParsesKeyValuePairs(): void
    {
        file_put_contents($this->tempFile, "TEST_ENV_KEY=hello_world\n");
        load_env($this->tempFile);

        $this->assertEquals('hello_world', $_ENV['TEST_ENV_KEY'] ?? null);
    }

    public function testLoadEnvStripsQuotes(): void
    {
        file_put_contents($this->tempFile, "TEST_ENV_QUOTED=\"Dubai Computer Fast Cargo\"\n");
        load_env($this->tempFile);

        $this->assertEquals('Dubai Computer Fast Cargo', $_ENV['TEST_ENV_QUOTED'] ?? null);
    }

    public function testLoadEnvIgnoresCommentsAndBlankLines(): void
    {
        file_put_contents($this->tempFile, "# a comment\n\nTEST_ENV_KEY=value_after_comment\n");
        load_env($this->tempFile);

        $this->assertEquals('value_after_comment', $_ENV['TEST_ENV_KEY'] ?? null);
    }

    public function testLoadEnvHandlesMissingFileGracefully(): void
    {
        load_env('/path/does/not/exist.env');
        $this->assertTrue(true, 'load_env should not throw for a missing file.');
    }

    public function testLoadEnvAllowsEmptyValues(): void
    {
        file_put_contents($this->tempFile, "TEST_ENV_EMPTY=\n");
        load_env($this->tempFile);

        $this->assertEquals('', $_ENV['TEST_ENV_EMPTY'] ?? 'missing');
    }
}
