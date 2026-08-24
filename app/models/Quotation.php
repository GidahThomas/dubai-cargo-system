<?php

class Quotation extends Model
{
    public static function statuses(): array
    {
        return ['pending', 'reviewed', 'approved', 'rejected', 'converted_to_invoice'];
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT * FROM quotations';
        $where = [];
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::statuses(), true)) {
            $where[] = 'status = :status';
            $params['status'] = $filters['status'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY created_at DESC';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $id): ?array
    {
        $quotation = $this->fetch('SELECT * FROM quotations WHERE id = :id', ['id' => $id]);

        if ($quotation) {
            $quotation['items'] = $this->items($id);
        }

        return $quotation;
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO quotations (
                    customer_name, company_name, phone, email, delivery_full_name, delivery_phone,
                    delivery_address, delivery_region, delivery_district, delivery_ward,
                    delivery_landmark, delivery_date, delivery_time, delivery_instructions,
                    delivery_method, status, subtotal, discount, transport_cost,
                    installation_cost, tax, grand_total, admin_notes
                ) VALUES (
                    :customer_name, :company_name, :phone, :email, :delivery_full_name, :delivery_phone,
                    :delivery_address, :delivery_region, :delivery_district, :delivery_ward,
                    :delivery_landmark, :delivery_date, :delivery_time, :delivery_instructions,
                    :delivery_method, :status, :subtotal, :discount, :transport_cost,
                    :installation_cost, :tax, :grand_total, :admin_notes
                )'
            );

            $stmt->execute([
                'customer_name' => $data['customer_name'],
                'company_name' => $data['company_name'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'delivery_full_name' => $data['delivery_full_name'] ?? null,
                'delivery_phone' => $data['delivery_phone'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_region' => $data['delivery_region'] ?? null,
                'delivery_district' => $data['delivery_district'] ?? null,
                'delivery_ward' => $data['delivery_ward'] ?? null,
                'delivery_landmark' => $data['delivery_landmark'] ?? null,
                'delivery_date' => $data['delivery_date'] ?? null,
                'delivery_time' => $data['delivery_time'] ?? null,
                'delivery_instructions' => $data['delivery_instructions'] ?? null,
                'delivery_method' => $data['delivery_method'] ?? 'home_delivery',
                'status' => $data['status'] ?? 'pending',
                'subtotal' => (float) ($data['subtotal'] ?? 0),
                'discount' => (float) ($data['discount'] ?? 0),
                'transport_cost' => (float) ($data['transport_cost'] ?? 0),
                'installation_cost' => (float) ($data['installation_cost'] ?? 0),
                'tax' => (float) ($data['tax'] ?? 0),
                'grand_total' => (float) ($data['grand_total'] ?? 0),
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            $quotationId = (int) $this->db->lastInsertId();
            $itemStmt = $this->db->prepare(
                'INSERT INTO quotation_items (quotation_id, product_id, product_name, product_image, quantity, unit_price, discount, notes, line_total)
                 VALUES (:quotation_id, :product_id, :product_name, :product_image, :quantity, :unit_price, :discount, :notes, :line_total)'
            );

            foreach ($data['items'] ?? [] as $item) {
                $itemStmt->execute([
                    'quotation_id' => $quotationId,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'product_image' => $item['product_image'] ?? null,
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'discount' => (float) ($item['discount'] ?? 0),
                    'notes' => $item['notes'] ?? null,
                    'line_total' => (float) ($item['line_total'] ?? 0),
                ]);
            }

            $this->db->commit();

            return $quotationId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update(int $id, array $data): bool
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'UPDATE quotations SET
                    customer_name = :customer_name, company_name = :company_name, phone = :phone, email = :email,
                    delivery_full_name = :delivery_full_name, delivery_phone = :delivery_phone,
                    delivery_address = :delivery_address, delivery_region = :delivery_region,
                    delivery_district = :delivery_district, delivery_ward = :delivery_ward,
                    delivery_landmark = :delivery_landmark, delivery_date = :delivery_date,
                    delivery_time = :delivery_time, delivery_instructions = :delivery_instructions,
                    delivery_method = :delivery_method, status = :status, subtotal = :subtotal,
                    discount = :discount, transport_cost = :transport_cost,
                    installation_cost = :installation_cost, tax = :tax, grand_total = :grand_total,
                    admin_notes = :admin_notes
                 WHERE id = :id'
            );

            $stmt->execute([
                'id' => $id,
                'customer_name' => $data['customer_name'],
                'company_name' => $data['company_name'] ?? null,
                'phone' => $data['phone'],
                'email' => $data['email'] ?? null,
                'delivery_full_name' => $data['delivery_full_name'] ?? null,
                'delivery_phone' => $data['delivery_phone'] ?? null,
                'delivery_address' => $data['delivery_address'] ?? null,
                'delivery_region' => $data['delivery_region'] ?? null,
                'delivery_district' => $data['delivery_district'] ?? null,
                'delivery_ward' => $data['delivery_ward'] ?? null,
                'delivery_landmark' => $data['delivery_landmark'] ?? null,
                'delivery_date' => $data['delivery_date'] ?? null,
                'delivery_time' => $data['delivery_time'] ?? null,
                'delivery_instructions' => $data['delivery_instructions'] ?? null,
                'delivery_method' => $data['delivery_method'] ?? 'home_delivery',
                'status' => $data['status'] ?? 'pending',
                'subtotal' => (float) ($data['subtotal'] ?? 0),
                'discount' => (float) ($data['discount'] ?? 0),
                'transport_cost' => (float) ($data['transport_cost'] ?? 0),
                'installation_cost' => (float) ($data['installation_cost'] ?? 0),
                'tax' => (float) ($data['tax'] ?? 0),
                'grand_total' => (float) ($data['grand_total'] ?? 0),
                'admin_notes' => $data['admin_notes'] ?? null,
            ]);

            $this->db->prepare('DELETE FROM quotation_items WHERE quotation_id = :quotation_id')->execute(['quotation_id' => $id]);

            $itemStmt = $this->db->prepare(
                'INSERT INTO quotation_items (quotation_id, product_id, product_name, product_image, quantity, unit_price, discount, notes, line_total)
                 VALUES (:quotation_id, :product_id, :product_name, :product_image, :quantity, :unit_price, :discount, :notes, :line_total)'
            );

            foreach ($data['items'] ?? [] as $item) {
                $itemStmt->execute([
                    'quotation_id' => $id,
                    'product_id' => $item['product_id'] ?? null,
                    'product_name' => $item['product_name'],
                    'product_image' => $item['product_image'] ?? null,
                    'quantity' => (int) ($item['quantity'] ?? 1),
                    'unit_price' => (float) ($item['unit_price'] ?? 0),
                    'discount' => (float) ($item['discount'] ?? 0),
                    'notes' => $item['notes'] ?? null,
                    'line_total' => (float) ($item['line_total'] ?? 0),
                ]);
            }

            $this->db->commit();

            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function items(int $quotationId): array
    {
        return $this->fetchAll(
            'SELECT qi.*, p.name AS current_product_name, p.image AS current_product_image,
                    i.quantity AS stock_available
             FROM quotation_items qi
             LEFT JOIN products p ON p.id = qi.product_id
             LEFT JOIN inventory i ON i.product_id = qi.product_id
             WHERE qi.quotation_id = :quotation_id
             ORDER BY qi.id ASC',
            ['quotation_id' => $quotationId]
        );
    }

    public function countByStatus(string $status = 'pending'): int
    {
        $row = $this->fetch('SELECT COUNT(*) AS total FROM quotations WHERE status = :status', ['status' => $status]);

        return (int) $row['total'];
    }

    public function countByStatuses(array $statuses): int
    {
        $statuses = array_values(array_filter($statuses, static fn (string $status): bool => in_array($status, self::statuses(), true)));

        if (!$statuses) {
            return 0;
        }

        $placeholders = [];
        $params = [];

        foreach ($statuses as $index => $status) {
            $key = 'status_' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $status;
        }

        $row = $this->fetch(
            'SELECT COUNT(*) AS total FROM quotations WHERE status IN (' . implode(',', $placeholders) . ')',
            $params
        );

        return (int) $row['total'];
    }

    public function createDeliveryFromQuotation(int $quotationId, ?int $invoiceId = null): int
    {
        $quotation = $this->find($quotationId);

        if (!$quotation || !in_array($quotation['delivery_method'], ['home_delivery', 'courier'], true)) {
            throw new RuntimeException('No delivery required for this quotation.');
        }

        $existing = $this->fetch(
            'SELECT id FROM deliveries WHERE quotation_id = :quotation_id LIMIT 1',
            ['quotation_id' => $quotationId]
        );

        if ($existing) {
            $this->execute(
                'UPDATE deliveries SET invoice_id = COALESCE(:invoice_id, invoice_id) WHERE id = :id',
                ['id' => (int) $existing['id'], 'invoice_id' => $invoiceId]
            );

            return (int) $existing['id'];
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO deliveries (quotation_id, invoice_id, customer_name, phone, address, region, district, ward, landmark, delivery_method, preferred_date, preferred_time, special_instructions, status, tracking_number)
                 VALUES (:quotation_id, :invoice_id, :customer_name, :phone, :address, :region, :district, :ward, :landmark, :delivery_method, :preferred_date, :preferred_time, :special_instructions, :status, :tracking_number)'
            );

            $tracking = 'DEL-' . date('Ymd') . '-' . str_pad((string) $quotationId, 4, '0', STR_PAD_LEFT);
            $stmt->execute([
                'quotation_id' => $quotationId,
                'invoice_id' => $invoiceId,
                'customer_name' => $quotation['delivery_full_name'] ?: $quotation['customer_name'],
                'phone' => $quotation['delivery_phone'] ?: $quotation['phone'],
                'address' => $quotation['delivery_address'],
                'region' => $quotation['delivery_region'],
                'district' => $quotation['delivery_district'],
                'ward' => $quotation['delivery_ward'],
                'landmark' => $quotation['delivery_landmark'],
                'delivery_method' => $quotation['delivery_method'],
                'preferred_date' => $quotation['delivery_date'],
                'preferred_time' => $quotation['delivery_time'],
                'special_instructions' => $quotation['delivery_instructions'],
                'status' => 'pending',
                'tracking_number' => $tracking,
            ]);

            $this->db->commit();

            return (int) $this->db->lastInsertId();
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }
}
