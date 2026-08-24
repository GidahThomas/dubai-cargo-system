<?php

class Delivery extends Model
{
    public static function statuses(): array
    {
        return ['pending', 'preparing', 'packed', 'out_for_delivery', 'delivered', 'cancelled', 'returned'];
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT d.*, q.customer_name AS quotation_customer, i.invoice_number
                FROM deliveries d
                LEFT JOIN quotations q ON q.id = d.quotation_id
                LEFT JOIN invoices i ON i.invoice_id = d.invoice_id';
        $where = [];
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::statuses(), true)) {
            $where[] = 'd.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(d.customer_name LIKE :search_customer
                OR d.phone LIKE :search_phone
                OR d.tracking_number LIKE :search_tracking
                OR i.invoice_number LIKE :search_invoice)';
            $params['search_customer'] = '%' . $filters['search'] . '%';
            $params['search_phone'] = '%' . $filters['search'] . '%';
            $params['search_tracking'] = '%' . $filters['search'] . '%';
            $params['search_invoice'] = '%' . $filters['search'] . '%';
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY d.created_at DESC';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM deliveries WHERE id = :id', ['id' => $id]);
    }

    public function updateStatus(int $id, string $status): bool
    {
        if (!in_array($status, self::statuses(), true)) {
            return false;
        }

        return $this->execute(
            'UPDATE deliveries SET status = :status WHERE id = :id',
            ['id' => $id, 'status' => $status]
        );
    }

    public function countByStatus(?string $status = null): int
    {
        if ($status === null) {
            $row = $this->fetch('SELECT COUNT(*) AS total FROM deliveries');
            return (int) $row['total'];
        }

        $row = $this->fetch('SELECT COUNT(*) AS total FROM deliveries WHERE status = :status', ['status' => $status]);

        return (int) $row['total'];
    }
}
