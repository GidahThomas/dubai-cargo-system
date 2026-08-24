<?php

class Order extends Model
{
    public static function statuses(): array
    {
        return ['pending', 'confirmed', 'processing', 'ready', 'shipped', 'delivered', 'cancelled'];
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT o.*, u.name AS customer_name, u.email AS customer_email,
                       p.status AS payment_status, p.method AS payment_method,
                       s.tracking_number, s.status AS shipment_status
                FROM orders o
                INNER JOIN users u ON u.id = o.user_id
                LEFT JOIN payments p ON p.order_id = o.id
                LEFT JOIN shipments s ON s.order_id = o.id';
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'o.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'o.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(o.order_number LIKE :search_order OR u.name LIKE :search_customer OR u.email LIKE :search_email)';
            $params['search_order'] = '%' . $filters['search'] . '%';
            $params['search_customer'] = '%' . $filters['search'] . '%';
            $params['search_email'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(o.created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(o.created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY o.created_at DESC';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone
             FROM orders o
             INNER JOIN users u ON u.id = o.user_id
             WHERE o.id = :id',
            ['id' => $id]
        );
    }

    public function items(int $orderId): array
    {
        return $this->fetchAll(
            'SELECT oi.*, p.name, p.brand, p.category
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :order_id',
            ['order_id' => $orderId]
        );
    }

    public function createFromProduct(int $userId, int $productId, int $quantity, string $shippingAddress, ?string $notes = null): int
    {
        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $this->db->beginTransaction();

        try {
            $productStmt = $this->db->prepare(
                'SELECT p.*, i.quantity AS stock
                 FROM products p
                 INNER JOIN inventory i ON i.product_id = p.id
                 WHERE p.id = :id AND p.status = "active"
                 FOR UPDATE'
            );
            $productStmt->execute(['id' => $productId]);
            $product = $productStmt->fetch();

            if (!$product) {
                throw new RuntimeException('Product was not found or is inactive.');
            }

            if ((int) $product['stock'] < $quantity) {
                throw new RuntimeException('Not enough inventory is available for this product.');
            }

            $lineTotal = (float) $product['price'] * $quantity;
            $orderNumber = $this->generateOrderNumber();

            $orderStmt = $this->db->prepare(
                'INSERT INTO orders (order_number, user_id, status, total_amount, shipping_address, notes)
                 VALUES (:order_number, :user_id, "pending", :total_amount, :shipping_address, :notes)'
            );
            $orderStmt->execute([
                'order_number' => $orderNumber,
                'user_id' => $userId,
                'total_amount' => $lineTotal,
                'shipping_address' => $shippingAddress,
                'notes' => $notes,
            ]);

            $orderId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                'INSERT INTO order_items (order_id, product_id, quantity, unit_price, line_total)
                 VALUES (:order_id, :product_id, :quantity, :unit_price, :line_total)'
            );
            $itemStmt->execute([
                'order_id' => $orderId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => (float) $product['price'],
                'line_total' => $lineTotal,
            ]);

            $stockStmt = $this->db->prepare(
                'UPDATE inventory SET quantity = quantity - :quantity WHERE product_id = :product_id'
            );
            $stockStmt->execute([
                'quantity' => $quantity,
                'product_id' => $productId,
            ]);

            $paymentStmt = $this->db->prepare(
                'INSERT INTO payments (order_id, amount, status)
                 VALUES (:order_id, :amount, "pending")'
            );
            $paymentStmt->execute([
                'order_id' => $orderId,
                'amount' => $lineTotal,
            ]);

            $this->db->commit();

            return $orderId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function updateStatus(int $orderId, string $status): bool
    {
        return $this->execute(
            'UPDATE orders SET status = :status WHERE id = :id',
            ['id' => $orderId, 'status' => $status]
        );
    }

    public function cancel(int $orderId, ?int $userId = null): bool
    {
        $this->db->beginTransaction();

        try {
            $sql = 'SELECT * FROM orders WHERE id = :id AND status = "pending" FOR UPDATE';
            $params = ['id' => $orderId];

            if ($userId !== null) {
                $sql .= ' AND user_id = :user_id';
                $params['user_id'] = $userId;
            }

            $orderStmt = $this->db->prepare($sql);
            $orderStmt->execute($params);
            $order = $orderStmt->fetch();

            if (!$order) {
                $this->db->rollBack();
                return false;
            }

            $items = $this->items($orderId);

            foreach ($items as $item) {
                $restore = $this->db->prepare(
                    'UPDATE inventory SET quantity = quantity + :quantity WHERE product_id = :product_id'
                );
                $restore->execute([
                    'quantity' => (int) $item['quantity'],
                    'product_id' => (int) $item['product_id'],
                ]);
            }

            $this->execute('UPDATE orders SET status = "cancelled" WHERE id = :id', ['id' => $orderId]);
            $this->db->commit();

            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function countByStatus(?string $status = null): int
    {
        if ($status === null) {
            $row = $this->fetch('SELECT COUNT(*) AS total FROM orders');
            return (int) $row['total'];
        }

        $row = $this->fetch('SELECT COUNT(*) AS total FROM orders WHERE status = :status', [
            'status' => $status,
        ]);

        return (int) $row['total'];
    }

    public function salesTotal(): float
    {
        $row = $this->fetch(
            'SELECT COALESCE(SUM(o.total_amount), 0) AS total
             FROM orders o
             INNER JOIN payments p ON p.order_id = o.id
             WHERE p.status = "confirmed"'
        );

        return (float) $row['total'];
    }

    public function monthlySales(): array
    {
        return $this->fetchAll(
            'SELECT DATE_FORMAT(o.created_at, "%Y-%m") AS month, COALESCE(SUM(o.total_amount), 0) AS total
             FROM orders o
             INNER JOIN payments p ON p.order_id = o.id
             WHERE p.status = "confirmed"
             GROUP BY DATE_FORMAT(o.created_at, "%Y-%m")
             ORDER BY month DESC
             LIMIT 12'
        );
    }

    public function salesSeries(string $group = 'daily'): array
    {
        $format = match ($group) {
            'weekly' => '%x-W%v',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        return $this->fetchAll(
            'SELECT DATE_FORMAT(o.created_at, "' . $format . '") AS label,
                    COALESCE(SUM(o.total_amount), 0) AS total
             FROM orders o
             INNER JOIN payments p ON p.order_id = o.id
             WHERE p.status = "confirmed"
             GROUP BY DATE_FORMAT(o.created_at, "' . $format . '")
             ORDER BY label ASC
             LIMIT 12'
        );
    }

    public function statusCounts(): array
    {
        return $this->fetchAll(
            'SELECT status AS label, COUNT(*) AS total
             FROM orders
             GROUP BY status
             ORDER BY total DESC'
        );
    }

    public function recent(int $limit = 8): array
    {
        return $this->fetchAll(
            'SELECT o.*, u.name AS customer_name, p.status AS payment_status
             FROM orders o
             INNER JOIN users u ON u.id = o.user_id
             LEFT JOIN payments p ON p.order_id = o.id
             ORDER BY o.created_at DESC
             LIMIT ' . (int) $limit
        );
    }

    private function generateOrderNumber(): string
    {
        return 'ORD-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}
