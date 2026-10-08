<?php

/**
 * Tabular data for the report downloads (Reports page). Each report returns
 * ['title' => ..., 'headers' => [...], 'rows' => [[...], ...]], filtered by date range and branch.
 */
class ReportExport extends Model
{
    public const REPORTS = [
        'sales' => 'Online orders',
        'store_sales' => 'Counter sales',
        'payments' => 'Payments',
        'inventory' => 'Inventory',
        'shipments' => 'Shipments',
    ];

    public function build(string $report, ?string $from, ?string $to, ?int $locationId): array
    {
        return match ($report) {
            'sales' => $this->sales($from, $to, $locationId),
            'store_sales' => $this->storeSales($from, $to, $locationId),
            'payments' => $this->payments($from, $to, $locationId),
            'inventory' => $this->inventory($locationId),
            'shipments' => $this->shipments($from, $to, $locationId),
            default => throw new InvalidArgumentException('Unknown report.'),
        };
    }

    private function sales(?string $from, ?string $to, ?int $locationId): array
    {
        [$where, $params] = $this->range('o.created_at', $from, $to, $locationId, 'o.location_id');
        $rows = $this->rows(
            "SELECT o.order_number, DATE_FORMAT(o.created_at, '%Y-%m-%d %H:%i'), u.name, u.phone, l.name,
                    (SELECT COALESCE(SUM(oi.quantity), 0) FROM order_items oi WHERE oi.order_id = o.id),
                    o.total_amount, o.status, COALESCE(p.status, '-'), COALESCE(p.method, '-')
             FROM orders o
             INNER JOIN users u ON u.id = o.user_id
             LEFT JOIN locations l ON l.id = o.location_id
             LEFT JOIN payments p ON p.order_id = o.id
             {$where}
             ORDER BY o.created_at DESC",
            $params
        );

        return $this->table('Online orders', ['Order', 'Date', 'Customer', 'Phone', 'Branch', 'Items', 'Total (TZS)', 'Order status', 'Payment status', 'Payment method'], $rows);
    }

    private function storeSales(?string $from, ?string $to, ?int $locationId): array
    {
        [$where, $params] = $this->range('s.sale_date', $from, $to, $locationId, 's.location_id');
        $rows = $this->rows(
            "SELECT s.sale_number, DATE_FORMAT(s.sale_date, '%Y-%m-%d %H:%i'), COALESCE(s.customer_name, 'Walk-in'), l.name,
                    s.payment_method, s.total_amount, u.name
             FROM store_sales s
             LEFT JOIN locations l ON l.id = s.location_id
             LEFT JOIN users u ON u.id = s.sold_by
             {$where}
             ORDER BY s.sale_date DESC",
            $params
        );

        return $this->table('Counter sales', ['Sale', 'Date', 'Customer', 'Branch', 'Payment method', 'Total (TZS)', 'Sold by'], $rows);
    }

    private function payments(?string $from, ?string $to, ?int $locationId): array
    {
        [$where, $params] = $this->range('p.created_at', $from, $to, $locationId, 'COALESCE(o.location_id, i.location_id)');
        $rows = $this->rows(
            "SELECT DATE_FORMAT(p.created_at, '%Y-%m-%d %H:%i'), COALESCE(o.order_number, i.invoice_number, '-'),
                    COALESCE(u.name, i.customer_name, '-'), p.method, COALESCE(p.payment_reference, '-'), p.amount, p.status,
                    COALESCE(DATE_FORMAT(p.confirmed_at, '%Y-%m-%d %H:%i'), '-')
             FROM payments p
             LEFT JOIN orders o ON o.id = p.order_id
             LEFT JOIN users u ON u.id = o.user_id
             LEFT JOIN invoices i ON i.invoice_id = p.invoice_id
             {$where}
             ORDER BY p.created_at DESC",
            $params
        );

        return $this->table('Payments', ['Date', 'Order / invoice', 'Customer', 'Method', 'Reference', 'Amount (TZS)', 'Status', 'Confirmed at'], $rows);
    }

    private function inventory(?int $locationId): array
    {
        $params = [];
        $where = 'WHERE p.status = "active"';
        if ($locationId !== null) {
            $where .= ' AND i.location_id = :location_id';
            $params['location_id'] = $locationId;
        }
        $rows = $this->rows(
            "SELECT p.name, i.sku, p.category, p.brand, COALESCE(p.country_of_origin, '-'), l.name,
                    i.quantity, i.reorder_level, p.price, i.quantity * p.price,
                    CASE WHEN i.quantity <= 0 THEN 'Out of stock' WHEN i.quantity <= i.reorder_level THEN 'Low' ELSE 'OK' END
             FROM inventory i
             INNER JOIN products p ON p.id = i.product_id
             LEFT JOIN locations l ON l.id = i.location_id
             {$where}
             ORDER BY p.category, p.name, l.name",
            $params
        );

        return $this->table('Inventory', ['Product', 'SKU', 'Category', 'Brand', 'Origin', 'Branch', 'Quantity', 'Reorder level', 'Unit price (TZS)', 'Stock value (TZS)', 'Stock status'], $rows);
    }

    private function shipments(?string $from, ?string $to, ?int $locationId): array
    {
        [$where, $params] = $this->range('s.created_at', $from, $to, $locationId, 'o.location_id');
        $rows = $this->rows(
            "SELECT s.tracking_number, o.order_number, u.name, s.status, s.origin, s.destination, COALESCE(s.carrier, '-'),
                    COALESCE(DATE_FORMAT(s.expected_arrival, '%Y-%m-%d'), '-'), COALESCE(DATE_FORMAT(s.delivered_at, '%Y-%m-%d'), '-'),
                    DATE_FORMAT(s.created_at, '%Y-%m-%d')
             FROM shipments s
             INNER JOIN orders o ON o.id = s.order_id
             INNER JOIN users u ON u.id = o.user_id
             {$where}
             ORDER BY s.created_at DESC",
            $params
        );

        return $this->table('Shipments', ['Tracking number', 'Order', 'Customer', 'Stage', 'Origin', 'Destination', 'Carrier', 'Expected arrival', 'Delivered', 'Created'], $rows);
    }

    /**
     * Rows by position, not by column name (several columns are called "name").
     */
    private function rows(string $sql, array $params): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_NUM);
    }

    private function range(string $dateColumn, ?string $from, ?string $to, ?int $locationId, string $locationColumn): array
    {
        $where = [];
        $params = [];
        if ($from !== null) {
            $where[] = "{$dateColumn} >= :date_from";
            $params['date_from'] = $from . ' 00:00:00';
        }
        if ($to !== null) {
            $where[] = "{$dateColumn} <= :date_to";
            $params['date_to'] = $to . ' 23:59:59';
        }
        if ($locationId !== null) {
            $where[] = "{$locationColumn} = :location_id";
            $params['location_id'] = $locationId;
        }

        return [$where ? 'WHERE ' . implode(' AND ', $where) : '', $params];
    }

    private function table(string $title, array $headers, array $rows): array
    {
        return [
            'title' => $title,
            'headers' => $headers,
            'rows' => array_map(static fn (array $row): array => array_map(
                static fn ($value) => is_string($value) && in_array($value, ['pending', 'confirmed', 'rejected', 'processing', 'ready', 'shipped', 'delivered', 'cancelled', 'cash', 'bank_transfer', 'mobile_money', 'card', 'order_received', 'payment_confirmed', 'ordered_from_supplier', 'shipped_from_origin', 'in_transit', 'arrived_at_port', 'cleared', 'ready_for_pickup'], true) ? readable_status($value) : $value,
                array_values($row)
            ), $rows),
        ];
    }
}
