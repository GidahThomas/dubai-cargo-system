<?php $unread = count(array_filter($notifications, static fn (array $n): bool => !(int) $n['is_read'])); ?>
<div class="page-header">
    <div>
        <p class="eyebrow">Updates</p>
        <h1>Notifications</h1>
    </div>
    <?php if ($unread > 0): ?>
        <div class="page-actions">
            <form method="post" action="<?= h(url('notifications/readAll')) ?>">
                <?= Auth::csrfField() ?>
                <button class="btn btn-outline-primary" type="submit"><i class="bi bi-check2-all"></i> Mark all as read (<?= $unread ?>)</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<section class="panel">
    <?php if (!$notifications): ?>
        <p class="text-muted text-center py-4 mb-0">No notifications yet. Order, payment and shipment updates will appear here.</p>
    <?php else: ?>
        <ul class="notification-list">
            <?php foreach ($notifications as $notification): ?>
                <li class="<?= (int) $notification['is_read'] ? '' : 'is-unread' ?>">
                    <span class="notification-dot text-bg-<?= h(in_array($notification['type'], ['success', 'danger', 'warning', 'info'], true) ? $notification['type'] : 'secondary') ?>"></span>
                    <div>
                        <strong><?= h($notification['title']) ?></strong>
                        <p><?= h($notification['message']) ?></p>
                        <small class="text-muted"><?= h(date('M j, Y H:i', strtotime($notification['created_at']))) ?></small>
                    </div>
                </li>
            <?php endforeach; ?>
        </ul>
    <?php endif; ?>
</section>
