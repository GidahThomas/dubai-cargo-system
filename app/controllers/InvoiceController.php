<?php

class InvoiceController extends Controller
{
    public function index(?string $forcedStatus = null): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);

        $filters = [
            'search' => $this->cleanString($this->get('search')),
            'status' => $forcedStatus ?: $this->cleanString($this->get('status')),
            'date_from' => $this->cleanString($this->get('date_from')),
            'date_to' => $this->cleanString($this->get('date_to')),
        ];

        if (!in_array($filters['status'], Invoice::statuses(), true)) {
            $filters['status'] = '';
        }

        $this->view('invoices/index', [
            'pageTitle' => 'Invoices',
            'invoices' => (new Invoice())->all($filters),
            'statuses' => Invoice::statuses(),
            'filters' => $filters,
        ]);
    }

    public function draft(): void
    {
        $this->index('draft');
    }

    public function paid(): void
    {
        $this->index('paid');
    }

    public function unpaid(): void
    {
        $this->index('unpaid');
    }

    public function cancelled(): void
    {
        $this->index('cancelled');
    }

    public function create(): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);

        $invoiceModel = new Invoice();

        $this->view('invoices/create', [
            'pageTitle' => 'Create Invoice',
            'customers' => $invoiceModel->customerOptions(),
            'products' => (new Product())->all([], true),
            'settings' => $invoiceModel->settings(),
            'defaultInvoiceDate' => date('Y-m-d'),
            'defaultDueDate' => date('Y-m-d', strtotime('+7 days')),
        ]);
    }

    public function store(): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);
        $this->validateCsrf();

        try {
            $invoiceId = (new Invoice())->create($this->invoicePayload(), Auth::id());
            (new AuditLog())->create(Auth::id(), 'invoice_created', 'invoices', $invoiceId);
            flash('success', 'Invoice created successfully.');
            $this->redirect('invoices/preview/' . $invoiceId);
        } catch (Throwable $exception) {
            flash('error', 'Invoice could not be created: ' . $exception->getMessage());
            $this->redirect('invoices/create');
        }
    }

    public function preview(int $invoiceId): void
    {
        $this->renderInvoice($invoiceId, false);
    }

    public function print(int $invoiceId): void
    {
        $this->renderInvoice($invoiceId, true);
    }

    public function pdf(int $invoiceId): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);

        $invoiceModel = new Invoice();
        $invoice = $invoiceModel->find($invoiceId);

        if (!$invoice) {
            http_response_code(404);
            echo 'Invoice not found.';
            return;
        }

        $pdf = (new InvoicePdfGenerator())->generate($invoice, $invoiceModel->items($invoiceId), $invoiceModel->settings());
        $fileName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $invoice['invoice_number']) . '.pdf';

        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Content-Length: ' . strlen($pdf));
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        echo $pdf;
        exit;
    }

    public function email(int $invoiceId): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);
        $this->validateCsrf();

        $invoiceModel = new Invoice();
        $invoice = $invoiceModel->find($invoiceId);

        if (!$invoice) {
            flash('error', 'Invoice not found.');
            $this->redirect('invoices');
        }

        $to = trim((string) ($invoice['customer_email'] ?? ''));

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            flash('error', 'This invoice does not have a valid customer email address.');
            $this->redirect('invoices/preview/' . $invoiceId);
        }

        $settings = $invoiceModel->settings();
        $pdf = (new InvoicePdfGenerator())->generate($invoice, $invoiceModel->items($invoiceId), $settings);

        if ($this->sendInvoiceEmail($to, $invoice, $settings, $pdf)) {
            (new AuditLog())->create(Auth::id(), 'invoice_emailed', 'invoices', $invoiceId, $to);
            flash('success', 'Invoice PDF emailed to ' . $to . '.');
        } else {
            flash('error', 'The email could not be sent. Please check the server mail configuration.');
        }

        $this->redirect('invoices/preview/' . $invoiceId);
    }

    public function settings(): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);

        $this->view('invoices/settings', [
            'pageTitle' => 'Invoice Settings',
            'settings' => (new Invoice())->settings(),
        ]);
    }

    public function updateSettings(): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);
        $this->validateCsrf();

        $invoice = new Invoice();
        $current = $invoice->settings();

        $data = [
            'company_name' => $this->cleanString($this->post('company_name')),
            'logo_path' => $this->handleLogoUpload($current['logo_path'] ?? null),
            'address' => $this->cleanString($this->post('address')),
            'phone' => $this->cleanString($this->post('phone')),
            'email' => strtolower($this->cleanString($this->post('email'))),
            'support_email' => strtolower($this->cleanString($this->post('support_email'))),
            'instagram_url' => $this->cleanString($this->post('instagram_url')),
            'social_handle' => $this->cleanString($this->post('social_handle')),
            'tin' => $this->cleanString($this->post('tin')),
            'vrn' => $this->cleanString($this->post('vrn')),
            'vat_rate' => max(0, (float) $this->post('vat_rate', 0)),
            'currency_code' => strtoupper($this->cleanString($this->post('currency_code', default_currency_code()))),
            'footer_note' => $this->cleanString($this->post('footer_note')),
            'terms' => $this->cleanString($this->post('terms')),
        ];

        if ($data['company_name'] === '' || $data['address'] === '' || $data['phone'] === '' || !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            flash('error', 'Company name, address, phone, and valid email are required.');
            $this->redirect('invoices/settings');
        }

        $invoice->updateSettings($data, Auth::id());
        (new AuditLog())->create(Auth::id(), 'invoice_settings_updated', 'invoice_settings', 1);
        flash('success', 'Invoice settings updated.');
        $this->redirect('invoices/settings');
    }

    public function product(int $productId): void
    {
        $this->requireAjaxRole(Invoice::ACCESS_ROLES);

        $product = (new Invoice())->productPayload($productId, $this->activeLocationId());

        if (!$product) {
            $this->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $this->json(['success' => true, 'product' => $product]);
    }

    public function fromOrder(int $orderId): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);

        try {
            $invoiceId = (new Invoice())->createFromOrder($orderId, Auth::id());
            (new AuditLog())->create(Auth::id(), 'invoice_created_from_order', 'orders', $orderId, 'Invoice #' . $invoiceId);
            flash('success', 'Order converted to invoice.');
            $this->redirect('invoices/preview/' . $invoiceId);
        } catch (Throwable $exception) {
            flash('error', 'Order could not be converted to invoice: ' . $exception->getMessage());
            $this->redirect('orders');
        }
    }

    public function markPaid(int $invoiceId): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);
        $this->validateCsrf();

        $amount = (float) $this->post('amount', 0);
        $method = $this->cleanString($this->post('method', 'bank_transfer'));
        $reference = $this->cleanString($this->post('payment_reference'));

        try {
            if ((new Invoice())->recordPayment($invoiceId, $amount, $method, $reference, Auth::id())) {
                (new AuditLog())->create(Auth::id(), 'invoice_payment_recorded', 'invoices', $invoiceId, $reference);
                flash('success', 'Invoice payment recorded and status updated.');
            } else {
                flash('error', 'Invoice cannot be paid.');
            }
        } catch (Throwable $exception) {
            flash('error', 'Payment could not be recorded: ' . $exception->getMessage());
        }

        $this->redirect('invoices/preview/' . $invoiceId);
    }

    public function cancel(int $invoiceId): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);
        $this->validateCsrf();

        if ((new Invoice())->cancel($invoiceId)) {
            (new AuditLog())->create(Auth::id(), 'invoice_cancelled', 'invoices', $invoiceId);
            flash('success', 'Invoice cancelled.');
        } else {
            flash('error', 'Paid invoices cannot be cancelled.');
        }

        $this->redirect('invoices');
    }

    private function renderInvoice(int $invoiceId, bool $printMode): void
    {
        $this->requireRole(Invoice::ACCESS_ROLES);

        $invoiceModel = new Invoice();
        $invoice = $invoiceModel->find($invoiceId);

        if (!$invoice) {
            flash('error', 'Invoice not found.');
            $this->redirect('invoices');
        }

        $this->view('invoices/preview', [
            'pageTitle' => $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => $invoiceModel->items($invoiceId),
            'settings' => $invoiceModel->settings(),
            'printMode' => $printMode,
        ]);
    }

    private function invoicePayload(): array
    {
        $items = [];
        $productIds = $_POST['items']['product_id'] ?? [];
        $names = $_POST['items']['product_name'] ?? [];
        $images = $_POST['items']['product_image'] ?? [];
        $descriptions = $_POST['items']['description'] ?? [];
        $specifications = $_POST['items']['specifications'] ?? [];
        $quantities = $_POST['items']['quantity'] ?? [];
        $unitPrices = $_POST['items']['unit_price'] ?? [];
        $discounts = $_POST['items']['line_discount'] ?? [];

        foreach ($names as $index => $name) {
            $items[] = [
                'product_id' => (int) ($productIds[$index] ?? 0),
                'product_name' => $this->cleanString($name),
                'product_image' => $this->cleanString($images[$index] ?? ''),
                'description' => $this->cleanString($descriptions[$index] ?? ''),
                'specifications' => $this->cleanString($specifications[$index] ?? ''),
                'quantity' => max(0, (int) ($quantities[$index] ?? 0)),
                'unit_price' => max(0, (float) ($unitPrices[$index] ?? 0)),
                'line_discount' => max(0, (float) ($discounts[$index] ?? 0)),
            ];
        }

        $status = $this->cleanString($this->post('status', 'draft'));

        return [
            'customer_mode' => $this->cleanString($this->post('customer_mode', 'existing')),
            'customer_id' => (int) $this->post('customer_id', 0),
            'customer_name' => $this->cleanString($this->post('customer_name')),
            'company_name' => $this->cleanString($this->post('company_name')),
            'phone' => $this->cleanString($this->post('phone')),
            'email' => strtolower($this->cleanString($this->post('email'))),
            'address' => $this->cleanString($this->post('address')),
            'tin' => $this->cleanString($this->post('tin')),
            'vrn' => $this->cleanString($this->post('vrn')),
            'invoice_number' => $this->cleanString($this->post('invoice_number')),
            'invoice_date' => $this->cleanString($this->post('invoice_date')),
            'due_date' => $this->cleanString($this->post('due_date')),
            'status' => in_array($status, Invoice::statuses(), true) ? $status : 'draft',
            'invoice_discount' => max(0, (float) $this->post('invoice_discount', 0)),
            'vat_rate' => max(0, (float) $this->post('vat_rate', 0)),
            'order_id' => (int) $this->post('order_id', 0),
            'quotation_id' => (int) $this->post('quotation_id', 0),
            'items' => $items,
        ];
    }

    private function handleLogoUpload(?string $existingLogo = null): ?string
    {
        $logoText = $this->cleanString($this->post('logo_path'));

        if (!isset($_FILES['logo_file']) || $_FILES['logo_file']['error'] === UPLOAD_ERR_NO_FILE) {
            return $logoText !== '' ? $logoText : $existingLogo;
        }

        if ($_FILES['logo_file']['error'] !== UPLOAD_ERR_OK) {
            return $existingLogo;
        }

        $extension = strtolower(pathinfo($_FILES['logo_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png'];

        if (!in_array($extension, $allowed, true)) {
            return $existingLogo;
        }

        $uploadDir = ROOT_PATH . '/public/uploads';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $fileName = 'invoice-logo-' . time() . '-' . random_int(1000, 9999) . '.' . $extension;
        $target = $uploadDir . '/' . $fileName;

        if (move_uploaded_file($_FILES['logo_file']['tmp_name'], $target)) {
            return 'uploads/' . $fileName;
        }

        return $existingLogo;
    }

    private function sendInvoiceEmail(string $to, array $invoice, array $settings, string $pdf): bool
    {
        $fileName = preg_replace('/[^A-Za-z0-9_-]+/', '-', $invoice['invoice_number']) . '.pdf';
        $subject = 'Invoice ' . $invoice['invoice_number'] . ' from ' . $settings['company_name'];

        $message = "Dear " . $invoice['customer_name'] . ",\n\n";
        $message .= "Please find attached invoice " . $invoice['invoice_number'] . ".\n";
        $message .= "Grand total: " . currency_money($invoice['grand_total'], $settings['currency_code'] ?? default_currency_code()) . "\n";
        $message .= "Balance due: " . currency_money($invoice['balance_due'], $settings['currency_code'] ?? default_currency_code()) . "\n\n";
        $message .= "Thank you,\n" . $settings['company_name'];

        $replyTo = filter_var($settings['email'] ?? '', FILTER_VALIDATE_EMAIL) ? $settings['email'] : null;

        return Mailer::sendWithAttachment($to, $subject, $message, $replyTo, $pdf, $fileName);
    }
}
