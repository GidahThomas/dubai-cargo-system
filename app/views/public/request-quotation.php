<section class="public-page-heading">
    <span class="public-kicker">Sales request</span>
    <h1>Request Quotation</h1>
    <p>Share the product model, quantity, and destination details for a sales quotation.</p>
</section>

<section class="panel public-form-panel">
    <form method="post" action="<?= h(url('request-quotation')) ?>" class="needs-validation" novalidate>
        <?= Auth::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Full Name</label>
                <input class="form-control" id="name" name="name" placeholder="e.g. Juma Mwakalinga" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="company">Company Name</label>
                <input class="form-control" id="company" name="company" placeholder="Optional">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="phone">Phone</label>
                <input class="form-control" id="phone" name="phone" placeholder="e.g. 0652 532 646" required>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com">
            </div>

            <div class="col-12">
                <label class="form-label">Requested Products</label>
                <div class="border rounded p-3">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label" for="product">Product</label>
                            <input class="form-control" id="product" name="product[]" list="productOptions" value="<?= h($preselectedProduct['name'] ?? '') ?>" placeholder="Search or type a product name" required>
                            <datalist id="productOptions">
                                <?php foreach ($products as $product): ?>
                                    <option value="<?= h($product['name']) ?>"></option>
                                <?php endforeach; ?>
                            </datalist>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label" for="quantity">Quantity</label>
                            <input type="number" min="1" class="form-control" id="quantity" name="quantity[]" value="1">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label" for="notes">Notes</label>
                            <input class="form-control" id="notes" name="notes[]" placeholder="Optional note for this product">
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <h5 class="mt-2">Delivery Details</h5>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="delivery_full_name">Delivery Full Name</label>
                <input class="form-control" id="delivery_full_name" name="delivery_full_name" placeholder="Leave blank to use your name above">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="delivery_phone">Delivery Phone</label>
                <input class="form-control" id="delivery_phone" name="delivery_phone" placeholder="Leave blank to use your phone above">
            </div>
            <div class="col-12">
                <label class="form-label" for="delivery_address">Delivery Address</label>
                <input class="form-control" id="delivery_address" name="delivery_address" placeholder="Street, ward, and landmark">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="delivery_region">Region</label>
                <input class="form-control" id="delivery_region" name="delivery_region" placeholder="e.g. Dar es Salaam">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="delivery_district">District</label>
                <input class="form-control" id="delivery_district" name="delivery_district" placeholder="e.g. Kinondoni">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="delivery_ward">Ward / Street</label>
                <input class="form-control" id="delivery_ward" name="delivery_ward" placeholder="e.g. Mikocheni">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="delivery_landmark">Nearest Landmark</label>
                <input class="form-control" id="delivery_landmark" name="delivery_landmark" placeholder="e.g. Near Shoppers Plaza">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="delivery_date">Preferred Delivery Date</label>
                <input type="date" class="form-control" id="delivery_date" name="delivery_date">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="delivery_time">Preferred Delivery Time</label>
                <input class="form-control" id="delivery_time" name="delivery_time" placeholder="e.g. Weekday afternoons">
            </div>
            <div class="col-md-6">
                <label class="form-label" for="delivery_method">Delivery Method</label>
                <select class="form-select" id="delivery_method" name="delivery_method">
                    <option value="office_pickup">Office Pickup</option>
                    <option value="home_delivery" selected>Home Delivery</option>
                    <option value="courier">Courier</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label" for="delivery_instructions">Special Delivery Instructions</label>
                <textarea class="form-control" id="delivery_instructions" name="delivery_instructions" rows="3" placeholder="e.g. Call on arrival, gate code, preferred entrance"></textarea>
            </div>
            <div class="col-12">
                <button class="btn btn-primary" type="submit">
                    <i class="bi bi-send"></i> Submit Request
                </button>
            </div>
        </div>
    </form>
</section>
