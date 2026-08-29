<?php

class UserModelTest extends TestCase
{
    public function testFindByEmailReturnsSeededAdmin(): void
    {
        $user = (new User())->findByEmail('admin@dubai-fast-cargo.test');

        $this->assertNotNull($user);
        $this->assertEquals('admin', $user['role']);
        $this->assertEquals('active', $user['status']);
    }

    public function testFindByEmailReturnsNullForUnknownAddress(): void
    {
        $user = (new User())->findByEmail('definitely-not-a-real-user-' . uniqid() . '@example.com');

        $this->assertNull($user);
    }

    public function testSeededAdminPasswordVerifies(): void
    {
        $user = (new User())->findByEmail('admin@dubai-fast-cargo.test');

        $this->assertNotNull($user);
        $this->assertTrue(password_verify('password', $user['password']));
        $this->assertFalse(password_verify('definitely-wrong-password', $user['password']));
    }

    public function testCountByRoleReturnsNonNegativeInteger(): void
    {
        $count = (new User())->countByRole('customer');

        $this->assertTrue($count >= 0, 'countByRole should return a non-negative integer.');
    }
}
