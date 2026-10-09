<?php
$currentUser = Auth::user();
$topbarLocations = Auth::isStaff() ? (new Location())->all(true) : [];
$activeLocationId = active_location_id();
$unreadCount = (new Notification())->unreadCount((int) Auth::id());
$initial = strtoupper(mb_substr($currentUser['name'] ?? 'U', 0, 1));
?>
<header class="app-topbar">
    <button class="tb-icon-btn tb-menu" type="button" data-sidebar-toggle aria-controls="appSidebar" aria-expanded="false" aria-label="Open menu">
        <i class="bi bi-list"></i>
    </button>

    <div class="tb-title" title="<?= h($pageTitle ?? '') ?>"><?= h($pageTitle ?? '') ?></div>

    <div class="tb-actions">
        <?php if ($topbarLocations): ?>
            <form method="post" action="<?= h(url('locations/switch')) ?>" class="tb-location">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="return_to" value="<?= h(trim((string) ($_GET['url'] ?? 'dashboard'), '/')) ?>">
                <i class="bi bi-geo-alt" aria-hidden="true"></i>
                <select class="form-select form-select-sm" name="location_id" onchange="this.form.submit()" aria-label="Branch">
                    <option value="" <?= $activeLocationId === null ? 'selected' : '' ?>>All branches</option>
                    <?php foreach ($topbarLocations as $loc): ?>
                        <option value="<?= (int) $loc['id'] ?>" <?= $activeLocationId === (int) $loc['id'] ? 'selected' : '' ?>><?= h($loc['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
        <?php endif; ?>

        <a class="tb-icon-btn tb-bell" href="<?= h(url('notifications')) ?>" aria-label="Notifications<?= $unreadCount ? ', ' . $unreadCount . ' unread' : '' ?>" title="Notifications">
            <i class="bi bi-bell"></i>
            <?php if ($unreadCount > 0): ?>
                <span class="tb-dot"><?= $unreadCount > 9 ? '9+' : $unreadCount ?></span>
            <?php endif; ?>
        </a>

        <div class="dropdown">
            <button class="tb-user" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                <span class="tb-avatar"><?= h($initial) ?></span>
                <span class="tb-user-text">
                    <strong><?= h($currentUser['name'] ?? 'User') ?></strong>
                    <small><?= h(readable_status($currentUser['role'] ?? '')) ?></small>
                </span>
                <i class="bi bi-chevron-down tb-caret" aria-hidden="true"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end tb-menu-list">
                <li class="dropdown-header">
                    <strong><?= h($currentUser['name'] ?? '') ?></strong>
                    <small class="d-block text-muted"><?= h($currentUser['email'] ?? '') ?></small>
                </li>
                <li><a class="dropdown-item" href="<?= h(url('dashboard')) ?>"><i class="bi bi-speedometer2"></i> Dashboard</a></li>
                <?php if (Auth::role() === 'customer'): ?>
                    <li><a class="dropdown-item" href="<?= h(url('dashboard')) ?>#profile"><i class="bi bi-person-circle"></i> My profile</a></li>
                <?php endif; ?>
                <li><a class="dropdown-item" href="<?= h(url('users/password')) ?>"><i class="bi bi-shield-lock"></i> Change password</a></li>
                <li><a class="dropdown-item" href="<?= h(url()) ?>"><i class="bi bi-globe2"></i> View website</a></li>
                <li><button class="dropdown-item" type="button" data-install-app hidden><i class="bi bi-phone"></i> Install app</button></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item text-danger" href="<?= h(url('logout')) ?>"><i class="bi bi-box-arrow-right"></i> Sign out</a></li>
            </ul>
        </div>
    </div>
</header>
