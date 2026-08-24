<?php

class StoreLedger extends Model
{
    public function recordStockEntry(array $data, int $userId): int
    {
        $quantity = (int) $data['quantity'];

        if ($quantity < 1) {
            throw new InvalidArgumentException('Quantity must be at least 1.');
        }

        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO stock_entries (product_id, quantity, unit_cost, supplier_name, received_date, received_by, notes)
                 VALUES (:product_id, :quantity, :unit_cost, :supplier_name, :received_date, :received_by, :notes)'
            );

            $stmt->execute([
                'product_id' => (int) $data['product_id'],
                'quantity' => $quantity,
                'unit_cost' => (float) ($data['unit_cost'] ?? 0),
                'supplier_name' => ($data['supplier_name'] ?? null) ?: null,
                'received_date' => ($data['received_date'] ?? null) ?: date('Y-m-d'),
                'received_by' => $userId,
                'notes' => ($data['notes'] ?? null) ?: null,
            ]);

            $entryId = (int) $this->db->lastInsertId();

            $stock = $this->db->prepare(
                'UPDATE inventory SET quantity = quantity + :quantity WHERE product_id = :product_id'
            );
            $stock->execute([
                'quantity' => $quantity,
                'product_id' => (int) $data['product_id'],
            ]);

            $this->db->commit();

            return $entryId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function recordSale(array $data, int $userId): int
    {
        $productId = (int) $data['product_id'];
        $quantity = (int) $data['quantity'];
        $unitPrice = (float) $data['unit_price'];

        if ($productId < 1 || $quantity < 1 || $unitPrice < 0) {
            throw new InvalidArgumentException('Product, quantity, and price are required.');
        }

        $this->db->beginTransaction();

        try {
            $productStmt = $this->db->prepare(
                'SELECT p.name, i.quantity AS stock
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
                throw new RuntimeException('Stock haitoshi kwa bidhaa hii.');
            }

            $lineTotal = $quantity * $unitPrice;

            $saleStmt = $this->db->prepare(
                'INSERT INTO store_sales (sale_number, customer_name, payment_method, total_amount, sale_date, sold_by, notes)
                 VALUES (:sale_number, :customer_name, :payment_method, :total_amount, :sale_date, :sold_by, :notes)'
            );
            $saleStmt->execute([
                'sale_number' => $this->generateSaleNumber(),
                'customer_name' => ($data['customer_name'] ?? null) ?: 'Walk-in Customer',
                'payment_method' => ($data['payment_method'] ?? null) ?: 'cash',
                'total_amount' => $lineTotal,
                'sale_date' => ($data['sale_date'] ?? null) ?: date('Y-m-d'),
                'sold_by' => $userId,
                'notes' => ($data['notes'] ?? null) ?: null,
            ]);

            $saleId = (int) $this->db->lastInsertId();

            $itemStmt = $this->db->prepare(
                'INSERT INTO store_sale_items (sale_id, product_id, quantity, unit_price, line_total)
                 VALUES (:sale_id, :product_id, :quantity, :unit_price, :line_total)'
            );
            $itemStmt->execute([
                'sale_id' => $saleId,
                'product_id' => $productId,
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal,
            ]);

            $stockStmt = $this->db->prepare(
                'UPDATE inventory SET quantity = quantity - :quantity WHERE product_id = :product_id'
            );
            $stockStmt->execute([
                'quantity' => $quantity,
                'product_id' => $productId,
            ]);

            $this->db->commit();

            return $saleId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function stockEntries(string $period = 'day'): array
    {
        [$start, $end] = $this->periodRange($period);

        return $this->fetchAll(
            'SELECT se.*, p.name AS product_name, p.brand, i.sku, u.name AS received_by_name
             FROM stock_entries se
             INNER JOIN products p ON p.id = se.product_id
             LEFT JOIN inventory i ON i.product_id = p.id
             LEFT JOIN users u ON u.id = se.received_by
             WHERE se.received_date BETWEEN :start_date AND :end_date
             ORDER BY se.received_date DESC, se.id DESC',
            ['start_date' => $start, 'end_date' => $end]
        );
    }

    public function sales(string $period = 'day'): array
    {
        [$start, $end] = $this->periodRange($period);

        return $this->fetchAll(
            'SELECT ss.*, ssi.quantity, ssi.unit_price, ssi.line_total,
                    p.name AS product_name, p.brand, i.sku, u.name AS sold_by_name
             FROM store_sales ss
             INNER JOIN store_sale_items ssi ON ssi.sale_id = ss.id
             INNER JOIN products p ON p.id = ssi.product_id
             LEFT JOIN inventory i ON i.product_id = p.id
             LEFT JOIN users u ON u.id = ss.sold_by
             WHERE ss.sale_date BETWEEN :start_date AND :end_date
             ORDER BY ss.sale_date DESC, ss.id DESC',
            ['start_date' => $start, 'end_date' => $end]
        );
    }

    public function summary(string $period = 'day'): array
    {
        [$start, $end] = $this->periodRange($period);

        $stock = $this->fetch(
            'SELECT COALESCE(SUM(quantity), 0) AS units_in,
                    COALESCE(SUM(quantity * unit_cost), 0) AS stock_cost
             FROM stock_entries
             WHERE received_date BETWEEN :start_date AND :end_date',
            ['start_date' => $start, 'end_date' => $end]
        );

        $sales = $this->fetch(
            'SELECT COALESCE(SUM(ssi.quantity), 0) AS units_sold,
                    COALESCE(SUM(ssi.line_total), 0) AS revenue,
                    COUNT(DISTINCT ss.id) AS sale_count
             FROM store_sales ss
             INNER JOIN store_sale_items ssi ON ssi.sale_id = ss.id
             WHERE ss.sale_date BETWEEN :start_date AND :end_date',
            ['start_date' => $start, 'end_date' => $end]
        );

        return [
            'start_date' => $start,
            'end_date' => $end,
            'units_in' => (int) $stock['units_in'],
            'stock_cost' => (float) $stock['stock_cost'],
            'units_sold' => (int) $sales['units_sold'],
            'revenue' => (float) $sales['revenue'],
            'sale_count' => (int) $sales['sale_count'],
        ];
    }

    public function topSellingProducts(string $period = 'month'): array
    {
        [$start, $end] = $this->periodRange($period);

        return $this->fetchAll(
            'SELECT p.name, p.brand, COALESCE(SUM(ssi.quantity), 0) AS units_sold,
                    COALESCE(SUM(ssi.line_total), 0) AS revenue
             FROM store_sale_items ssi
             INNER JOIN store_sales ss ON ss.id = ssi.sale_id
             INNER JOIN products p ON p.id = ssi.product_id
             WHERE ss.sale_date BETWEEN :start_date AND :end_date
             GROUP BY p.id, p.name, p.brand
             ORDER BY units_sold DESC, revenue DESC
             LIMIT 10',
            ['start_date' => $start, 'end_date' => $end]
        );
    }

    public function periodRange(string $period): array
    {
        $today = new DateTimeImmutable('today');

        return match ($period) {
            'week' => [
                $today->modify('monday this week')->format('Y-m-d'),
                $today->modify('sunday this week')->format('Y-m-d'),
            ],
            'month' => [
                $today->modify('first day of this month')->format('Y-m-d'),
                $today->modify('last day of this month')->format('Y-m-d'),
            ],
            default => [$today->format('Y-m-d'), $today->format('Y-m-d')],
        };
    }

    public function transactions(array $filters = []): array
    {
        $stockSql = 'SELECT "stock_in" AS transaction_type, se.id, se.received_date AS transaction_date,
                            p.name AS product_name, i.sku, se.quantity,
                            se.unit_cost AS unit_amount, (se.quantity * se.unit_cost) AS total_amount,
                            se.supplier_name AS party_name, se.notes
                     FROM stock_entries se
                     INNER JOIN products p ON p.id = se.product_id
                     LEFT JOIN inventory i ON i.product_id = p.id';

        $salesSql = 'SELECT "sale" AS transaction_type, ss.id, ss.sale_date AS transaction_date,
                            p.name AS product_name, i.sku, ssi.quantity,
                            ssi.unit_price AS unit_amount, ssi.line_total AS total_amount,
                            ss.customer_name AS party_name, ss.notes
                     FROM store_sales ss
                     INNER JOIN store_sale_items ssi ON ssi.sale_id = ss.id
                     INNER JOIN products p ON p.id = ssi.product_id
                     LEFT JOIN inventory i ON i.product_id = p.id';

        $queries = [];
        $params = [];

        if (($filters['type'] ?? '') !== 'sale') {
            [$where, $whereParams] = $this->transactionWhere($filters, 'stock');
            $queries[] = $stockSql . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
            $params = array_merge($params, $whereParams);
        }

        if (($filters['type'] ?? '') !== 'stock_in') {
            [$where, $whereParams] = $this->transactionWhere($filters, 'sale');
            $queries[] = $salesSql . ($where ? ' WHERE ' . implode(' AND ', $where) : '');
            $params = array_merge($params, $whereParams);
        }

        if (!$queries) {
            return [];
        }

        return $this->fetchAll(
            implode(' UNION ALL ', $queries) . ' ORDER BY transaction_date DESC, id DESC',
            $params
        );
    }

    public function chartSeries(string $group = 'daily'): array
    {
        $format = match ($group) {
            'weekly' => '%x-W%v',
            'monthly' => '%Y-%m',
            default => '%Y-%m-%d',
        };

        $rows = $this->fetchAll(
            'SELECT DATE_FORMAT(ss.sale_date, "' . $format . '") AS label,
                    COALESCE(SUM(ssi.line_total), 0) AS total
             FROM store_sales ss
             INNER JOIN store_sale_items ssi ON ssi.sale_id = ss.id
             GROUP BY DATE_FORMAT(ss.sale_date, "' . $format . '")
             ORDER BY label ASC'
        );

        $totals = array_column($rows, 'total', 'label');

        return array_map(
            static fn (string $label): array => ['label' => $label, 'total' => $totals[$label] ?? 0],
            $this->periodLabels($group)
        );
    }

    private function periodLabels(string $group): array
    {
        $today = new DateTimeImmutable('today');

        return match ($group) {
            'weekly' => (function () use ($today): array {
                $labels = [];
                for ($i = 7; $i >= 0; $i--) {
                    $week = $today->modify("-{$i} weeks");
                    $labels[] = $week->format('o') . '-W' . $week->format('W');
                }
                return $labels;
            })(),
            'monthly' => (function () use ($today): array {
                $labels = [];
                $firstOfMonth = $today->modify('first day of this month');
                for ($i = 5; $i >= 0; $i--) {
                    $labels[] = $firstOfMonth->modify("-{$i} months")->format('Y-m');
                }
                return $labels;
            })(),
            default => (function () use ($today): array {
                $labels = [];
                for ($i = 13; $i >= 0; $i--) {
                    $labels[] = $today->modify("-{$i} days")->format('Y-m-d');
                }
                return $labels;
            })(),
        };
    }

    private function transactionWhere(array $filters, string $source): array
    {
        $where = [];
        $params = [];
        $prefix = $source === 'stock' ? 'stock_' : 'sale_';
        $dateColumn = $source === 'stock' ? 'se.received_date' : 'ss.sale_date';
        $amountExpression = $source === 'stock' ? '(se.quantity * se.unit_cost)' : 'ssi.line_total';
        $searchColumn = $source === 'stock' ? 'p.name' : 'p.name';

        if (!empty($filters['search'])) {
            $where[] = '(' . $searchColumn . ' LIKE :' . $prefix . 'search_product OR i.sku LIKE :' . $prefix . 'search_sku)';
            $params[$prefix . 'search_product'] = '%' . $filters['search'] . '%';
            $params[$prefix . 'search_sku'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[] = $dateColumn . ' >= :' . $prefix . 'date_from';
            $params[$prefix . 'date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = $dateColumn . ' <= :' . $prefix . 'date_to';
            $params[$prefix . 'date_to'] = $filters['date_to'];
        }

        if (($filters['min_amount'] ?? '') !== '') {
            $where[] = $amountExpression . ' >= :' . $prefix . 'min_amount';
            $params[$prefix . 'min_amount'] = (float) $filters['min_amount'];
        }

        if (($filters['max_amount'] ?? '') !== '') {
            $where[] = $amountExpression . ' <= :' . $prefix . 'max_amount';
            $params[$prefix . 'max_amount'] = (float) $filters['max_amount'];
        }

        return [$where, $params];
    }

    private function generateSaleNumber(): string
    {
        return 'SALE-' . date('Ymd-His') . '-' . random_int(100, 999);
    }
}
