<div class="page-header">
    <div>
        <p class="eyebrow">Cargo tracking</p>
        <h1>Shipments</h1>
    </div>
</div>

<section class="filter-panel">
    <form class="row g-3 align-items-end" method="get" action="<?= h(url()) ?>">
        <input type="hidden" name="url" value="shipments">
        <div class="col-md-3">
            <label class="form-label" for="tracking_number"><i class="bi bi-search me-1"></i>Tracking number</label>
            <input class="form-control" id="tracking_number" name="tracking_number" value="<?= h($_GET['tracking_number'] ?? '') ?>" placeholder="DCF-...">
        </div>
        <?php if (Auth::hasRole(['manager', 'admin'])): ?>
            <div class="col-md-2">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="">All statuses</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= h($status) ?>" <?= ($filters['status'] ?? '') === $status ? 'selected' : '' ?>><?= h(readable_status($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="search">Search</label>
                <input class="form-control" id="search" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Order, customer, tracking number">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="date_from">From</label>
                <input type="date" class="form-control" id="date_from" name="date_from" value="<?= h($filters['date_from'] ?? '') ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="date_to">To</label>
                <input type="date" class="form-control" id="date_to" name="date_to" value="<?= h($filters['date_to'] ?? '') ?>">
            </div>
        <?php endif; ?>
        <div class="col-md-2">
            <button class="btn btn-dark w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </div>
    </form>

    <?php if (isset($_GET['tracking_number'])): ?>
        <?php if ($trackingResult): ?>
            <div class="tracking-result mt-3">
                <div>
                    <small>Tracking number</small>
                    <strong class="code-text"><?= h($trackingResult['tracking_number']) ?></strong>
                </div>
                <span class="badge <?= h(badge_class($trackingResult['status'])) ?>"><?= h(readable_status($trackingResult['status'])) ?></span>
                <p class="mb-0">Order <span class="code-text"><?= h($trackingResult['order_number']) ?></span> from <?= h($trackingResult['origin']) ?> to <?= h($trackingResult['destination']) ?>.</p>
            </div>
            <?php require ROOT_PATH . '/app/views/partials/shipment-timeline.php'; ?>
        <?php else: ?>
            <div class="alert alert-warning mt-3 mb-0">No shipment was found for that tracking number.</div>
        <?php endif; ?>
    <?php endif; ?>
</section>

<?php if (Auth::hasRole(['manager', 'admin'])): ?>
    <section class="panel mt-4">
        <div class="panel-header">
            <h2>Create Shipment</h2>
        </div>
        <form method="post" action="<?= h(url('shipments/store')) ?>" class="row g-3 align-items-end needs-validation" novalidate>
            <?= Auth::csrfField() ?>
            <div class="col-md-3">
                <label class="form-label" for="order_id">Order</label>
                <select class="form-select" id="order_id" name="order_id" required>
                    <option value="">Choose order</option>
                    <?php foreach ($orders as $order): ?>
                        <option value="<?= (int) $order['id'] ?>"><?= h($order['order_number']) ?> / <?= h($order['customer_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2">
                <label class="form-label" for="tracking_new">Tracking</label>
                <input class="form-control" id="tracking_new" name="tracking_number" placeholder="Auto">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="carrier">Carrier</label>
                <input class="form-control" id="carrier" name="carrier" value="<?= h(company_name()) ?>">
            </div>
            <div class="col-md-2">
                <label class="form-label" for="origin">Origin</label>
                <input class="form-control" id="origin" name="origin" placeholder="e.g. Warehouse city">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="destination">Destination</label>
                <input class="form-control" id="destination" name="destination" placeholder="Use order address if blank">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="status_new">Starting stage</label>
                <select class="form-select" id="status_new" name="status">
                    <option value="" selected>Automatic (Payment Confirmed if already paid)</option>
                    <?php foreach ($statuses as $status): ?>
                        <option value="<?= h($status) ?>"><?= h(readable_status($status)) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="expected_arrival">Expected arrival</label>
                <input type="date" class="form-control" id="expected_arrival" name="expected_arrival">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="notes">Notes</label>
                <input class="form-control" id="notes" name="notes" placeholder="Optional">
            </div>
            <div class="col-md-2">
                <button class="btn btn-primary w-100" type="submit">
                    <i class="bi bi-plus-lg me-2"></i>Create
                </button>
            </div>
        </form>
    </section>
<?php endif; ?>

<section class="panel mt-4">
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Tracking</th>
                <th>Order</th>
                <th>Customer</th>
                <th>Route</th>
                <th>Expected</th>
                <th>Status</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($shipments as $shipment): ?>
                <tr>
                    <td><strong class="code-text"><?= h($shipment['tracking_number']) ?></strong></td>
                    <td class="code-text"><?= h($shipment['order_number']) ?></td>
                    <td><?= h($shipment['customer_name']) ?></td>
                    <td><?= h($shipment['origin']) ?> to <?= h($shipment['destination']) ?></td>
                    <td><?= h($shipment['expected_arrival'] ?? 'Pending') ?></td>
                    <td><span id="shipmentStatus<?= (int) $shipment['id'] ?>" class="badge <?= h(badge_class($shipment['status'])) ?>"><?= h(readable_status($shipment['status'])) ?></span></td>
                    <td class="text-end">
                        <?php if (Auth::hasRole(['manager', 'admin'])): ?>
                            <form method="post" action="<?= h(url('shipments/ajaxUpdateStatus')) ?>" class="ajax-status-form" data-ajax-form data-status-target="#shipmentStatus<?= (int) $shipment['id'] ?>">
                                <?= Auth::csrfField() ?>
                                <input type="hidden" name="shipment_id" value="<?= (int) $shipment['id'] ?>">
                                <input type="hidden" name="notes" value="<?= h($shipment['notes'] ?? '') ?>">
                                <select class="form-select form-select-sm" name="status">
                                    <?php foreach ($statuses as $status): ?>
                                        <option value="<?= h($status) ?>" <?= $shipment['status'] === $status ? 'selected' : '' ?>><?= h(readable_status($status)) ?></option>
                                    <?php endforeach; ?>
                                </select>
                                <button class="btn btn-sm btn-outline-primary btn-icon" type="submit" title="Update shipment"><i class="bi bi-truck"></i></button>
                                <span class="ajax-message"></span>
                            </form>
                        <?php else: ?>
                            <span class="text-muted"><?= h($shipment['carrier'] ?? '') ?></span>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$shipments): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No shipments found.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
