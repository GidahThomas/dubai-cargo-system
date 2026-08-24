<?php

class Shipment extends Model
{
    public static function statuses(): array
    {
        return [
            'order_received',
            'payment_confirmed',
            'ordered_from_supplier',
            'shipped_from_origin',
            'in_transit',
            'arrived_at_port',
            'cleared',
            'ready_for_pickup',
            'delivered',
        ];
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT s.*, o.order_number, o.total_amount, o.user_id,
                       u.name AS customer_name, u.email AS customer_email
                FROM shipments s
                INNER JOIN orders o ON o.id = s.order_id
                INNER JOIN users u ON u.id = o.user_id';
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = 'o.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 's.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(s.tracking_number LIKE :search_tracking OR o.order_number LIKE :search_order OR u.name LIKE :search_customer)';
            $params['search_tracking'] = '%' . $filters['search'] . '%';
            $params['search_order'] = '%' . $filters['search'] . '%';
            $params['search_customer'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(s.created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(s.created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY s.created_at DESC';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            'SELECT s.*, o.order_number, o.user_id, u.name AS customer_name
             FROM shipments s
             INNER JOIN orders o ON o.id = s.order_id
             INNER JOIN users u ON u.id = o.user_id
             WHERE s.id = :id',
            ['id' => $id]
        );
    }

    public function findByTrackingNumber(string $trackingNumber, ?int $userId = null): ?array
    {
        $sql = 'SELECT s.*, o.order_number, o.total_amount, o.user_id, u.name AS customer_name
                FROM shipments s
                INNER JOIN orders o ON o.id = s.order_id
                INNER JOIN users u ON u.id = o.user_id
                WHERE s.tracking_number = :tracking_number';
        $params = ['tracking_number' => $trackingNumber];

        if ($userId !== null) {
            $sql .= ' AND o.user_id = :user_id';
            $params['user_id'] = $userId;
        }

        return $this->fetch($sql, $params);
    }

    public function createForOrder(int $orderId, array $data, int $createdBy): int
    {
        $order = $this->fetch(
            'SELECT o.*, p.status AS payment_status
             FROM orders o
             LEFT JOIN payments p ON p.order_id = o.id
             WHERE o.id = :id',
            ['id' => $orderId]
        );

        if (!$order) {
            throw new RuntimeException('Order was not found.');
        }

        $status = ($order['payment_status'] ?? null) === 'confirmed' ? 'payment_confirmed' : 'order_received';

        $stmt = $this->db->prepare(
            'INSERT INTO shipments (order_id, tracking_number, status, origin, destination, carrier, expected_arrival, notes, created_by)
             VALUES (:order_id, :tracking_number, :status, :origin, :destination, :carrier, :expected_arrival, :notes, :created_by)'
        );

        $stmt->execute([
            'order_id' => $orderId,
            'tracking_number' => ($data['tracking_number'] ?? null) ?: $this->generateTrackingNumber(),
            'status' => ($data['status'] ?? null) ?: $status,
            'origin' => ($data['origin'] ?? null) ?: 'Dubai',
            'destination' => ($data['destination'] ?? null) ?: $order['shipping_address'],
            'carrier' => $data['carrier'] ?? null,
            'expected_arrival' => ($data['expected_arrival'] ?? null) ?: null,
            'notes' => $data['notes'] ?? null,
            'created_by' => $createdBy,
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function updateStatus(int $id, string $status, ?string $notes = null): bool
    {
        $this->db->beginTransaction();

        try {
            $shipment = $this->find($id);

            if (!$shipment) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare(
                'UPDATE shipments
                 SET status = :status,
                     notes = :notes,
                     delivered_at = CASE WHEN :status_for_date = "delivered" THEN CURRENT_TIMESTAMP ELSE delivered_at END
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $id,
                'status' => $status,
                'status_for_date' => $status,
                'notes' => $notes,
            ]);

            $orderStatus = $this->orderStatusForShipment($status);

            if ($orderStatus !== null) {
                $this->execute(
                    'UPDATE orders SET status = :status WHERE id = :id',
                    ['id' => (int) $shipment['order_id'], 'status' => $orderStatus]
                );
            }

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
            $row = $this->fetch('SELECT COUNT(*) AS total FROM shipments');
            return (int) $row['total'];
        }

        $row = $this->fetch('SELECT COUNT(*) AS total FROM shipments WHERE status = :status', [
            'status' => $status,
        ]);

        return (int) $row['total'];
    }

    public function reportByStatus(): array
    {
        return $this->fetchAll(
            'SELECT status, COUNT(*) AS total
             FROM shipments
             GROUP BY status
             ORDER BY total DESC'
        );
    }

    public function statusCounts(): array
    {
        return $this->reportByStatus();
    }

    private function generateTrackingNumber(): string
    {
        return 'DCF-' . date('Ymd-His') . '-' . random_int(100, 999);
    }

    private function orderStatusForShipment(string $shipmentStatus): ?string
    {
        return match ($shipmentStatus) {
            'payment_confirmed' => 'confirmed',
            'ordered_from_supplier' => 'processing',
            'shipped_from_origin', 'in_transit', 'arrived_at_port', 'cleared' => 'shipped',
            'ready_for_pickup' => 'ready',
            'delivered' => 'delivered',
            default => null,
        };
    }
}
