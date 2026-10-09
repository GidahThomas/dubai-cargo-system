<?php
/**
 * Pricing, condition and specification fields shared by the create and edit product forms.
 * Expects $product (empty array when creating).
 */
$product = $product ?? [];
$specRows = product_spec_rows($product['specifications'] ?? '');
while (count($specRows) < 4) {
    $specRows[] = ['label' => '', 'value' => ''];
}
$currency = h(default_currency_code());
?>
<fieldset class="product-form-section">
    <legend><i class="bi bi-tag"></i> Pricing &amp; condition</legend>
    <div class="row g-3">
        <div class="col-md-3">
            <label class="form-label" for="price">Selling price (<?= $currency ?>)</label>
            <input type="number" step="1" min="0" class="form-control" id="price" name="price" value="<?= isset($product['price']) ? h((string) (float) $product['price']) : '' ?>" placeholder="e.g. 1950000" required data-price-input>
            <div class="invalid-feedback">Enter the price customers pay.</div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="compare_at_price">Price before discount <span class="text-muted fw-normal">(optional)</span></label>
            <input type="number" step="1" min="0" class="form-control" id="compare_at_price" name="compare_at_price" value="<?= !empty($product['compare_at_price']) ? h((string) (float) $product['compare_at_price']) : '' ?>" placeholder="e.g. 2200000" data-compare-input>
            <div class="form-text" data-discount-preview><?php $off = discount_percent($product['price'] ?? 0, $product['compare_at_price'] ?? 0); ?><?= $off > 0 ? 'Customers see: Save ' . $off . '%' : 'Shown crossed out when higher than the selling price.' ?></div>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="item_condition">Condition</label>
            <select class="form-select" id="item_condition" name="item_condition">
                <?php foreach (Product::CONDITIONS as $value => $label): ?>
                    <option value="<?= h($value) ?>" <?= ($product['item_condition'] ?? 'new') === $value ? 'selected' : '' ?>><?= h($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label" for="warranty">Warranty</label>
            <input class="form-control" id="warranty" name="warranty" list="warrantyOptions" value="<?= h($product['warranty'] ?? '') ?>" placeholder="e.g. 1 year">
            <datalist id="warrantyOptions"><option value="No warranty"><option value="3 months"><option value="6 months"><option value="1 year"><option value="2 years"><option value="1 year manufacturer warranty"></datalist>
        </div>
    </div>
</fieldset>

<fieldset class="product-form-section">
    <legend><i class="bi bi-list-check"></i> Specifications</legend>
    <p class="text-muted small mb-2">One feature per row. These show as a table on the product page; the first ones (processor, RAM, storage) also appear on product cards.</p>
    <div class="spec-presets" role="group" aria-label="Fill in a template">
        <span class="text-muted small">Start from a template:</span>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-spec-preset="laptop"><i class="bi bi-laptop"></i> Laptop</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-spec-preset="desktop"><i class="bi bi-pc-display"></i> Desktop</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-spec-preset="monitor"><i class="bi bi-display"></i> Monitor</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-spec-preset="printer"><i class="bi bi-printer"></i> Printer</button>
        <button type="button" class="btn btn-sm btn-outline-secondary" data-spec-preset="accessory"><i class="bi bi-mouse2"></i> Accessory</button>
    </div>

    <div class="spec-editor" data-spec-editor>
        <div class="spec-editor-head" aria-hidden="true"><span>Feature</span><span>Detail</span><span></span></div>
        <div data-spec-rows>
            <?php foreach ($specRows as $row): ?>
                <div class="spec-row" data-spec-row>
                    <input class="form-control" name="spec_label[]" value="<?= h($row['label']) ?>" list="specLabels" placeholder="e.g. Processor" aria-label="Feature">
                    <input class="form-control" name="spec_value[]" value="<?= h($row['value']) ?>" placeholder="Details" aria-label="Detail">
                    <button type="button" class="btn btn-outline-danger btn-icon" data-spec-remove title="Remove row" aria-label="Remove row"><i class="bi bi-x-lg"></i></button>
                </div>
            <?php endforeach; ?>
        </div>
        <button type="button" class="btn btn-sm btn-outline-primary mt-2" data-spec-add><i class="bi bi-plus-lg"></i> Add row</button>
    </div>
    <datalist id="specLabels">
        <?php foreach (['Processor', 'RAM', 'Storage', 'Display', 'Screen size', 'Resolution', 'Graphics', 'Operating system', 'Battery', 'Weight', 'Ports', 'Keyboard', 'Webcam', 'Wireless', 'Panel type', 'Refresh rate', 'Print type', 'Print speed', 'Connectivity', 'Colour', 'In the box', 'Model number'] as $label): ?>
            <option value="<?= h($label) ?>">
        <?php endforeach; ?>
    </datalist>
</fieldset>
