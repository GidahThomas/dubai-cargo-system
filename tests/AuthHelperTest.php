<?php

class AuthHelperTest extends TestCase
{
    protected function setUp(): void
    {
        $_SESSION = [];
    }

    public function testMoneyFormatsTzsWithNoDecimals(): void
    {
        $this->assertEquals('TZS 1,950,000', money(1950000));
        $this->assertEquals('TZS 0', money(0));
        $this->assertEquals('TZS 0', money(null));
    }

    public function testCurrencyMoneyUsesDecimalsForNonWholeCurrencies(): void
    {
        $this->assertEquals('USD 19.99', currency_money(19.99, 'USD'));
        $this->assertEquals('TZS 500', currency_money(500, 'TZS'));
    }

    public function testMoneyPartsSplitsPrefixAndValue(): void
    {
        $parts = money_parts(1950000, 'TZS');
        $this->assertEquals('TZS', $parts['prefix']);
        $this->assertEquals('1,950,000', $parts['value']);
    }

    public function testBadgeClassMapsSuccessStatuses(): void
    {
        $this->assertEquals('text-bg-success', badge_class('confirmed'));
        $this->assertEquals('text-bg-success', badge_class('paid'));
        $this->assertEquals('text-bg-danger', badge_class('cancelled'));
        $this->assertEquals('text-bg-warning', badge_class('pending'));
        $this->assertEquals('text-bg-info', badge_class('in_transit'));
        $this->assertEquals('text-bg-secondary', badge_class('some_unknown_status'));
    }

    public function testStatusColorMapsToHexValues(): void
    {
        $this->assertEquals('#16a34a', status_color('confirmed'));
        $this->assertEquals('#d97706', status_color('pending'));
        $this->assertEquals('#dc2626', status_color('cancelled'));
    }

    public function testReadableStatusConvertsUnderscoresToTitleCase(): void
    {
        $this->assertEquals('In Transit', readable_status('in_transit'));
        $this->assertEquals('Pending', readable_status('pending'));
        $this->assertEquals('', readable_status(null));
    }

    public function testHEscapesHtmlSpecialCharacters(): void
    {
        $this->assertEquals('&lt;script&gt;', h('<script>'));
        $this->assertEquals('Tom &amp; Jerry', h('Tom & Jerry'));
    }

    public function testCsrfTokenRoundTrip(): void
    {
        $token = Auth::csrfToken();
        $this->assertTrue(strlen($token) === 64, 'CSRF token should be a 64-char hex string.');
        $this->assertTrue(Auth::verifyCsrf($token));
        $this->assertFalse(Auth::verifyCsrf('wrong-token'));
        $this->assertFalse(Auth::verifyCsrf(null));
    }

    public function testStaffRolesDoesNotIncludeCustomer(): void
    {
        $this->assertFalse(in_array('customer', Auth::staffRoles(), true));
        $this->assertTrue(in_array('admin', Auth::staffRoles(), true));
    }
}
