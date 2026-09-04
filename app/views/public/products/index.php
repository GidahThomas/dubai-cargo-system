<section class="public-page-heading">
    <span class="public-kicker">Product catalogue</span>
    <h1>Products</h1>
    <p>Browse our full range of available products, priced in <?= h(default_currency_code()) ?>.</p>
</section>

<section class="filter-panel public-filter-panel">
    <form class="row g-3 align-items-end" method="get" action="<?= h(url()) ?>">
        <input type="hidden" name="url" value="products">
        <div class="col-md-4">
            <label class="form-label" for="search"><i class="bi bi-search me-1"></i>Search</label>
            <input class="form-control" id="search" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Name, brand, category, SKU">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="category">Category</label>
            <input class="form-control" id="category" name="category" value="<?= h($filters['category'] ?? '') ?>" placeholder="Any category">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="brand">Brand</label>
            <input class="form-control" id="brand" name="brand" value="<?= h($filters['brand'] ?? '') ?>" placeholder="Any brand">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="max_price">Max price (TZS)</label>
            <input type="number" step="1" min="0" class="form-control" id="max_price" name="max_price" value="<?= h($filters['max_price'] ?? '') ?>" placeholder="No limit">
        </div>
        <div class="col-md-2">
            <button class="btn btn-dark w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </div>
    </form>
</section>

<section class="public-product-grid mt-4">
    <?php foreach ($products as $product): ?>
        <article class="public-product-card">
            <a class="public-product-image" href="<?= h(url('products/show/' . $product['id'])) ?>">
                <?php if (!empty($product['image'])): ?>
                    <img src="<?= h(public_url($product['image'])) ?>" alt="<?= h($product['name']) ?>">
                <?php else: ?>
                    <span><i class="bi bi-laptop"></i></span>
                <?php endif; ?>
            </a>
            <div>
                <small class="code-text"><?= h($product['sku'] ?? '') ?></small>
                <h3><a href="<?= h(url('products/show/' . $product['id'])) ?>"><?= h($product['name']) ?></a></h3>
                <p><?= h($product['description'] ?: $product['brand'] . ' ' . $product['category']) ?></p>
            </div>
            <div class="public-product-meta">
                <strong><?= h(money($product['price'])) ?></strong>
                <span><?= (int) ($product['quantity'] ?? 0) ?> available</span>
            </div>
            <div class="public-card-actions">
                <a class="btn btn-sm btn-outline-primary" href="<?= h(url('products/show/' . $product['id'])) ?>">
                    <i class="bi bi-eye"></i> Details
                </a>
                <a class="btn btn-sm btn-primary" href="<?= h(url('request-invoice')) ?>">
                    <i class="bi bi-receipt"></i> Request Invoice
                </a>
            </div>
        </article>
    <?php endforeach; ?>
    <?php if (!$products): ?>
        <div class="panel public-empty-state">
            <strong>No products found.</strong>
            <span>Adjust your search or contact sales for sourcing support.</span>
        </div>
    <?php endif; ?>
</section>
