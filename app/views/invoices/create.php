<div class="page-header">
    <div>
        <p class="eyebrow">Invoice generator</p>
        <h1>Create Invoice</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-secondary" href="<?= h(url('invoices')) ?>">
            <i class="bi bi-arrow-left"></i> Invoice List
        </a>
        <a class="btn btn-outline-primary" href="<?= h(url('invoices/settings')) ?>">
            <i class="bi bi-gear"></i> Settings
        </a>
    </div>
</div>

<form method="post" action="<?= h(url('invoices/store')) ?>" class="needs-validation invoice-builder" data-invoice-builder data-product-url-template="<?= h(url('invoices/product/__ID__')) ?>" novalidate>
    <?= Auth::csrfField() ?>
    <input type="hidden" name="order_id" value="">
    <input type="hidden" name="quotation_id" value="">

    <div class="invoice-workspace">
        <section class="panel">
            <div class="panel-header">
                <h2><i class="bi bi-person-vcard me-2"></i>Customer Information</h2>
            </div>

            <div class="btn-group customer-mode-toggle" role="group" aria-label="Customer source">
                <input type="radio" class="btn-check" name="customer_mode" id="customer_existing" value="existing" checked>
                <label class="btn btn-outline-primary" for="customer_existing">Existing Customer</label>
                <input type="radio" class="btn-check" name="customer_mode" id="customer_new" value="new">
                <label class="btn btn-outline-primary" for="customer_new">New Customer</label>
            </div>

            <div class="mt-3" data-existing-customer>
                <label class="form-label" for="customer_id">Select customer</label>
                <select class="form-select" id="customer_id" name="customer_id" data-customer-select>
                    <option value="">Choose existing customer</option>
                    <?php foreach ($customers as $customer): ?>
                        <option
                            value="<?= (int) $customer['id'] ?>"
                            data-name="<?= h($customer['name']) ?>"
                            data-company="<?= h($customer['company_name'] ?? '') ?>"
                            data-phone="<?= h($customer['phone'] ?? '') ?>"
                            data-email="<?= h($customer['email'] ?? '') ?>"
                            data-address="<?= h($customer['address'] ?? '') ?>"
                            data-tin="<?= h($customer['tin'] ?? '') ?>"
                            data-vrn="<?= h($customer['vrn'] ?? '') ?>"
                        >
                            <?= h($customer['name']) ?><?= $customer['company_name'] ? ' / ' . h($customer['company_name']) : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="row g-3 mt-1" data-new-customer>
                <div class="col-md-6">
                    <label class="form-label" for="customer_name">Customer Name</label>
                    <input class="form-control" id="customer_name" name="customer_name" data-customer-field="name" placeholder="e.g. Juma Mwakalinga">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="company_name">Company Name</label>
                    <input class="form-control" id="company_name" name="company_name" data-customer-field="company" placeholder="Optional">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="phone">Phone Number</label>
                    <input class="form-control" id="phone" name="phone" data-customer-field="phone" placeholder="e.g. 0652 532 646">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="email">Email</label>
                    <input type="email" class="form-control" id="email" name="email" data-customer-field="email" placeholder="customer@example.com">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="address">Address</label>
                    <input class="form-control" id="address" name="address" data-customer-field="address" placeholder="Street, ward, and landmark">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="tin">TIN</label>
                    <input class="form-control" id="tin" name="tin" data-customer-field="tin" placeholder="Taxpayer Identification Number">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="vrn">VRN</label>
                    <input class="form-control" id="vrn" name="vrn" data-customer-field="vrn" placeholder="VAT Registration Number">
                </div>
            </div>
        </section>

        <section class="panel">
            <div class="panel-header">
                <h2><i class="bi bi-receipt-cutoff me-2"></i>Invoice Information</h2>
            </div>

            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="invoice_number">Invoice Number</label>
                    <input class="form-control" id="invoice_number" name="invoice_number" placeholder="Auto generated">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="status">Payment Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="draft">Draft</option>
                        <option value="unpaid" selected>Unpaid</option>
                        <option value="paid">Paid</option>
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="invoice_date">Invoice Date</label>
                    <input type="date" class="form-control" id="invoice_date" name="invoice_date" value="<?= h($defaultInvoiceDate) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="due_date">Due Date</label>
                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?= h($defaultDueDate) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="invoice_discount">Invoice Discount</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="invoice_discount" name="invoice_discount" value="0" data-invoice-discount>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="vat_rate">VAT %</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="vat_rate" name="vat_rate" value="<?= h($settings['vat_rate'] ?? '0') ?>" data-vat-rate>
                </div>
            </div>
        </section>
    </div>

    <section class="panel mt-4">
        <div class="panel-header">
            <h2><i class="bi bi-box-seam me-2"></i>Product Selection</h2>
            <div class="invoice-product-picker">
                <select class="form-select" data-invoice-product-select>
                    <option value="">Search/select inventory product</option>
                    <?php foreach ($products as $product): ?>
                        <option value="<?= (int) $product['id'] ?>">
                            <?= h($product['name']) ?> / <?= h($product['sku'] ?? '') ?> / <?= (int) ($product['quantity'] ?? 0) ?> available
                        </option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-primary" type="button" data-add-invoice-product>
                    <i class="bi bi-plus-lg"></i> Add
                </button>
            </div>
        </div>

        <div class="table-responsive">
            <table class="table align-middle invoice-item-table">
                <thead>
                <tr>
                    <th>Image</th>
                    <th>Item Name / Description / Specifications</th>
                    <th>Available</th>
                    <th>Qty</th>
                    <th>Rate Price (TZS)</th>
                    <th>Discount</th>
                    <th>Amount</th>
                    <th></th>
                </tr>
                </thead>
                <tbody data-invoice-items></tbody>
            </table>
        </div>
    </section>

    <section class="invoice-totals-panel mt-4">
        <div class="invoice-total-card">
            <span>Subtotal</span>
            <strong data-total-subtotal><?= h(currency_money(0, $settings['currency_code'] ?? default_currency_code())) ?></strong>
        </div>
        <div class="invoice-total-card">
            <span>Discount</span>
            <strong data-total-discount><?= h(currency_money(0, $settings['currency_code'] ?? default_currency_code())) ?></strong>
        </div>
        <div class="invoice-total-card">
            <span>VAT</span>
            <strong data-total-vat><?= h(currency_money(0, $settings['currency_code'] ?? default_currency_code())) ?></strong>
        </div>
        <div class="invoice-total-card invoice-total-card-strong">
            <span>Grand Total</span>
            <strong data-total-grand><?= h(currency_money(0, $settings['currency_code'] ?? default_currency_code())) ?></strong>
        </div>
        <div class="invoice-total-card invoice-total-card-strong">
            <span>Balance Due</span>
            <strong data-total-balance><?= h(currency_money(0, $settings['currency_code'] ?? default_currency_code())) ?></strong>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-outline-secondary" type="submit" name="status" value="draft">
            <i class="bi bi-file-earmark"></i> Save Draft
        </button>
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-eye"></i> Preview Invoice
        </button>
    </div>
</form>

<template id="invoiceItemTemplate">
    <tr data-invoice-item-row>
        <td>
            <div class="invoice-item-image">
                <img alt="" data-item-image>
                <span data-item-placeholder><i class="bi bi-image"></i></span>
            </div>
            <input type="hidden" name="items[product_id][]" data-field-product-id>
            <input type="hidden" name="items[product_image][]" data-field-product-image>
        </td>
        <td class="invoice-item-description-cell">
            <input class="form-control form-control-sm mb-2" name="items[product_name][]" data-field-product-name placeholder="Product name" required>
            <textarea class="form-control form-control-sm mb-2" name="items[description][]" rows="2" data-field-description placeholder="Full product description"></textarea>
            <textarea class="form-control form-control-sm" name="items[specifications][]" rows="3" data-field-specifications placeholder="Specifications"></textarea>
        </td>
        <td><span class="badge text-bg-light text-dark" data-field-available>0</span></td>
        <td><input type="number" min="1" class="form-control form-control-sm invoice-number-input" name="items[quantity][]" value="1" data-field-quantity required></td>
        <td><input type="number" step="1" min="0" class="form-control form-control-sm invoice-money-input" name="items[unit_price][]" value="0" data-field-unit-price required></td>
        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm invoice-money-input" name="items[line_discount][]" value="0" data-field-line-discount></td>
        <td>
            <input type="text" class="form-control form-control-sm invoice-money-input" data-field-amount-display readonly>
        </td>
        <td class="text-end">
            <button class="btn btn-sm btn-outline-danger btn-icon" type="button" data-remove-invoice-item title="Remove item">
                <i class="bi bi-trash"></i>
            </button>
        </td>
    </tr>
</template>

<script>
    window.invoiceCurrency = <?= json_encode($settings['currency_code'] ?? default_currency_code(), JSON_THROW_ON_ERROR) ?>;
</script>
