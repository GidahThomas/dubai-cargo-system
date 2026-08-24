<section class="public-page-heading">
    <span class="public-kicker">Commercial invoice</span>
    <h1>Request Invoice</h1>
    <p>Create an invoice preview with product images, descriptions, quantities, totals, and balance due.</p>
</section>

<form method="post" action="<?= h(url('public/storeInvoiceRequest')) ?>" class="needs-validation invoice-builder" data-invoice-builder data-product-url-template="<?= h(url('public/productJson/__ID__')) ?>"<?= !empty($preselectedProductId) ? ' data-preselected-product="' . (int) $preselectedProductId . '"' : '' ?> novalidate>
    <?= Auth::csrfField() ?>
    <input type="hidden" name="customer_mode" value="snapshot">

    <section class="panel public-form-panel" data-new-customer>
        <div class="panel-header">
            <h2><i class="bi bi-person-vcard me-2"></i>Customer Details</h2>
        </div>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="customer_name">Full Name</label>
                <input class="form-control" id="customer_name" name="customer_name" data-customer-field="name" placeholder="e.g. Juma Mwakalinga" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="company_name">Company Name</label>
                <input class="form-control" id="company_name" name="company_name" data-customer-field="company" placeholder="Optional">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" data-customer-field="phone" placeholder="e.g. 0652 532 646" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" data-customer-field="email" placeholder="you@example.com" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="address">Address</label>
                <input class="form-control" id="address" name="address" data-customer-field="address" placeholder="Street, ward, and landmark" required>
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

    <section class="panel public-form-panel mt-4">
        <div class="panel-header">
            <h2><i class="bi bi-box-seam me-2"></i>Products</h2>
            <div class="invoice-product-picker">
                <select class="form-select" data-invoice-product-select>
                    <option value="">Select product</option>
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
                    <th>Product / Description / Specifications</th>
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

    <input type="hidden" data-invoice-discount value="0">
    <input type="hidden" data-vat-rate value="<?= h($settings['vat_rate'] ?? '0') ?>">
    <input type="hidden" id="status" value="unpaid">

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
            <span>Total</span>
            <strong data-total-grand><?= h(currency_money(0, $settings['currency_code'] ?? default_currency_code())) ?></strong>
        </div>
        <div class="invoice-total-card invoice-total-card-strong">
            <span>Balance Due</span>
            <strong data-total-balance><?= h(currency_money(0, $settings['currency_code'] ?? default_currency_code())) ?></strong>
        </div>
    </section>

    <div class="form-actions">
        <button class="btn btn-primary" type="submit">
            <i class="bi bi-receipt-cutoff"></i> Generate Invoice Preview
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
            <textarea class="form-control form-control-sm mb-2" name="items[description][]" rows="2" data-field-description placeholder="Description"></textarea>
            <textarea class="form-control form-control-sm" name="items[specifications][]" rows="3" data-field-specifications placeholder="Specifications"></textarea>
        </td>
        <td><span class="badge text-bg-light text-dark" data-field-available>0</span></td>
        <td><input type="number" min="1" class="form-control form-control-sm invoice-number-input" name="items[quantity][]" value="1" data-field-quantity required></td>
        <td><input type="number" step="1" min="0" class="form-control form-control-sm invoice-money-input" name="items[unit_price][]" value="0" data-field-unit-price required></td>
        <td><input type="number" step="0.01" min="0" class="form-control form-control-sm invoice-money-input" name="items[line_discount][]" value="0" data-field-line-discount></td>
        <td><input type="text" class="form-control form-control-sm invoice-money-input" data-field-amount-display readonly></td>
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
