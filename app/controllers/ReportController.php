<?php

class ReportController extends Controller
{
    public function index(): void
    {
        $this->requireRole(['manager', 'admin']);

        $ledger = new StoreLedger();
        $locationId = $this->activeLocationId();
        $recentSales = $ledger->transactions(['type' => 'sale']);

        $this->view('reports/index', [
            'pageTitle' => 'Reports',
            'activeLocation' => $locationId !== null ? (new Location())->find($locationId) : null,
            'salesTotal' => (new Order())->salesTotal($locationId),
            'monthlySales' => (new Order())->monthlySales($locationId),
            'storeToday' => $ledger->summary('day', $locationId),
            'storeWeek' => $ledger->summary('week', $locationId),
            'storeMonth' => $ledger->summary('month', $locationId),
            'recentStoreSales' => array_slice($recentSales, 0, 12),
            'topStoreProducts' => $ledger->topSellingProducts('month', $locationId),
            'lowStock' => (new Product())->lowStock(20, $locationId),
            'inventoryUnits' => (new Inventory())->totalUnits($locationId),
            'shipmentsByStatus' => (new Shipment())->reportByStatus(),
            'auditLogs' => Auth::role() === 'admin' ? (new AuditLog())->all() : [],
        ]);
    }
}
