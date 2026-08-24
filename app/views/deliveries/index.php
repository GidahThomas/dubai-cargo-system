<div class="page-header">
    <div>
        <p class="eyebrow">Fulfilment</p>
        <h1>Deliveries</h1>
    </div>
</div>

<section class="filter-panel">
    <form class="row g-3 align-items-end" method="get" action="<?= h(url()) ?>">
        <input type="hidden" name="url" value="deliveries">
        <div class="col-md-4">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= h(readable_status($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-4">
            <label class="form-label" for="search">Search</label>
            <input class="form-control" id="search" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Customer, phone, tracking or invoice">
        </div>
        <div class="col-md-2">
            <button class="btn btn-dark w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </div>
    </form>
</section>

<section class="panel mt-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Customer</th>
                <th>Contact</th>
                <th>Address</th>
                <th>Method</th>
                <th>Preferred date</th>
                <th>Reference</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($deliveries as $delivery): ?>
                <tr>
                    <td><?= h($delivery['customer_name']) ?></td>
                    <td><?= h($delivery['phone'] ?? '') ?></td>
                    <td>
                        <?= h(trim(implode(', ', array_filter([
                            $delivery['address'] ?? '',
                            $delivery['ward'] ?? '',
                            $delivery['district'] ?? '',
                            $delivery['region'] ?? '',
                        ])))) ?: '—' ?>
                    </td>
                    <td><?= h(readable_status($delivery['delivery_method'])) ?></td>
                    <td><?= h($delivery['preferred_date'] ?? 'Not set') ?></td>
                    <td class="code-text"><?= h($delivery['tracking_number'] ?? $delivery['invoice_number'] ?? '') ?></td>
                    <td><span id="deliveryStatus<?= (int) $delivery['id'] ?>" class="badge <?= h(badge_class($delivery['status'])) ?>"><?= h(readable_status($delivery['status'])) ?></span></td>
                    <td class="text-end">
                        <form method="post" action="<?= h(url('deliveries/updateStatus')) ?>" class="d-flex gap-2 justify-content-end">
                            <?= Auth::csrfField() ?>
                            <input type="hidden" name="delivery_id" value="<?= (int) $delivery['id'] ?>">
                            <select class="form-select form-select-sm" name="status">
                                <?php foreach ($statuses as $status): ?>
                                    <option value="<?= h($status) ?>" <?= $delivery['status'] === $status ? 'selected' : '' ?>><?= h(readable_status($status)) ?></option>
                                <?php endforeach; ?>
                            </select>
                            <button class="btn btn-sm btn-outline-primary btn-icon" type="submit" title="Update delivery"><i class="bi bi-box-seam"></i></button>
                        </form>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$deliveries): ?>
                <tr><td colspan="8" class="text-center text-muted py-4">No deliveries found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
