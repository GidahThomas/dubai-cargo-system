<?php

class QuotationController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['manager', 'admin']);

        $this->view('quotations/index', [
            'pageTitle' => 'Quotations',
            'quotations' => (new Quotation())->all(),
        ]);
    }

    public function show(int $id): void
    {
        $this->requireRole(['manager', 'admin']);

        $quotation = (new Quotation())->find($id);

        if (!$quotation) {
            flash('error', 'Quotation not found.');
            $this->redirect('quotations');
        }

        $this->view('quotations/view', [
            'pageTitle' => 'Quotation Review',
            'quotation' => $quotation,
        ]);
    }

    public function update(int $id): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $quotationModel = new Quotation();
        $quotation = $quotationModel->find($id);

        if (!$quotation) {
            flash('error', 'Quotation not found.');
            $this->redirect('quotations');
        }

        $data = [
            'customer_name' => $this->cleanString($this->post('customer_name')),
            'company_name' => $this->cleanString($this->post('company_name')),
            'phone' => $this->cleanString($this->post('phone')),
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
            'status' => $this->cleanString($this->post('status')) ?: 'pending',
            'subtotal' => (float) $this->post('subtotal', 0),
            'discount' => (float) $this->post('discount', 0),
            'transport_cost' => (float) $this->post('transport_cost', 0),
            'installation_cost' => (float) $this->post('installation_cost', 0),
            'tax' => (float) $this->post('tax', 0),
            'grand_total' => (float) $this->post('grand_total', 0),
            'admin_notes' => $this->cleanString($this->post('admin_notes')),
            'items' => [],
        ];

        foreach ($this->post('item_product_name', []) as $index => $productName) {
            $data['items'][] = [
                'product_id' => $this->post('item_product_id', [])[$index] ?? null,
                'product_name' => $this->cleanString((string) $productName),
                'product_image' => $this->cleanString((string) ($this->post('item_product_image', [])[$index] ?? '')),
                'quantity' => max(1, (int) ($this->post('item_quantity', [])[$index] ?? 1)),
                'unit_price' => (float) ($this->post('item_unit_price', [])[$index] ?? 0),
                'discount' => (float) ($this->post('item_discount', [])[$index] ?? 0),
                'notes' => $this->cleanString((string) ($this->post('item_notes', [])[$index] ?? '')),
                'line_total' => (float) ($this->post('item_line_total', [])[$index] ?? 0),
            ];
        }

        $quotationModel->update($id, $data);

        if ($data['status'] === 'approved') {
            $invoiceId = $this->generateInvoiceFromQuotation($id, $data);
            $quotationModel->update($id, array_merge($data, ['status' => 'approved']));
            flash('success', 'Quotation approved and invoice generated.');
            $this->redirect('quotations/show/' . $id);
        }

        flash('success', 'Quotation updated.');
        $this->redirect('quotations/show/' . $id);
    }

    private function generateInvoiceFromQuotation(int $quotationId, array $data): int
    {
        $invoiceModel = new Invoice();
        $quotation = (new Quotation())->find($quotationId);

        $items = [];
        foreach ($quotation['items'] ?? [] as $item) {
            $items[] = [
                'product_id' => $item['product_id'] ?? null,
                'product_name' => $item['product_name'],
                'product_image' => $item['product_image'] ?? null,
                'description' => $item['notes'] ?? '',
                'specifications' => '',
                'quantity' => (int) $item['quantity'],
                'unit_price' => (float) $item['unit_price'],
                'line_discount' => (float) $item['discount'],
            ];
        }

        $invoiceId = $invoiceModel->create([
            'customer_mode' => 'snapshot',
            'customer_name' => $data['customer_name'],
            'company_name' => $data['company_name'],
            'phone' => $data['phone'],
            'email' => $data['email'],
            'address' => $data['delivery_address'] ?? '',
            'invoice_number' => null,
            'invoice_date' => date('Y-m-d'),
            'due_date' => date('Y-m-d', strtotime('+7 days')),
            'status' => 'unpaid',
            'quotation_id' => $quotationId,
            'invoice_discount' => (float) $data['discount'],
            'vat_rate' => (float) $data['tax'],
            'items' => $items,
        ], Auth::id());

        if (!empty($quotation['delivery_method']) && in_array($quotation['delivery_method'], ['home_delivery', 'courier'], true)) {
            (new Quotation())->createDeliveryFromQuotation($quotationId);
        }

        return $invoiceId;
    }
}
