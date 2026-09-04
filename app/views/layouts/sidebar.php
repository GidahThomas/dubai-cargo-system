<?php
$role = Auth::role();
$user = Auth::user();
$currentRoute = trim((string) ($_GET['url'] ?? 'dashboard'), '/');
$staffRoles = Auth::staffRoles();
$navItems = [
    ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => 'dashboard', 'roles' => [...$staffRoles, 'customer']],
    ['label' => 'Browse Products', 'icon' => 'bi-laptop', 'url' => 'products', 'roles' => ['customer']],
    ['label' => 'Track Shipment', 'icon' => 'bi-truck', 'url' => 'track-shipment', 'roles' => ['customer']],
    ['label' => 'Request Invoice', 'icon' => 'bi-receipt-cutoff', 'url' => 'request-invoice', 'roles' => ['customer']],
    ['label' => 'Request Quotation', 'icon' => 'bi-chat-square-text', 'url' => 'request-quotation', 'roles' => ['customer']],
    ['label' => 'Products', 'icon' => 'bi-laptop', 'url' => 'products', 'roles' => $staffRoles],
    ['label' => 'Sales Tracking', 'icon' => 'bi-cash-coin', 'url' => 'store', 'roles' => $staffRoles],
    ['label' => 'Customers', 'icon' => 'bi-people', 'url' => 'users', 'roles' => $staffRoles],
    ['label' => 'Quotations', 'icon' => 'bi-chat-square-text', 'url' => 'quotations', 'roles' => $staffRoles],
    [
        'label' => 'Invoices',
        'icon' => 'bi-receipt-cutoff',
        'url' => 'invoices',
        'roles' => $staffRoles,
        'children' => [
            ['label' => 'Create Invoice', 'url' => 'invoices/create'],
            ['label' => 'Invoice List', 'url' => 'invoices'],
            ['label' => 'Draft Invoices', 'url' => 'invoices/draft'],
            ['label' => 'Paid Invoices', 'url' => 'invoices/paid'],
            ['label' => 'Unpaid Invoices', 'url' => 'invoices/unpaid'],
            ['label' => 'Cancelled Invoices', 'url' => 'invoices/cancelled'],
            ['label' => 'Invoice Settings', 'url' => 'invoices/settings'],
        ],
    ],
    ['label' => 'Orders', 'icon' => 'bi-receipt', 'url' => 'orders', 'roles' => $staffRoles],
    ['label' => 'Payments', 'icon' => 'bi-credit-card', 'url' => 'payments', 'roles' => $staffRoles],
    ['label' => 'Shipments', 'icon' => 'bi-truck', 'url' => 'shipments', 'roles' => $staffRoles],
    ['label' => 'Locations', 'icon' => 'bi-geo-alt', 'url' => 'locations', 'roles' => $staffRoles],
    ['label' => 'Reports', 'icon' => 'bi-bar-chart', 'url' => 'reports', 'roles' => $staffRoles],
    ['label' => 'Settings', 'icon' => 'bi-gear', 'url' => 'invoices/settings', 'roles' => $staffRoles],
];
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-brand">
        <img class="brand-logo" src="<?= h(public_url(company_logo_path())) ?>" alt="<?= h(company_name()) ?> logo">
        <div>
            <strong><?= h(company_name()) ?></strong>
            <small>Business Management System</small>
        </div>
    </div>

    <div class="sidebar-user">
        <div class="avatar"><?= h(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?></div>
        <div>
            <strong><?= h($user['name'] ?? '') ?></strong>
            <small><?= h(readable_status($role)) ?></small>
        </div>
    </div>

    <nav class="sidebar-nav">
        <?php foreach ($navItems as $item): ?>
            <?php if (in_array($role, $item['roles'], true)): ?>
                <a class="<?= str_starts_with($currentRoute, $item['url']) || ($currentRoute === '' && $item['url'] === 'dashboard') ? 'is-active' : '' ?>" href="<?= h(url($item['url'])) ?>">
                    <i class="bi <?= h($item['icon']) ?>"></i>
                    <span><?= h($item['label']) ?></span>
                </a>
                <?php if (!empty($item['children']) && str_starts_with($currentRoute, $item['url'])): ?>
                    <div class="sidebar-subnav">
                        <?php foreach ($item['children'] as $child): ?>
                            <a class="<?= $currentRoute === $child['url'] ? 'is-active' : '' ?>" href="<?= h(url($child['url'])) ?>">
                                <?= h($child['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a class="btn btn-outline-light w-100" href="<?= h(url('logout')) ?>">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
        </a>
    </div>
</aside>
