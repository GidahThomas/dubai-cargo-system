<?php

class Invoice extends Model
{
    public const ACCESS_ROLES = ['manager', 'admin'];

    public static function statuses(): array
    {
        return ['draft', 'unpaid', 'paid', 'cancelled'];
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT i.*, u.name AS created_by_name
                FROM invoices i
                LEFT JOIN users u ON u.id = i.created_by';
        $where = [];
        $params = [];

        if (!empty($filters['status']) && in_array($filters['status'], self::statuses(), true)) {
            $where[] = 'i.status = :status';
            $params['status'] = $filters['status'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(i.invoice_number LIKE :search_invoice
                OR i.customer_name LIKE :search_customer
                OR i.customer_company LIKE :search_company
                OR i.customer_phone LIKE :search_phone
                OR i.customer_email LIKE :search_email)';
            $params['search_invoice'] = '%' . $filters['search'] . '%';
            $params['search_customer'] = '%' . $filters['search'] . '%';
            $params['search_company'] = '%' . $filters['search'] . '%';
            $params['search_phone'] = '%' . $filters['search'] . '%';
            $params['search_email'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $where[] = 'i.invoice_date >= :date_from';
            $params['date_from'] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'i.invoice_date <= :date_to';
            $params['date_to'] = $filters['date_to'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY i.invoice_date DESC, i.invoice_id DESC';

        return $this->fetchAll($sql, $params);
    }

    public function find(int $invoiceId): ?array
    {
        return $this->fetch(
            'SELECT i.*, u.name AS created_by_name, o.order_number
             FROM invoices i
             LEFT JOIN users u ON u.id = i.created_by
             LEFT JOIN orders o ON o.id = i.order_id
             WHERE i.invoice_id = :invoice_id',
            ['invoice_id' => $invoiceId]
        );
    }

    public function findByOrder(int $orderId): ?array
    {
        return $this->fetch(
            'SELECT * FROM invoices WHERE order_id = :order_id LIMIT 1',
            ['order_id' => $orderId]
        );
    }

    public function findByQuotation(int $quotationId): ?array
    {
        return $this->fetch(
            'SELECT * FROM invoices WHERE quotation_id = :quotation_id LIMIT 1',
            ['quotation_id' => $quotationId]
        );
    }

    public function items(int $invoiceId): array
    {
        return $this->fetchAll(
            'SELECT ii.*, p.name AS current_product_name, p.image AS current_product_image,
                    p.description AS current_description, p.specifications AS current_specifications
             FROM invoice_items ii
             LEFT JOIN products p ON p.id = ii.product_id
             WHERE ii.invoice_id = :invoice_id
             ORDER BY ii.invoice_item_id ASC',
            ['invoice_id' => $invoiceId]
        );
    }

    public function customerOptions(): array
    {
        return $this->fetchAll(
            'SELECT c.id, c.customer_code, c.company_name, c.tin, c.vrn,
                    u.name, u.email, u.phone, u.address
             FROM customers c
             INNER JOIN users u ON u.id = c.user_id
             WHERE u.status = "active"
             ORDER BY u.name ASC'
        );
    }

    public function settings(): array
    {
        $settings = $this->fetch('SELECT * FROM invoice_settings WHERE id = 1');

        if ($settings) {
            return $settings;
        }

        $this->execute(
            'INSERT INTO invoice_settings (id, company_name, address, phone, email, currency_code, terms)
             VALUES (1, "Dubai Computer Cargo", "Dar es Salaam, Tanzania / Deira, Dubai", "0749006994", "dubaicomputers14@14gmail.com", "TZS", "Payment is due on or before the invoice due date.")'
        );

        return $this->fetch('SELECT * FROM invoice_settings WHERE id = 1');
    }

    public function updateSettings(array $data, int $userId): bool
    {
        return $this->execute(
            'INSERT INTO invoice_settings (
                id, company_name, logo_path, address, phone, email, tin, vrn,
                vat_rate, currency_code, footer_note, terms, updated_by
             ) VALUES (
                1, :company_name, :logo_path, :address, :phone, :email, :tin, :vrn,
                :vat_rate, :currency_code, :footer_note, :terms, :updated_by
             )
             ON DUPLICATE KEY UPDATE
                company_name = VALUES(company_name),
                logo_path = VALUES(logo_path),
                address = VALUES(address),
                phone = VALUES(phone),
                email = VALUES(email),
                tin = VALUES(tin),
                vrn = VALUES(vrn),
                vat_rate = VALUES(vat_rate),
                currency_code = VALUES(currency_code),
                footer_note = VALUES(footer_note),
                terms = VALUES(terms),
                updated_by = VALUES(updated_by)',
            [
                'company_name' => $data['company_name'],
                'logo_path' => ($data['logo_path'] ?? null) ?: null,
                'address' => $data['address'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'tin' => ($data['tin'] ?? null) ?: null,
                'vrn' => ($data['vrn'] ?? null) ?: null,
                'vat_rate' => (float) $data['vat_rate'],
                'currency_code' => strtoupper(($data['currency_code'] ?? null) ?: default_currency_code()),
                'footer_note' => ($data['footer_note'] ?? null) ?: 'Thank you for your business.',
                'terms' => ($data['terms'] ?? null) ?: null,
                'updated_by' => $userId,
            ]
        );
    }

    public function create(array $data, ?int $createdBy): int
    {
        $items = $this->normalizeItems($data['items'] ?? []);

        if (!$items) {
            throw new InvalidArgumentException('At least one invoice item is required.');
        }

        $this->db->beginTransaction();

        try {
            $customer = $this->customerSnapshot($data);
            $totals = $this->calculateTotals(
                $items,
                (float) ($data['invoice_discount'] ?? 0),
                (float) ($data['vat_rate'] ?? 0),
                $data['status'] ?? 'draft',
                (float) ($data['transport_cost'] ?? 0),
                (float) ($data['installation_cost'] ?? 0),
                array_key_exists('tax_amount', $data) ? (float) $data['tax_amount'] : null
            );

            $stmt = $this->db->prepare(
                'INSERT INTO invoices (
                    invoice_number, customer_id, order_id, quotation_id,
                    customer_name, customer_company, customer_phone, customer_email,
                    customer_address, customer_tin, customer_vrn,
                    delivery_full_name, delivery_phone, delivery_address, delivery_region,
                    delivery_district, delivery_ward, delivery_landmark, delivery_method,
                    preferred_delivery_date, preferred_delivery_time, delivery_instructions,
                    invoice_date, due_date, subtotal, discount, vat, grand_total,
                    transport_cost, installation_cost, balance_due, status, created_by
                 ) VALUES (
                    :invoice_number, :customer_id, :order_id, :quotation_id,
                    :customer_name, :customer_company, :customer_phone, :customer_email,
                    :customer_address, :customer_tin, :customer_vrn,
                    :delivery_full_name, :delivery_phone, :delivery_address, :delivery_region,
                    :delivery_district, :delivery_ward, :delivery_landmark, :delivery_method,
                    :preferred_delivery_date, :preferred_delivery_time, :delivery_instructions,
                    :invoice_date, :due_date, :subtotal, :discount, :vat, :grand_total,
                    :transport_cost, :installation_cost, :balance_due, :status, :created_by
                 )'
            );

            $status = in_array($data['status'] ?? 'draft', self::statuses(), true) ? $data['status'] : 'draft';

            $stmt->execute([
                'invoice_number' => ($data['invoice_number'] ?? null) ?: $this->generateInvoiceNumber(),
                'customer_id' => $customer['customer_id'],
                'order_id' => ($data['order_id'] ?? null) ?: null,
                'quotation_id' => ($data['quotation_id'] ?? null) ?: null,
                'customer_name' => $customer['name'],
                'customer_company' => $customer['company_name'],
                'customer_phone' => $customer['phone'],
                'customer_email' => $customer['email'],
                'customer_address' => $customer['address'],
                'customer_tin' => $customer['tin'],
                'customer_vrn' => $customer['vrn'],
                'delivery_full_name' => ($data['delivery_full_name'] ?? '') ?: null,
                'delivery_phone' => ($data['delivery_phone'] ?? '') ?: null,
                'delivery_address' => ($data['delivery_address'] ?? '') ?: null,
                'delivery_region' => ($data['delivery_region'] ?? '') ?: null,
                'delivery_district' => ($data['delivery_district'] ?? '') ?: null,
                'delivery_ward' => ($data['delivery_ward'] ?? '') ?: null,
                'delivery_landmark' => ($data['delivery_landmark'] ?? '') ?: null,
                'delivery_method' => in_array($data['delivery_method'] ?? '', ['office_pickup', 'home_delivery', 'courier'], true) ? $data['delivery_method'] : null,
                'preferred_delivery_date' => ($data['preferred_delivery_date'] ?? '') ?: null,
                'preferred_delivery_time' => ($data['preferred_delivery_time'] ?? '') ?: null,
                'delivery_instructions' => ($data['delivery_instructions'] ?? '') ?: null,
                'invoice_date' => ($data['invoice_date'] ?? null) ?: date('Y-m-d'),
                'due_date' => ($data['due_date'] ?? null) ?: date('Y-m-d', strtotime('+7 days')),
                'subtotal' => $totals['subtotal'],
                'discount' => $totals['discount'],
                'vat' => $totals['vat'],
                'grand_total' => $totals['grand_total'],
                'transport_cost' => $totals['transport_cost'],
                'installation_cost' => $totals['installation_cost'],
                'balance_due' => $totals['balance_due'],
                'status' => $status,
                'created_by' => $createdBy,
            ]);

            $invoiceId = (int) $this->db->lastInsertId();
            $itemStmt = $this->db->prepare(
                'INSERT INTO invoice_items (
                    invoice_id, product_id, product_name, product_image, description,
                    specifications, quantity, unit_price, line_discount, amount
                 ) VALUES (
                    :invoice_id, :product_id, :product_name, :product_image, :description,
                    :specifications, :quantity, :unit_price, :line_discount, :amount
                 )'
            );

            foreach ($items as $item) {
                $itemStmt->execute([
                    'invoice_id' => $invoiceId,
                    'product_id' => $item['product_id'] ?: null,
                    'product_name' => $item['product_name'],
                    'product_image' => $item['product_image'] ?: null,
                    'description' => $item['description'] ?: null,
                    'specifications' => $item['specifications'] ?: null,
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'line_discount' => $item['line_discount'],
                    'amount' => $item['amount'],
                ]);
            }

            if ($status === 'paid') {
                $this->insertInvoicePayment($invoiceId, $totals['grand_total'], 'cash', 'Paid at invoice creation', $createdBy, 'confirmed');
            }

            if ($status !== 'draft' && $status !== 'cancelled' && empty($data['order_id'])) {
                $this->adjustStockForInvoiceItems($invoiceId, $items, $createdBy);
            }

            $this->db->commit();

            return $invoiceId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function createFromOrder(int $orderId, int $createdBy): int
    {
        $existing = $this->findByOrder($orderId);

        if ($existing) {
            return (int) $existing['invoice_id'];
        }

        $order = $this->fetch(
            'SELECT o.*, u.name AS customer_name, u.email AS customer_email, u.phone AS customer_phone,
                    u.address AS customer_address, c.id AS customer_id, c.company_name, c.tin, c.vrn
             FROM orders o
             INNER JOIN users u ON u.id = o.user_id
             LEFT JOIN customers c ON c.user_id = u.id
             WHERE o.id = :order_id',
            ['order_id' => $orderId]
        );

        if (!$order) {
            throw new RuntimeException('Order was not found.');
        }

        $orderItems = $this->fetchAll(
            'SELECT oi.*, p.name, p.image, p.description, p.specifications
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = :order_id',
            ['order_id' => $orderId]
        );

        $items = [];

        foreach ($orderItems as $item) {
            $items[] = [
                'product_id' => (int) $item['product_id'],
                'product_name' => $item['name'],
                'product_image' => $item['image'] ?? null,
                'description' => $item['description'] ?? '',
                'specifications' => $item['specifications'] ?? '',
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'line_discount' => 0.0,
            ];
        }

        return $this->create([
            'customer_mode' => 'snapshot',
            'customer_id' => $order['customer_id'] ?? null,
            'customer_name' => $order['customer_name'],
            'company_name' => $order['company_name'] ?? '',
            'phone' => $order['customer_phone'] ?? '',
            'email' => $order['customer_email'] ?? '',
            'address' => $order['customer_address'] ?: $order['shipping_address'],
            'tin' => $order['tin'] ?? '',
            'vrn' => $order['vrn'] ?? '',
            'order_id' => $orderId,
            'quotation_id' => null,
            'invoice_number' => null,
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'unpaid',
            'invoice_discount' => 0,
            'vat_rate' => (float) ($this->settings()['vat_rate'] ?? 0),
            'transport_cost' => 0,
            'installation_cost' => 0,
            'tax_amount' => null,
            'delivery_full_name' => $order['customer_name'],
            'delivery_phone' => $order['customer_phone'] ?? '',
            'delivery_address' => $order['shipping_address'],
            'delivery_region' => '',
            'delivery_district' => '',
            'delivery_ward' => '',
            'delivery_landmark' => '',
            'delivery_method' => 'home_delivery',
            'preferred_delivery_date' => '',
            'preferred_delivery_time' => '',
            'delivery_instructions' => $order['notes'] ?? '',
            'items' => $items,
        ], $createdBy);
    }

    public function recordPayment(int $invoiceId, float $amount, string $method, string $reference, int $confirmedBy): bool
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException('Payment amount must be greater than zero.');
        }

        $this->db->beginTransaction();

        try {
            $invoice = $this->findForUpdate($invoiceId);

            if (!$invoice || $invoice['status'] === 'cancelled') {
                $this->db->rollBack();
                return false;
            }

            $this->insertInvoicePayment($invoiceId, $amount, $method, $reference, $confirmedBy, 'confirmed');
            $this->syncPaymentStatus($invoiceId);
            $this->db->commit();

            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function cancel(int $invoiceId): bool
    {
        $this->db->beginTransaction();

        try {
            $invoice = $this->findForUpdate($invoiceId);

            if (!$invoice || in_array($invoice['status'], ['paid', 'cancelled'], true)) {
                $this->db->rollBack();
                return false;
            }

            $items = $this->items($invoiceId);

            foreach ($items as $item) {
                $quantity = max(0, (int) ($item['quantity'] ?? 0));
                $productId = (int) ($item['product_id'] ?? 0);

                if ($productId > 0 && $quantity > 0) {
                    $this->execute(
                        'UPDATE inventory SET quantity = quantity + :quantity WHERE product_id = :product_id',
                        ['quantity' => $quantity, 'product_id' => $productId]
                    );
                    $this->execute(
                        'INSERT INTO stock_entries (product_id, quantity, unit_cost, supplier_name, received_date, received_by, notes)
                         VALUES (:product_id, :quantity, 0, "Invoice", CURRENT_DATE, NULL, :notes)',
                        [
                            'product_id' => $productId,
                            'quantity' => $quantity,
                            'notes' => 'Stock restored for cancelled invoice #' . $invoiceId,
                        ]
                    );
                }
            }

            $stmt = $this->db->prepare(
                'UPDATE invoices
                 SET status = "cancelled", balance_due = grand_total
                 WHERE invoice_id = :invoice_id AND status NOT IN ("paid", "cancelled")'
            );
            $stmt->execute(['invoice_id' => $invoiceId]);
            $this->db->commit();

            return $stmt->rowCount() > 0;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function syncPaymentStatus(int $invoiceId): void
    {
        $invoice = $this->find($invoiceId);

        if (!$invoice || $invoice['status'] === 'cancelled') {
            return;
        }

        $paidRow = $this->fetch(
            'SELECT COALESCE(SUM(amount), 0) AS paid_total
             FROM payments
             WHERE invoice_id = :invoice_id AND status = "confirmed"',
            ['invoice_id' => $invoiceId]
        );

        $paidTotal = (float) ($paidRow['paid_total'] ?? 0);
        $grandTotal = (float) $invoice['grand_total'];
        $balanceDue = max($grandTotal - $paidTotal, 0);
        $status = $balanceDue <= 0.009 ? 'paid' : 'unpaid';

        $this->execute(
            'UPDATE invoices
             SET balance_due = :balance_due, status = :status
             WHERE invoice_id = :invoice_id AND status <> "draft"',
            [
                'invoice_id' => $invoiceId,
                'balance_due' => $balanceDue,
                'status' => $status,
            ]
        );
    }

    public function productPayload(int $productId): ?array
    {
        $product = $this->fetch(
            'SELECT p.*, i.quantity, i.sku
             FROM products p
             LEFT JOIN inventory i ON i.product_id = p.id
             WHERE p.id = :id AND p.status = "active"',
            ['id' => $productId]
        );

        if (!$product) {
            return null;
        }

        return [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'description' => $product['description'] ?? '',
            'specifications' => $product['specifications'] ?? '',
            'image' => $product['image'] ?? '',
            'quantity' => (int) ($product['quantity'] ?? 0),
            'price' => (float) $product['price'],
            'sku' => $product['sku'] ?? '',
        ];
    }

    private function findForUpdate(int $invoiceId): ?array
    {
        return $this->fetch(
            'SELECT * FROM invoices WHERE invoice_id = :invoice_id FOR UPDATE',
            ['invoice_id' => $invoiceId]
        );
    }

    private function customerSnapshot(array $data): array
    {
        if (($data['customer_mode'] ?? 'existing') === 'existing' && empty($data['customer_id'])) {
            throw new InvalidArgumentException('Please select an existing customer or choose New Customer.');
        }

        if (($data['customer_mode'] ?? 'existing') === 'existing' && !empty($data['customer_id'])) {
            $customer = $this->fetch(
                'SELECT c.id, c.company_name, c.tin, c.vrn, u.name, u.email, u.phone, u.address
                 FROM customers c
                 INNER JOIN users u ON u.id = c.user_id
                 WHERE c.id = :customer_id',
                ['customer_id' => (int) $data['customer_id']]
            );

            if (!$customer) {
                throw new RuntimeException('Selected customer was not found.');
            }

            return [
                'customer_id' => (int) $customer['id'],
                'name' => $customer['name'],
                'company_name' => $customer['company_name'] ?? null,
                'phone' => $customer['phone'] ?? null,
                'email' => $customer['email'] ?? null,
                'address' => $customer['address'] ?? null,
                'tin' => $customer['tin'] ?? null,
                'vrn' => $customer['vrn'] ?? null,
            ];
        }

        if (($data['customer_mode'] ?? '') === 'new') {
            return $this->createCustomerSnapshot($data);
        }

        $name = trim((string) ($data['customer_name'] ?? ''));

        if ($name === '') {
            throw new InvalidArgumentException('Customer name is required.');
        }

        return [
            'customer_id' => ($data['customer_id'] ?? null) ?: null,
            'name' => $name,
            'company_name' => ($data['company_name'] ?? null) ?: null,
            'phone' => ($data['phone'] ?? null) ?: null,
            'email' => ($data['email'] ?? null) ?: null,
            'address' => ($data['address'] ?? null) ?: null,
            'tin' => ($data['tin'] ?? null) ?: null,
            'vrn' => ($data['vrn'] ?? null) ?: null,
        ];
    }

    private function createCustomerSnapshot(array $data): array
    {
        $name = trim((string) ($data['customer_name'] ?? ''));
        $email = strtolower(trim((string) ($data['email'] ?? '')));

        if ($name === '') {
            throw new InvalidArgumentException('Customer name is required.');
        }

        if ($email === '') {
            $email = 'invoice-customer-' . date('YmdHis') . '-' . random_int(100, 999) . '@local.customer';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Customer email must be valid when provided.');
        }

        $existing = $this->fetch(
            'SELECT c.id, c.company_name, c.tin, c.vrn, u.name, u.email, u.phone, u.address
             FROM users u
             LEFT JOIN customers c ON c.user_id = u.id
             WHERE u.email = :email
             LIMIT 1',
            ['email' => $email]
        );

        if ($existing && !empty($existing['id'])) {
            return [
                'customer_id' => (int) $existing['id'],
                'name' => $existing['name'],
                'company_name' => $existing['company_name'] ?? null,
                'phone' => $existing['phone'] ?? null,
                'email' => $existing['email'] ?? null,
                'address' => $existing['address'] ?? null,
                'tin' => $existing['tin'] ?? null,
                'vrn' => $existing['vrn'] ?? null,
            ];
        }

        if ($existing) {
            throw new RuntimeException('That email belongs to a non-customer user.');
        }

        $userStmt = $this->db->prepare(
            'INSERT INTO users (name, email, password, role, phone, address, status)
             VALUES (:name, :email, :password, "customer", :phone, :address, "active")'
        );
        $userStmt->execute([
            'name' => $name,
            'email' => $email,
            'password' => password_hash(bin2hex(random_bytes(12)), PASSWORD_DEFAULT),
            'phone' => ($data['phone'] ?? null) ?: null,
            'address' => ($data['address'] ?? null) ?: null,
        ]);

        $userId = (int) $this->db->lastInsertId();
        $customerCode = 'CUS-' . date('Y') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);

        $customerStmt = $this->db->prepare(
            'INSERT INTO customers (user_id, customer_code, company_name, city, country, tin, vrn)
             VALUES (:user_id, :customer_code, :company_name, :city, :country, :tin, :vrn)'
        );
        $customerStmt->execute([
            'user_id' => $userId,
            'customer_code' => $customerCode,
            'company_name' => ($data['company_name'] ?? null) ?: null,
            'city' => null,
            'country' => 'United Arab Emirates',
            'tin' => ($data['tin'] ?? null) ?: null,
            'vrn' => ($data['vrn'] ?? null) ?: null,
        ]);

        return [
            'customer_id' => (int) $this->db->lastInsertId(),
            'name' => $name,
            'company_name' => ($data['company_name'] ?? null) ?: null,
            'phone' => ($data['phone'] ?? null) ?: null,
            'email' => $email,
            'address' => ($data['address'] ?? null) ?: null,
            'tin' => ($data['tin'] ?? null) ?: null,
            'vrn' => ($data['vrn'] ?? null) ?: null,
        ];
    }

    private function normalizeItems(array $items): array
    {
        $normalized = [];

        foreach ($items as $item) {
            $productName = trim((string) ($item['product_name'] ?? ''));
            $quantity = max(0, (int) ($item['quantity'] ?? 0));
            $unitPrice = max(0, (float) ($item['unit_price'] ?? 0));
            $lineDiscount = max(0, (float) ($item['line_discount'] ?? 0));

            if ($productName === '' || $quantity < 1) {
                continue;
            }

            $gross = $quantity * $unitPrice;
            $lineDiscount = min($lineDiscount, $gross);

            $normalized[] = [
                'product_id' => (int) ($item['product_id'] ?? 0),
                'product_name' => $productName,
                'product_image' => trim((string) ($item['product_image'] ?? '')),
                'description' => trim((string) ($item['description'] ?? '')),
                'specifications' => trim((string) ($item['specifications'] ?? '')),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'line_discount' => $lineDiscount,
                'amount' => $gross - $lineDiscount,
            ];
        }

        return $normalized;
    }

    private function calculateTotals(
        array $items,
        float $invoiceDiscount,
        float $vatRate,
        string $status,
        float $transportCost = 0,
        float $installationCost = 0,
        ?float $taxAmount = null
    ): array
    {
        $subtotal = array_reduce(
            $items,
            static fn (float $carry, array $item): float => $carry + (float) $item['amount'],
            0.0
        );

        $discount = min(max(0, $invoiceDiscount), $subtotal);
        $transportCost = max(0, $transportCost);
        $installationCost = max(0, $installationCost);
        $taxable = max($subtotal - $discount, 0) + $transportCost + $installationCost;
        $vat = $taxAmount === null
            ? round($taxable * max(0, $vatRate) / 100, 2)
            : max(0, round($taxAmount, 2));
        $grandTotal = round($taxable + $vat, 2);
        $balanceDue = $status === 'paid' ? 0.0 : $grandTotal;

        return [
            'subtotal' => round($subtotal, 2),
            'discount' => round($discount, 2),
            'transport_cost' => round($transportCost, 2),
            'installation_cost' => round($installationCost, 2),
            'vat' => $vat,
            'grand_total' => $grandTotal,
            'balance_due' => $balanceDue,
        ];
    }

    private function insertInvoicePayment(int $invoiceId, float $amount, string $method, string $reference, int $confirmedBy, string $status): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO payments (
                order_id, invoice_id, payment_reference, method, amount, status,
                submitted_at, confirmed_by, confirmed_at, notes
             ) VALUES (
                NULL, :invoice_id, :payment_reference, :method, :amount, :status,
                CURRENT_TIMESTAMP, :confirmed_by,
                CASE WHEN :status_for_date = "confirmed" THEN CURRENT_TIMESTAMP ELSE NULL END,
                :notes
             )'
        );
        $stmt->execute([
            'invoice_id' => $invoiceId,
            'payment_reference' => $reference ?: null,
            'method' => in_array($method, ['cash', 'bank_transfer', 'mobile_money', 'card'], true) ? $method : 'bank_transfer',
            'amount' => $amount,
            'status' => $status,
            'status_for_date' => $status,
            'confirmed_by' => $confirmedBy,
            'notes' => 'Invoice payment',
        ]);
    }

    private function adjustStockForInvoiceItems(int $invoiceId, array $items, ?int $userId): void
    {
        foreach ($items as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = max(0, (int) ($item['quantity'] ?? 0));

            if ($productId <= 0 || $quantity <= 0) {
                continue;
            }

            $inventory = $this->fetch('SELECT quantity FROM inventory WHERE product_id = :product_id FOR UPDATE', ['product_id' => $productId]);

            if (!$inventory) {
                throw new RuntimeException('Inventory record was not found for the selected product.');
            }

            if ((int) $inventory['quantity'] < $quantity) {
                throw new RuntimeException('Stock is insufficient for one or more selected products.');
            }

            $this->execute(
                'UPDATE inventory SET quantity = quantity - :quantity WHERE product_id = :product_id',
                ['quantity' => $quantity, 'product_id' => $productId]
            );
            $this->execute(
                'INSERT INTO stock_entries (product_id, quantity, unit_cost, supplier_name, received_date, received_by, notes)
                 VALUES (:product_id, :quantity, 0, "Invoice", CURRENT_DATE, :received_by, :notes)',
                [
                    'product_id' => $productId,
                    'quantity' => -$quantity,
                    'received_by' => $userId,
                    'notes' => 'Stock reduction for invoice #' . $invoiceId,
                ]
            );
        }
    }

    private function generateInvoiceNumber(): string
    {
        $prefix = 'INV-' . date('Ymd') . '-';
        $row = $this->fetch(
            'SELECT invoice_number
             FROM invoices
             WHERE invoice_number LIKE :prefix
             ORDER BY invoice_id DESC
             LIMIT 1',
            ['prefix' => $prefix . '%']
        );

        $next = 1;

        if ($row && preg_match('/(\d+)$/', $row['invoice_number'], $matches)) {
            $next = (int) $matches[1] + 1;
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
