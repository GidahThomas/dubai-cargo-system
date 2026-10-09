<?php

/**
 * The order → payment → shipment → invoice chain keeps its statuses consistent.
 * Runs on the throwaway test database.
 */
class OrderToInvoiceTest extends TestCase
{
    private PDO $db;
    private int $customerId;
    private int $staffId;

    protected function setUp(): void
    {
        $this->db = Database::connect();
        $this->customerId = (int) $this->db->query('SELECT id FROM users WHERE role = "customer" ORDER BY id LIMIT 1')->fetchColumn();
        $this->staffId = (int) $this->db->query('SELECT id FROM users WHERE role = "manager" ORDER BY id LIMIT 1')->fetchColumn();
    }

    public function testInvoiceFromAPaidOrderIsPaid(): void
    {
        [$orderId, $paymentId] = $this->newOrder();
        (new Payment())->updateStatus($paymentId, 'confirmed', $this->staffId);

        $invoiceId = (new Invoice())->createFromOrder($orderId, $this->staffId);
        $invoice = (new Invoice())->find($invoiceId);

        $this->assertEquals('paid', $invoice['status']);
        $this->assertEquals(0.0, round((float) $invoice['balance_due'], 2));
    }

    public function testInvoiceTurnsPaidWhenTheOrderPaymentIsConfirmedLater(): void
    {
        [$orderId, $paymentId] = $this->newOrder();
        $invoiceId = (new Invoice())->createFromOrder($orderId, $this->staffId);
        $this->assertEquals('unpaid', (new Invoice())->find($invoiceId)['status']);

        (new Payment())->updateStatus($paymentId, 'confirmed', $this->staffId);

        $this->assertEquals('paid', (new Invoice())->find($invoiceId)['status']);
    }

    public function testShipmentForAPaidOrderStartsAtPaymentConfirmed(): void
    {
        [$orderId, $paymentId] = $this->newOrder();
        (new Payment())->updateStatus($paymentId, 'confirmed', $this->staffId);

        // The form's "Automatic" option sends an empty stage.
        $shipmentId = (new Shipment())->createForOrder($orderId, ['status' => ''], $this->staffId);
        $shipment = new Shipment();

        $this->assertEquals('payment_confirmed', $shipment->find($shipmentId)['status']);
        $this->assertEquals(['payment_confirmed'], array_column($shipment->events($shipmentId), 'status'));
    }

    private function newOrder(): array
    {
        $productId = (int) $this->db->query('SELECT i.product_id FROM inventory i JOIN products p ON p.id = i.product_id WHERE p.status = "active" AND i.quantity >= 2 ORDER BY i.quantity DESC LIMIT 1')->fetchColumn();
        $orderId = (new Order())->createFromItems($this->customerId, [$productId => 1], 'Test address');
        $paymentId = (int) $this->db->query("SELECT id FROM payments WHERE order_id = {$orderId}")->fetchColumn();

        return [$orderId, $paymentId];
    }
}
