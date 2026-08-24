<div class="page-header">
    <div>
        <p class="eyebrow">Executive view</p>
        <h1>Company Owner Dashboard</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-primary" href="<?= h(url('users')) ?>">
            <i class="bi bi-people"></i> Users
        </a>
        <a class="btn btn-primary" href="<?= h(url('reports')) ?>">
            <i class="bi bi-bar-chart"></i> Reports
        </a>
    </div>
</div>

<?php
$salesParts = money_parts($salesTotal);
$pendingQuotations = (int) $pendingQuotations;
$pendingDeliveries = (int) $pendingDeliveries;
?>
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-cash-stack"></i></span>
        <div>
            <small>Confirmed Sales</small>
            <strong class="stat-value"><span class="stat-currency"><?= h($salesParts['prefix']) ?></span><span class="stat-amount"><?= h($salesParts['value']) ?></span></strong>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-people"></i></span>
        <div><small>Customers</small><strong><?= (int) $totalCustomers ?></strong></div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-receipt"></i></span>
        <div><small>Orders</small><strong><?= (int) $totalOrders ?></strong></div>
    </div>
    <div class="stat-card <?= $pendingQuotations === 0 ? 'is-empty' : '' ?>">
        <span class="stat-icon"><i class="bi bi-chat-square-text"></i></span>
        <div>
            <small>Pending Quotations</small>
            <strong><?= (int) $pendingQuotations ?></strong>
            <?php if ($pendingQuotations === 0): ?><span class="stat-empty-note">All caught up</span><?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-file-earmark-check"></i></span>
        <div><small>Generated Invoices</small><strong><?= count($generatedInvoices) ?></strong></div>
    </div>
    <div class="stat-card <?= $pendingDeliveries === 0 ? 'is-empty' : '' ?>">
        <span class="stat-icon"><i class="bi bi-truck"></i></span>
        <div>
            <small>Pending Deliveries</small>
            <strong><?= (int) $pendingDeliveries ?></strong>
            <?php if ($pendingDeliveries === 0): ?><span class="stat-empty-note">All caught up</span><?php endif; ?>
        </div>
    </div>
</div>

<?php
$dailySalesChart = [
    'type' => 'line',
    'label' => 'Daily Sales',
    'labels' => array_column($chartData['dailySales'], 'label'),
    'data' => array_map('floatval', array_column($chartData['dailySales'], 'total')),
    'backgroundColor' => 'rgba(8, 80, 65, 0.15)',
    'borderColor' => '#085041',
];
$monthlySalesChart = [
    'type' => 'bar',
    'label' => 'Monthly Sales',
    'labels' => array_column($chartData['monthlySales'], 'label'),
    'data' => array_map('floatval', array_column($chartData['monthlySales'], 'total')),
    'backgroundColor' => '#085041',
];
$ordersBars = status_breakdown_bars($chartData['ordersByStatus']);
$shipmentsBars = status_breakdown_bars($chartData['shipmentsByStatus'], 'status');
?>

<div class="chart-grid mt-4">
    <section class="panel chart-card">
        <div class="panel-header">
            <h2><i class="bi bi-graph-up-arrow me-2"></i>Daily Sales</h2>
        </div>
        <div class="chart-box">
            <canvas data-chart="<?= chart_json($dailySalesChart) ?>"></canvas>
        </div>
    </section>
    <section class="panel chart-card">
        <div class="panel-header">
            <h2><i class="bi bi-calendar-month me-2"></i>Monthly Sales</h2>
        </div>
        <div class="chart-box">
            <canvas data-chart="<?= chart_json($monthlySalesChart) ?>"></canvas>
        </div>
    </section>
    <section class="panel chart-card">
        <div class="panel-header">
            <h2><i class="bi bi-receipt me-2"></i>Orders by Status</h2>
        </div>
        <div class="status-bars">
            <?php foreach ($ordersBars as $bar): ?>
                <div class="status-bar-row">
                    <div class="status-bar-label">
                        <span><?= h($bar['label']) ?></span>
                        <span><?= (int) $bar['count'] ?> &middot; <?= (int) $bar['percentage'] ?>%</span>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width: <?= (int) $bar['percentage'] ?>%; background: <?= h($bar['color']) ?>;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$ordersBars): ?>
                <p class="text-muted mb-0">No orders yet.</p>
            <?php endif; ?>
        </div>
    </section>
    <section class="panel chart-card">
        <div class="panel-header">
            <h2><i class="bi bi-truck me-2"></i>Shipments by Status</h2>
        </div>
        <div class="status-bars">
            <?php foreach ($shipmentsBars as $bar): ?>
                <div class="status-bar-row">
                    <div class="status-bar-label">
                        <span><?= h($bar['label']) ?></span>
                        <span><?= (int) $bar['count'] ?> &middot; <?= (int) $bar['percentage'] ?>%</span>
                    </div>
                    <div class="status-bar-track">
                        <div class="status-bar-fill" style="width: <?= (int) $bar['percentage'] ?>%; background: <?= h($bar['color']) ?>;"></div>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if (!$shipmentsBars): ?>
                <p class="text-muted mb-0">No shipments yet.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <section class="panel">
            <div class="panel-header">
                <h2>Recent Orders</h2>
                <a href="<?= h(url('orders')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-right"></i></a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle table-fixed-cols">
                    <colgroup>
                        <col style="width: 26%">
                        <col style="width: 26%">
                        <col style="width: 18%">
                        <col style="width: 15%">
                        <col style="width: 15%">
                    </colgroup>
                    <thead>
                    <tr>
                        <th>Order</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Payment</th>
                        <th>Status</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                        <tr>
                            <td><span class="id-truncate code-text" title="<?= h($order['order_number']) ?>"><?= h($order['order_number']) ?></span></td>
                            <td><?= h($order['customer_name']) ?></td>
                            <td><?= h(money($order['total_amount'])) ?></td>
                            <td><span class="badge <?= h(badge_class($order['payment_status'])) ?>"><?= h(readable_status($order['payment_status'])) ?></span></td>
                            <td><span class="badge <?= h(badge_class($order['status'])) ?>"><?= h(readable_status($order['status'])) ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="panel">
            <div class="panel-header">
                <h2>Monthly Sales</h2>
            </div>
            <div class="stack-list">
                <?php foreach ($monthlySales as $month): ?>
                    <div class="stack-item">
                        <div>
                            <strong><?= h($month['month']) ?></strong>
                            <small>Confirmed payments</small>
                        </div>
                        <span><?= h(money($month['total'])) ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$monthlySales): ?>
                    <p class="text-muted mb-0">No confirmed sales yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel mt-4">
            <div class="panel-header">
                <h2>Audit Activity</h2>
                <a href="<?= h(url('reports')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-list-check"></i></a>
            </div>
            <div class="stack-list audit-mini">
                <?php foreach (array_slice($auditLogs, 0, 5) as $log): ?>
                    <div class="stack-item">
                        <div>
                            <strong><?= h(readable_status($log['action'])) ?></strong>
                            <small><?= h($log['user_name'] ?? 'System') ?> / <?= h($log['created_at']) ?></small>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </section>
    </div>
</div>
