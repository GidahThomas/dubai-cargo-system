<?php
$role = Auth::role();
$user = Auth::user();
$currentRoute = trim((string) ($_GET['url'] ?? ''), '/') ?: 'dashboard';
$staffRoles = Auth::staffRoles();
$managerRoles = ['manager', 'admin'];

$cartCount = $role === 'customer' ? CartController::count() : 0;

$navSections = [
    [
        'title' => null,
        'items' => [
            ['label' => 'Dashboard', 'icon' => 'bi-speedometer2', 'url' => 'dashboard', 'roles' => [...$staffRoles, 'customer']],
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
            ['label' => 'Message Log', 'icon' => 'bi-chat-dots', 'url' => 'messages', 'roles' => $managerRoles],
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
<aside class="app-sidebar" id="appSidebar" aria-label="Main menu">
    <div class="sb-brand">
        <a class="sb-brand-link" href="<?= h(url('dashboard')) ?>">
            <img src="<?= h(public_url(company_logo_path())) ?>" alt="">
            <span><?= h(company_name()) ?></span>
        </a>
        <button class="sb-icon-btn sb-collapse" type="button" data-sidebar-collapse aria-label="Collapse menu" title="Collapse menu">
            <i class="bi bi-chevron-double-left"></i>
        </button>
        <button class="sb-icon-btn sb-close" type="button" data-sidebar-close aria-label="Close menu">
            <i class="bi bi-x-lg"></i>
        </button>
    </div>

    <nav class="sb-nav" aria-label="Main navigation">
        <?php foreach ($navSections as $section): ?>
            <?php if (!$section['items']) continue; ?>
            <div class="sb-section">
                <?php if ($section['title'] !== null): ?>
                    <span class="sb-section-title"><?= h($section['title']) ?></span>
                <?php endif; ?>
                <?php foreach ($section['items'] as $item): ?>
                    <?php $isActive = $item['url'] === $activeUrl; ?>
                    <a class="sb-link <?= $isActive ? 'is-active' : '' ?>" href="<?= h($item['href'] ?? url($item['url'])) ?>" data-tooltip="<?= h($item['label']) ?>" <?= $isActive ? 'aria-current="page"' : '' ?>>
                        <i class="bi <?= h($item['icon']) ?>" aria-hidden="true"></i>
                        <span class="sb-label"><?= h($item['label']) ?></span>
                        <?php if (!empty($item['badge'])): ?>
                            <span class="sb-badge" aria-label="<?= (int) $item['badge'] ?>"><?= (int) $item['badge'] > 99 ? '99+' : (int) $item['badge'] ?></span>
                        <?php endif; ?>
                    </a>
                    <?php if (!empty($item['children']) && $isActive): ?>
                        <div class="sb-sub">
                            <?php foreach ($item['children'] as $child): ?>
                                <a class="<?= $currentRoute === $child['url'] ? 'is-active' : '' ?>" href="<?= h(url($child['url'])) ?>"><?= h($child['label']) ?></a>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        <?php endforeach; ?>
    </nav>

    <div class="sb-footer">
        <div class="sb-user" data-tooltip="<?= h(($user['name'] ?? '') . ' · ' . readable_status($role)) ?>">
            <span class="sb-avatar"><?= h(strtoupper(mb_substr($user['name'] ?? 'U', 0, 1))) ?></span>
            <span class="sb-user-text">
                <strong><?= h($user['name'] ?? '') ?></strong>
                <small><?= h(readable_status($role)) ?></small>
            </span>
        </div>
        <a class="sb-link sb-link-quiet <?= $currentRoute === 'users/password' ? 'is-active' : '' ?>" href="<?= h(url('users/password')) ?>" data-tooltip="Change password">
            <i class="bi bi-shield-lock" aria-hidden="true"></i><span class="sb-label">Change password</span>
        </a>
        <a class="sb-link sb-link-quiet sb-logout" href="<?= h(url('logout')) ?>" data-tooltip="Sign out">
            <i class="bi bi-box-arrow-right" aria-hidden="true"></i><span class="sb-label">Sign out</span>
        </a>
    </div>
</aside>
