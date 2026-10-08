<?php

class AzamPayTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        $this->db = Database::connect();
    }

    public function testSuccessfulCallbackConfirmsPaymentAndOrder(): void
    {
        [$paymentId, $orderId, $amount] = $this->newOrderPayment();
        $externalId = $this->newRequest($paymentId, $amount);

        $result = AzamPay::handleCallback(['utilityref' => $externalId, 'transactionstatus' => 'success', 'amount' => (string) $amount, 'reference' => 'MP123ABC']);

        $this->assertEquals('confirmed', $result);
        $payment = $this->db->query("SELECT status, method, payment_reference FROM payments WHERE id = {$paymentId}")->fetch();
        $this->assertEquals('confirmed', $payment['status']);
        $this->assertEquals('mobile_money', $payment['method']);
        $this->assertEquals('MP123ABC', $payment['payment_reference']);
        $this->assertEquals('confirmed', $this->db->query("SELECT status FROM orders WHERE id = {$orderId}")->fetchColumn());

        $this->assertEquals('duplicate', AzamPay::handleCallback(['utilityref' => $externalId, 'transactionstatus' => 'success', 'amount' => (string) $amount]));
    }

    public function testFailedOrShortPaymentLeavesPaymentPending(): void
    {
        [$paymentId, , $amount] = $this->newOrderPayment();

        $declined = $this->newRequest($paymentId, $amount);
        $this->assertEquals('failed', AzamPay::handleCallback(['utilityref' => $declined, 'transactionstatus' => 'failure', 'amount' => (string) $amount]));

        $short = $this->newRequest($paymentId, $amount);
        $this->assertEquals('amount_mismatch', AzamPay::handleCallback(['utilityref' => $short, 'transactionstatus' => 'success', 'amount' => (string) ($amount - 1000)]));

        $this->assertEquals('pending', $this->db->query("SELECT status FROM payments WHERE id = {$paymentId}")->fetchColumn());
    }

    public function testUnknownReferenceIsIgnored(): void
    {
        $this->assertEquals('unknown', AzamPay::handleCallback(['utilityref' => 'DCF-NOT-OURS', 'transactionstatus' => 'success', 'amount' => '1000']));
    }

    public function testCallbackTokenMustMatch(): void
    {
        $saved = $_ENV['AZAMPAY_CALLBACK_TOKEN'] ?? null;
        $_ENV['AZAMPAY_CALLBACK_TOKEN'] = 'secret-token-123';
        $this->assertTrue(AzamPay::callbackTokenMatches('secret-token-123'));
        $this->assertFalse(AzamPay::callbackTokenMatches('wrong'));
        $_ENV['AZAMPAY_CALLBACK_TOKEN'] = '';
        $this->assertFalse(AzamPay::callbackTokenMatches(''), 'An empty token must never match.');
        $_ENV['AZAMPAY_CALLBACK_TOKEN'] = $saved;
    }

    private function newOrderPayment(): array
    {
        $customerId = (int) $this->db->query('SELECT id FROM users WHERE role = "customer" ORDER BY id LIMIT 1')->fetchColumn();
        $orderId = (new Order())->createFromItems($customerId, [3 => 1], 'Test address');
        $payment = $this->db->query("SELECT id, amount FROM payments WHERE order_id = {$orderId}")->fetch();

        return [(int) $payment['id'], $orderId, (float) $payment['amount']];
    }

    private function newRequest(int $paymentId, float $amount): string
    {
        $externalId = 'DCFTEST' . bin2hex(random_bytes(4));
        $this->db->prepare('INSERT INTO payment_gateway_requests (payment_id, external_id, provider, msisdn, amount) VALUES (?, ?, "Mpesa", "255754000000", ?)')
            ->execute([$paymentId, $externalId, $amount]);

        return $externalId;
    }
}
