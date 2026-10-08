<?php

class WhatsAppTest extends TestCase
{
    private array $savedEnv = [];

    protected function setUp(): void
    {
        $keys = ['WHATSAPP_NUMBER', 'WHATSAPP_COUNTRY_CODE', 'WHATSAPP_TOKEN', 'WHATSAPP_PHONE_NUMBER_ID'];
        $this->savedEnv = array_intersect_key($_ENV, array_flip($keys));
        foreach ($keys as $key) {
            unset($_ENV[$key]);
        }
    }

    protected function tearDown(): void
    {
        foreach (['WHATSAPP_NUMBER', 'WHATSAPP_COUNTRY_CODE', 'WHATSAPP_TOKEN', 'WHATSAPP_PHONE_NUMBER_ID'] as $key) {
            unset($_ENV[$key]);
        }
        $_ENV = $this->savedEnv + $_ENV;
    }

    public function testWhatsappNumberConvertsLocalNumbersToInternational(): void
    {
        $this->assertEquals('255652532646', whatsapp_number('0652 532 646'));
        $this->assertEquals('255652532646', whatsapp_number('652532646'));
        $this->assertEquals('255712345678', whatsapp_number('0712-345-678'));
    }

    public function testWhatsappNumberKeepsInternationalNumbers(): void
    {
        $this->assertEquals('971500000001', whatsapp_number('+971 50 000 0001'));
        $this->assertEquals('255700000003', whatsapp_number('00255700000003'));
    }

    public function testWhatsappNumberRejectsInvalidOrEmptyInput(): void
    {
        $this->assertEquals('', whatsapp_number(''));
        $this->assertEquals('', whatsapp_number('12345'));
    }

    public function testWhatsappNumberUsesConfiguredCountryCode(): void
    {
        $_ENV['WHATSAPP_COUNTRY_CODE'] = '+254';
        $this->assertEquals('254712345678', whatsapp_number('0712345678'));
    }

    public function testWhatsappUrlEncodesMessage(): void
    {
        $this->assertEquals(
            'https://wa.me/255652532646?text=Hi%20there%2C%20is%20it%20available%3F',
            whatsapp_url('Hi there, is it available?', '0652532646')
        );
        $this->assertEquals('https://wa.me/255652532646', whatsapp_url('', '0652532646'));
        $this->assertEquals('', whatsapp_url('Hi', ''));
    }

    public function testCloudApiIsDisabledWithoutCredentials(): void
    {
        $this->assertFalse(WhatsApp::isEnabled());
        $this->assertFalse(WhatsApp::send('0652532646', 'Title', 'Message'));
        $this->assertEquals(0, CustomerMessenger::queue(3, 'Title', 'Message'));
    }
}
