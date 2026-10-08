<?php $total = array_sum(array_column($lines, 'line_total')); ?>
<div class="page-header">
    <div>
        <p class="eyebrow">Checkout</p>
        <h1>My Cart</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-secondary" href="<?= h(url('products')) ?>">
            <i class="bi bi-arrow-left"></i> Continue shopping
        </a>
    </div>
</div>

<?php if (!$lines): ?>
    <section class="panel text-center py-5">
        <i class="bi bi-cart3 fs-1 text-muted"></i>
        <h2 class="h5 mt-3">Your cart is empty</h2>
        <p class="text-muted">Add products from the catalogue, then come back here to place one order for all of them.</p>
        <a class="btn btn-primary" href="<?= h(url('products')) ?>"><i class="bi bi-laptop"></i> Browse products</a>
    </section>
<?php else: ?>
    <div class="row g-4">
        <div class="col-lg-8">
            <section class="panel">
                <form method="post" action="<?= h(url('cart/update')) ?>" id="cartForm">
                    <?= Auth::csrfField() ?>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Price</th>
                                    <th style="width: 110px">Quantity</th>
                                    <th class="text-end">Total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($lines as $line): ?>
                                    <?php $product = $line['product']; ?>
                                    <tr>
                                        <td>
                                            <div class="d-flex align-items-center gap-3">
                                                <?php if (!empty($product['image'])): ?>
                                                    <img class="cart-thumb" src="<?= h(public_url($product['image'])) ?>" alt="">
                                                <?php else: ?>
                                                    <span class="cart-thumb"><i class="bi <?= h(category_icon($product['category'] ?? '')) ?>"></i></span>
                                                <?php endif; ?>
                                                <div>
                                                    <a class="fw-semibold" href="<?= h(url('products/show/' . (int) $product['id'])) ?>"><?= h($product['name']) ?></a>
                                                    <small class="d-block text-muted"><?= h($product['brand']) ?> &middot; <?= (int) $product['quantity'] ?> in stock</small>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="text-nowrap"><?= h(money($product['price'])) ?></td>
                                        <td>
                                            <input type="number" class="form-control form-control-sm" name="quantities[<?= (int) $product['id'] ?>]" value="<?= (int) $line['quantity'] ?>" min="0" max="99" aria-label="Quantity of <?= h($product['name']) ?>">
                                        </td>
                                        <td class="text-end text-nowrap fw-semibold"><?= h(money($line['line_total'])) ?></td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-danger" type="submit" form="removeItem<?= (int) $product['id'] ?>" title="Remove">
                                                <i class="bi bi-x-lg"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <button class="btn btn-outline-primary btn-sm" type="submit"><i class="bi bi-arrow-repeat"></i> Update quantities</button>
                    <small class="text-muted ms-2">Set a quantity to 0 to remove it.</small>
                </form>
                <?php foreach ($lines as $line): ?>
                    <form method="post" action="<?= h(url('cart/remove/' . (int) $line['product']['id'])) ?>" id="removeItem<?= (int) $line['product']['id'] ?>" class="d-none">
                        <?= Auth::csrfField() ?>
                    </form>
                <?php endforeach; ?>
            </section>
        </div>

        <div class="col-lg-4">
            <section class="panel">
                <h2 class="h5">Order summary</h2>
                <dl class="cart-summary">
                    <dt>Items</dt><dd><?= array_sum(array_column($lines, 'quantity')) ?></dd>
                    <dt>Total</dt><dd class="fs-5 fw-bold"><?= h(money($total)) ?></dd>
                </dl>
                <form method="post" action="<?= h(url('cart/checkout')) ?>" class="needs-validation" novalidate>
                    <?= Auth::csrfField() ?>
                    <div class="mb-3">
                        <label class="form-label" for="shipping_address">Delivery address</label>
                        <input class="form-control" id="shipping_address" name="shipping_address" value="<?= h(Auth::user()['address'] ?? '') ?>" placeholder="Street, ward, and landmark" required>
                        <div class="invalid-feedback">Enter where we should deliver.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="notes">Notes</label>
                        <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Optional, e.g. preferred delivery time"></textarea>
                    </div>
                    <button class="btn btn-primary w-100" type="submit"><i class="bi bi-bag-check"></i> Place order</button>
                    <small class="d-block text-muted mt-2">You'll submit payment on the next page. Stock is reserved when the order is placed.</small>
                </form>
            </section>
        </div>
    </div>
<?php endif; ?>
