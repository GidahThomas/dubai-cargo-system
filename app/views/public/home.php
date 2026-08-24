<section class="public-hero">
    <div class="public-hero-inner">
        <div class="public-hero-content">
            <span class="public-kicker">Genuine computers, unbeatable prices</span>
            <h1>Dubai Computer Fast Cargo</h1>
            <p>Laptops, desktops, all-in-one computers, gaming PCs, workstations, accessories, invoices, and shipment tracking for customers buying from Dubai.</p>
            <div class="public-hero-actions">
                <a class="btn btn-primary" href="<?= h(url('products')) ?>">
                    <i class="bi bi-grid"></i> Browse Products
                </a>
                <a class="btn btn-outline-primary" href="<?= h(url('request-invoice')) ?>">
                    <i class="bi bi-receipt-cutoff"></i> Request Invoice
                </a>
            </div>
        </div>
    </div>
</section>

<section class="public-track-section" data-reveal>
    <div class="public-track-wrap">
        <div>
            <span class="public-kicker">Already shipped?</span>
            <h2>Track Your Shipment</h2>
            <p>Enter your tracking number to check cargo status from Dubai to final delivery.</p>
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
                            <span><i class="bi bi-laptop"></i></span>
                        <?php endif; ?>
                    </a>
                    <div>
                        <small class="code-text"><?= h($product['sku'] ?? 'DCF-PRODUCT') ?></small>
                        <h3><a href="<?= h(url('products/show/' . $product['id'])) ?>"><?= h($product['name']) ?></a></h3>
                        <p><?= h($product['brand']) ?> / <?= h($product['category']) ?></p>
                    </div>
                    <div class="public-product-meta">
                        <strong><?= h(money($product['price'])) ?></strong>
                        <span><?= (int) ($product['quantity'] ?? 0) ?> in stock</span>
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

<section class="public-service-band" data-reveal-stagger>
    <div>
        <i class="bi bi-file-earmark-text"></i>
        <h2>Commercial Invoice Ready</h2>
        <p>Generate a printable invoice request with customer details, product images, item descriptions, totals, and balance due.</p>
    </div>
    <div>
        <i class="bi bi-truck"></i>
        <h2>Cargo Tracking</h2>
        <p>Use your tracking number to check cargo status from Dubai to final pickup or delivery.</p>
    </div>
    <div>
        <i class="bi bi-headset"></i>
        <h2>Customer Support</h2>
        <p>Call <?= h(company_phone()) ?> or email <?= h(customer_contact_email()) ?> for product availability and quotations.</p>
    </div>
</section>

<div class="public-promo-overlay" data-promo-overlay>
    <div class="public-promo-card" role="dialog" aria-modal="true" aria-labelledby="promoTitle">
        <button class="public-promo-close" type="button" data-promo-close aria-label="Close">
            <i class="bi bi-x-lg"></i>
        </button>
        <span class="public-promo-icon"><i class="bi bi-stars"></i></span>
        <h3 id="promoTitle">Genuine Computers, Unbeatable Prices</h3>
        <p>Laptops, desktops, and accessories shipped fast from Dubai straight to you in Tanzania. Browse our latest stock or chat with us on Instagram.</p>
        <div class="public-promo-actions">
            <a class="btn btn-primary" href="<?= h(url('products')) ?>">
                <i class="bi bi-grid"></i> Browse Products
            </a>
            <a class="btn btn-outline-primary" href="<?= h(company_instagram_url()) ?>" target="_blank" rel="noopener">
                <i class="bi bi-instagram"></i> Chat with Us
            </a>
        </div>
    </div>
</div>
