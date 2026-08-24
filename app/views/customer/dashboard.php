<?php

if (!defined('ROOT_PATH')) {
    header('Location: ../../../public/index.php?url=dashboard');
    exit;
}

?>
<div class="page-header">
    <div>
        <p class="eyebrow">Customer workspace</p>
        <h1>Welcome, <?= h(Auth::user()['name']) ?></h1>
    </div>
    <a class="btn btn-primary" href="<?= h(url('products')) ?>">
        <i class="bi bi-bag-plus me-2"></i>Place Order
    </a>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-receipt"></i></span>
        <div>
            <small>Total Orders</small>
            <strong><?= count($orders) ?></strong>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-truck"></i></span>
        <div>
            <small>Active Shipments</small>
            <strong><?= count(array_filter($orders, fn ($order) => !empty($order['tracking_number']) && $order['shipment_status'] !== 'delivered')) ?></strong>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-bell"></i></span>
        <div>
            <small>Unread Alerts</small>
            <strong><?= (int) $unreadCount ?></strong>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-7">
        <section class="panel">
            <div class="panel-header">
                <h2>Track Shipment</h2>
            </div>
            <form class="row g-2" method="get" action="<?= h(url()) ?>">
                <input type="hidden" name="url" value="dashboard">
                <div class="col-md-8">
                    <input class="form-control" name="tracking_number" placeholder="Enter tracking number" value="<?= h($_GET['tracking_number'] ?? '') ?>">
                </div>
                <div class="col-md-4">
                    <button class="btn btn-dark w-100" type="submit">
                        <i class="bi bi-search me-2"></i>Track
                    </button>
                </div>
            </form>

            <?php if (isset($_GET['tracking_number'])): ?>
                <?php if ($trackingResult): ?>
                    <div class="tracking-result mt-3">
                        <div>
                            <small>Tracking number</small>
                            <strong><?= h($trackingResult['tracking_number']) ?></strong>
                        </div>
                        <span class="badge <?= h(badge_class($trackingResult['status'])) ?>">
                            <?= h(readable_status($trackingResult['status'])) ?>
                        </span>
                        <p class="mb-0">Order <?= h($trackingResult['order_number']) ?> from <?= h($trackingResult['origin']) ?> to <?= h($trackingResult['destination']) ?>.</p>
                    </div>
                <?php else: ?>
                    <div class="alert alert-warning mt-3 mb-0">No shipment was found for that tracking number.</div>
                <?php endif; ?>
            <?php endif; ?>
        </section>

        <section class="panel mt-4">
            <div class="panel-header">
                <h2>Order History</h2>
                <a href="<?= h(url('orders')) ?>" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Order</th>
                        <th>Total</th>
                        <th>Order</th>
                        <th>Payment</th>
                        <th>Tracking</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach (array_slice($orders, 0, 8) as $order): ?>
                        <tr>
                            <td><?= h($order['order_number']) ?></td>
                            <td><?= h(money($order['total_amount'])) ?></td>
                            <td><span class="badge <?= h(badge_class($order['status'])) ?>"><?= h(readable_status($order['status'])) ?></span></td>
                            <td><span class="badge <?= h(badge_class($order['payment_status'])) ?>"><?= h(readable_status($order['payment_status'])) ?></span></td>
                            <td><?= $order['tracking_number'] ? h($order['tracking_number']) : '<span class="text-muted">Pending</span>' ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$orders): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No orders yet.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-lg-5">
        <section class="panel">
            <div class="panel-header">
                <h2><i class="bi bi-bell me-2"></i>Notifications</h2>
            </div>
            <div class="notification-list">
                <?php foreach ($notifications as $notification): ?>
                    <div class="notification-item <?= (int) $notification['is_read'] === 1 ? 'is-read' : '' ?>">
                        <span class="dot dot-<?= h($notification['type']) ?>"></span>
                        <div>
                            <strong><?= h($notification['title']) ?></strong>
                            <p><?= h($notification['message']) ?></p>
                            <small><?= h($notification['created_at']) ?></small>
                        </div>
                        <?php if ((int) $notification['is_read'] === 0): ?>
                            <button class="btn btn-sm btn-outline-secondary ms-auto" type="button" data-url="<?= h(url('notifications/markRead')) ?>" data-notification-read="<?= (int) $notification['id'] ?>" title="Mark as read">
                                <i class="bi bi-check2"></i>
                            </button>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
                <?php if (!$notifications): ?>
                    <p class="text-muted mb-0">No notifications yet.</p>
                <?php endif; ?>
            </div>
        </section>

        <section class="panel mt-4">
            <div class="panel-header">
                <h2>Profile</h2>
            </div>
            <form method="post" action="<?= h(url('users/updateProfile')) ?>" class="needs-validation" novalidate>
                <?= Auth::csrfField() ?>
                <div class="mb-3">
                    <label class="form-label" for="profile_name">Full name</label>
                    <input class="form-control" id="profile_name" name="name" value="<?= h($profile['name'] ?? Auth::user()['name']) ?>" required>
                </div>
                <div class="mb-3">
                    <label class="form-label" for="profile_phone">Phone</label>
                    <input class="form-control" id="profile_phone" name="phone" value="<?= h($profile['phone'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="profile_city">City</label>
                    <input class="form-control" id="profile_city" name="city" value="<?= h($profile['city'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="profile_country">Country</label>
                    <input class="form-control" id="profile_country" name="country" value="<?= h($profile['country'] ?? '') ?>">
                </div>
                <div class="mb-3">
                    <label class="form-label" for="profile_address">Address</label>
                    <textarea class="form-control" id="profile_address" name="address" rows="2" placeholder="Street, ward, and landmark"><?= h($profile['address'] ?? '') ?></textarea>
                </div>
                <button class="btn btn-outline-primary w-100" type="submit">
                    <i class="bi bi-save me-2"></i>Update Profile
                </button>
            </form>
        </section>
    </div>
</div>
