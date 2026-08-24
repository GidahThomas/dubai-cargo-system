<div class="page-header">
    <div>
        <p class="eyebrow">Computer inventory upload</p>
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
                <label class="form-label" for="price">Price (TZS)</label>
                <input type="number" step="1" min="0" class="form-control" id="price" name="price" value="<?= h($product['price']) ?>" required>
            </div>
            <div class="col-md-3">
                <label class="form-label" for="sku">SKU</label>
                <input class="form-control" id="sku" name="sku" value="<?= h($product['sku']) ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="quantity">Quantity</label>
                <input type="number" min="0" class="form-control" id="quantity" name="quantity" value="<?= (int) $product['quantity'] ?>">
            </div>
            <div class="col-md-3">
                <label class="form-label" for="reorder_level">Reorder level</label>
                <input type="number" min="0" class="form-control" id="reorder_level" name="reorder_level" value="<?= (int) $product['reorder_level'] ?>">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="location">Location</label>
                <input class="form-control" id="location" name="location" value="<?= h($product['location']) ?>" placeholder="e.g. Warehouse A, Shelf 3">
            </div>
            <div class="col-md-4">
                <label class="form-label" for="supplier_name">Supplier</label>
                <input class="form-control" id="supplier_name" name="supplier_name" value="<?= h($product['supplier_name']) ?>" placeholder="e.g. Dubai Tech Traders">
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
