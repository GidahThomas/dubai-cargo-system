<div class="page-header">
    <div>
        <p class="eyebrow">Order processing</p>
        <h1>Orders</h1>
    </div>
    <?php if (Auth::role() === 'customer'): ?>
        <a class="btn btn-primary" href="<?= h(url('products')) ?>">
            <i class="bi bi-bag-plus me-2"></i>New Order
        </a>
    <?php endif; ?>
</div>

<section class="filter-panel">
    <form class="row g-3 align-items-end" method="get" action="<?= h(url()) ?>">
        <input type="hidden" name="url" value="orders">
        <div class="col-md-4">
            <label class="form-label" for="search"><i class="bi bi-search me-1"></i>Search</label>
            <input class="form-control" id="search" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Order, customer, email">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="status">Status</label>
            <select class="form-select" id="status" name="status">
                <option value="">All statuses</option>
                <?php foreach ($statuses as $status): ?>
                    <option value="<?= h($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= h(readable_status($status)) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_from">From</label>
            <input type="date" class="form-control" id="date_from" name="date_from" value="<?= h($filters['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="date_to">To</label>
            <input type="date" class="form-control" id="date_to" name="date_to" value="<?= h($filters['date_to'] ?? '') ?>">
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
                <th>Order</th>
                <th>Customer</th>
                <th>Total</th>
                <th>Order Status</th>
                <th>Payment</th>
                <th>Shipment</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($orders as $order): ?>
                <tr>
                    <td>
                        <strong class="code-text"><?= h($order['order_number']) ?></strong>
                        <small class="d-block text-muted"><?= h($order['created_at']) ?></small>
                    </td>
                    <td><?= h($order['customer_name']) ?></td>
                    <td><?= h(money($order['total_amount'])) ?></td>
                    <td><span id="orderStatus<?= (int) $order['id'] ?>" class="badge <?= h(badge_class($order['status'])) ?>"><?= h(readable_status($order['status'])) ?></span></td>
                    <td><span class="badge <?= h(badge_class($order['payment_status'])) ?>"><?= h(readable_status($order['payment_status'])) ?></span></td>
                    <td><?= $order['tracking_number'] ? '<span class="code-text">' . h($order['tracking_number']) . '</span>' : '<span class="text-muted">Not created</span>' ?></td>
                    <td class="text-end">
                        <?php if (Auth::hasRole(['manager', 'admin'])): ?>
                            <div class="order-action-stack">
                                <a class="btn btn-sm btn-outline-success btn-icon" href="<?= h(url('invoices/fromOrder/' . $order['id'])) ?>" title="Convert order to invoice">
                                    <i class="bi bi-receipt-cutoff"></i>
                                </a>
                                <form method="post" action="<?= h(url('orders/ajaxUpdateStatus')) ?>" class="ajax-status-form" data-ajax-form data-status-target="#orderStatus<?= (int) $order['id'] ?>">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                    <select class="form-select form-select-sm" name="status">
                                        <?php foreach ($statuses as $status): ?>
                                            <option value="<?= h($status) ?>" <?= $order['status'] === $status ? 'selected' : '' ?>><?= h(readable_status($status)) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <button class="btn btn-sm btn-outline-primary btn-icon" type="submit" title="Update order status"><i class="bi bi-arrow-repeat"></i></button>
                                    <span class="ajax-message"></span>
                                </form>
                            </div>
                        <?php elseif ($order['status'] === 'pending'): ?>
                            <form method="post" action="<?= h(url('orders/cancel')) ?>" data-confirm="Cancel this order?">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="order_id" value="<?= (int) $order['id'] ?>">
                                <button class="btn btn-sm btn-outline-danger" type="submit">
                                    <i class="bi bi-x-circle me-1"></i>Cancel
                                </button>
                            </form>
                        <?php else: ?>
                            <span class="text-muted">No action</span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No orders found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
