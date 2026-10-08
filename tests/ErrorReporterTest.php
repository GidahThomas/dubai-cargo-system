<?php

class ErrorReporterTest extends TestCase
{
    public function testReportWritesTheErrorToTheLog(): void
    {
        $log = ErrorReporter::LOG_DIR . '/app-errors.log';
        $before = is_file($log) ? filesize($log) : 0;
        $marker = 'reporter-test-' . bin2hex(random_bytes(4));

        ErrorReporter::report(new RuntimeException($marker));

        clearstatcache();
        $this->assertTrue(is_file($log), 'The error log should exist.');
        $written = (string) file_get_contents($log, false, null, $before);
        $this->assertStringContainsString('RuntimeException: ' . $marker, $written);
        $this->assertStringContainsString('ErrorReporterTest.php', $written);
    }
}
