<?php
$activeCategory = trim((string) ($filters['category'] ?? ''));
$categoryKey = static fn (?string $name): string => strtolower(trim((string) $name));
$productsUrl = static fn (string $category = ''): string => url('products') . ($category !== '' ? '&category=' . urlencode($category) : '');
$pageUrl = static function (int $page) use ($filters): string {
    $query = array_filter([
        'category' => $filters['category'] ?? '',
        'search' => $filters['search'] ?? '',
        'brand' => $filters['brand'] ?? '',
        'min_price' => (string) ($filters['min_price'] ?? ''),
        'max_price' => (string) ($filters['max_price'] ?? ''),
        'page' => $page > 1 ? (string) $page : '',
    ], static fn (string $value): bool => $value !== '');

    return url('products') . ($query ? '&' . http_build_query($query) : '');
};

$stockTag = static function (array $product): ?array {
    $quantity = (int) ($product['quantity'] ?? 0);
    if ($quantity <= 0) {
        return ['label' => 'Made to Order', 'class' => 'is-order'];
    }
    if ($quantity <= (int) ($product['reorder_level'] ?? 0)) {
        return ['label' => 'Low Stock', 'class' => 'is-low'];
    }
    return null;
};
?>
<section class="catalog-heading">
    <div>
        <span class="public-kicker">Product catalogue</span>
        <h1><?= $activeCategory !== '' ? h($activeCategory) : (($filters['search'] ?? '') !== '' ? 'Search results' : 'All Products') ?></h1>
        <p><?= number_format($total) ?> product<?= $total === 1 ? '' : 's' ?>, priced in <?= h(default_currency_code()) ?>.</p>
    </div>
    <form class="catalog-search" method="get" action="<?= h(url()) ?>" role="search">
        <input type="hidden" name="url" value="products">
        <?php if ($activeCategory !== ''): ?>
            <input type="hidden" name="category" value="<?= h($activeCategory) ?>">
        <?php endif; ?>
        <i class="bi bi-search" aria-hidden="true"></i>
        <input class="form-control" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Search name, brand, SKU" aria-label="Search products">
        <button class="btn btn-dark" type="submit">Search</button>
    </form>
</section>

<nav class="catalog-tabs" aria-label="Product categories">
    <a class="catalog-tab <?= $activeCategory === '' ? 'is-active' : '' ?>" href="<?= h($productsUrl()) ?>">
        <i class="bi bi-grid"></i> All
    </a>
    <?php foreach ($categories as $cat): ?>
        <a class="catalog-tab <?= $categoryKey($cat['name']) === $categoryKey($activeCategory) ? 'is-active' : '' ?>" href="<?= h($productsUrl($cat['name'])) ?>">
            <i class="bi <?= h(category_icon($cat['name'])) ?>"></i> <?= h($cat['name']) ?>
        </a>
    <?php endforeach; ?>
</nav>

<?php $hasFilters = ($filters['brand'] ?? '') !== '' || (string) ($filters['min_price'] ?? '') !== '' || (string) ($filters['max_price'] ?? '') !== ''; ?>
<form class="catalog-filters" method="get" action="<?= h(url()) ?>" aria-label="Filter products">
    <input type="hidden" name="url" value="products">
    <?php if ($activeCategory !== ''): ?>
        <input type="hidden" name="category" value="<?= h($activeCategory) ?>">
    <?php endif; ?>
    <?php if (($filters['search'] ?? '') !== ''): ?>
        <input type="hidden" name="search" value="<?= h($filters['search']) ?>">
    <?php endif; ?>
    <label class="visually-hidden" for="filterBrand">Brand</label>
    <select class="form-select" id="filterBrand" name="brand">
        <option value="">All brands</option>
        <?php foreach ($brands as $brand): ?>
            <option value="<?= h($brand) ?>" <?= strcasecmp($brand, (string) ($filters['brand'] ?? '')) === 0 ? 'selected' : '' ?>><?= h($brand) ?></option>
        <?php endforeach; ?>
    </select>
    <div class="catalog-price">
        <label class="visually-hidden" for="filterMin">Minimum price</label>
        <input class="form-control" id="filterMin" type="number" min="0" step="1000" name="min_price" value="<?= h((string) ($filters['min_price'] ?? '')) ?>" placeholder="Min <?= h(default_currency_code()) ?>">
        <span aria-hidden="true">&ndash;</span>
        <label class="visually-hidden" for="filterMax">Maximum price</label>
        <input class="form-control" id="filterMax" type="number" min="0" step="1000" name="max_price" value="<?= h((string) ($filters['max_price'] ?? '')) ?>" placeholder="Max <?= h(default_currency_code()) ?>">
    </div>
    <button class="btn btn-outline-dark" type="submit"><i class="bi bi-funnel"></i> Apply</button>
    <?php if ($hasFilters): ?>
        <a class="btn btn-link" href="<?= h($productsUrl($activeCategory)) ?>">Clear filters</a>
    <?php endif; ?>
</form>

<?php foreach ($sections as $section): ?>
    <section class="catalog-group">
        <header class="catalog-group-header">
            <h2><?= h($section['name']) ?> <small><?= (int) $section['total'] ?></small></h2>
            <?php if ($browsingAll && !$section['mixed']): ?>
                <a href="<?= h($productsUrl($section['name'])) ?>">View All <?= h($section['name']) ?> <i class="bi bi-chevron-right"></i></a>
            <?php elseif ($browsingAll && $section['mixed'] && $section['total'] > count($section['products'])): ?>
                <span class="text-muted small">Showing <?= count($section['products']) ?> of <?= (int) $section['total'] ?> &middot; pick a category above for more</span>
            <?php endif; ?>
        </header>

        <div class="catalog-cards">
            <?php foreach ($section['products'] as $index => $product): ?>
                <?php $tag = $stockTag($product); $isLarge = $section['featured'] && $index === 0; ?>
                <a class="catalog-card <?= $isLarge ? 'is-large' : '' ?>" href="<?= h(url('products/show/' . $product['id'])) ?>">
                    <?php if ($tag): ?>
                        <span class="catalog-tag <?= h($tag['class']) ?>"><?= h($tag['label']) ?></span>
                    <?php endif; ?>
                    <span class="catalog-card-media">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?= h(Thumbnail::url($product['image'])) ?>" alt="<?= h($product['name']) ?>" loading="lazy">
                        <?php else: ?>
                            <i class="bi <?= h(category_icon($product['category'] ?? '')) ?>" aria-hidden="true"></i>
                        <?php endif; ?>
                    </span>
                    <span class="catalog-card-body">
                        <small><?= h($section['mixed'] ? $product['category'] . ' · ' . $product['brand'] : $product['brand']) ?></small>
                        <strong class="catalog-card-name" title="<?= h($product['name']) ?>"><?= h($product['name']) ?></strong>
                        <?php if ($isLarge && !empty($product['description'])): ?>
                            <span class="catalog-card-desc"><?= h($product['description']) ?></span>
                        <?php endif; ?>
                        <span class="catalog-card-price"><?= h(money($product['price'])) ?></span>
                    </span>
                </a>
            <?php endforeach; ?>
        </div>
    </section>
<?php endforeach; ?>

<?php if ($pages > 1): ?>
    <nav class="catalog-pagination" aria-label="Product pages">
        <a class="btn btn-outline-dark <?= $page <= 1 ? 'disabled' : '' ?>" href="<?= h($pageUrl($page - 1)) ?>" <?= $page <= 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>><i class="bi bi-chevron-left"></i> Previous</a>
        <span>Page <?= $page ?> of <?= $pages ?></span>
        <a class="btn btn-outline-dark <?= $page >= $pages ? 'disabled' : '' ?>" href="<?= h($pageUrl($page + 1)) ?>" <?= $page >= $pages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Next <i class="bi bi-chevron-right"></i></a>
    </nav>
<?php endif; ?>

<?php if (!$sections): ?>
    <div class="panel public-empty-state mt-4">
        <strong>No products found.</strong>
        <span>Try another category or search, or <a href="<?= h(url('request-quotation')) ?>">request a quotation</a> for sourcing support.</span>
    </div>
<?php endif; ?>

<section class="catalog-cta">
    <div>
        <strong>Need a formal invoice or bulk pricing?</strong>
        <span>Tell us the products and quantities and we'll prepare it for you.</span>
    </div>
    <a class="btn btn-primary" href="<?= h(url('request-invoice')) ?>"><i class="bi bi-receipt"></i> Request Invoice</a>
</section>
