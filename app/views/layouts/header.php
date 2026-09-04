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
            <div class="public-nav-links" data-public-nav-links>
                <a href="<?= h(url()) ?>">Home</a>
                <a href="<?= h(url('products')) ?>">Products</a>
                <a href="<?= h(url('track-shipment')) ?>">Track Shipment</a>
                <a href="<?= h(url('request-invoice')) ?>">Request Invoice</a>
                <a href="<?= h(url('contact')) ?>">Contact</a>
                <a class="btn btn-light btn-sm" href="<?= h(url('request-quotation')) ?>">
                    <i class="bi bi-truck"></i> Get Quote
                </a>
                <a class="btn btn-outline-light btn-sm" href="<?= h(url('login')) ?>">Sign In</a>
            </div>
        </nav>
        <div class="public-nav-backdrop" data-public-nav-backdrop></div>
    </header>
    <main class="public-main">
<?php elseif (Auth::check()): ?>
    <button class="btn btn-dark sidebar-toggle d-lg-none" type="button" data-sidebar-toggle aria-label="Open navigation">
        <i class="bi bi-list"></i>
    </button>
    <div class="sidebar-backdrop" data-sidebar-backdrop></div>
    <div class="app-shell">
<?php else: ?>
    <main class="auth-page">
<?php endif; ?>
