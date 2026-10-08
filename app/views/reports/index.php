<div class="page-header">
    <div>
        <p class="eyebrow">Business intelligence</p>
        <h1>Reports</h1>
        <p class="text-muted mb-0">Showing: <strong><?= h($activeLocation['name'] ?? 'All Locations (combined)') ?></strong></p>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-primary" href="<?= h(url('store')) ?>">
            <i class="bi bi-cash-coin"></i> Sales Tracking
        </a>
        <button class="btn btn-primary" type="button" onclick="window.print()">
            <i class="bi bi-printer"></i> Print
        </button>
    </div>
</div>

<section class="panel mb-4 d-print-none">
    <div class="panel-header">
        <h2><i class="bi bi-file-earmark-spreadsheet"></i> Download for Excel</h2>
    </div>
    <form method="get" action="<?= h(url()) ?>" class="row g-3 align-items-end" id="reportExportForm">
        <input type="hidden" name="url" value="reports/export/sales" id="reportExportUrl">
        <div class="col-md-3">
            <label class="form-label" for="exportReport">Report</label>
            <select class="form-select" id="exportReport" onchange="document.getElementById('reportExportUrl').value = 'reports/export/' + this.value">
                <?php foreach ($exportReports as $key => $label): ?>
                    <option value="<?= h($key) ?>"><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label" for="exportFrom">From</label>
            <input type="date" class="form-control" id="exportFrom" name="date_from" value="<?= h(date('Y-m-01')) ?>">
        </div>
        <div class="col-6 col-md-3">
            <label class="form-label" for="exportTo">To</label>
            <input type="date" class="form-control" id="exportTo" name="date_to" value="<?= h(date('Y-m-d')) ?>">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-download"></i> Download CSV</button>
        </div>
        <div class="col-12">
            <small class="text-muted">Opens in Excel. Uses the branch selected at the top of the page; inventory is a current snapshot and ignores the dates.</small>
        </div>
    </form>
</section>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-cash-stack"></i></span>
        <div><small>Confirmed Order Sales</small><strong><?= h(money($salesTotal)) ?></strong></div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-box-seam"></i></span>
        <div><small>Inventory Units</small><strong><?= (int) $inventoryUnits ?></strong></div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-exclamation-triangle"></i></span>
        <div><small>Low Stock Items</small><strong><?= count($lowStock) ?></strong></div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-truck"></i></span>
        <div><small>Shipment Statuses</small><strong><?= count($shipmentsByStatus) ?></strong></div>
    </div>
</div>

<div class="stats-grid mt-4">
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-calendar-day"></i></span>
        <div><small>Store Sales Today</small><strong><?= h(money($storeToday['revenue'])) ?></strong></div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-calendar-week"></i></span>
        <div><small>Store Sales This Week</small><strong><?= h(money($storeWeek['revenue'])) ?></strong></div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-calendar-month"></i></span>
        <div><small>Store Sales This Month</small><strong><?= h(money($storeMonth['revenue'])) ?></strong></div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-bag-check"></i></span>
        <div><small>Units Sold This Month</small><strong><?= (int) $storeMonth['units_sold'] ?></strong></div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <section class="panel">
            <div class="panel-header">
                <h2>Recent Store Sales</h2>
                <a class="btn btn-sm btn-outline-secondary" href="<?= h(url('store')) ?>"><i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Date</th><th>Product</th><th>Qty</th><th>Total</th><th>Customer</th></tr></thead>
                    <tbody>
                    <?php foreach ($recentStoreSales as $sale): ?>
                        <tr>
                            <td><?= h($sale['transaction_date']) ?></td>
                            <td><?= h($sale['product_name']) ?></td>
                            <td><?= (int) $sale['quantity'] ?></td>
                            <td><?= h(money($sale['total_amount'])) ?></td>
                            <td><?= h($sale['party_name'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$recentStoreSales): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No store sales recorded yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="panel">
            <div class="panel-header">
                <h2>Top Products This Month</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Product</th><th>Qty</th><th>Revenue</th></tr></thead>
                    <tbody>
                    <?php foreach ($topStoreProducts as $product): ?>
                        <tr>
                            <td><?= h($product['name']) ?></td>
                            <td><?= (int) $product['units_sold'] ?></td>
                            <td><?= h(money($product['revenue'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$topStoreProducts): ?>
                        <tr><td colspan="3" class="text-center text-muted py-4">No top products yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="panel">
            <div class="panel-header">
                <h2>Sales Report</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Month</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($monthlySales as $row): ?>
                        <tr><td><?= h($row['month']) ?></td><td><?= h(money($row['total'])) ?></td></tr>
                    <?php endforeach; ?>
                    <?php if (!$monthlySales): ?>
                        <tr><td colspan="2" class="text-center text-muted py-4">No sales data.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="panel">
            <div class="panel-header">
                <h2>Shipment Report</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Status</th><th>Total</th></tr></thead>
                    <tbody>
                    <?php foreach ($shipmentsByStatus as $row): ?>
                        <tr>
                            <td><span class="badge <?= h(badge_class($row['status'])) ?>"><?= h(readable_status($row['status'])) ?></span></td>
                            <td><?= (int) $row['total'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$shipmentsByStatus): ?>
                        <tr><td colspan="2" class="text-center text-muted py-4">No shipment data.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="panel">
            <div class="panel-header">
                <h2>Inventory Report</h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead><tr><th>Product</th><th>SKU</th><th>Qty</th><th>Reorder</th></tr></thead>
                    <tbody>
                    <?php foreach ($lowStock as $item): ?>
                        <tr>
                            <td><?= h($item['name']) ?></td>
                            <td><?= h($item['sku']) ?></td>
                            <td><span class="badge text-bg-warning"><?= (int) $item['quantity'] ?></span></td>
                            <td><?= (int) $item['reorder_level'] ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$lowStock): ?>
                        <tr><td colspan="4" class="text-center text-muted py-4">No low-stock items.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <?php if (Auth::role() === 'admin'): ?>
        <div class="col-lg-6">
            <section class="panel">
                <div class="panel-header">
                    <h2>Audit Logs</h2>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead><tr><th>Action</th><th>User</th><th>Date</th></tr></thead>
                        <tbody>
                        <?php foreach ($auditLogs as $log): ?>
                            <tr>
                                <td><?= h(readable_status($log['action'])) ?></td>
                                <td><?= h($log['user_name'] ?? 'System') ?></td>
                                <td><?= h($log['created_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if (!$auditLogs): ?>
                            <tr><td colspan="3" class="text-center text-muted py-4">No audit logs.</td></tr>
                        <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>
    <?php endif; ?>
</div>
