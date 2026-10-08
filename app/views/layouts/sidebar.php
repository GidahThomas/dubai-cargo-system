<?php
$role = Auth::role();
$user = Auth::user();
$currentRoute = trim((string) ($_GET['url'] ?? ''), '/') ?: 'dashboard';
$staffRoles = Auth::staffRoles();
$managerRoles = ['manager', 'admin'];

$unreadNotifications = (new Notification())->unreadCount((int) Auth::id());
$cartCount = $role === 'customer' ? CartController::count() : 0;

$navSections = [
    [
        'title' => null,
        'items' => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => 'dashboard', 'roles' => [...$staffRoles, 'customer']],
            ['label' => 'Notifications', 'icon' => 'bi-bell', 'url' => 'notifications', 'roles' => [...$staffRoles, 'customer'], 'badge' => $unreadNotifications],
        ],
    ],
    [
        'title' => 'Shop',
        'items' => [
            ['label' => 'Browse Products', 'icon' => 'bi-laptop', 'url' => 'products', 'roles' => ['customer']],
            ['label' => 'My Cart', 'icon' => 'bi-cart3', 'url' => 'cart', 'roles' => ['customer'], 'badge' => $cartCount],
            ['label' => 'My Orders', 'icon' => 'bi-receipt', 'url' => 'orders', 'roles' => ['customer']],
            ['label' => 'Payments', 'icon' => 'bi-credit-card', 'url' => 'payments', 'roles' => ['customer']],
            ['label' => 'Track Shipment', 'icon' => 'bi-truck', 'url' => 'track-shipment', 'roles' => ['customer']],
        ],
    ],
    [
        'title' => 'Requests',
        'items' => [
            ['label' => 'Request Invoice', 'icon' => 'bi-receipt-cutoff', 'url' => 'request-invoice', 'roles' => ['customer']],
            ['label' => 'Request Quotation', 'icon' => 'bi-chat-square-text', 'url' => 'request-quotation', 'roles' => ['customer']],
            // A jump to the profile form on the dashboard; never highlighted as the current page.
            ['label' => 'My Profile', 'icon' => 'bi-person-circle', 'url' => '#profile', 'href' => url('dashboard') . '#profile', 'roles' => ['customer']],
        ],
    ],
    [
        'title' => 'Sales',
        'items' => [
            ['label' => 'Orders', 'icon' => 'bi-receipt', 'url' => 'orders', 'roles' => $staffRoles],
            [
                'label' => 'Invoices',
                'icon' => 'bi-receipt-cutoff',
                'url' => 'invoices',
                'roles' => $managerRoles,
                'children' => [
                    ['label' => 'Create Invoice', 'url' => 'invoices/create'],
                    ['label' => 'All Invoices', 'url' => 'invoices'],
                    ['label' => 'Draft', 'url' => 'invoices/draft'],
                    ['label' => 'Paid', 'url' => 'invoices/paid'],
                    ['label' => 'Unpaid', 'url' => 'invoices/unpaid'],
                    ['label' => 'Cancelled', 'url' => 'invoices/cancelled'],
                ],
            ],
            ['label' => 'Quotations', 'icon' => 'bi-chat-square-text', 'url' => 'quotations', 'roles' => $managerRoles],
            ['label' => 'Payments', 'icon' => 'bi-credit-card', 'url' => 'payments', 'roles' => $staffRoles],
            ['label' => 'Sales Tracking', 'icon' => 'bi-cash-coin', 'url' => 'store', 'roles' => $managerRoles],
        ],
    ],
    [
        'title' => 'Inventory',
        'items' => [
            ['label' => 'Products', 'icon' => 'bi-laptop', 'url' => 'products', 'roles' => $staffRoles],
            ['label' => 'Locations', 'icon' => 'bi-geo-alt', 'url' => 'locations', 'roles' => $managerRoles],
        ],
    ],
    [
        'title' => 'Logistics',
        'items' => [
            ['label' => 'Shipments', 'icon' => 'bi-truck', 'url' => 'shipments', 'roles' => $staffRoles],
            ['label' => 'Deliveries', 'icon' => 'bi-box-seam', 'url' => 'deliveries', 'roles' => $managerRoles],
        ],
    ],
    [
        'title' => 'Management',
        'items' => [
            ['label' => 'Customers', 'icon' => 'bi-people', 'url' => 'users', 'roles' => $managerRoles],
            ['label' => 'Reports', 'icon' => 'bi-bar-chart', 'url' => 'reports', 'roles' => $managerRoles],
            ['label' => 'Instagram Import', 'icon' => 'bi-instagram', 'url' => 'instagram', 'roles' => $managerRoles],
            ['label' => 'Audit Review', 'icon' => 'bi-shield-check', 'url' => 'audit', 'roles' => ['admin']],
            ['label' => 'Settings', 'icon' => 'bi-gear', 'url' => 'invoices/settings', 'roles' => $managerRoles],
        ],
    ],
];

$routeMatches = static fn (string $url): bool => $currentRoute === $url || str_starts_with($currentRoute, $url . '/');

// Keep only what this role can open, then highlight the single most specific match
// (so "invoices/settings" lights up Settings, not Invoices as well).
$activeUrl = null;
foreach ($navSections as $sectionIndex => $section) {
    $navSections[$sectionIndex]['items'] = array_values(array_filter(
        $section['items'],
        static fn (array $item): bool => in_array($role, $item['roles'], true)
    ));

    foreach ($navSections[$sectionIndex]['items'] as $item) {
        if ($routeMatches($item['url']) && strlen($item['url']) > strlen((string) $activeUrl)) {
            $activeUrl = $item['url'];
        }
    }
}
?>
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-user">
        <div class="avatar"><?= h(strtoupper(substr($user['name'] ?? 'U', 0, 1))) ?></div>
        <div>
            <strong><?= h($user['name'] ?? '') ?></strong>
            <small><?= h(readable_status($role)) ?></small>
        </div>
    </div>

    <nav class="sidebar-nav" aria-label="Main navigation">
        <?php foreach ($navSections as $section): ?>
            <?php if (!$section['items']) continue; ?>
            <?php if ($section['title'] !== null): ?>
                <span class="sidebar-section-title"><?= h($section['title']) ?></span>
            <?php endif; ?>
            <?php foreach ($section['items'] as $item): ?>
                <?php $isActive = $item['url'] === $activeUrl; ?>
                <a class="<?= $isActive ? 'is-active' : '' ?>" href="<?= h($item['href'] ?? url($item['url'])) ?>" title="<?= h($item['label']) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                    <i class="bi <?= h($item['icon']) ?>"></i>
                    <span><?= h($item['label']) ?></span>
                    <?php if (!empty($item['badge'])): ?>
                        <span class="sidebar-badge"><?= (int) $item['badge'] > 99 ? '99+' : (int) $item['badge'] ?></span>
                    <?php endif; ?>
                </a>
                <?php if (!empty($item['children']) && $isActive): ?>
                    <div class="sidebar-subnav">
                        <?php foreach ($item['children'] as $child): ?>
                            <a class="<?= $currentRoute === $child['url'] ? 'is-active' : '' ?>" href="<?= h(url($child['url'])) ?>">
                                <?= h($child['label']) ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="sidebar-footer">
        <a class="btn btn-outline-light w-100" href="<?= h(url('logout')) ?>">
            <i class="bi bi-box-arrow-right me-2"></i>Logout
        </a>
    </div>
</aside>
