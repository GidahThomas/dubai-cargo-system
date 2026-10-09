<?php
$galleryImages = $product['gallery_images'] ?? [];
$primaryImage = $product['image'] ?: ($galleryImages[0]['image_path'] ?? '');
$allImages = [];
if ($primaryImage) {
    $allImages[] = $primaryImage;
}
foreach ($galleryImages as $image) {
    if (($image['image_path'] ?? '') !== '' && !in_array($image['image_path'], $allImages, true)) {
        $allImages[] = $image['image_path'];
    }
}
?>

<a class="product-back-link" href="<?= h(url('products') . (!empty($product['category']) ? '&category=' . urlencode($product['category']) : '')) ?>">
    <i class="bi bi-arrow-left"></i> Back to <?= h($product['category'] ?: 'products') ?>
</a>

<section class="public-product-detail">
    <div>
        <div class="public-detail-media" data-product-carousel>
            <?php if ($allImages): ?>
                <?php foreach ($allImages as $index => $imagePath): ?>
                    <img class="product-carousel-image<?= $index === 0 ? ' active' : '' ?>" src="<?= h(public_url($imagePath)) ?>" alt="<?= h($product['name']) ?>" data-index="<?= $index ?>">
                <?php endforeach; ?>
                <button type="button" class="product-carousel-btn product-carousel-prev" data-direction="prev" aria-label="Previous image"><i class="bi bi-chevron-left"></i></button>
                <button type="button" class="product-carousel-btn product-carousel-next" data-direction="next" aria-label="Next image"><i class="bi bi-chevron-right"></i></button>
            <?php else: ?>
                <span><i class="bi bi-laptop"></i></span>
            <?php endif; ?>
        </div>

        <?php if ($allImages): ?>
            <div class="public-detail-gallery">
                <?php foreach ($allImages as $index => $imagePath): ?>
                    <img src="<?= h(public_url($imagePath)) ?>" alt="<?= h($product['name']) ?>" data-thumb-index="<?= $index ?>" class="product-thumb<?= $index === 0 ? ' active' : '' ?>">
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <div class="public-detail-content">
        <span class="public-kicker"><?= h($product['brand']) ?> / <?= h($product['category']) ?></span>
        <h1><?= h($product['name']) ?></h1>
        <?php $saving = discount_percent($product['price'], $product['compare_at_price'] ?? 0); ?>
        <div class="public-detail-price">
            <?= h(money($product['price'])) ?>
            <?php if ($saving > 0): ?>
                <del class="price-was"><?= h(money($product['compare_at_price'])) ?></del>
                <span class="price-saving">Save <?= $saving ?>%</span>
            <?php endif; ?>
        </div>
        <div class="product-facts">
            <span><i class="bi bi-patch-check"></i> <?= h(Product::CONDITIONS[$product['item_condition'] ?? 'new'] ?? 'Brand new') ?></span>
            <?php if (!empty($product['warranty'])): ?>
                <span><i class="bi bi-shield-check"></i> <?= h(stripos($product['warranty'], 'warranty') !== false ? $product['warranty'] : $product['warranty'] . ' warranty') ?></span>
            <?php endif; ?>
        </div>
        <div class="public-detail-stock">
            <span class="badge <?= ((int) ($product['quantity'] ?? 0) > 0) ? 'text-bg-success' : 'text-bg-warning' ?>">
                <?= (int) ($product['quantity'] ?? 0) ?> available
            </span>
            <?php if (!empty($product['sku'])): ?>
                <span class="code-text"><?= h($product['sku']) ?></span>
            <?php endif; ?>
            <?php if (!empty($product['country_of_origin'])): ?>
                <span class="text-muted"><i class="bi bi-globe2"></i> Imported from <?= h($product['country_of_origin']) ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($product['description'])): ?>
            <p><?= nl2br(h($product['description'])) ?></p>
        <?php endif; ?>

        <?php $specRows = product_spec_rows($product['specifications'] ?? ''); ?>
        <?php if ($specRows): ?>
            <section class="public-spec-box">
                <h2>Specifications</h2>
                <table class="spec-table">
                    <tbody>
                        <?php foreach ($specRows as $row): ?>
                            <tr>
                                <?php if ($row['label'] !== ''): ?>
                                    <th scope="row"><?= h($row['label']) ?></th>
                                    <td><?= h($row['value']) ?></td>
                                <?php else: ?>
                                    <td colspan="2"><?= h($row['value']) ?></td>
                                <?php endif; ?>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </section>
        <?php endif; ?>

        <?php if (Auth::role() === 'customer' && (int) ($product['quantity'] ?? 0) > 0): ?>
            <form method="post" action="<?= h(url('cart/add')) ?>" class="add-to-cart-form add-to-cart-lg mt-4">
                <?= Auth::csrfField() ?>
                <input type="hidden" name="product_id" value="<?= (int) $product['id'] ?>">
                <input type="hidden" name="return_to" value="cart">
                <input type="number" class="form-control" name="quantity" value="1" min="1" max="<?= min(99, (int) $product['quantity']) ?>" aria-label="Quantity">
                <button class="btn btn-primary btn-lg" type="submit"><i class="bi bi-cart-plus"></i> Add to cart</button>
            </form>
        <?php elseif (!Auth::check()): ?>
            <a class="btn btn-primary btn-lg mt-4" href="<?= h(url('login')) ?>"><i class="bi bi-box-arrow-in-right"></i> Log in to order online</a>
        <?php endif; ?>

        <div class="public-hero-actions">
            <a class="btn btn-primary" href="<?= h(url('request-invoice') . '&product_id=' . (int) $product['id']) ?>">
                <i class="bi bi-receipt-cutoff"></i> Request Invoice
            </a>
            <a class="btn btn-outline-primary" href="<?= h(url('request-quotation') . '&product_id=' . (int) $product['id']) ?>">
                <i class="bi bi-chat-square-text"></i> Request Quotation
            </a>
            <?php $askUrl = whatsapp_url('Hello ' . company_name() . ", I'm interested in " . $product['name'] . ' (' . money($product['price']) . '). Is it available?'); ?>
            <?php if ($askUrl !== ''): ?>
                <a class="btn btn-whatsapp" href="<?= h($askUrl) ?>" target="_blank" rel="noopener">
                    <i class="bi bi-whatsapp"></i> Ask on WhatsApp
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>
