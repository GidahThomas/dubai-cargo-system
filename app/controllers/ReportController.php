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
            'exportReports' => ReportExport::REPORTS,
        ]);
    }

    /**
     * Downloads a report as CSV (opens directly in Excel), e.g. reports/export/sales&date_from=2026-10-01.
     */
    public function export(string $report = ''): void
    {
        $this->requireRole(['manager', 'admin']);

        if (!isset(ReportExport::REPORTS[$report])) {
            flash('error', 'Unknown report.');
            $this->redirect('reports');
        }

        $date = static fn ($value): ?string => preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $value) ? (string) $value : null;
        $from = $date($this->get('date_from'));
        $to = $date($this->get('date_to'));
        $locationId = $this->activeLocationId();

        $table = (new ReportExport())->build($report, $from, $to, $locationId);
        (new AuditLog())->create(Auth::id(), 'report_exported', null, null, $table['title'] . ' ' . ($from ?? 'start') . ' to ' . ($to ?? 'today'));

        $fileName = sprintf('%s_%s%s.csv', $report, $from ?? 'all', $to ? '_to_' . $to : '');
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $fileName . '"');
        header('Cache-Control: no-store');

        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 marker so Excel shows names and symbols correctly
        fputcsv($out, [company_name() . ' - ' . $table['title'] . ' - ' . ($from ?? 'all dates') . ($to ? ' to ' . $to : '') . ' - exported ' . date('Y-m-d H:i')]);
        fputcsv($out, $table['headers']);
        foreach ($table['rows'] as $row) {
            // Prefix cells that Excel would treat as a formula, so exported data cannot run formulas.
            fputcsv($out, array_map(static fn ($cell) => is_string($cell) && $cell !== '-' && preg_match('/^[=+\-@]/', $cell) ? "'" . $cell : $cell, $row));
        }
        fclose($out);
        exit;
    }
}
