<?php
$showcaseProduct = null;
foreach ($products as $p) {
    if (!empty($p['image'])) {
        $showcaseProduct = $p;
        break;
    }
}
$heroCategories = array_slice($categories, 0, 4);
?>
<section class="home-hero">
    <div class="home-hero-inner">
        <div class="home-hero-content">
            <span class="home-badge"><i class="bi bi-stars"></i> Genuine Products, Trusted Sourcing</span>
            <h1>Quality <?= h(strtolower($heroCategories[0]['name'] ?? 'products')) ?> and more, with <span class="text-brand">trusted distribution</span></h1>
            <p class="lead"><?= h(company_name()) ?> supplies genuine products with fast delivery, honest pricing, and full invoice and shipment tracking support.</p>
            <div class="home-hero-actions">
                <a class="btn btn-brand btn-lg" href="<?= h(url('products')) ?>">
                    <i class="bi bi-bag"></i> Shop Products
                </a>
                <a class="btn btn-outline-brand btn-lg" href="<?= h(url('request-quotation')) ?>">
                    <i class="bi bi-telephone"></i> Request Quote
                </a>
            </div>
            <?php if ($heroCategories): ?>
                <div class="home-category-pills">
                    <?php foreach ($heroCategories as $cat): ?>
                        <a class="category-pill" href="<?= h(url('products')) ?>&category=<?= urlencode($cat['name']) ?>">
                            <i class="bi <?= h(category_icon($cat['name'])) ?>"></i> <?= h($cat['name']) ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
        <div class="home-hero-media">
            <div class="home-hero-card">
                <?php if ($showcaseProduct): ?>
                    <img src="<?= h(public_url($showcaseProduct['image'])) ?>" alt="<?= h($showcaseProduct['name']) ?>">
                    <span class="badge-price">From <?= h(money($showcaseProduct['price'])) ?></span>
                <?php else: ?>
                    <img src="<?= h(asset('images/background-dubai.jpeg')) ?>" alt="<?= h(company_name()) ?>">
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<section class="home-trust-bar">
    <div class="home-trust-inner">
        <div><i class="bi bi-shield-check"></i> Genuine Products</div>
        <div><i class="bi bi-lightning-charge"></i> Fast Dispatch</div>
        <div><i class="bi bi-globe"></i> Trusted Sourcing</div>
        <div><i class="bi bi-credit-card"></i> Flexible Payments</div>
    </div>
</section>

<?php if ($categories): ?>
<section class="public-section" id="categories" data-reveal>
    <div class="public-section-header">
        <div>
            <span class="public-kicker">Available inventory</span>
            <h2>Top Categories</h2>
        </div>
        <a class="btn btn-outline-primary" href="<?= h(url('products')) ?>">Browse All</a>
    </div>
    <div class="home-category-grid">
        <?php foreach ($categories as $cat): ?>
            <a class="home-category-card" href="<?= h(url('products')) ?>&category=<?= urlencode($cat['name']) ?>">
                <span class="feature-icon"><i class="bi <?= h(category_icon($cat['name'])) ?>"></i></span>
                <strong><?= h($cat['name']) ?></strong>
                <small><?= (int) $cat['total'] ?> item<?= (int) $cat['total'] === 1 ? '' : 's' ?></small>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<section class="public-section" id="featured-products" data-reveal>
    <div class="public-section-header">
        <div>
            <span class="public-kicker">Available inventory</span>
            <h2>Featured Products</h2>
        </div>
        <a class="btn btn-outline-primary" href="<?= h(url('products')) ?>">View All</a>
    </div>

    <div class="home-product-slider" data-home-slider>
        <div class="home-product-track" data-slider-track>
            <?php foreach ($products as $product): ?>
                <article class="public-product-card home-slide">
                    <a class="public-product-image" href="<?= h(url('products/show/' . $product['id'])) ?>">
                        <?php if (!empty($product['image'])): ?>
                            <img src="<?= h(public_url($product['image'])) ?>" alt="<?= h($product['name']) ?>">
                        <?php else: ?>
                            <span><i class="bi <?= h(category_icon($product['category'] ?? '')) ?>"></i></span>
                        <?php endif; ?>
                        <span class="badge-price"><?= h(money($product['price'])) ?></span>
                    </a>
                    <div>
                        <small class="code-text"><?= h($product['sku'] ?? 'DCF-PRODUCT') ?></small>
                        <h3><a href="<?= h(url('products/show/' . $product['id'])) ?>"><?= h($product['name']) ?></a></h3>
                        <p><?= h($product['brand']) ?> / <?= h($product['category']) ?></p>
                    </div>
                    <div class="public-product-meta">
                        <span class="<?= (int) ($product['quantity'] ?? 0) > 0 ? 'text-success' : 'text-muted' ?> fw-semibold">
                            <?= (int) ($product['quantity'] ?? 0) > 0 ? 'In Stock' : 'Made to Order' ?>
                        </span>
                        <a class="btn btn-sm btn-outline-brand" href="<?= h(url('products/show/' . $product['id'])) ?>">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
        <button class="home-slider-arrow home-slider-prev" type="button" data-slider-prev aria-label="Previous products">
            <i class="bi bi-chevron-left"></i>
        </button>
        <button class="home-slider-arrow home-slider-next" type="button" data-slider-next aria-label="Next products">
            <i class="bi bi-chevron-right"></i>
        </button>
        <div class="home-slider-dots" data-slider-dots></div>
    </div>
</section>

<section class="public-track-section" data-reveal>
    <div class="public-track-wrap">
        <div>
            <span class="public-kicker">Already shipped?</span>
            <h2>Track Your Shipment</h2>
            <p>Enter your tracking number to check your cargo status through to final delivery.</p>
        </div>
        <form class="public-track-card" method="get" action="<?= h(url()) ?>">
            <input type="hidden" name="url" value="track-shipment">
            <label class="form-label" for="tracking_number">Track Shipment</label>
            <div class="input-group">
                <input class="form-control code-text" id="tracking_number" name="tracking_number" placeholder="DCF-20260622-0001">
                <button class="btn btn-dark" type="submit"><i class="bi bi-search"></i></button>
            </div>
        </form>
    </div>
</section>

<section class="public-section" id="services" data-reveal-stagger>
    <div class="public-section-header">
        <div>
            <span class="public-kicker">Why choose us</span>
            <h2>Logistics &amp; Value-Added Services</h2>
        </div>
    </div>
    <div class="home-service-grid">
        <div class="home-service-card">
            <span class="feature-icon"><i class="bi bi-file-earmark-text"></i></span>
            <h5>Commercial Invoice Ready</h5>
            <p>Printable invoice requests with customer details, product images, and totals.</p>
        </div>
        <div class="home-service-card">
            <span class="feature-icon"><i class="bi bi-truck"></i></span>
            <h5>Cargo Tracking</h5>
            <p>Live status from origin to final pickup or delivery, in one search.</p>
        </div>
        <div class="home-service-card">
            <span class="feature-icon"><i class="bi bi-shield-check"></i></span>
            <h5>Genuine Products</h5>
            <p>Every item sourced and verified before it ships to you.</p>
        </div>
        <div class="home-service-card">
            <span class="feature-icon"><i class="bi bi-headset"></i></span>
            <h5>Customer Support</h5>
            <p>Call <?= h(company_phone()) ?> or email <?= h(customer_contact_email()) ?> anytime.</p>
        </div>
    </div>
</section>

<section class="home-cta" id="quote" data-reveal>
    <div class="home-cta-inner">
        <div class="home-cta-copy">
            <h2>Ready to place an order or request a custom quotation?</h2>
            <p>Tell us what you need and where to deliver &mdash; we'll respond quickly.</p>
        </div>
        <form class="home-cta-form" method="post" action="<?= h(url('contact')) ?>">
            <?= Auth::csrfField() ?>
            <div class="mb-3">
                <label class="form-label" for="cta_name">Name</label>
                <input class="form-control" id="cta_name" name="name" placeholder="e.g. Juma Mwakalinga" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="cta_phone">Phone</label>
                <input class="form-control" id="cta_phone" name="phone" placeholder="e.g. 0652 532 646" required>
            </div>
            <div class="mb-3">
                <label class="form-label" for="cta_message">What do you need?</label>
                <textarea class="form-control" id="cta_message" name="message" rows="3" placeholder="e.g. 5x laptops, delivery to Dar es Salaam..." required></textarea>
            </div>
            <button class="btn btn-brand btn-lg w-100" type="submit">
                <i class="bi bi-send"></i> Send Request
            </button>
        </form>
    </div>
</section>

<div class="public-promo-overlay" data-promo-overlay>
    <div class="public-promo-card" role="dialog" aria-modal="true" aria-labelledby="promoTitle">
        <button class="public-promo-close" type="button" data-promo-close aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
        <span class="public-promo-icon"><i class="bi bi-stars"></i></span>
        <h3 id="promoTitle">Genuine Products, Trusted Sourcing</h3>
        <p><?= h(company_name()) ?> ships fast, straight to you. Browse our latest stock or get in touch.</p>
        <div class="public-promo-actions">
            <a class="btn btn-primary" href="<?= h(url('products')) ?>">
                <i class="bi bi-grid"></i> Browse Products
            </a>
            <a class="btn btn-outline-primary" href="<?= h(company_instagram_url() ?: url('contact')) ?>" target="_blank" rel="noopener">
                <i class="bi bi-instagram"></i> Chat with Us
            </a>
        </div>
    </div>
</div>
