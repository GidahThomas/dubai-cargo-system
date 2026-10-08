<?php

/**
 * Multi-product orders against the real database. Every test removes what it created
 * and restores stock, so the demo data is left as it was.
 */
class OrderItemsTest extends TestCase
{
    private PDO $db;
    private int $customerId;
    private int $locationId;
    private array $createdOrders = [];
    private array $stockBefore = [];

    protected function setUp(): void
    {
        $this->db = Database::connect();
        $this->customerId = (int) $this->db->query('SELECT id FROM users WHERE role = "customer" ORDER BY id LIMIT 1')->fetchColumn();
        $this->locationId = (int) ((new Invoice())->settings()['default_location_id'] ?? 1);
        foreach ([1, 3] as $productId) {
            $this->stockBefore[$productId] = $this->stock($productId);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->createdOrders as $orderId) {
            $this->db->prepare('DELETE FROM payments WHERE order_id = ?')->execute([$orderId]);
            $this->db->prepare('DELETE FROM orders WHERE id = ?')->execute([$orderId]);
        }
        foreach ($this->stockBefore as $productId => $quantity) {
            $this->db->prepare('UPDATE inventory SET quantity = ?, low_stock_notified = 0 WHERE product_id = ? AND location_id = ?')
                ->execute([$quantity, $productId, $this->locationId]);
        }
        $this->db->exec('DELETE FROM notifications WHERE title IN ("Low stock", "Out of stock") AND created_at > NOW() - INTERVAL 5 MINUTE');
        $this->createdOrders = [];
    }

    public function testOneOrderHoldsSeveralProductsAndReservesStock(): void
    {
        $orderModel = new Order();
        $orderId = $orderModel->createFromItems($this->customerId, [1 => 2, 3 => 1], 'Test address');
        $this->createdOrders[] = $orderId;

        $items = $orderModel->items($orderId);
        $order = $orderModel->find($orderId);
        $prices = $this->db->query('SELECT id, price FROM products WHERE id IN (1, 3)')->fetchAll(PDO::FETCH_KEY_PAIR);

        $this->assertEquals(2, count($items));
        $this->assertEquals(round(2 * $prices[1] + $prices[3], 2), round((float) $order['total_amount'], 2));
        $this->assertEquals($this->stockBefore[1] - 2, $this->stock(1));
        $this->assertEquals($this->stockBefore[3] - 1, $this->stock(3));
    }

    public function testTooLargeQuantityChangesNothing(): void
    {
        $failed = false;
        try {
            (new Order())->createFromItems($this->customerId, [3 => 1, 1 => 100000], 'Test address');
        } catch (RuntimeException $exception) {
            $failed = true;
            $this->assertStringContainsString('in stock', $exception->getMessage());
        }

        $this->assertTrue($failed, 'An order larger than the stock must be refused.');
        $this->assertEquals($this->stockBefore[1], $this->stock(1));
        $this->assertEquals($this->stockBefore[3], $this->stock(3), 'The other line must not be reserved either.');
    }

    public function testEmptyCartIsRefused(): void
    {
        $failed = false;
        try {
            (new Order())->createFromItems($this->customerId, [], 'Test address');
        } catch (InvalidArgumentException) {
            $failed = true;
        }
        $this->assertTrue($failed);
    }

    public function testLowStockAlertsManagersOnceUntilRestocked(): void
    {
        $reorder = (int) $this->db->query('SELECT reorder_level FROM inventory WHERE product_id = 1 AND location_id = ' . $this->locationId)->fetchColumn();
        $this->db->prepare('UPDATE inventory SET quantity = ?, low_stock_notified = 0 WHERE product_id = 1 AND location_id = ?')
            ->execute([$reorder + 2, $this->locationId]);

        $inventory = new Inventory();
        $inventory->adjustStock(1, $this->locationId, -3); // crosses the reorder level
        $afterFirstDrop = $this->alertCount();
        $inventory->adjustStock(1, $this->locationId, -1); // still low: no second alert
        $afterSecondDrop = $this->alertCount();
        $inventory->adjustStock(1, $this->locationId, +10); // restocked
        $inventory->adjustStock(1, $this->locationId, -10); // low again: alerts again
        $afterRestockAndDrop = $this->alertCount();

        $managers = count((new User())->byRoles(['manager', 'admin']));
        $this->assertTrue($managers > 0);
        $this->assertEquals($managers, $afterFirstDrop);
        $this->assertEquals($afterFirstDrop, $afterSecondDrop);
        $this->assertEquals(2 * $managers, $afterRestockAndDrop);
    }

    private function stock(int $productId): int
    {
        $stmt = $this->db->prepare('SELECT quantity FROM inventory WHERE product_id = ? AND location_id = ?');
        $stmt->execute([$productId, $this->locationId]);

        return (int) $stmt->fetchColumn();
    }

    private function alertCount(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM notifications WHERE title IN ("Low stock", "Out of stock") AND created_at > NOW() - INTERVAL 5 MINUTE')->fetchColumn();
    }
}
