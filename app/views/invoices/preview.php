<div class="page-header invoice-screen-actions">
    <div>
        <p class="eyebrow">Commercial invoice</p>
        <h1 class="code-text"><?= h($invoice['invoice_number']) ?></h1>
    </div>
    <div class="page-actions">
        <?php if (!empty($publicInvoice)): ?>
            <a class="btn btn-outline-secondary" href="<?= h(url('products')) ?>">
                <i class="bi bi-arrow-left"></i> Products
            </a>
            <a class="btn btn-outline-primary" href="<?= h(url('public/invoicePrint/' . $invoice['invoice_id'])) ?>" target="_blank">
                <i class="bi bi-printer"></i> Print Invoice
            </a>
            <a class="btn btn-outline-secondary" href="<?= h(url('public/invoicePdf/' . $invoice['invoice_id'])) ?>">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </a>
        <?php else: ?>
            <a class="btn btn-outline-secondary" href="<?= h(url('invoices')) ?>">
                <i class="bi bi-arrow-left"></i> Invoice List
            </a>
            <a class="btn btn-outline-primary" href="<?= h(url('invoices/print/' . $invoice['invoice_id'])) ?>" target="_blank">
                <i class="bi bi-printer"></i> Print Invoice
            </a>
            <a class="btn btn-outline-secondary" href="<?= h(url('invoices/pdf/' . $invoice['invoice_id'])) ?>">
                <i class="bi bi-file-earmark-pdf"></i> Download PDF
            </a>
            <form method="post" action="<?= h(url('invoices/email/' . $invoice['invoice_id'])) ?>" class="d-inline">
                <?= Auth::csrfField() ?>
                <button class="btn btn-outline-secondary" type="submit">
                    <i class="bi bi-envelope"></i> Email PDF
                </button>
            </form>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($publicInvoice) && $invoice['status'] !== 'paid' && $invoice['status'] !== 'cancelled'): ?>
    <section class="filter-panel invoice-screen-actions">
        <form method="post" action="<?= h(url('invoices/markPaid/' . $invoice['invoice_id'])) ?>" class="row g-3 align-items-end needs-validation" novalidate>
            <?= Auth::csrfField() ?>
            <div class="col-md-3">
                <label class="form-label" for="amount">Payment Amount</label>
                <input type="number" step="0.01" min="0.01" class="form-control" id="amount" name="amount" value="<?= h($invoice['balance_due']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="method">Method</label>
                <select class="form-select" id="method" name="method">
                    <option value="bank_transfer">Bank Transfer</option>
                    <option value="cash">Cash</option>
                    <option value="mobile_money">Mobile Money</option>
                    <option value="card">Card</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="payment_reference">Reference</label>
                <input class="form-control" id="payment_reference" name="payment_reference" placeholder="Receipt or bank reference">
            </div>
            <div class="col-md-2">
                <button class="btn btn-success w-100" type="submit">
                    <i class="bi bi-check2-circle"></i> Record Payment
                </button>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="invoice-document-wrap">
    <article class="invoice-document">
        <header class="invoice-doc-header">
            <div class="invoice-company">
                <h2><?= h($settings['company_name']) ?></h2>
                <p class="invoice-tagline"><?= h($settings['address']) ?></p>
                <?php if (!empty($settings['logo_path'])): ?>
                    <img class="invoice-company-logo" src="<?= h(public_url($settings['logo_path'])) ?>" alt="<?= h($settings['company_name']) ?>">
                <?php else: ?>
                    <span class="invoice-logo-fallback">DCF</span>
                <?php endif; ?>
                <p><?= h($settings['phone']) ?> / <?= h($settings['email']) ?></p>
                <p>TIN: <?= h($settings['tin'] ?? '') ?> &nbsp; VRN: <?= h($settings['vrn'] ?? '') ?></p>
            </div>
            <div class="invoice-doc-meta">
                <h3>INVOICE</h3>
                <strong class="code-text"><?= h($invoice['invoice_number']) ?></strong>
                <span class="badge <?= h(badge_class($invoice['status'])) ?>"><?= h(readable_status($invoice['status'])) ?></span>
            </div>
        </header>

        <div class="invoice-info-grid">
            <section>
                <h4>Customer Information</h4>
                <strong><?= h($invoice['customer_name']) ?></strong>
                <?php if (!empty($invoice['customer_company'])): ?><p><?= h($invoice['customer_company']) ?></p><?php endif; ?>
                <?php if (!empty($invoice['customer_phone'])): ?><p>Phone: <?= h($invoice['customer_phone']) ?></p><?php endif; ?>
                <?php if (!empty($invoice['customer_email'])): ?><p>Email: <?= h($invoice['customer_email']) ?></p><?php endif; ?>
                <?php if (!empty($invoice['customer_address'])): ?><p><?= h($invoice['customer_address']) ?></p><?php endif; ?>
                <p>TIN: <?= h($invoice['customer_tin'] ?? '') ?> &nbsp; VRN: <?= h($invoice['customer_vrn'] ?? '') ?></p>
            </section>
            <section>
                <h4>Invoice Information</h4>
                <dl>
                    <dt>Invoice Date</dt><dd><?= h($invoice['invoice_date']) ?></dd>
                    <dt>Due Date</dt><dd><?= h($invoice['due_date']) ?></dd>
                    <dt>Prepared By</dt><dd><?= h($invoice['created_by_name'] ?? 'System') ?></dd>
                    <dt>Order Ref</dt><dd class="code-text"><?= h($invoice['order_number'] ?? 'Direct invoice') ?></dd>
                </dl>
            </section>
        </div>

        <div class="invoice-doc-watermark-zone">
            <?php if (!empty($settings['logo_path'])): ?>
                <img class="invoice-doc-watermark" src="<?= h(public_url($settings['logo_path'])) ?>" alt="">
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table invoice-doc-table align-middle">
                    <thead>
                    <tr>
                        <th>Image</th>
                        <th>Item Name / Description / Specifications</th>
                        <th class="text-center">Qty</th>
                        <th class="text-end">Rate Price (TZS)</th>
                        <th class="text-end">Amount</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($items as $item): ?>
                        <tr>
                            <td class="invoice-doc-image-cell">
                                <?php $image = $item['product_image'] ?: ($item['current_product_image'] ?? ''); ?>
                                <?php if ($image): ?>
                                    <img src="<?= h(public_url($image)) ?>" alt="<?= h($item['product_name']) ?>">
                                <?php else: ?>
                                    <span><i class="bi bi-image"></i></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <strong><?= h($item['product_name']) ?></strong>
                                <?php if (!empty($item['description'])): ?>
                                    <p><?= nl2br(h($item['description'])) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($item['specifications'])): ?>
                                    <small><?= nl2br(h($item['specifications'])) ?></small>
                                <?php endif; ?>
                                <?php if ((float) $item['line_discount'] > 0): ?>
                                    <em>Line discount: <?= h(currency_money($item['line_discount'], $settings['currency_code'])) ?></em>
                                <?php endif; ?>
                            </td>
                            <td class="text-center"><?= (int) $item['quantity'] ?></td>
                            <td class="text-end"><?= h(currency_money($item['unit_price'], $settings['currency_code'])) ?></td>
                            <td class="text-end"><strong><?= h(currency_money($item['amount'], $settings['currency_code'])) ?></strong></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <section class="invoice-doc-summary">
                <div class="invoice-thanks">
                    <strong><?= h($settings['footer_note'] ?? 'Thank you for your business.') ?></strong>
                    <p>Contact: <?= h($settings['phone']) ?> / <?= h($settings['email']) ?></p>
                </div>
                <div class="invoice-summary-box">
                    <div><span>Subtotal</span><strong><?= h(currency_money($invoice['subtotal'], $settings['currency_code'])) ?></strong></div>
                    <div><span>Discount</span><strong><?= h(currency_money($invoice['discount'], $settings['currency_code'])) ?></strong></div>
                    <div><span>VAT</span><strong><?= h(currency_money($invoice['vat'], $settings['currency_code'])) ?></strong></div>
                    <div class="is-grand"><span>Grand Total</span><strong><?= h(currency_money($invoice['grand_total'], $settings['currency_code'])) ?></strong></div>
                    <div class="is-balance"><span>Balance Due</span><strong><?= h(currency_money($invoice['balance_due'], $settings['currency_code'])) ?></strong></div>
                </div>
            </section>
        </div>

        <footer class="invoice-doc-footer">
            <div>
                <span>Prepared By</span>
                <strong><?= h($invoice['created_by_name'] ?? '') ?></strong>
            </div>
            <div>
                <span>Authorized Signature</span>
                <strong></strong>
            </div>
            <div class="invoice-stamp">
                <span>Company Stamp Area</span>
            </div>
        </footer>

        <?php if (!empty($settings['terms'])): ?>
            <section class="invoice-terms">
                <h4>Terms & Conditions</h4>
                <p><?= nl2br(h($settings['terms'])) ?></p>
            </section>
        <?php endif; ?>
    </article>
</section>

<?php if ($printMode): ?>
    <script>
        window.addEventListener('load', () => window.print());
    </script>
<?php endif; ?>
