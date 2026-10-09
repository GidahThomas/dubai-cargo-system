<?php
/**
 * App-style tab bar shown on phones. Items depend on who is using the app;
 * "Menu" opens the full sidebar drawer so nothing is out of reach.
 */
$bnSection = explode('/', trim((string) ($_GET['url'] ?? ''), '/'))[0] ?: 'home';
$bnRole = Auth::role();
$bnInApp = ($layout ?? '') !== 'public' && Auth::check();

if (!Auth::check() || !$bnInApp) {
    $bnItems = [
        ['route' => 'home', 'href' => url(), 'icon' => 'bi-house', 'label' => 'Home'],
        ['route' => 'products', 'href' => url('products'), 'icon' => 'bi-grid', 'label' => 'Shop'],
        ['route' => 'track-shipment', 'href' => url('track-shipment'), 'icon' => 'bi-truck', 'label' => 'Track'],
        ['route' => 'contact', 'href' => url('contact'), 'icon' => 'bi-chat-dots', 'label' => 'Contact'],
        Auth::check()
            ? ['route' => 'dashboard', 'href' => url('dashboard'), 'icon' => 'bi-person-circle', 'label' => 'Account']
            : ['route' => 'login', 'href' => url('login'), 'icon' => 'bi-person', 'label' => 'Sign in'],
    ];
} elseif ($bnRole === 'customer') {
    $bnCart = CartController::count();
    $bnItems = [
        ['route' => 'dashboard', 'href' => url('dashboard'), 'icon' => 'bi-house', 'label' => 'Home'],
        ['route' => 'products', 'href' => url('products'), 'icon' => 'bi-grid', 'label' => 'Shop'],
        ['route' => 'cart', 'href' => url('cart'), 'icon' => 'bi-cart3', 'label' => 'Cart', 'badge' => $bnCart],
        ['route' => 'orders', 'href' => url('orders'), 'icon' => 'bi-receipt', 'label' => 'Orders'],
        ['route' => '__menu', 'icon' => 'bi-list', 'label' => 'Menu'],
    ];
} else {
    $bnItems = [
        ['route' => 'dashboard', 'href' => url('dashboard'), 'icon' => 'bi-speedometer2', 'label' => 'Dashboard'],
        ['route' => 'orders', 'href' => url('orders'), 'icon' => 'bi-receipt', 'label' => 'Orders'],
        ['route' => 'products', 'href' => url('products'), 'icon' => 'bi-laptop', 'label' => 'Products'],
        ['route' => 'shipments', 'href' => url('shipments'), 'icon' => 'bi-truck', 'label' => 'Shipments'],
        ['route' => '__menu', 'icon' => 'bi-list', 'label' => 'Menu'],
    ];
}
?>
<nav class="bottom-nav" aria-label="Quick navigation">
    <?php foreach ($bnItems as $item): ?>
        <?php if ($item['route'] === '__menu'): ?>
            <button type="button" class="bottom-nav-item" data-sidebar-toggle aria-controls="appSidebar" aria-expanded="false">
                <i class="bi <?= h($item['icon']) ?>" aria-hidden="true"></i>
                <span><?= h($item['label']) ?></span>
            </button>
        <?php else: ?>
            <?php $isHere = $bnSection === $item['route']; ?>
            <a class="bottom-nav-item <?= $isHere ? 'is-active' : '' ?>" href="<?= h($item['href']) ?>" <?= $isHere ? 'aria-current="page"' : '' ?>>
                <span class="bottom-nav-icon">
                    <?php $hasFill = in_array($item['icon'], ['bi-house', 'bi-grid', 'bi-chat-dots', 'bi-person', 'bi-laptop'], true); ?>
                    <i class="bi <?= h($item['icon']) ?><?= $isHere && $hasFill ? '-fill' : '' ?>" aria-hidden="true"></i>
                    <?php if (!empty($item['badge'])): ?>
                        <span class="bottom-nav-badge"><?= (int) $item['badge'] > 9 ? '9+' : (int) $item['badge'] ?></span>
                    <?php endif; ?>
                </span>
                <span><?= h($item['label']) ?></span>
            </a>
        <?php endif; ?>
    <?php endforeach; ?>
</nav>
