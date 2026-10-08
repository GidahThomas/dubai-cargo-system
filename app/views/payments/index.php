<div class="page-header">
    <div>
        <p class="eyebrow">Payment control</p>
        <h1>Payments</h1>
    </div>
</div>

<section class="filter-panel">
    <form class="row g-3 align-items-end" method="get" action="<?= h(url()) ?>">
        <input type="hidden" name="url" value="payments">
        <div class="col-md-3">
            <label class="form-label" for="search"><i class="bi bi-search me-1"></i>Search</label>
            <input class="form-control" id="search" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Order, invoice, customer, reference">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="method">Method</label>
            <select class="form-select" id="method" name="method">
                <option value="">All methods</option>
                <?php foreach (['cash', 'bank_transfer', 'mobile_money', 'card'] as $method): ?>
                    <option value="<?= h($method) ?>" <?= ($filters['method'] ?? '') === $method ? 'selected' : '' ?>><?= h(readable_status($method)) ?></option>
                <?php endforeach; ?>
            </select>
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
                <th>Reference</th>
                <th>Customer</th>
                <th>Amount</th>
                <th>Method</th>
                <th>Reference</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($payments as $payment): ?>
                <tr>
                    <td>
                        <strong class="code-text"><?= h($payment['order_number'] ?: ($payment['invoice_number'] ?? 'Invoice payment')) ?></strong>
                        <?php if (!empty($payment['invoice_number'])): ?>
                            <small class="d-block text-muted">Invoice</small>
                        <?php else: ?>
                            <small class="d-block text-muted">Order</small>
                        <?php endif; ?>
                    </td>
                    <td><?= h($payment['customer_name']) ?></td>
                    <td><?= h(money($payment['amount'])) ?></td>
                    <td><?= h(readable_status($payment['method'])) ?></td>
                    <td><?= $payment['payment_reference'] ? h($payment['payment_reference']) : '<span class="text-muted">Not submitted</span>' ?></td>
                    <td><span id="paymentStatus<?= (int) $payment['id'] ?>" class="badge <?= h(badge_class($payment['status'])) ?>"><?= h(readable_status($payment['status'])) ?></span></td>
                    <td class="text-end">
                        <?php if (Auth::role() === 'customer' && $payment['status'] !== 'confirmed'): ?>
                            <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#payment<?= (int) $payment['id'] ?>" title="Submit payment">
                                <i class="bi bi-credit-card"></i>
                            </button>
                        <?php elseif (Auth::hasRole(['manager', 'admin']) && $payment['status'] === 'pending'): ?>
                            <div class="d-flex justify-content-end gap-2">
                                <form method="post" action="<?= h(url('payments/ajaxStatus')) ?>" data-ajax-form data-status-target="#paymentStatus<?= (int) $payment['id'] ?>">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
                                    <input type="hidden" name="status" value="confirmed">
                                    <input type="hidden" name="notes" value="Confirmed by <?= h(Auth::user()['name']) ?>">
                                    <button class="btn btn-sm btn-success btn-icon" type="submit" title="Confirm payment"><i class="bi bi-check2-circle"></i></button>
                                </form>
                                <form method="post" action="<?= h(url('payments/ajaxStatus')) ?>" data-ajax-form data-status-target="#paymentStatus<?= (int) $payment['id'] ?>" data-confirm="Reject this payment?">
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
                                    <input type="hidden" name="status" value="rejected">
                                    <input type="hidden" name="notes" value="Rejected by <?= h(Auth::user()['name']) ?>">
                                    <button class="btn btn-sm btn-outline-danger btn-icon" type="submit" title="Reject payment"><i class="bi bi-x-circle"></i></button>
                                </form>
                                <span class="ajax-message"></span>
                            </div>
                        <?php else: ?>
                            <span class="text-muted">No action</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (Auth::role() === 'customer' && $payment['status'] !== 'confirmed'): ?>
                    <tr class="collapse" id="payment<?= (int) $payment['id'] ?>">
                        <td colspan="7" class="bg-light">
                            <?php if (AzamPay::isEnabled() && !empty($payment['order_id'])): ?>
                                <?php $lastRequest = AzamPay::latestRequest((int) $payment['id']); ?>
                                <form method="post" action="<?= h(url('payments/mobileMoney')) ?>" class="row g-2 align-items-end mb-3 pb-3 border-bottom needs-validation" novalidate>
                                    <?= Auth::csrfField() ?>
                                    <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
                                    <div class="col-12">
                                        <strong><i class="bi bi-phone"></i> Pay <?= h(money($payment['amount'])) ?> with mobile money</strong>
                                        <small class="text-muted d-block">You'll get a PIN prompt on your phone; the payment confirms automatically.</small>
                                        <?php if ($lastRequest): ?>
                                            <small class="d-block mt-1 <?= $lastRequest['status'] === 'failed' ? 'text-danger' : 'text-muted' ?>">
                                                Last request <?= h(date('M j H:i', strtotime($lastRequest['created_at']))) ?> to +<?= h($lastRequest['msisdn']) ?>:
                                                <?= h(['requested' => 'waiting for your approval', 'success' => 'paid', 'failed' => 'not completed'][$lastRequest['status']] ?? $lastRequest['status']) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Network</label>
                                        <select class="form-select" name="provider" required>
                                            <?php foreach (AzamPay::PROVIDERS as $code => $label): ?>
                                                <option value="<?= h($code) ?>"><?= h($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="col-md-5">
                                        <label class="form-label">Phone number</label>
                                        <input class="form-control" name="phone" type="tel" value="<?= h(Auth::user()['phone'] ?? '') ?>" placeholder="e.g. 0754 123 456" required>
                                    </div>
                                    <div class="col-md-3">
                                        <button class="btn btn-success w-100" type="submit"><i class="bi bi-phone-vibrate me-1"></i>Send prompt</button>
                                    </div>
                                </form>
                                <small class="text-muted d-block mb-2">Or enter a payment you already made:</small>
                            <?php endif; ?>
                            <form method="post" action="<?= h(url('payments/submit')) ?>" class="row g-2 align-items-end needs-validation" novalidate>
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="payment_id" value="<?= (int) $payment['id'] ?>">
                                <div class="col-md-3">
                                    <label class="form-label">Method</label>
                                    <select class="form-select" name="method">
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="card">Card</option>
                                        <option value="cash">Cash</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Payment reference</label>
                                    <input class="form-control" name="payment_reference" value="<?= h($payment['payment_reference'] ?? '') ?>" placeholder="Receipt or bank reference" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Notes</label>
                                    <input class="form-control" name="notes" placeholder="Optional">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary w-100" type="submit">
                                        <i class="bi bi-send me-2"></i>Submit
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$payments): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No payments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
