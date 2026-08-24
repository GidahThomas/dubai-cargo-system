<div class="page-header">
    <div>
        <p class="eyebrow">Invoice management</p>
        <h1>Invoices</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= h(url('invoices/create')) ?>">
            <i class="bi bi-plus-lg"></i> Create Invoice
        </a>
        <a class="btn btn-outline-primary" href="<?= h(url('invoices/settings')) ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
    </div>
</div>

<div class="invoice-status-tabs">
    <a class="<?= ($filters['status'] ?? '') === '' ? 'is-active' : '' ?>" href="<?= h(url('invoices')) ?>">All</a>
    <a class="<?= ($filters['status'] ?? '') === 'draft' ? 'is-active' : '' ?>" href="<?= h(url('invoices/draft')) ?>">Draft</a>
    <a class="<?= ($filters['status'] ?? '') === 'unpaid' ? 'is-active' : '' ?>" href="<?= h(url('invoices/unpaid')) ?>">Unpaid</a>
    <a class="<?= ($filters['status'] ?? '') === 'paid' ? 'is-active' : '' ?>" href="<?= h(url('invoices/paid')) ?>">Paid</a>
    <a class="<?= ($filters['status'] ?? '') === 'cancelled' ? 'is-active' : '' ?>" href="<?= h(url('invoices/cancelled')) ?>">Cancelled</a>
</div>

<section class="filter-panel mt-3">
    <form class="row g-3 align-items-end" method="get" action="<?= h(url()) ?>">
        <input type="hidden" name="url" value="invoices">
        <div class="col-md-4">
            <label class="form-label" for="search"><i class="bi bi-search me-1"></i>Search</label>
            <input class="form-control" id="search" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Invoice number, customer, phone, email">
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
        <table class="table align-middle data-table">
            <thead>
            <tr>
                <th>Invoice</th>
                <th>Customer</th>
                <th>Date</th>
                <th>Total</th>
                <th>Balance Due</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($invoices as $invoice): ?>
                <tr>
                    <td>
                        <strong class="code-text"><?= h($invoice['invoice_number']) ?></strong>
                        <small class="d-block text-muted"><?= $invoice['order_id'] ? 'Order #' . (int) $invoice['order_id'] : 'Direct invoice' ?></small>
                    </td>
                    <td>
                        <?= h($invoice['customer_name']) ?>
                        <?php if (!empty($invoice['customer_company'])): ?>
                            <small class="d-block text-muted"><?= h($invoice['customer_company']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?= h($invoice['invoice_date']) ?>
                        <small class="d-block text-muted">Due <?= h($invoice['due_date']) ?></small>
                    </td>
                    <td><?= h(currency_money($invoice['grand_total'])) ?></td>
                    <td><?= h(currency_money($invoice['balance_due'])) ?></td>
                    <td><span class="badge <?= h(badge_class($invoice['status'])) ?>"><?= h(readable_status($invoice['status'])) ?></span></td>
                    <td class="text-end">
                        <div class="invoice-row-actions">
                            <a class="btn btn-sm btn-outline-primary btn-icon" href="<?= h(url('invoices/preview/' . $invoice['invoice_id'])) ?>" title="Preview invoice">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a class="btn btn-sm btn-outline-secondary btn-icon" href="<?= h(url('invoices/pdf/' . $invoice['invoice_id'])) ?>" title="Download PDF">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </a>
                            <form method="post" action="<?= h(url('invoices/email/' . $invoice['invoice_id'])) ?>" class="d-inline">
                                <?= Auth::csrfField() ?>
                                <button class="btn btn-sm btn-outline-secondary btn-icon" type="submit" title="Email PDF">
                                    <i class="bi bi-envelope"></i>
                                </button>
                            </form>
                            <?php if ($invoice['status'] !== 'paid' && $invoice['status'] !== 'cancelled'): ?>
                                <button class="btn btn-sm btn-outline-success btn-icon" type="button" data-bs-toggle="collapse" data-bs-target="#payInvoice<?= (int) $invoice['invoice_id'] ?>" title="Record payment">
                                    <i class="bi bi-cash-coin"></i>
                                </button>
                                <form method="post" action="<?= h(url('invoices/cancel/' . $invoice['invoice_id'])) ?>" class="d-inline" data-confirm="Cancel this invoice?">
                                    <?= Auth::csrfField() ?>
                                    <button class="btn btn-sm btn-outline-danger btn-icon" type="submit" title="Cancel invoice">
                                        <i class="bi bi-x-circle"></i>
                                    </button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php if ($invoice['status'] !== 'paid' && $invoice['status'] !== 'cancelled'): ?>
                    <tr class="collapse" id="payInvoice<?= (int) $invoice['invoice_id'] ?>">
                        <td colspan="7" class="bg-light">
                            <form method="post" action="<?= h(url('invoices/markPaid/' . $invoice['invoice_id'])) ?>" class="row g-2 align-items-end needs-validation" novalidate>
                                <?= Auth::csrfField() ?>
                                <div class="col-md-3">
                                    <label class="form-label">Amount</label>
                                    <input type="number" step="0.01" min="0.01" class="form-control" name="amount" value="<?= h($invoice['balance_due']) ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Method</label>
                                    <select class="form-select" name="method">
                                        <option value="bank_transfer">Bank Transfer</option>
                                        <option value="cash">Cash</option>
                                        <option value="mobile_money">Mobile Money</option>
                                        <option value="card">Card</option>
                                    </select>
                                </div>
                                <div class="col-md-4">
                                    <label class="form-label">Payment reference</label>
                                    <input class="form-control" name="payment_reference" placeholder="Receipt or bank reference">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-success w-100" type="submit">
                                        <i class="bi bi-check2-circle"></i> Record
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$invoices): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No invoices found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
