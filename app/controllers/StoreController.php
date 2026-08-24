<?php

class StoreController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['manager', 'admin']);

        $period = $this->period();
        $ledger = new StoreLedger();
        $filters = [
            'type' => $this->cleanString($this->get('type')),
            'search' => $this->cleanString($this->get('search')),
            'date_from' => $this->cleanString($this->get('date_from')),
            'date_to' => $this->cleanString($this->get('date_to')),
            'min_amount' => $this->get('min_amount'),
            'max_amount' => $this->get('max_amount'),
        ];

        $this->view('store/index', [
            'pageTitle' => 'Sales Tracking',
            'period' => $period,
            'filters' => $filters,
            'summary' => $ledger->summary($period),
            'stockEntries' => $ledger->stockEntries($period),
            'sales' => $ledger->sales($period),
            'transactions' => $ledger->transactions($filters),
            'topProducts' => $ledger->topSellingProducts($period),
            'products' => (new Product())->all([], true),
        ]);
    }

    public function stockIn(): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $data = [
            'product_id' => (int) $this->post('product_id'),
            'quantity' => (int) $this->post('quantity'),
            'unit_cost' => (float) $this->post('unit_cost', 0),
            'supplier_name' => $this->cleanString($this->post('supplier_name')),
            'received_date' => $this->cleanString($this->post('received_date')),
            'notes' => $this->cleanString($this->post('notes')),
        ];

        if ($data['product_id'] < 1 || $data['quantity'] < 1) {
            flash('error', 'Chagua bidhaa na weka quantity sahihi ya mzigo ulioingia.');
            $this->redirect('store');
        }

        try {
            $entryId = (new StoreLedger())->recordStockEntry($data, Auth::id());
            (new AuditLog())->create(Auth::id(), 'stock_entry_recorded', 'stock_entries', $entryId, 'Qty: ' . $data['quantity']);
            flash('success', 'Mzigo ulioingia umerekodiwa na stock imeongezeka.');
        } catch (Throwable $exception) {
            flash('error', 'Stock-in imeshindikana: ' . $exception->getMessage());
        }

        $this->redirect('store');
    }

    public function sale(): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $data = [
            'product_id' => (int) $this->post('product_id'),
            'quantity' => (int) $this->post('quantity'),
            'unit_price' => (float) $this->post('unit_price'),
            'customer_name' => $this->cleanString($this->post('customer_name')),
            'payment_method' => $this->cleanString($this->post('payment_method')),
            'sale_date' => $this->cleanString($this->post('sale_date')),
            'notes' => $this->cleanString($this->post('notes')),
        ];

        if ($data['product_id'] < 1 || $data['quantity'] < 1 || $data['unit_price'] < 0) {
            flash('error', 'Chagua bidhaa, quantity, na bei ya kuuza.');
            $this->redirect('store');
        }

        if (!in_array($data['payment_method'], ['cash', 'bank_transfer', 'mobile_money', 'card'], true)) {
            flash('error', 'Chagua njia sahihi ya malipo.');
            $this->redirect('store');
        }

        try {
            $saleId = (new StoreLedger())->recordSale($data, Auth::id());
            (new AuditLog())->create(Auth::id(), 'store_sale_recorded', 'store_sales', $saleId, 'Qty: ' . $data['quantity']);
            flash('success', 'Sale imerekodiwa, stock imepungua, na pesa imeingia kwenye ripoti.');
        } catch (Throwable $exception) {
            flash('error', 'Sale imeshindikana: ' . $exception->getMessage());
        }

        $this->redirect('store');
    }

    private function period(): string
    {
        $period = $this->cleanString($this->get('period', 'day'));

        return in_array($period, ['day', 'week', 'month'], true) ? $period : 'day';
    }
}
