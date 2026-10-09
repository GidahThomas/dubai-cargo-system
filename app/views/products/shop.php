<?php
$activeCategory = trim((string) ($filters['category'] ?? ''));
$shopUrl = static fn (string $category = ''): string => url('products') . ($category !== '' ? '&category=' . urlencode($category) : '');
?>
<div class="page-header">
    <div>
        <p class="eyebrow">Shop</p>
        <h1><?= $activeCategory !== '' ? h($activeCategory) : 'Shop Products' ?></h1>
        <p class="text-muted mb-0"><?= count($products) ?> product<?= count($products) === 1 ? '' : 's' ?> &middot; add items to your cart and place one order.</p>
    </div>
    <div class="page-actions">
        <a class="btn btn-primary" href="<?= h(url('cart')) ?>">
            <i class="bi bi-cart3"></i> View cart<?= $cartCount > 0 ? ' (' . (int) $cartCount . ')' : '' ?>
        </a>
    </div>
</div>

<form class="shop-toolbar" method="get" action="<?= h(url()) ?>" role="search">
    <input type="hidden" name="url" value="products">
    <?php if ($activeCategory !== ''): ?>
        <input type="hidden" name="category" value="<?= h($activeCategory) ?>">
    <?php endif; ?>
    <div class="shop-toolbar-search">
        <i class="bi bi-search" aria-hidden="true"></i>
        <input class="form-control" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Search name, brand, SKU" aria-label="Search products">
    </div>
    <select class="form-select" name="brand" aria-label="Brand">
        <option value="">All brands</option>
        <?php foreach ($brands as $brand): ?>
            <option value="<?= h($brand) ?>" <?= strcasecmp($brand, (string) ($filters['brand'] ?? '')) === 0 ? 'selected' : '' ?>><?= h($brand) ?></option>
        <?php endforeach; ?>
    </select>
    <input class="form-control" type="number" min="0" step="1000" name="max_price" value="<?= h((string) ($filters['max_price'] ?? '')) ?>" placeholder="Max <?= h(default_currency_code()) ?>" aria-label="Maximum price">
    <button class="btn btn-dark" type="submit"><i class="bi bi-funnel"></i> Apply</button>
</form>

<nav class="catalog-tabs shop-tabs" aria-label="Product categories">
    <a class="catalog-tab <?= $activeCategory === '' ? 'is-active' : '' ?>" href="<?= h($shopUrl()) ?>"><i class="bi bi-grid"></i> All</a>
    <?php foreach ($categories as $category): ?>
        <a class="catalog-tab <?= strcasecmp($category['name'], $activeCategory) === 0 ? 'is-active' : '' ?>" href="<?= h($shopUrl($category['name'])) ?>">
            <i class="bi <?= h(category_icon($category['name'])) ?>"></i> <?= h($category['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php if (!$products): ?>
    <section class="panel text-center py-5">
        <i class="bi bi-search fs-2 text-muted"></i>
        <p class="mt-2 mb-3">No products match. Try another category or search.</p>
        <a class="btn btn-outline-primary" href="<?= h(url('products')) ?>">Show all products</a>
    </section>
<?php endif; ?>

<div class="shop-grid">
    <?php foreach ($products as $product): ?>
        <?php $inStock = (int) ($product['quantity'] ?? 0); ?>
        <article class="catalog-card shop-card">
            <a class="shop-card-link" href="<?= h(url('products/show/' . (int) $product['id'])) ?>">
                <?php if ($inStock <= 0): ?>
                    <span class="catalog-tag is-order">Out of stock</span>
                <?php elseif ($inStock <= (int) ($product['reorder_level'] ?? 0)): ?>
                    <span class="catalog-tag is-low">Only <?= $inStock ?> left</span>
                <?php endif; ?>
                <span class="catalog-card-media">
                    <?php if (!empty($product['image'])): ?>
                        <img src="<?= h(Thumbnail::url($product['image'])) ?>" alt="<?= h($product['name']) ?>" loading="lazy">
                    <?php else: ?>
                        <i class="bi <?= h(category_icon($product['category'] ?? '')) ?>" aria-hidden="true"></i>
                    <?php endif; ?>
                </span>
                <span class="catalog-card-body">
                    <small><?= h($product['category']) ?> &middot; <?= h($product['brand']) ?></small>
                    <strong class="catalog-card-name" title="<?= h($product['name']) ?>"><?= h($product['name']) ?></strong>
                    <span class="catalog-card-price"><?= h(money($product['price'])) ?></span>
                </span>
            </a>
            <?php if ($inStock > 0): ?>
                <form method="post" action="<?= h(url('cart/add')) ?>" class="shop-card-cart">
                    <?= Auth::csrfField() ?>
                    <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                    <input type="number" class="form-control form-control-sm" name="quantity" value="1" min="1" max="<?= min(99, $inStock) ?>" aria-label="Quantity of <?= h($product['name']) ?>">
                    <button class="btn btn-sm btn-primary" type="submit"><i class="bi bi-cart-plus"></i> Add to cart</button>
                </form>
            <?php else: ?>
                <a class="shop-card-cart btn btn-sm btn-outline-secondary" href="<?= h(url('request-quotation') . '&product_id=' . (int) $product['id']) ?>">Request a quote</a>
            <?php endif; ?>
        </article>
    <?php endforeach; ?>
</div>
