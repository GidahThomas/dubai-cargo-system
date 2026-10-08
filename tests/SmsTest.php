<?php

class SmsTest extends TestCase
{
    private array $savedEnv = [];
    private const KEYS = ['SMS_PROVIDER', 'SMS_USERNAME', 'SMS_API_KEY', 'SMS_SECRET_KEY'];

    protected function setUp(): void
    {
        $this->savedEnv = array_intersect_key($_ENV, array_flip(self::KEYS));
        foreach (self::KEYS as $key) {
            unset($_ENV[$key]);
        }
    }

    protected function tearDown(): void
    {
        foreach (self::KEYS as $key) {
            unset($_ENV[$key]);
        }
        $_ENV = $this->savedEnv + $_ENV;
    }

    public function testDisabledWithoutProviderOrCredentials(): void
    {
        $this->assertFalse(Sms::isEnabled());
        $this->assertFalse(Sms::send('0652532646', 'Hello'));

        $_ENV['SMS_PROVIDER'] = 'africastalking';
        $_ENV['SMS_USERNAME'] = 'sandbox';
        $this->assertFalse(Sms::isEnabled(), 'An API key is required too.');

        $_ENV['SMS_PROVIDER'] = 'beem';
        $_ENV['SMS_API_KEY'] = 'key';
        $this->assertFalse(Sms::isEnabled(), 'Beem needs the secret key as well.');
    }

    public function testEnabledWhenProviderIsConfigured(): void
    {
        $_ENV['SMS_PROVIDER'] = 'AfricasTalking';
        $_ENV['SMS_USERNAME'] = 'sandbox';
        $_ENV['SMS_API_KEY'] = 'key';
        $this->assertTrue(Sms::isEnabled());
    }

    public function testFormatPrefixesCompanyAndLimitsLength(): void
    {
        $text = Sms::format('Shipment updated', 'Tracking DCF-1 is now In Transit.');
        $this->assertStringContainsString(company_name() . ': Shipment updated. Tracking DCF-1', $text);

        $long = Sms::format('Title', str_repeat('x', 1000));
        $this->assertEquals(300, mb_strlen($long));
    }
}
