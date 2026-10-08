<?php $statusClass = ['pending' => 'text-bg-warning', 'sent' => 'text-bg-success', 'failed' => 'text-bg-danger']; ?>
<div class="page-header">
    <div>
        <p class="eyebrow">Customer messaging</p>
        <h1>Message Log</h1>
    </div>
    <?php if ($counts['failed'] > 0): ?>
        <div class="page-actions">
            <form method="post" action="<?= h(url('messages/retry')) ?>">
                <?= Auth::csrfField() ?>
                <button class="btn btn-outline-danger" type="submit"><i class="bi bi-arrow-clockwise"></i> Retry <?= (int) $counts['failed'] ?> failed</button>
            </form>
        </div>
    <?php endif; ?>
</div>

<section class="row g-3 mb-4">
    <?php foreach ($channels as $name => $enabled): ?>
        <div class="col-sm-6 col-lg-3">
            <div class="panel h-100">
                <small class="text-muted"><?= h($name) ?></small>
                <h2 class="h5 mb-0 <?= $enabled ? 'text-success' : 'text-muted' ?>"><?= $enabled ? 'Connected' : 'Not connected' ?></h2>
            </div>
        </div>
    <?php endforeach; ?>
    <?php foreach (['pending' => 'Waiting', 'sent' => 'Sent', 'failed' => 'Failed'] as $key => $label): ?>
        <div class="col-4 col-lg-2">
            <a class="panel h-100 d-block text-decoration-none <?= $status === $key ? 'border-primary' : '' ?>" href="<?= h(url('messages') . '&status=' . $key) ?>">
                <small class="text-muted"><?= h($label) ?></small>
                <h2 class="h5 mb-0"><?= (int) $counts[$key] ?></h2>
            </a>
        </div>
    <?php endforeach; ?>
</section>

<?php if (!in_array(true, $channels, true)): ?>
    <div class="alert alert-info">WhatsApp and SMS are not connected yet, so nothing is queued. Add the provider keys to <code>.env</code> to start messaging customers.</div>
<?php endif; ?>

<section class="panel">
    <?php if ($status !== ''): ?>
        <p class="mb-3"><a href="<?= h(url('messages')) ?>"><i class="bi bi-x-circle"></i> Show all</a></p>
    <?php endif; ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Queued</th>
                    <th>Channel</th>
                    <th>Customer</th>
                    <th>Message</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($messages as $message): ?>
                    <tr>
                        <td class="text-nowrap"><?= h(date('Y-m-d H:i', strtotime($message['created_at']))) ?></td>
                        <td><i class="bi <?= $message['channel'] === 'whatsapp' ? 'bi-whatsapp text-success' : 'bi-chat-dots' ?>"></i> <?= $message['channel'] === 'whatsapp' ? 'WhatsApp' : 'SMS' ?></td>
                        <td><?= h($message['user_name'] ?? '-') ?><small class="d-block text-muted code-text">+<?= h($message['recipient']) ?></small></td>
                        <td class="audit-details"><strong><?= h($message['title']) ?></strong><br><?= h(mb_strimwidth($message['body'], 0, 140, '...')) ?></td>
                        <td>
                            <span class="badge <?= h($statusClass[$message['status']] ?? 'text-bg-light') ?>"><?= h(ucfirst($message['status'])) ?></span>
                            <?php if ($message['status'] === 'sent' && $message['sent_at']): ?>
                                <small class="d-block text-muted"><?= h(date('M j H:i', strtotime($message['sent_at']))) ?></small>
                            <?php elseif ($message['last_error']): ?>
                                <small class="d-block text-danger" title="<?= h($message['last_error']) ?>">Attempt <?= (int) $message['attempts'] ?>: <?= h(mb_strimwidth($message['last_error'], 0, 60, '...')) ?></small>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$messages): ?>
                    <tr><td colspan="5" class="text-center text-muted py-4">No messages yet.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
