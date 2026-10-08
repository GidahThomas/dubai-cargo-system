<?php

class PublicController extends Controller
{
    public function home(): void
    {
        $productModel = new Product();
        $products = $productModel->all([], true, null, 8);
        $categories = $productModel->categories(8);

        $this->view('public/home', [
            'layout' => 'public',
            'pageTitle' => 'Home',
            'products' => $products,
            'categories' => $categories,
        ]);
    }

    public function trackShipment(): void
    {
        $trackingNumber = $this->cleanString($this->get('tracking_number'));
        $trackingResult = $trackingNumber !== ''
            ? (new Shipment())->findByTrackingNumber($trackingNumber)
            : null;

        $this->view('public/track-shipment', [
            'layout' => 'public',
            'pageTitle' => 'Track Shipment',
            'trackingNumber' => $trackingNumber,
            'trackingResult' => $trackingResult,
        ]);
    }

    public function gallery(): void
    {
        $this->view('public/gallery', [
            'layout' => 'public',
            'pageTitle' => 'Gallery',
            'posts' => (new InstagramPost())->all(true),
        ]);
    }

    public function contact(): void
    {
        if ($this->isPost()) {
            $this->validateCsrf();

            $name = $this->cleanString($this->post('name'));
            $phone = $this->cleanString($this->post('phone'));
            $message = $this->cleanString($this->post('message'));

            if ($name === '' || $phone === '' || $message === '') {
                flash('error', 'Name, phone, and message are required.');
                $this->redirect('contact');
            }

            $subject = 'New customer contact request from ' . $name;
            $message = "Name: {$name}\nPhone: {$phone}\n\nMessage:\n{$message}";
            $this->sendCustomerRequestEmail($subject, $message);

            (new AuditLog())->create(null, 'public_contact_request', null, null, $name . ' / ' . $phone);
            flash('success', 'Your message has been received. Our team will contact you shortly through ' . customer_contact_email() . ' or ' . company_phone() . '.');
            $this->redirect('contact');
        }

        $this->view('public/contact', [
            'layout' => 'public',
            'pageTitle' => 'Contact Us',
        ]);
    }

    public function requestQuotation(): void
    {
        if ($this->isPost()) {
            $this->validateCsrf();

            $name = $this->cleanString($this->post('name'));
            $phone = $this->cleanString($this->post('phone'));
            $products = $this->post('product', []);
            $quantities = $this->post('quantity', []);
            $notes = $this->post('notes', []);

            if ($name === '' || $phone === '' || empty($products)) {
                flash('error', 'Name, phone, and at least one product are required.');
                $this->redirect('request-quotation');
            }

            $items = [];
            foreach ($products as $index => $productName) {
                $productName = $this->cleanString((string) $productName);
                if ($productName === '') {
                    continue;
                }
                $product = (new Product())->all(['name' => $productName], true);
                $productRow = $product[0] ?? null;
                $quantity = max(1, (int) ($quantities[$index] ?? 1));
                $unitPrice = $productRow['price'] ?? 0;
                $lineTotal = $unitPrice * $quantity;
                $items[] = [
                    'product_id' => $productRow['id'] ?? null,
                    'product_name' => $productName,
                    'product_image' => $productRow['image'] ?? null,
                    'quantity' => $quantity,
                    'unit_price' => $unitPrice,
                    'discount' => 0.0,
                    'notes' => $this->cleanString((string) ($notes[$index] ?? '')),
                    'line_total' => $lineTotal,
                ];
            }

            $subtotal = array_sum(array_map(static fn (array $item): float => (float) ($item['line_total'] ?? 0), $items));
            $quotationId = (new Quotation())->create([
                'customer_name' => $name,
                'company_name' => $this->cleanString($this->post('company')),
                'phone' => $phone,
                'email' => $this->cleanString($this->post('email')),
                'delivery_full_name' => $this->cleanString($this->post('delivery_full_name')),
                'delivery_phone' => $this->cleanString($this->post('delivery_phone')),
                'delivery_address' => $this->cleanString($this->post('delivery_address')),
                'delivery_region' => $this->cleanString($this->post('delivery_region')),
                'delivery_district' => $this->cleanString($this->post('delivery_district')),
                'delivery_ward' => $this->cleanString($this->post('delivery_ward')),
                'delivery_landmark' => $this->cleanString($this->post('delivery_landmark')),
                'delivery_date' => $this->cleanString($this->post('delivery_date')),
                'delivery_time' => $this->cleanString($this->post('delivery_time')),
                'delivery_instructions' => $this->cleanString($this->post('delivery_instructions')),
                'delivery_method' => $this->cleanString($this->post('delivery_method')) ?: 'home_delivery',
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => 0.0,
                'transport_cost' => 0.0,
                'installation_cost' => 0.0,
                'tax' => 0.0,
                'grand_total' => $subtotal,
                'items' => $items,
            ]);

            $subject = 'New quotation request from ' . $name;
            $message = "Name: {$name}\nPhone: {$phone}\nDelivery method: " . ($this->cleanString($this->post('delivery_method')) ?: 'home_delivery') . "\nQuotation ID: {$quotationId}";
            $this->sendCustomerRequestEmail($subject, $message);

            (new AuditLog())->create(null, 'public_quotation_request', null, null, $name . ' / ' . $phone . ' / ' . implode(', ', $products));
            flash('success', 'Quotation request received. Our sales team will prepare a response.');
            $this->redirect('request-quotation');
        }

        $preselectedProductId = (int) $this->get('product_id', 0);
        $preselectedProduct = $preselectedProductId > 0
            ? (new Product())->find($preselectedProductId)
            : null;

        $this->view('public/request-quotation', [
            'layout' => 'public',
            'pageTitle' => 'Request Quotation',
            'products' => (new Product())->all([], true),
            'preselectedProduct' => $preselectedProduct,
        ]);
    }

    public function requestInvoice(): void
    {
        $invoiceModel = new Invoice();

        $this->view('public/request-invoice', [
            'layout' => 'public',
            'pageTitle' => 'Request Invoice',
            'products' => (new Product())->all([], true),
            'settings' => $invoiceModel->settings(),
            'defaultInvoiceDate' => date('Y-m-d'),
            'defaultDueDate' => date('Y-m-d', strtotime('+7 days')),
            'preselectedProductId' => (int) $this->get('product_id', 0),
        ]);
    }

    public function storeInvoiceRequest(): void
    {
        $this->validateCsrf();

        try {
            $payload = $this->publicInvoicePayload();

            if ($payload['customer_name'] === '' || $payload['phone'] === '' || $payload['address'] === '' || !filter_var($payload['email'], FILTER_VALIDATE_EMAIL)) {
                flash('error', 'Full name, phone, valid email, and address are required.');
                $this->redirect('request-invoice');
            }

            $invoiceId = (new Invoice())->create($payload, null);
            $_SESSION['public_invoice_ids'][$invoiceId] = true;

            $subject = 'New invoice request from ' . $payload['customer_name'];
            $message = "Customer: {$payload['customer_name']}\nPhone: {$payload['phone']}\nEmail: {$payload['email']}\nAddress: {$payload['address']}\nInvoice ID: {$invoiceId}";
            $this->sendCustomerRequestEmail($subject, $message);

            (new AuditLog())->create(null, 'public_invoice_request_created', 'invoices', $invoiceId);
            flash('success', 'Invoice generated. You can preview, print, or download the PDF.');
            $this->redirect('public/invoicePreview/' . $invoiceId);
        } catch (Throwable $exception) {
            flash('error', 'Invoice request could not be generated: ' . $exception->getMessage());
            $this->redirect('request-invoice');
        }
    }

    public function productJson(int $productId): void
    {
        $product = (new Invoice())->productPayload($productId);

        if (!$product) {
            $this->json(['success' => false, 'message' => 'Product not found.'], 404);
        }

        $this->json(['success' => true, 'product' => $product]);
    }

    private function sendCustomerRequestEmail(string $subject, string $message): bool
    {
        return Mailer::send(customer_contact_email(), $subject, $message, company_email());
    }

    public function invoicePreview(int $invoiceId): void
    {
        $this->renderPublicInvoice($invoiceId, false);
    }

    public function invoicePrint(int $invoiceId): void
    {
        $this->renderPublicInvoice($invoiceId, true);
    }

    public function invoicePdf(int $invoiceId): void
    {
        if (!$this->canAccessPublicInvoice($invoiceId)) {
            http_response_code(403);
            echo 'Invoice access expired.';
            return;
        }

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

    private function renderPublicInvoice(int $invoiceId, bool $printMode): void
    {
        if (!$this->canAccessPublicInvoice($invoiceId)) {
            http_response_code(403);
            $this->view('public/not-found', [
                'layout' => 'public',
                'pageTitle' => 'Invoice Access Expired',
            ]);
            return;
        }

        $invoiceModel = new Invoice();
        $invoice = $invoiceModel->find($invoiceId);

        if (!$invoice) {
            http_response_code(404);
            $this->view('public/not-found', [
                'layout' => 'public',
                'pageTitle' => 'Invoice Not Found',
            ]);
            return;
        }

        $this->view('invoices/preview', [
            'layout' => 'public',
            'pageTitle' => $invoice['invoice_number'],
            'invoice' => $invoice,
            'items' => $invoiceModel->items($invoiceId),
            'settings' => $invoiceModel->settings(),
            'printMode' => $printMode,
            'publicInvoice' => true,
        ]);
    }

    private function canAccessPublicInvoice(int $invoiceId): bool
    {
        return !empty($_SESSION['public_invoice_ids'][$invoiceId]);
    }

    private function publicInvoicePayload(): array
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

        $settings = (new Invoice())->settings();

        return [
            'customer_mode' => 'snapshot',
            'customer_id' => null,
            'customer_name' => $this->cleanString($this->post('customer_name')),
            'company_name' => $this->cleanString($this->post('company_name')),
            'phone' => $this->cleanString($this->post('phone')),
            'email' => strtolower($this->cleanString($this->post('email'))),
            'address' => $this->cleanString($this->post('address')),
            'tin' => $this->cleanString($this->post('tin')),
            'vrn' => $this->cleanString($this->post('vrn')),
            'invoice_number' => null,
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'unpaid',
            'invoice_discount' => 0,
            'vat_rate' => (float) ($settings['vat_rate'] ?? 0),
            'order_id' => 0,
            'quotation_id' => 0,
            'items' => $items,
        ];
    }
}
