<div class="page-header">
    <div>
        <p class="eyebrow">Sales pipeline</p>
        <h1>Quotations</h1>
    </div>
    <a class="btn btn-outline-primary" href="<?= h(url('request-quotation')) ?>" target="_blank">
        <i class="bi bi-box-arrow-up-right"></i> Public Form
    </a>
</div>

<section class="panel">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Customer</th>
                <th>Delivery</th>
                <th>Status</th>
                <th>Total</th>
                <th>Date</th>
                <th>Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($quotations as $quotation): ?>
                <tr>
                    <td>
                        <strong><?= h($quotation['customer_name']) ?></strong><br>
                        <small class="text-muted"><?= h($quotation['phone']) ?></small>
                    </td>
                    <td><?= h($quotation['delivery_method']) ?> / <?= h($quotation['delivery_address']) ?></td>
                    <td><span class="badge text-bg-info"><?= h(ucwords(str_replace('_', ' ', $quotation['status']))) ?></span></td>
                    <td><?= h(money($quotation['grand_total'])) ?></td>
                    <td><?= h($quotation['created_at']) ?></td>
                    <td><a class="btn btn-sm btn-outline-primary" href="<?= h(url('quotations/show/' . $quotation['id'])) ?>">Review</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$quotations): ?>
                <tr><td colspan="6" class="text-center text-muted py-4">No quotation requests found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
