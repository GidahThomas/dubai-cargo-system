<?php
$pageTitle = $pageTitle ?? company_name();
$layout = $layout ?? (Auth::check() ? 'app' : 'auth');
$success = flash('success');
$error = flash('error');
$info = flash('info');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <?php if (Auth::check()): ?>
        <meta name="csrf-token" content="<?= h(Auth::csrfToken()) ?>">
    <?php endif; ?>
    <title><?= h($pageTitle) ?> | <?= h(company_name()) ?></title>
    <link href="<?= h(asset('vendor/bootstrap/css/bootstrap.min.css')) ?>" rel="stylesheet">
    <link href="<?= h(asset('vendor/bootstrap-icons/bootstrap-icons.min.css')) ?>" rel="stylesheet">
    <link href="<?= h(asset('vendor/fonts/fonts.css')) ?>" rel="stylesheet">
    <link href="<?= h(versioned_asset('css/style.css')) ?>" rel="stylesheet">
    <?php if (Auth::check()): ?>
        <script>try { if (localStorage.getItem('dcf-sidebar') === 'collapsed') document.documentElement.classList.add('sidebar-collapsed'); } catch (e) {}</script>
    <?php endif; ?>
</head>
<body class="<?= h($layout === 'public' ? 'public-body' : (Auth::check() ? 'app-body' : 'auth-body')) ?>">
<?php if ($success || $error || $info): ?>
    <div class="flash-stack">
        <?php if ($success): ?>
            <div class="alert alert-success shadow-sm" role="alert"><?= h($success) ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-danger shadow-sm" role="alert"><?= h($error) ?></div>
        <?php endif; ?>
        <?php if ($info): ?>
            <div class="alert alert-info shadow-sm" role="alert"><?= h($info) ?></div>
        <?php endif; ?>
    </div>
<?php endif; ?>

<?php if ($layout === 'public'): ?>
    <header class="public-header">
        <nav class="public-nav">
            <a class="public-brand" href="<?= h(url()) ?>">
                <img class="brand-logo" src="<?= h(public_url(company_logo_path())) ?>" alt="<?= h(company_name()) ?> logo">
                <span><?= h(company_name()) ?></span>
            </a>
            <button class="public-nav-toggle" type="button" data-public-nav-toggle aria-label="Open navigation" aria-expanded="false">
                <i class="bi bi-list"></i>
            </button>
            <?php
            $publicSection = explode('/', trim((string) ($_GET['url'] ?? ''), '/'))[0] ?: 'home';
            $publicLinks = ['home' => 'Home', 'products' => 'Products', 'track-shipment' => 'Track Shipment', 'request-invoice' => 'Request Invoice'];
            if (gallery_has_posts()) {
                $publicLinks['gallery'] = 'Gallery';
            }
            $publicLinks['contact'] = 'Contact';
            ?>
            <div class="public-nav-links" data-public-nav-links>
                <?php foreach ($publicLinks as $route => $label): ?>
                    <a class="<?= $publicSection === $route ? 'is-active' : '' ?>" href="<?= h($route === 'home' ? url() : url($route)) ?>" <?= $publicSection === $route ? 'aria-current="page"' : '' ?>><?= h($label) ?></a>
                <?php endforeach; ?>
                <a class="btn btn-light btn-sm" href="<?= h(url('request-quotation')) ?>">
                    <i class="bi bi-truck"></i> Get Quote
                </a>
                <?php if (Auth::check()): ?>
                    <a class="btn btn-outline-light btn-sm" href="<?= h(url('dashboard')) ?>"><i class="bi bi-person-circle"></i> My account</a>
                <?php else: ?>
                    <a class="btn btn-outline-light btn-sm" href="<?= h(url('login')) ?>">Sign In</a>
                <?php endif; ?>
            </div>
        </nav>
        <div class="public-nav-backdrop" data-public-nav-backdrop></div>
    </header>
    <main class="public-main">
<?php elseif (Auth::check()): ?>
    <div class="sidebar-backdrop" data-sidebar-backdrop></div>
    <div class="app-shell">
<?php else: ?>
    <main class="auth-page">
<?php endif; ?>
