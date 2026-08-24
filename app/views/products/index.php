<div class="page-header">
    <div>
        <p class="eyebrow">Computer inventory</p>
        <h1>Products & Uploads</h1>
    </div>
    <?php if (Auth::hasRole(['manager', 'admin'])): ?>
        <a class="btn btn-primary" href="<?= h(url('products/create')) ?>">
            <i class="bi bi-cloud-upload me-2"></i>Upload Product
        </a>
    <?php endif; ?>
</div>

<section class="filter-panel">
    <form class="row g-3 align-items-end" method="get" action="<?= h(url()) ?>" data-live-product-search="<?= h(url('products/ajaxSearch')) ?>">
        <input type="hidden" name="url" value="products">
        <div class="col-md-3">
            <label class="form-label" for="filter_search"><i class="bi bi-search me-1"></i>Search</label>
            <input class="form-control" id="filter_search" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Name, SKU, category, brand">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="filter_category">Category</label>
            <input class="form-control" id="filter_category" name="category" value="<?= h($filters['category'] ?? '') ?>" placeholder="Any category">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="filter_brand">Brand</label>
            <input class="form-control" id="filter_brand" name="brand" value="<?= h($filters['brand'] ?? '') ?>" placeholder="Any brand">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="min_price">Min price (TZS)</label>
            <input type="number" step="1" class="form-control" id="min_price" name="min_price" value="<?= h($filters['min_price'] ?? '') ?>" placeholder="0">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="max_price">Max price (TZS)</label>
            <input type="number" step="1" class="form-control" id="max_price" name="max_price" value="<?= h($filters['max_price'] ?? '') ?>" placeholder="No limit">
        </div>
        <?php if (Auth::hasRole(['manager', 'admin'])): ?>
            <div class="col-md-2">
                <label class="form-label" for="filter_status">Status</label>
                <select class="form-select" id="filter_status" name="status">
                    <option value="">All</option>
                    <option value="active" <?= ($filters['status'] ?? '') === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
        <?php endif; ?>
        <div class="col-md-1">
            <button class="btn btn-dark w-100" type="submit" title="Filter">
                <i class="bi bi-funnel"></i>
            </button>
        </div>
    </form>
</section>

<section class="panel mt-4">
    <div class="table-responsive">
        <table class="table align-middle product-table">
            <thead>
            <tr>
                <th>Product</th>
                <th>Category</th>
                <th>Brand</th>
                <th>Price (TZS)</th>
                <th>Stock</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody id="productsTableBody">
            <?php foreach ($products as $product): ?>
                <tr>
                    <td>
                        <div class="product-cell">
                            <?php if (!empty($product['image'])): ?>
                                <img src="<?= h(public_url($product['image'])) ?>" alt="<?= h($product['name']) ?>">
                            <?php else: ?>
                                <span class="product-placeholder"><i class="bi bi-laptop"></i></span>
                            <?php endif; ?>
                            <div>
                                <strong><?= h($product['name']) ?></strong>
                                <small class="code-text"><?= h($product['sku'] ?? '') ?></small>
                            </div>
                        </div>
                    </td>
                    <td><?= h($product['category']) ?></td>
                    <td><?= h($product['brand']) ?></td>
                    <td><?= h(money($product['price'])) ?></td>
                    <td>
                        <span class="badge <?= ((int) $product['quantity'] <= (int) $product['reorder_level']) ? 'text-bg-warning' : 'text-bg-light text-dark' ?>">
                            <?= (int) $product['quantity'] ?>
                        </span>
                    </td>
                    <td><span class="badge <?= h(badge_class($product['status'])) ?>"><?= h(readable_status($product['status'])) ?></span></td>
                    <td class="text-end">
                        <?php if (Auth::role() === 'customer'): ?>
                            <?php if ((int) $product['quantity'] > 0): ?>
                                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#orderProduct<?= (int) $product['id'] ?>" title="Order product">
                                    <i class="bi bi-bag-plus"></i>
                                </button>
                            <?php else: ?>
                                <span class="text-muted">Out of stock</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <a class="btn btn-sm btn-outline-primary" href="<?= h(url('products/edit/' . $product['id'])) ?>" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="post" action="<?= h(url('products/delete/' . $product['id'])) ?>" class="d-inline" data-confirm="Deactivate this product?">
                                <?= Auth::csrfField() ?>
                                <button class="btn btn-sm btn-outline-danger" type="submit" title="Deactivate">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (Auth::role() === 'customer' && (int) $product['quantity'] > 0): ?>
                    <tr class="collapse" id="orderProduct<?= (int) $product['id'] ?>">
                        <td colspan="7" class="bg-light">
                            <form method="post" action="<?= h(url('orders/store')) ?>" class="row g-2 align-items-end needs-validation" novalidate>
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                                <div class="col-md-2">
                                    <label class="form-label">Quantity</label>
                                    <input type="number" min="1" max="<?= (int) $product['quantity'] ?>" class="form-control" name="quantity" value="1" required>
                                </div>
                                <div class="col-md-5">
                                    <label class="form-label">Shipping address</label>
                                    <input class="form-control" name="shipping_address" value="<?= h(Auth::user()['address'] ?? '') ?>" placeholder="Street, ward, and landmark" required>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label">Notes</label>
                                    <input class="form-control" name="notes" placeholder="Optional">
                                </div>
                                <div class="col-md-2">
                                    <button class="btn btn-primary w-100" type="submit">
                                        <i class="bi bi-check2-circle me-2"></i>Order
                                    </button>
                                </div>
                            </form>
                        </td>
                    </tr>
                <?php endif; ?>
            <?php endforeach; ?>
            <?php if (!$products): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No products found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
