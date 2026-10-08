<div class="page-header">
    <div>
        <p class="eyebrow">Product inventory upload</p>
        <h1>Edit Product</h1>
    </div>
    <a class="btn btn-outline-secondary" href="<?= h(url('products')) ?>">
        <i class="bi bi-arrow-left me-2"></i>Back
    </a>
</div>

<section class="panel">
    <form method="post" action="<?= h(url('products/update/' . $product['id'])) ?>" enctype="multipart/form-data" class="needs-validation" novalidate>
        <?= Auth::csrfField() ?>
        <div class="row g-3">
            <div class="col-md-6">
                <label class="form-label" for="name">Product name</label>
                <input class="form-control" id="name" name="name" value="<?= h($product['name']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="category">Category</label>
                <input class="form-control" id="category" name="category" value="<?= h($product['category']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="brand">Brand</label>
                <input class="form-control" id="brand" name="brand" value="<?= h($product['brand']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="country_of_origin">Country of origin</label>
                <input class="form-control" id="country_of_origin" name="country_of_origin" list="originCountries" value="<?= h($product['country_of_origin'] ?? '') ?>" placeholder="e.g. United Arab Emirates">
                <datalist id="originCountries"><option value="United Arab Emirates"><option value="China"><option value="United States"><option value="Japan"><option value="South Korea"><option value="Taiwan"><option value="India"><option value="United Kingdom"><option value="Germany"></datalist>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="price">Price (TZS)</label>
                <input type="number" step="1" min="0" class="form-control" id="price" name="price" value="<?= h($product['price']) ?>" required>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="status">Status</label>
                <select class="form-select" id="status" name="status">
                    <option value="active" <?= $product['status'] === 'active' ? 'selected' : '' ?>>Active</option>
                    <option value="inactive" <?= $product['status'] === 'inactive' ? 'selected' : '' ?>>Inactive</option>
                </select>
            </div>
            <div class="col-md-4">
                <label class="form-label" for="image">Image URL</label>
                <input class="form-control" id="image" name="image" value="<?= h($product['image']) ?>" placeholder="Optional if you upload a file">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="image_file">Replace primary photo</label>
                <input type="file" class="form-control" id="image_file" name="image_file" accept=".jpg,.jpeg,.png,.webp">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="gallery_files">Upload extra photos</label>
                <input type="file" class="form-control" id="gallery_files" name="gallery_files[]" accept=".jpg,.jpeg,.png,.webp" multiple>
            </div>
            <?php if (!empty($product['gallery_images'])): ?>
                <div class="col-12">
                    <div class="product-gallery-preview">
                        <?php foreach ($product['gallery_images'] as $image): ?>
                            <img src="<?= h(public_url($image['image_path'])) ?>" alt="<?= h($product['name']) ?>">
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            <div class="col-12">
                <label class="form-label" for="description">Description</label>
                <textarea class="form-control" id="description" name="description" rows="4" placeholder="Short customer-facing summary of this product"><?= h($product['description']) ?></textarea>
            </div>
            <div class="col-12">
                <label class="form-label" for="specifications">Specifications</label>
                <textarea class="form-control" id="specifications" name="specifications" rows="4" placeholder="Processor, RAM, storage, operating system, accessories, warranty"><?= h($product['specifications'] ?? '') ?></textarea>
            </div>
        </div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">
                <i class="bi bi-save me-2"></i>Update Product
            </button>
        </div>
    </form>
</section>

<section class="panel mt-4">
    <div class="panel-header">
        <h2>Stock by Location</h2>
    </div>
    <?php foreach ($product['stock_by_location'] ?? [] as $stock): ?>
        <form id="stockForm<?= (int) $stock['location_id'] ?>" method="post" action="<?= h(url('products/updateStock/' . $product['id'])) ?>">
            <?= Auth::csrfField() ?>
            <input type="hidden" name="location_id" value="<?= (int) $stock['location_id'] ?>">
        </form>
    <?php endforeach; ?>
    <div class="table-responsive">
        <table class="table align-middle">
            <thead>
            <tr>
                <th>Location</th>
                <th>SKU</th>
                <th>Quantity</th>
                <th>Reorder level</th>
                <th>Shelf / bin</th>
                <th>Supplier</th>
                <th class="text-end">Action</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($product['stock_by_location'] ?? [] as $stock): ?>
                <?php $formId = 'stockForm' . (int) $stock['location_id']; ?>
                <tr>
                    <td>
                        <strong><?= h($stock['location_name']) ?></strong>
                        <small class="code-text d-block"><?= h($stock['location_code']) ?></small>
                    </td>
                    <td><input form="<?= h($formId) ?>" class="form-control form-control-sm" name="sku" value="<?= h($stock['sku'] ?? '') ?>" placeholder="<?= h($product['sku'] ?? '') ?>"></td>
                    <td><input form="<?= h($formId) ?>" type="number" min="0" class="form-control form-control-sm" name="quantity" value="<?= (int) ($stock['quantity'] ?? 0) ?>" style="max-width: 100px;"></td>
                    <td><input form="<?= h($formId) ?>" type="number" min="0" class="form-control form-control-sm" name="reorder_level" value="<?= (int) ($stock['reorder_level'] ?? 5) ?>" style="max-width: 100px;"></td>
                    <td><input form="<?= h($formId) ?>" class="form-control form-control-sm" name="location_label" value="<?= h($stock['location'] ?? '') ?>" placeholder="e.g. Shelf 3"></td>
                    <td><input form="<?= h($formId) ?>" class="form-control form-control-sm" name="supplier_name" value="<?= h($stock['supplier_name'] ?? '') ?>"></td>
                    <td class="text-end">
                        <button form="<?= h($formId) ?>" class="btn btn-sm btn-outline-primary" type="submit">
                            <i class="bi bi-save"></i> Save
                        </button>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($product['stock_by_location'])): ?>
                <tr><td colspan="7" class="text-center text-muted py-4">No active locations yet.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>
