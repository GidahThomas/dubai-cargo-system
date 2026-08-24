<div class="page-header">
    <div>
        <p class="eyebrow">Quotation review</p>
        <h1>Review Quotation</h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= h(url('quotations')) ?>">
        <i class="bi bi-arrow-left me-2"></i>Back
    </a>
</div>

<section class="panel">
    <form method="post" action="<?= h(url('quotations/update/' . $quotation['id'])) ?>" class="needs-validation" novalidate>
        <?= Auth::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label">Customer Name</label>
                <input class="form-control" name="customer_name" value="<?= h($quotation['customer_name']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Company</label>
                <input class="form-control" name="company_name" value="<?= h($quotation['company_name']) ?>" placeholder="Optional">
            </div>
            <div class="col-md-6">
                <label class="form-label">Phone</label>
                <input class="form-control" name="phone" value="<?= h($quotation['phone']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Email</label>
                <input class="form-control" name="email" value="<?= h($quotation['email']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Delivery Full Name</label>
                <input class="form-control" name="delivery_full_name" value="<?= h($quotation['delivery_full_name']) ?>">
            </div>
            <div class="col-md-6">
                <label class="form-label">Delivery Phone</label>
                <input class="form-control" name="delivery_phone" value="<?= h($quotation['delivery_phone']) ?>">
            </div>
            <div class="col-12">
                <label class="form-label">Delivery Address</label>
                <input class="form-control" name="delivery_address" value="<?= h($quotation['delivery_address']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Region</label>
                <input class="form-control" name="delivery_region" value="<?= h($quotation['delivery_region']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">District</label>
                <input class="form-control" name="delivery_district" value="<?= h($quotation['delivery_district']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Ward / Street</label>
                <input class="form-control" name="delivery_ward" value="<?= h($quotation['delivery_ward']) ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label">Landmark</label>
                <input class="form-control" name="delivery_landmark" value="<?= h($quotation['delivery_landmark']) ?>" placeholder="e.g. Near Shoppers Plaza">
            </div>
            <div class="col-md-4">
                <label class="form-label">Delivery Date</label>
                <input class="form-control" name="delivery_date" value="<?= h($quotation['delivery_date']) ?>" placeholder="YYYY-MM-DD">
            </div>
            <div class="col-md-4">
                <label class="form-label">Delivery Time</label>
                <input class="form-control" name="delivery_time" value="<?= h($quotation['delivery_time']) ?>" placeholder="e.g. Weekday afternoons">
            </div>
            <div class="col-md-6">
                <label class="form-label">Delivery Method</label>
                <select class="form-select" name="delivery_method">
                    <option value="office_pickup" <?= $quotation['delivery_method'] === 'office_pickup' ? 'selected' : '' ?>>Office Pickup</option>
                    <option value="home_delivery" <?= $quotation['delivery_method'] === 'home_delivery' ? 'selected' : '' ?>>Home Delivery</option>
                    <option value="courier" <?= $quotation['delivery_method'] === 'courier' ? 'selected' : '' ?>>Courier</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label">Status</label>
                <select class="form-select" name="status">
                    <?php foreach (['pending','reviewed','approved','rejected','converted_to_invoice'] as $status): ?>
                        <option value="<?= h($status) ?>" <?= $quotation['status'] === $status ? 'selected' : '' ?>><?= h(ucwords(str_replace('_', ' ', $status))) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-12">
                <label class="form-label">Instructions</label>
                <textarea class="form-control" name="delivery_instructions" rows="3" placeholder="e.g. Call on arrival, gate code, preferred entrance"><?= h($quotation['delivery_instructions']) ?></textarea>
            </div>
        </div>

        <div class="mt-4">
            <h5>Selected Products</h5>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Product</th>
                        <th>Qty</th>
                        <th>Unit Price</th>
                        <th>Discount</th>
                        <th>Line Total</th>
                        <th>Notes</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($quotation['items'] as $index => $item): ?>
                        <tr>
                            <td>
                                <input type="hidden" name="item_product_id[]" value="<?= h($item['product_id'] ?? '') ?>">
                                <input type="hidden" name="item_product_image[]" value="<?= h($item['product_image'] ?? '') ?>">
                                <input class="form-control" name="item_product_name[]" value="<?= h($item['product_name']) ?>">
                            </td>
                            <td><input class="form-control" type="number" min="1" name="item_quantity[]" value="<?= (int) $item['quantity'] ?>"></td>
                            <td><input class="form-control" type="number" step="0.01" name="item_unit_price[]" value="<?= h($item['unit_price']) ?>"></td>
                            <td><input class="form-control" type="number" step="0.01" name="item_discount[]" value="<?= h($item['discount']) ?>"></td>
                            <td><input class="form-control" type="number" step="0.01" name="item_line_total[]" value="<?= h($item['line_total']) ?>"></td>
                            <td><input class="form-control" name="item_notes[]" value="<?= h($item['notes']) ?>" placeholder="Optional"></td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="row g-3 mt-1">
            <div class="col-md-3"><label class="form-label">Subtotal</label><input class="form-control" name="subtotal" value="<?= h($quotation['subtotal']) ?>"></div>
            <div class="col-md-3"><label class="form-label">Discount</label><input class="form-control" name="discount" value="<?= h($quotation['discount']) ?>"></div>
            <div class="col-md-3"><label class="form-label">Transport Cost</label><input class="form-control" name="transport_cost" value="<?= h($quotation['transport_cost']) ?>"></div>
            <div class="col-md-3"><label class="form-label">Installation Cost</label><input class="form-control" name="installation_cost" value="<?= h($quotation['installation_cost']) ?>"></div>
            <div class="col-md-3"><label class="form-label">Tax</label><input class="form-control" name="tax" value="<?= h($quotation['tax']) ?>"></div>
            <div class="col-md-3"><label class="form-label">Grand Total</label><input class="form-control" name="grand_total" value="<?= h($quotation['grand_total']) ?>"></div>
            <div class="col-12"><label class="form-label">Admin Notes</label><textarea class="form-control" name="admin_notes" rows="3" placeholder="Internal notes for this quotation"><?= h($quotation['admin_notes']) ?></textarea></div>
        </div>

        <div class="form-actions mt-4">
            <button class="btn btn-primary" type="submit">Save Changes</button>
        </div>
    </form>
</section>
