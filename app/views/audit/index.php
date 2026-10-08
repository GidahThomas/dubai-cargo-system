<?php
$pageUrl = static function (int $page) use ($filters): string {
    $query = array_filter([
        'action' => $filters['action'],
        'user_id' => $filters['user_id'],
        'date_from' => $filters['date_from'],
        'date_to' => $filters['date_to'],
        'search' => $filters['search'],
        'page' => $page > 1 ? $page : null,
    ], static fn ($value): bool => $value !== null && $value !== '');

    return url('audit') . ($query ? '&' . http_build_query($query) : '');
};
$actionClass = static fn (string $action): string => match (true) {
    str_contains($action, 'failed'), str_contains($action, 'reject'), str_contains($action, 'cancel'), str_contains($action, 'delete') => 'text-bg-danger',
    str_contains($action, 'login'), str_contains($action, 'logout') => 'text-bg-light text-dark',
    str_contains($action, 'settings'), str_contains($action, 'role'), str_contains($action, 'user') => 'text-bg-warning',
    default => 'text-bg-success',
};
?>
<div class="page-header">
    <div>
        <p class="eyebrow">Owner oversight</p>
        <h1>Audit Review</h1>
    </div>
</div>

<section class="filter-panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= h(url()) ?>">
        <input type="hidden" name="url" value="audit">
        <div class="col-md-3">
            <label class="form-label" for="action">Action</label>
            <select class="form-select" id="action" name="action">
                <option value="">All actions</option>
                <?php foreach ($actions as $action): ?>
                    <option value="<?= h($action) ?>" <?= $filters['action'] === $action ? 'selected' : '' ?>><?= h(readable_status($action)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="user_id">User</label>
            <select class="form-select" id="user_id" name="user_id">
                <option value="">Everyone</option>
                <?php foreach ($users as $user): ?>
                    <option value="<?= (int) $user['id'] ?>" <?= (int) $filters['user_id'] === (int) $user['id'] ? 'selected' : '' ?>><?= h($user['name']) ?> (<?= h(readable_status($user['role'] ?? '')) ?>)</option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="date_from">From</label>
            <input type="date" class="form-control" id="date_from" name="date_from" value="<?= h((string) $filters['date_from']) ?>">
        </div>
        <div class="col-6 col-md-2">
            <label class="form-label" for="date_to">To</label>
            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= h((string) $filters['date_to']) ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="search">Details / IP</label>
            <input class="form-control" id="search" name="search" value="<?= h($filters['search']) ?>" placeholder="e.g. ORD-">
        </div>
        <div class="col-12 d-flex gap-2">
            <button class="btn btn-dark" type="submit"><i class="bi bi-funnel"></i> Filter</button>
            <a class="btn btn-link" href="<?= h(url('audit')) ?>">Reset</a>
            <span class="ms-auto align-self-center text-muted small"><?= number_format($total) ?> entr<?= $total === 1 ? 'y' : 'ies' ?></span>
        </div>
    </form>
</section>

<section class="panel">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>When</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Record</th>
                    <th>Details</th>
                    <th>IP address</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <tr>
                        <td class="text-nowrap"><?= h(date('Y-m-d H:i', strtotime($log['created_at']))) ?></td>
                        <td>
                            <?php if ($log['user_name']): ?>
                                <?= h($log['user_name']) ?>
                                <small class="d-block text-muted"><?= h(readable_status($log['user_role'] ?? '')) ?></small>
                            <?php else: ?>
                                <span class="text-muted">Visitor / system</span>
                            <?php endif; ?>
                        </td>
                        <td><span class="badge <?= h($actionClass($log['action'])) ?>"><?= h(readable_status($log['action'])) ?></span></td>
                        <td class="text-nowrap"><?= $log['table_name'] ? h($log['table_name']) . ($log['record_id'] ? ' #' . (int) $log['record_id'] : '') : '<span class="text-muted">-</span>' ?></td>
                        <td class="audit-details"><?= h(mb_strimwidth((string) $log['details'], 0, 160, '...')) ?></td>
                        <td class="code-text small"><?= h((string) $log['ip_address']) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$logs): ?>
                    <tr><td colspan="6" class="text-center text-muted py-4">No audit entries match these filters.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <?php if ($pages > 1): ?>
        <nav aria-label="Audit pages" class="d-flex justify-content-between align-items-center mt-3">
            <span class="text-muted small">Page <?= $page ?> of <?= $pages ?></span>
            <div class="btn-group">
                <a class="btn btn-sm btn-outline-secondary <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= h($pageUrl($page - 1)) ?>" <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>><i class="bi bi-chevron-left"></i> Newer</a>
                <a class="btn btn-sm btn-outline-secondary <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= h($pageUrl($page + 1)) ?>" <?= $page >= $pages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Older <i class="bi bi-chevron-right"></i></a>
            </div>
        </nav>
    <?php endif; ?>
</section>
