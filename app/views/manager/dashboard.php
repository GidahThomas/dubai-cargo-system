<div class="page-header">
    <div>
        <p class="eyebrow">Operations</p>
        <h1>Store Manager Dashboard</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-primary" href="<?= h(url('store')) ?>">
            <i class="bi bi-box-seam"></i> Inventory
        </a>
        <a class="btn btn-primary" href="<?= h(url('products/create')) ?>">
            <i class="bi bi-plus-lg"></i> New Product
        </a>
    </div>
</div>

<?php
$pendingOrders = (int) $pendingOrders;
$pendingQuotations = (int) $pendingQuotations;
$pendingDeliveries = (int) $pendingDeliveries;
?>
<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-laptop"></i></span>
        <div><small>Active Products</small><strong><?= (int) $productCount ?></strong></div>
    </div>
    <div class="stat-card <?= $pendingOrders === 0 ? 'is-empty' : '' ?>">
        <span class="stat-icon"><i class="bi bi-hourglass-split"></i></span>
        <div>
            <small>Pending Orders</small>
            <strong><?= $pendingOrders ?></strong>
            <?php if ($pendingOrders === 0): ?><span class="stat-empty-note">All caught up</span><?php endif; ?>
        </div>
    </div>
    <div class="stat-card <?= $pendingQuotations === 0 ? 'is-empty' : '' ?>">
        <span class="stat-icon"><i class="bi bi-chat-square-text"></i></span>
        <div>
            <small>Pending Quotations</small>
            <strong><?= $pendingQuotations ?></strong>
            <?php if ($pendingQuotations === 0): ?><span class="stat-empty-note">All caught up</span><?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-receipt"></i></span>
        <div><small>Invoices</small><strong><?= count($generatedInvoices) ?></strong></div>
    </div>
    <div class="stat-card <?= $pendingDeliveries === 0 ? 'is-empty' : '' ?>">
        <span class="stat-icon"><i class="bi bi-truck"></i></span>
        <div>
            <small>Pending Deliveries</small>
            <strong><?= $pendingDeliveries ?></strong>
            <?php if ($pendingDeliveries === 0): ?><span class="stat-empty-note">All caught up</span><?php endif; ?>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-check2-circle"></i></span>
        <div><small>Completed Deliveries</small><strong><?= (int) $completedDeliveries ?></strong></div>
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
$weeklySalesChart = [
    'type' => 'bar',
    'label' => 'Weekly Sales',
    'labels' => array_column($chartData['weeklySales'], 'label'),
    'data' => array_map('floatval', array_column($chartData['weeklySales'], 'total')),
    'backgroundColor' => '#1D9E75',
];
$ordersBars = status_breakdown_bars($chartData['ordersByStatus']);
$paymentsBars = status_breakdown_bars($chartData['paymentsByStatus']);
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
            <h2><i class="bi bi-calendar-week me-2"></i>Weekly Sales</h2>
        </div>
        <div class="chart-box">
            <canvas data-chart="<?= chart_json($weeklySalesChart) ?>"></canvas>
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
            <h2><i class="bi bi-credit-card me-2"></i>Payments by Status</h2>
        </div>
        <div class="status-bars">
            <?php foreach ($paymentsBars as $bar): ?>
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
            <?php if (!$paymentsBars): ?>
                <p class="text-muted mb-0">No payments yet.</p>
            <?php endif; ?>
        </div>
    </section>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-8">
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

    <div class="col-lg-4">
        <section class="panel">
            <div class="panel-header">
                <h2>Low Stock</h2>
                <a href="<?= h(url('reports')) ?>" class="btn btn-sm btn-outline-secondary"><i class="bi bi-bar-chart"></i></a>
            </div>
            <div class="stack-list">
                <?php foreach ($lowStock as $item): ?>
                    <div class="stack-item">
                        <div>
                            <strong><?= h($item['name']) ?></strong>
                            <small><?= h($item['sku']) ?> / Reorder at <?= (int) $item['reorder_level'] ?></small>
                        </div>
                        <span class="badge text-bg-warning"><?= (int) $item['quantity'] ?></span>
                    </div>
                <?php endforeach; ?>
                <?php if (!$lowStock): ?>
                    <p class="text-muted mb-0">Inventory levels look healthy.</p>
                <?php endif; ?>
            </div>
        </section>
    </div>
</div>
