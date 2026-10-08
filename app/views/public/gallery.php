<?php $instagramUrl = company_instagram_url(); ?>
<section class="catalog-heading">
    <div>
        <span class="public-kicker">From our Instagram</span>
        <h1>Gallery</h1>
        <p>Latest products, deliveries and shop moments<?= company_social_handle() ? ' from ' . h(company_social_handle()) : '' ?>.</p>
    </div>
    <?php if ($instagramUrl): ?>
        <a class="btn btn-dark" href="<?= h($instagramUrl) ?>" target="_blank" rel="noopener">
            <i class="bi bi-instagram"></i> Follow on Instagram
        </a>
    <?php endif; ?>
</section>

<?php if (!$posts): ?>
    <div class="panel public-empty-state">
        <strong>The gallery is being prepared.</strong>
        <span>
            In the meantime, see our latest posts
            <?php if ($instagramUrl): ?>on <a href="<?= h($instagramUrl) ?>" target="_blank" rel="noopener">Instagram</a><?php else: ?>on Instagram<?php endif; ?>.
        </span>
    </div>
<?php endif; ?>

<div class="gallery-grid">
    <?php foreach ($posts as $post): ?>
        <?php $postId = 'igPost' . (int) $post['id']; $media = $post['media']; ?>
        <article class="gallery-card">
            <div class="gallery-media">
                <?php if (count($media) > 1): ?>
                    <div id="<?= h($postId) ?>" class="carousel slide" data-bs-touch="true" data-bs-interval="false">
                        <div class="carousel-inner">
                            <?php foreach ($media as $index => $item): ?>
                                <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                    <?php if ($item['media_type'] === 'video'): ?>
                                        <video src="<?= h(public_url($item['media_path'])) ?>" controls preload="metadata" playsinline></video>
                                    <?php else: ?>
                                        <img src="<?= h(public_url($item['media_path'])) ?>" alt="<?= h(mb_substr((string) $post['caption'], 0, 80)) ?>" loading="lazy">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <button class="carousel-control-prev" type="button" data-bs-target="#<?= h($postId) ?>" data-bs-slide="prev" aria-label="Previous photo">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#<?= h($postId) ?>" data-bs-slide="next" aria-label="Next photo">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                        </button>
                        <span class="gallery-count"><i class="bi bi-images"></i> <?= count($media) ?></span>
                    </div>
                <?php elseif ($media): ?>
                    <?php $item = $media[0]; ?>
                    <?php if ($item['media_type'] === 'video'): ?>
                        <video src="<?= h(public_url($item['media_path'])) ?>" controls preload="metadata" playsinline></video>
                    <?php else: ?>
                        <img src="<?= h(public_url($item['media_path'])) ?>" alt="<?= h(mb_substr((string) $post['caption'], 0, 80)) ?>" loading="lazy">
                    <?php endif; ?>
                <?php endif; ?>
            </div>

            <div class="gallery-body">
                <?php if (!empty($post['caption'])): ?>
                    <p class="gallery-caption" id="<?= h($postId) ?>Caption"><?= nl2br(h($post['caption'])) ?></p>
                    <?php if (mb_strlen($post['caption']) > 160 || substr_count($post['caption'], "\n") > 3): ?>
                        <button class="gallery-more" type="button" data-caption-toggle="<?= h($postId) ?>Caption" aria-expanded="false">Read more</button>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="gallery-footer">
                    <?php if (!empty($post['posted_at'])): ?>
                        <small><?= h(date('M j, Y', strtotime($post['posted_at']))) ?></small>
                    <?php endif; ?>
                    <?php if (!empty($post['product_id']) && ($post['product_status'] ?? '') === 'active'): ?>
                        <a class="btn btn-sm btn-primary" href="<?= h(url('products/show/' . (int) $post['product_id'])) ?>">View product</a>
                    <?php else: ?>
                        <?php $askUrl = whatsapp_url('Hello ' . company_name() . ', I saw this on your gallery: ' . mb_substr(str_replace("\n", ' ', (string) $post['caption']), 0, 120)); ?>
                        <?php if ($askUrl !== ''): ?>
                            <a class="btn btn-sm btn-whatsapp" href="<?= h($askUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> Ask</a>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
