<?php
$periodLabels = [
    'day' => 'Leo',
    'week' => 'Wiki Hii',
    'month' => 'Mwezi Huu',
];
?>

<div class="page-header">
    <div>
        <p class="eyebrow">Ufuatiliaji wa mauzo yote</p>
        <h1>Sales Tracking & Stock</h1>
    </div>
    <div class="btn-group">
        <?php foreach ($periodLabels as $key => $label): ?>
            <a class="btn <?= $period === $key ? 'btn-primary' : 'btn-outline-primary' ?>" href="<?= h(url('store')) ?>&period=<?= h($key) ?>">
                <?= h($label) ?>
            </a>
        <?php endforeach; ?>
    </div>
</div>

<section class="filter-panel mb-4">
    <form class="row g-3 align-items-end" method="get" action="<?= h(url()) ?>">
        <input type="hidden" name="url" value="store">
        <input type="hidden" name="period" value="<?= h($period) ?>">
        <div class="col-md-2">
            <label class="form-label" for="type"><i class="bi bi-filter me-1"></i>Type</label>
            <select class="form-select" id="type" name="type">
                <option value="">All transactions</option>
                <option value="stock_in" <?= ($filters['type'] ?? '') === 'stock_in' ? 'selected' : '' ?>>Stock In</option>
                <option value="sale" <?= ($filters['type'] ?? '') === 'sale' ? 'selected' : '' ?>>Sale</option>
            </select>
        </div>
        <div class="col-md-2">
            <label class="form-label" for="ledger_search">Product/SKU</label>
            <input class="form-control" id="ledger_search" name="search" value="<?= h($filters['search'] ?? '') ?>" placeholder="Name or SKU">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="ledger_from">From</label>
            <input type="date" class="form-control" id="ledger_from" name="date_from" value="<?= h($filters['date_from'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="ledger_to">To</label>
            <input type="date" class="form-control" id="ledger_to" name="date_to" value="<?= h($filters['date_to'] ?? '') ?>">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="min_amount">Min amount (TZS)</label>
            <input type="number" step="1" class="form-control" id="min_amount" name="min_amount" value="<?= h($filters['min_amount'] ?? '') ?>" placeholder="0">
        </div>
        <div class="col-md-2">
            <label class="form-label" for="max_amount">Max amount (TZS)</label>
            <input type="number" step="1" class="form-control" id="max_amount" name="max_amount" value="<?= h($filters['max_amount'] ?? '') ?>" placeholder="No limit">
        </div>
        <div class="col-md-2">
            <button class="btn btn-dark w-100" type="submit">
                <i class="bi bi-funnel"></i> Filter
            </button>
        </div>
    </form>
</section>

<div class="stats-grid">
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-box-arrow-in-down"></i></span>
        <div>
            <small>Vitu Vilivyoingia</small>
            <strong><?= (int) $summary['units_in'] ?></strong>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-bag-check"></i></span>
        <div>
            <small>Vitu Vilivyouzwa</small>
            <strong><?= (int) $summary['units_sold'] ?></strong>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-cash-stack"></i></span>
        <div>
            <small>Pesa Iliyoingia</small>
            <strong><?= h(money($summary['revenue'])) ?></strong>
        </div>
    </div>
    <div class="stat-card">
        <span class="stat-icon"><i class="bi bi-calendar-range"></i></span>
        <div>
            <small>Kipindi</small>
            <strong><?= h($summary['start_date']) ?> - <?= h($summary['end_date']) ?></strong>
        </div>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <section class="panel">
            <div class="panel-header">
                <h2>Record Vitu Vilivyoingia Dukani</h2>
            </div>
            <form method="post" action="<?= h(url('store/stockIn')) ?>" class="row g-3 needs-validation" novalidate>
                <?= Auth::csrfField() ?>
                <div class="col-md-6">
                    <label class="form-label" for="stock_product_id">Bidhaa</label>
                    <div class="barcode-scan" data-barcode-scan="stock_product_id">
                        <i class="bi bi-upc-scan" aria-hidden="true"></i>
                        <input class="form-control form-control-sm" type="text" inputmode="text" autocomplete="off" placeholder="Scan barcode / andika SKU, kisha Enter" aria-label="Scan barcode or type SKU" data-barcode-input>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-barcode-camera hidden title="Scan with camera"><i class="bi bi-camera"></i></button>
                    </div>
                    <small class="barcode-feedback" data-barcode-feedback></small>
                    <select class="form-select" id="stock_product_id" name="product_id" required>
                        <option value="">Chagua bidhaa</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= (int) $product['id'] ?>" data-sku="<?= h($product['sku'] ?? '') ?>">
                                <?= h($product['name']) ?> / Stock: <?= (int) $product['quantity'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="stock_quantity">Quantity</label>
                    <input type="number" min="1" class="form-control" id="stock_quantity" name="quantity" placeholder="0" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="unit_cost">Cost/Item (TZS)</label>
                    <input type="number" min="0" step="1" class="form-control" id="unit_cost" name="unit_cost" value="0">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="supplier_name">Supplier</label>
                    <input class="form-control" id="supplier_name" name="supplier_name" placeholder="e.g. Acme Supplies Ltd">
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="received_date">Tarehe</label>
                    <input type="date" class="form-control" id="received_date" name="received_date" value="<?= h(date('Y-m-d')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="stock_notes">Maelezo</label>
                    <textarea class="form-control" id="stock_notes" name="notes" rows="2" placeholder="Optional"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-plus-circle me-2"></i>Save Stock In
                    </button>
                </div>
            </form>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="panel">
            <div class="panel-header">
                <h2>Record Vitu Vilivyouzwa</h2>
            </div>
            <form method="post" action="<?= h(url('store/sale')) ?>" class="row g-3 needs-validation" novalidate>
                <?= Auth::csrfField() ?>
                <div class="col-md-6">
                    <label class="form-label" for="sale_product_id">Bidhaa</label>
                    <div class="barcode-scan" data-barcode-scan="sale_product_id">
                        <i class="bi bi-upc-scan" aria-hidden="true"></i>
                        <input class="form-control form-control-sm" type="text" inputmode="text" autocomplete="off" placeholder="Scan barcode / andika SKU, kisha Enter" aria-label="Scan barcode or type SKU" data-barcode-input>
                        <button class="btn btn-sm btn-outline-secondary" type="button" data-barcode-camera hidden title="Scan with camera"><i class="bi bi-camera"></i></button>
                    </div>
                    <small class="barcode-feedback" data-barcode-feedback></small>
                    <select class="form-select" id="sale_product_id" name="product_id" required>
                        <option value="">Chagua bidhaa</option>
                        <?php foreach ($products as $product): ?>
                            <option value="<?= (int) $product['id'] ?>" data-sku="<?= h($product['sku'] ?? '') ?>" data-price="<?= h($product['price']) ?>">
                                <?= h($product['name']) ?> / Stock: <?= (int) $product['quantity'] ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="sale_quantity">Quantity</label>
                    <input type="number" min="1" class="form-control" id="sale_quantity" name="quantity" value="1" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="unit_price">Bei/Item (TZS)</label>
                    <input type="number" min="0" step="1" class="form-control" id="unit_price" name="unit_price" placeholder="0" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="customer_name">Mteja</label>
                    <input class="form-control" id="customer_name" name="customer_name" placeholder="Walk-in Customer">
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="payment_method">Malipo</label>
                    <select class="form-select" id="payment_method" name="payment_method">
                        <option value="cash">Cash</option>
                        <option value="mobile_money">Mobile Money</option>
                        <option value="bank_transfer">Bank Transfer</option>
                        <option value="card">Card</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="form-label" for="sale_date">Tarehe</label>
                    <input type="date" class="form-control" id="sale_date" name="sale_date" value="<?= h(date('Y-m-d')) ?>">
                </div>
                <div class="col-12">
                    <label class="form-label" for="sale_notes">Maelezo</label>
                    <textarea class="form-control" id="sale_notes" name="notes" rows="2" placeholder="Optional"></textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">
                        <i class="bi bi-cash-coin me-2"></i>Save Sale
                    </button>
                </div>
            </form>
        </section>
    </div>
</div>

<div class="row g-4 mt-1">
    <div class="col-lg-6">
        <section class="panel">
            <div class="panel-header">
                <h2>Vitu Vilivyoingia - <?= h($periodLabels[$period]) ?></h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Tarehe</th>
                        <th>Bidhaa</th>
                        <th>Qty</th>
                        <th>Cost</th>
                        <th>Supplier</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($stockEntries as $entry): ?>
                        <tr>
                            <td><?= h($entry['received_date']) ?></td>
                            <td>
                                <strong><?= h($entry['product_name']) ?></strong>
                                <small class="d-block text-muted"><?= h($entry['sku'] ?? '') ?></small>
                            </td>
                            <td><?= (int) $entry['quantity'] ?></td>
                            <td><?= h(money($entry['unit_cost'])) ?></td>
                            <td><?= h($entry['supplier_name'] ?? '') ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$stockEntries): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Hakuna stock-in kwenye period hii.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>

    <div class="col-lg-6">
        <section class="panel">
            <div class="panel-header">
                <h2>Vitu Vilivyouzwa - <?= h($periodLabels[$period]) ?></h2>
            </div>
            <div class="table-responsive">
                <table class="table align-middle">
                    <thead>
                    <tr>
                        <th>Tarehe</th>
                        <th>Bidhaa</th>
                        <th>Qty</th>
                        <th>Total</th>
                        <th>Malipo</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($sales as $sale): ?>
                        <tr>
                            <td><?= h($sale['sale_date']) ?></td>
                            <td>
                                <strong><?= h($sale['product_name']) ?></strong>
                                <small class="d-block text-muted"><?= h($sale['sale_number']) ?></small>
                            </td>
                            <td><?= (int) $sale['quantity'] ?></td>
                            <td><?= h(money($sale['line_total'])) ?></td>
                            <td><?= h(readable_status($sale['payment_method'])) ?></td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (!$sales): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">Hakuna mauzo kwenye period hii.</td></tr>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</div>

<section class="panel mt-4">
    <div class="panel-header">
        <h2><i class="bi bi-list-check me-2"></i>All Filtered Sales & Stock Transactions</h2>
    </div>
    <div class="table-responsive">
        <table class="table align-middle data-table">
            <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Product</th>
                <th>Qty</th>
                <th>Unit Amount (TZS)</th>
                <th>Total (TZS)</th>
                <th>Party</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($transactions as $transaction): ?>
                <tr>
                    <td><?= h($transaction['transaction_date']) ?></td>
                    <td><span class="badge <?= $transaction['transaction_type'] === 'sale' ? 'text-bg-success' : 'text-bg-info' ?>"><?= h(readable_status($transaction['transaction_type'])) ?></span></td>
                    <td>
                        <strong><?= h($transaction['product_name']) ?></strong>
                        <small class="d-block text-muted"><?= h($transaction['sku'] ?? '') ?></small>
                    </td>
                    <td><?= (int) $transaction['quantity'] ?></td>
                    <td><?= h(money($transaction['unit_amount'])) ?></td>
                    <td><?= h(money($transaction['total_amount'])) ?></td>
                    <td><?= h($transaction['party_name'] ?? '') ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$transactions): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No transactions match the selected filters.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="panel mt-4">
    <div class="panel-header">
        <h2>Bidhaa Zinazouzwa Zaidi - <?= h($periodLabels[$period]) ?></h2>
    </div>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Bidhaa</th>
                <th>Brand</th>
                <th>Qty Sold</th>
                <th>Pesa Iliyoingia</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($topProducts as $product): ?>
                <tr>
                    <td><?= h($product['name']) ?></td>
                    <td><?= h($product['brand']) ?></td>
                    <td><?= (int) $product['units_sold'] ?></td>
                    <td><?= h(money($product['revenue'])) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$topProducts): ?>
                <tr><td colspan="4" class="text-center text-muted py-4">Hakuna mauzo ya bidhaa kwenye period hii.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
