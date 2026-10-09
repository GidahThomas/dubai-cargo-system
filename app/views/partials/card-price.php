<?php
/** Price line for product cards. Expects $product. */
$cardSaving = discount_percent($product['price'] ?? 0, $product['compare_at_price'] ?? 0);
?>
<span class="catalog-card-price">
    <?= h(money($product['price'])) ?>
    <?php if ($cardSaving > 0): ?>
        <del class="price-was"><?= h(money($product['compare_at_price'])) ?></del>
        <span class="price-saving">-<?= $cardSaving ?>%</span>
    <?php endif; ?>
</span>
