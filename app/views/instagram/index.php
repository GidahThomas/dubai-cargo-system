<div class="page-header">
    <div>
        <p class="eyebrow">Gallery &amp; catalogue</p>
        <h1>Instagram Import</h1>
    </div>
    <div class="page-actions">
        <a class="btn btn-outline-secondary" href="<?= h(url('gallery')) ?>" target="_blank" rel="noopener">
            <i class="bi bi-images"></i> View Gallery
        </a>
    </div>
</div>

<section class="row g-3 mb-4">
    <div class="col-sm-4"><div class="panel h-100"><small class="text-muted">Imported posts</small><h2 class="mb-0"><?= (int) $counts['total'] ?></h2></div></div>
    <div class="col-sm-4"><div class="panel h-100"><small class="text-muted">Shown in gallery</small><h2 class="mb-0"><?= (int) $counts['visible'] ?></h2></div></div>
    <div class="col-sm-4"><div class="panel h-100"><small class="text-muted">Draft products created</small><h2 class="mb-0"><?= (int) $counts['with_product'] ?></h2></div></div>
</section>

<section class="panel">
    <div class="panel-header">
        <h2>Import from your Instagram export</h2>
    </div>
    <ol class="instagram-steps">
        <li>On Instagram (logged in as <?= h(company_social_handle() ?: 'your business account') ?>) open <strong>Accounts Center &rarr; Your information and permissions &rarr; Download your information</strong>.</li>
        <li>Choose <strong>Some of your information &rarr; Content</strong> (posts and reels), <strong>Date range: All time</strong>, <strong>Format: JSON</strong>, <strong>Media quality: High</strong>. Instagram emails a download link, usually within a few hours.</li>
        <li>Download the ZIP, right-click it &rarr; <strong>Extract All</strong>, and extract it into:
            <code class="d-block mt-1 user-select-all"><?= h(str_replace('/', DIRECTORY_SEPARATOR, $exportDir)) ?></code>
        </li>
        <li>Click <strong>Import now</strong>. Running it again later only adds new posts.</li>
    </ol>

    <form method="post" action="<?= h(url('instagram/import')) ?>" class="d-flex flex-wrap align-items-center gap-3 mt-3">
        <?= Auth::csrfField() ?>
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="create_products" value="1" id="createProducts" checked>
            <label class="form-check-label" for="createProducts">Also create hidden draft products (set price, category &amp; stock, then publish)</label>
        </div>
        <button class="btn btn-primary" type="submit" <?= $exportReady ? '' : 'disabled' ?>>
            <i class="bi bi-cloud-download"></i> Import now
        </button>
        <?php if (!$exportReady): ?>
            <span class="text-muted small">Waiting for the export folder above.</span>
        <?php endif; ?>
    </form>
</section>

<?php if ($posts): ?>
<section class="panel mt-4">
    <div class="panel-header">
        <h2>Imported posts</h2>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
                <tr>
                    <th>Post</th>
                    <th>Caption</th>
                    <th>Date</th>
                    <th>Product</th>
                    <th class="text-end">Gallery</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($posts as $post): ?>
                    <?php $cover = $post['media'][0] ?? null; ?>
                    <tr>
                        <td>
                            <?php if ($cover && $cover['media_type'] === 'image'): ?>
                                <img class="instagram-thumb" src="<?= h(Thumbnail::url($cover['media_path'])) ?>" alt="" loading="lazy">
                            <?php else: ?>
                                <span class="instagram-thumb is-video"><i class="bi bi-play-btn"></i></span>
                            <?php endif; ?>
                            <small class="d-block text-muted"><?= count($post['media']) ?> file<?= count($post['media']) === 1 ? '' : 's' ?></small>
                        </td>
                        <td class="instagram-caption-cell"><?= h(mb_strimwidth((string) $post['caption'], 0, 140, '...')) ?></td>
                        <td class="text-nowrap"><?= $post['posted_at'] ? h(date('Y-m-d', strtotime($post['posted_at']))) : '-' ?></td>
                        <td>
                            <?php if ($post['product_id']): ?>
                                <a href="<?= h(url('products/edit/' . (int) $post['product_id'])) ?>"><?= h($post['product_name']) ?></a>
                                <span class="badge <?= h(badge_class($post['product_status'])) ?>"><?= h(readable_status($post['product_status'])) ?></span>
                            <?php else: ?>
                                <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-end">
                            <form method="post" action="<?= h(url('instagram/visibility/' . (int) $post['id'])) ?>" class="d-inline">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="visible" value="<?= $post['is_visible'] ? '0' : '1' ?>">
                                <button class="btn btn-sm <?= $post['is_visible'] ? 'btn-outline-secondary' : 'btn-outline-success' ?>" type="submit">
                                    <i class="bi <?= $post['is_visible'] ? 'bi-eye-slash' : 'bi-eye' ?>"></i> <?= $post['is_visible'] ? 'Hide' : 'Show' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>
<?php endif; ?>
