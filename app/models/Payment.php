<?php

class Payment extends Model
{
    public static function statuses(): array
    {
        return ['pending', 'confirmed', 'rejected'];
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT p.*, o.order_number, o.user_id, o.status AS order_status,
                       i.invoice_number, i.status AS invoice_status,
                       COALESCE(u.name, i.customer_name) AS customer_name,
                       COALESCE(u.email, i.customer_email) AS customer_email
                FROM payments p
                LEFT JOIN orders o ON o.id = p.order_id
                LEFT JOIN users u ON u.id = o.user_id
                LEFT JOIN invoices i ON i.invoice_id = p.invoice_id
                LEFT JOIN customers ic ON ic.id = i.customer_id';
        $where = [];
        $params = [];

        if (!empty($filters['user_id'])) {
            $where[] = '(o.user_id = :order_user_id OR ic.user_id = :invoice_user_id)';
            $params['order_user_id'] = (int) $filters['user_id'];
            $params['invoice_user_id'] = (int) $filters['user_id'];
        }

        if (!empty($filters['status'])) {
            $where[] = 'p.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['method'])) {
            $where[] = 'p.method = :method';
            $params['method'] = $filters['method'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(o.order_number LIKE :search_order
                OR i.invoice_number LIKE :search_invoice
                OR u.name LIKE :search_customer
                OR i.customer_name LIKE :search_invoice_customer
                OR p.payment_reference LIKE :search_reference)';
            $params['search_order'] = '%' . $filters['search'] . '%';
            $params['search_invoice'] = '%' . $filters['search'] . '%';
            $params['search_customer'] = '%' . $filters['search'] . '%';
            $params['search_invoice_customer'] = '%' . $filters['search'] . '%';
            $params['search_reference'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'DATE(p.created_at) >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'DATE(p.created_at) <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.created_at DESC';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        return $this->fetch(
            'SELECT p.*, o.order_number, o.user_id, i.invoice_number, i.customer_id,
                    COALESCE(u.name, i.customer_name) AS customer_name
             FROM payments p
             LEFT JOIN orders o ON o.id = p.order_id
             LEFT JOIN users u ON u.id = o.user_id
             LEFT JOIN invoices i ON i.invoice_id = p.invoice_id
             WHERE p.id = :id',
            ['id' => $id]
        );
    }

    public function submit(int $paymentId, int $userId, array $data): bool
    {
        return $this->execute(
            'UPDATE payments p
             LEFT JOIN orders o ON o.id = p.order_id
             LEFT JOIN invoices i ON i.invoice_id = p.invoice_id
             LEFT JOIN customers c ON c.id = i.customer_id
             SET p.payment_reference = :payment_reference,
                 p.method = :method,
                 p.notes = :notes,
                 p.submitted_at = CURRENT_TIMESTAMP,
                 p.status = "pending"
             WHERE p.id = :id AND (o.user_id = :order_user_id OR c.user_id = :invoice_user_id)',
            [
                'id' => $paymentId,
                'order_user_id' => $userId,
                'invoice_user_id' => $userId,
                'payment_reference' => $data['payment_reference'],
                'method' => $data['method'],
                'notes' => $data['notes'] ?? null,
            ]
        );
    }

    /**
     * @param int|null $confirmedBy the staff member, or null when a payment gateway confirmed it
     */
    public function updateStatus(int $paymentId, string $status, ?int $confirmedBy, ?string $notes = null): bool
    {
        $this->db->beginTransaction();

        try {
            $payment = $this->find($paymentId);

            if (!$payment) {
                $this->db->rollBack();
                return false;
            }

            $stmt = $this->db->prepare(
                'UPDATE payments
                 SET status = :status,
                     confirmed_by = :confirmed_by,
                     confirmed_at = CASE WHEN :status_for_date = "confirmed" THEN CURRENT_TIMESTAMP ELSE confirmed_at END,
                     notes = :notes
                 WHERE id = :id'
            );
            $stmt->execute([
                'id' => $paymentId,
                'status' => $status,
                'status_for_date' => $status,
                'confirmed_by' => $confirmedBy,
                'notes' => $notes,
            ]);

            if ($status === 'confirmed' && !empty($payment['order_id'])) {
                $this->execute(
                    'UPDATE orders
                     SET status = CASE WHEN status = "pending" THEN "confirmed" ELSE status END
                     WHERE id = :order_id',
                    ['order_id' => (int) $payment['order_id']]
                );

                $this->execute(
                    'UPDATE shipments
                     SET status = CASE WHEN status = "order_received" THEN "payment_confirmed" ELSE status END
                     WHERE order_id = :order_id',
                    ['order_id' => (int) $payment['order_id']]
                );
            }

            if (!empty($payment['invoice_id'])) {
                (new Invoice())->syncPaymentStatus((int) $payment['invoice_id']);
            }

            $this->db->commit();

            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function countByStatus(string $status): int
    {
        $row = $this->fetch('SELECT COUNT(*) AS total FROM payments WHERE status = :status', [
            'status' => $status,
        ]);

        return (int) $row['total'];
    }

    public function statusCounts(): array
    {
        return $this->fetchAll(
            'SELECT status AS label, COUNT(*) AS total
             FROM payments
             GROUP BY status
             ORDER BY total DESC'
        );
    }
}
