<?php

class ReportController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['manager', 'admin']);

        $ledger = new StoreLedger();
        $recentSales = $ledger->transactions(['type' => 'sale']);

        $this->view('reports/index', [
            'pageTitle' => 'Reports',
            'salesTotal' => (new Order())->salesTotal(),
            'monthlySales' => (new Order())->monthlySales(),
            'storeToday' => $ledger->summary('day'),
            'storeWeek' => $ledger->summary('week'),
            'storeMonth' => $ledger->summary('month'),
            'recentStoreSales' => array_slice($recentSales, 0, 12),
            'topStoreProducts' => $ledger->topSellingProducts('month'),
            'lowStock' => (new Product())->lowStock(20),
            'inventoryUnits' => (new Inventory())->totalUnits(),
            'shipmentsByStatus' => (new Shipment())->reportByStatus(),
            'auditLogs' => Auth::role() === 'admin' ? (new AuditLog())->all() : [],
        ]);
    }
}
