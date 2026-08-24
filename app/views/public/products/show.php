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
        <div class="public-detail-price"><?= h(money($product['price'])) ?></div>
        <div class="public-detail-stock">
            <span class="badge <?= ((int) ($product['quantity'] ?? 0) > 0) ? 'text-bg-success' : 'text-bg-warning' ?>">
                <?= (int) ($product['quantity'] ?? 0) ?> available
            </span>
            <?php if (!empty($product['sku'])): ?>
                <span class="code-text"><?= h($product['sku']) ?></span>
            <?php endif; ?>
        </div>

        <?php if (!empty($product['description'])): ?>
            <p><?= nl2br(h($product['description'])) ?></p>
        <?php endif; ?>

        <?php if (!empty($product['specifications'])): ?>
            <section class="public-spec-box">
                <h2>Specifications</h2>
                <p><?= nl2br(h($product['specifications'])) ?></p>
            </section>
        <?php endif; ?>

        <div class="public-hero-actions">
            <a class="btn btn-primary" href="<?= h(url('request-invoice') . '&product_id=' . (int) $product['id']) ?>">
                <i class="bi bi-receipt-cutoff"></i> Request Invoice
            </a>
            <a class="btn btn-outline-primary" href="<?= h(url('request-quotation') . '&product_id=' . (int) $product['id']) ?>">
                <i class="bi bi-chat-square-text"></i> Request Quotation
            </a>
        </div>
    </div>
</section>
