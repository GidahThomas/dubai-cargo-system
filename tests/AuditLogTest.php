<?php

class AuditLogTest extends TestCase
{
    private string $testIp = '';

    protected function setUp(): void
    {
        $this->testIp = '203.0.113.' . random_int(1, 254);
    }

    public function testRecentFailedLoginsCountsWithinWindow(): void
    {
        $auditLog = new AuditLog();

        $this->assertEquals(0, $auditLog->recentFailedLogins($this->testIp, 15));

        $_SERVER['REMOTE_ADDR'] = $this->testIp;
        $auditLog->create(null, 'login_failed', 'users', null, 'Test attempt 1');
        $auditLog->create(null, 'login_failed', 'users', null, 'Test attempt 2');

        $this->assertEquals(2, $auditLog->recentFailedLogins($this->testIp, 15));
    }

    public function testRecentFailedLoginsIsScopedByIp(): void
    {
        $auditLog = new AuditLog();
        $otherIp = '203.0.113.' . random_int(1, 254);

        $_SERVER['REMOTE_ADDR'] = $this->testIp;
        $auditLog->create(null, 'login_failed', 'users', null, 'Scoped attempt');

        $this->assertEquals(0, $auditLog->recentFailedLogins($otherIp, 15));
    }
}
