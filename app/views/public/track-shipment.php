<section class="public-page-heading">
    <span class="public-kicker">Cargo tracking</span>
    <h1>Track Shipment</h1>
    <p>Enter your tracking number to see the latest cargo status.</p>
</section>

<section class="panel public-form-panel">
    <form method="get" action="<?= h(url()) ?>" class="row g-3 align-items-end">
        <input type="hidden" name="url" value="track-shipment">
        <div class="col-md-9">
            <label class="form-label" for="tracking_number">Tracking Number</label>
            <input class="form-control code-text" id="tracking_number" name="tracking_number" value="<?= h($trackingNumber) ?>" placeholder="DCF-20260622-0001">
        </div>
        <div class="col-md-3">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-search"></i> Track</button>
        </div>
    </form>

    <?php if ($trackingNumber !== ''): ?>
        <?php if ($trackingResult): ?>
            <div class="public-tracking-result">
                <div>
                    <small>Tracking Number</small>
                    <strong class="code-text"><?= h($trackingResult['tracking_number']) ?></strong>
                </div>
                <div>
                    <small>Status</small>
                    <span class="badge <?= h(badge_class($trackingResult['status'])) ?>"><?= h(readable_status($trackingResult['status'])) ?></span>
                </div>
                <div>
                    <small>Route</small>
                    <strong><?= h($trackingResult['origin']) ?> to <?= h($trackingResult['destination']) ?></strong>
                </div>
                <div>
                    <small>Order</small>
                    <strong class="code-text"><?= h($trackingResult['order_number']) ?></strong>
                </div>
            </div>
        <?php else: ?>
            <div class="alert alert-warning mt-4 mb-0">No shipment was found for that tracking number.</div>
        <?php endif; ?>
    <?php endif; ?>
</section>
