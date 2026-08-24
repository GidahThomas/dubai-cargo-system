<?php
$currentUser = Auth::user();
?>
<div class="app-topbar">
    <div>
        <small>Signed in as</small>
        <strong><?= h($currentUser['name'] ?? 'User') ?> / <?= h(readable_status($currentUser['role'] ?? '')) ?></strong>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-secondary btn-sm" href="<?= h(url('dashboard')) ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="btn btn-outline-danger btn-sm" href="<?= h(url('logout')) ?>">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
