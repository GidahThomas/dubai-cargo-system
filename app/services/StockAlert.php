<?php

/**
 * Tells managers and the owner when a product's stock at a branch falls to its reorder level.
 *
 * Call check() after any change to inventory quantities. Each product/branch is alerted once
 * (inventory.low_stock_notified); the flag clears when stock rises above the reorder level
 * again, so the next drop alerts again. It runs on the shared connection, so when called
 * inside a transaction a rolled-back sale also rolls back its alert.
 */
class StockAlert
{
    private const ALERT_ROLES = ['manager', 'admin'];

    public static function check(int $productId, int $locationId): void
    {
        try {
            $db = Database::connect();
            $stmt = $db->prepare(
                'SELECT i.quantity, i.reorder_level, i.low_stock_notified, p.name AS product_name, l.name AS location_name
                 FROM inventory i
                 INNER JOIN products p ON p.id = i.product_id
                 LEFT JOIN locations l ON l.id = i.location_id
                 WHERE i.product_id = :product_id AND i.location_id = :location_id'
            );
            $stmt->execute(['product_id' => $productId, 'location_id' => $locationId]);
            $row = $stmt->fetch();

            if (!$row) {
                return;
            }

            $isLow = (int) $row['quantity'] <= (int) $row['reorder_level'];
            $alreadyNotified = (int) $row['low_stock_notified'] === 1;

            if ($isLow === $alreadyNotified) {
                return;
            }

            $db->prepare('UPDATE inventory SET low_stock_notified = :flag WHERE product_id = :product_id AND location_id = :location_id')
                ->execute(['flag' => $isLow ? 1 : 0, 'product_id' => $productId, 'location_id' => $locationId]);

            if (!$isLow) {
                return;
            }

            $quantity = (int) $row['quantity'];
            $message = sprintf(
                '%s at %s is down to %d (reorder level %d). Restock soon.',
                $row['product_name'],
                $row['location_name'] ?: 'the store',
                $quantity,
                (int) $row['reorder_level']
            );

            $notification = new Notification();
            foreach ((new User())->byRoles(self::ALERT_ROLES) as $user) {
                $notification->create((int) $user['id'], $quantity === 0 ? 'Out of stock' : 'Low stock', $message, $quantity === 0 ? 'danger' : 'warning');
            }
        } catch (Throwable $exception) {
            // An alert must never block a sale.
            error_log('StockAlert: ' . $exception->getMessage());
        }
    }
}
