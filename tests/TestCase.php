<?php

abstract class TestCase
{
    private array $failures = [];
    private int $assertionCount = 0;

    public function run(): array
    {
        $results = [];

        foreach (get_class_methods($this) as $method) {
            if (!str_starts_with($method, 'test')) {
                continue;
            }

            $this->failures = [];
            $this->assertionCount = 0;

            try {
                $this->setUp();
                $this->$method();
                $this->tearDown();
            } catch (Throwable $exception) {
                $this->failures[] = $exception->getMessage();
            }

            $results[] = [
                'name' => static::class . '::' . $method,
                'passed' => $this->failures === [],
                'failures' => $this->failures,
                'assertions' => $this->assertionCount,
            ];
        }

        return $results;
    }

    protected function setUp(): void
    {
    }

    protected function tearDown(): void
    {
    }

    protected function assertTrue(bool $condition, string $message = 'Failed asserting that condition is true.'): void
    {
        $this->assertionCount++;

        if (!$condition) {
            throw new RuntimeException($message);
        }
    }

    protected function assertFalse(bool $condition, string $message = 'Failed asserting that condition is false.'): void
    {
        $this->assertTrue(!$condition, $message);
    }

    protected function assertEquals(mixed $expected, mixed $actual, string $message = ''): void
    {
        $this->assertionCount++;

        if ($expected !== $actual) {
            $message = $message !== '' ? $message : sprintf(
                "Failed asserting that %s matches expected %s.",
                var_export($actual, true),
                var_export($expected, true)
            );
            throw new RuntimeException($message);
        }
    }

    protected function assertNull(mixed $value, string $message = 'Failed asserting that value is null.'): void
    {
        $this->assertTrue($value === null, $message);
    }

    protected function assertNotNull(mixed $value, string $message = 'Failed asserting that value is not null.'): void
    {
        $this->assertTrue($value !== null, $message);
    }

    protected function assertStringContainsString(string $needle, string $haystack, string $message = ''): void
    {
        $this->assertionCount++;

        if (!str_contains($haystack, $needle)) {
            $message = $message !== '' ? $message : "Failed asserting that \"{$haystack}\" contains \"{$needle}\".";
            throw new RuntimeException($message);
        }
    }
}
