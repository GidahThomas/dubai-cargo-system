<?php
$currentUser = Auth::user();
$topbarLocations = Auth::isStaff() ? (new Location())->all(true) : [];
$activeLocationId = active_location_id();
?>
<div class="app-topbar">
    <div>
        <small>Signed in as</small>
        <strong><?= h($currentUser['name'] ?? 'User') ?> / <?= h(readable_status($currentUser['role'] ?? '')) ?></strong>
    </div>
    <div class="page-actions">
        <?php if ($topbarLocations): ?>
            <form method="post" action="<?= h(url('locations/switch')) ?>" class="topbar-location-form">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="return_to" value="<?= h(trim((string) ($_GET['url'] ?? 'dashboard'), '/')) ?>">
                <select class="form-select form-select-sm" name="location_id" onchange="this.form.submit()">
                    <option value="" <?= $activeLocationId === null ? 'selected' : '' ?>>All Locations</option>
                    <?php foreach ($topbarLocations as $loc): ?>
                        <option value="<?= (int) $loc['id'] ?>" <?= $activeLocationId === (int) $loc['id'] ? 'selected' : '' ?>><?= h($loc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>
        <a class="btn btn-outline-secondary btn-sm" href="<?= h(url('dashboard')) ?>">
            <i class="bi bi-speedometer2"></i> Dashboard
        </a>
        <a class="btn btn-outline-danger btn-sm" href="<?= h(url('logout')) ?>">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</div>
