<?php

class ProductController extends Controller
{
    public function index(): void
    {
        $filters = [
            'search' => $this->cleanString($this->get('search')),
            'name' => $this->cleanString($this->get('name')),
            'category' => $this->cleanString($this->get('category')),
            'brand' => $this->cleanString($this->get('brand')),
            'min_price' => $this->get('min_price'),
            'max_price' => $this->get('max_price'),
            'status' => $this->cleanString($this->get('status')),
        ];

        if (!Auth::check()) {
            $this->publicCatalogue($filters);
            return;
        }

        $activeLocationId = $this->activeLocationId();
        $products = (new Product())->all($filters, Auth::role() === 'customer', $activeLocationId);

        $this->view('products/index', [
            'pageTitle' => 'Products',
            'products' => $products,
            'filters' => $filters,
            'activeLocation' => $activeLocationId !== null ? (new Location())->find($activeLocationId) : null,
        ]);
    }

    /**
     * Public catalogue. With no category or filter: one section per category (capped, with
     * "View all"). With a category, search or filter: the matches, 24 per page.
     */
    private function publicCatalogue(array $filters): void
    {
        $perPage = 24;
        $productModel = new Product();
        $categories = $productModel->categories(50);
        $browsingAll = ($filters['category'] ?? '') === '' && ($filters['search'] ?? '') === '' && ($filters['brand'] ?? '') === ''
            && (string) ($filters['min_price'] ?? '') === '' && (string) ($filters['max_price'] ?? '') === '';

        if ($browsingAll) {
            $products = $productModel->all($filters, true);
            $total = count($products);
            $sections = Product::catalogGroups($products, $categories);
            $page = $pages = 1;
        } else {
            $total = $productModel->countAll($filters, true);
            $pages = max(1, (int) ceil($total / $perPage));
            $page = min($pages, max(1, (int) $this->get('page', 1)));
            $products = $productModel->all($filters, true, null, $perPage, ($page - 1) * $perPage);
            $sections = $products ? [[
                'name' => ($filters['category'] ?? '') !== '' ? $filters['category'] : 'Results',
                'products' => $products,
                'total' => $total,
                'featured' => ($filters['category'] ?? '') !== '' && $page === 1,
                'mixed' => ($filters['category'] ?? '') === '',
            ]] : [];
        }

        $this->view('public/products/index', [
            'layout' => 'public',
            'pageTitle' => 'Products',
            'sections' => $sections,
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'browsingAll' => $browsingAll,
            'categories' => $categories,
            'brands' => $productModel->brands(),
            'filters' => $filters,
        ]);
    }

    public function show(int $id): void
    {
        $product = (new Product())->find($id);

        if (!$product || (!Auth::check() && $product['status'] !== 'active')) {
            http_response_code(404);
            $this->view('public/not-found', [
                'layout' => 'public',
                'pageTitle' => 'Product Not Found',
            ]);
            return;
        }

        $this->view('public/products/show', [
            'layout' => Auth::check() ? 'app' : 'public',
            'pageTitle' => $product['name'],
            'product' => $product,
        ]);
    }

    public function ajaxSearch(): void
    {
        $this->requireAjaxLogin();

        $filters = [
            'search' => $this->cleanString($this->get('search')),
            'category' => $this->cleanString($this->get('category')),
            'brand' => $this->cleanString($this->get('brand')),
            'min_price' => $this->get('min_price'),
            'max_price' => $this->get('max_price'),
            'status' => $this->cleanString($this->get('status')),
        ];

        $this->json([
            'success' => true,
            'products' => (new Product())->searchForAjax($filters, Auth::role() === 'customer'),
        ]);
    }

    public function create(): void
    {
        $this->requireRole(['manager', 'admin']);

        $this->view('products/create', ['pageTitle' => 'Create Product']);
    }

    public function store(): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $data = $this->productPayload();

        if ($data['name'] === '' || $data['category'] === '' || $data['brand'] === '' || $data['price'] < 0) {
            flash('error', 'Name, category, brand, and valid price are required.');
            $this->redirect('products/create');
        }

        try {
            $productId = (new Product())->create($data, Auth::id(), $this->activeLocationId());
            (new AuditLog())->create(Auth::id(), 'product_created', 'products', $productId, $data['name']);
            flash('success', 'Product created successfully.');
            $this->redirect('products');
        } catch (Throwable $exception) {
            flash('error', 'Product could not be created: ' . $exception->getMessage());
            $this->redirect('products/create');
        }
    }

    public function edit(int $id): void
    {
        $this->requireRole(['manager', 'admin']);

        $product = (new Product())->find($id);

        if (!$product) {
            flash('error', 'Product not found.');
            $this->redirect('products');
        }

        $this->view('products/edit', [
            'pageTitle' => 'Edit Product',
            'product' => $product,
        ]);
    }

    public function update(int $id): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $existing = (new Product())->find($id);

        if (!$existing) {
            flash('error', 'Product not found.');
            $this->redirect('products');
        }

        $data = $this->productPayload($existing);

        try {
            (new Product())->update($id, $data);
            (new AuditLog())->create(Auth::id(), 'product_updated', 'products', $id, $data['name']);
            flash('success', 'Product updated successfully.');
            $this->redirect('products');
        } catch (Throwable $exception) {
            flash('error', 'Product could not be updated: ' . $exception->getMessage());
            $this->redirect('products/edit/' . $id);
        }
    }

    public function delete(int $id): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        (new Product())->setStatus($id, 'inactive');
        (new AuditLog())->create(Auth::id(), 'product_deactivated', 'products', $id);
        flash('success', 'Product marked as inactive.');
        $this->redirect('products');
    }

    public function updateStock(int $id): void
    {
        $this->requireRole(['manager', 'admin']);
        $this->validateCsrf();

        $locationId = (int) $this->post('location_id');

        if ($locationId < 1) {
            flash('error', 'Location is required.');
            $this->redirect('products/edit/' . $id);
        }

        $product = (new Product())->find($id);

        (new Product())->updateStockForLocation($id, $locationId, [
            'product_name' => $product['name'] ?? '',
            'sku' => $this->cleanString($this->post('sku')),
            'quantity' => (int) $this->post('quantity', 0),
            'reorder_level' => (int) $this->post('reorder_level', 5),
            'location' => $this->cleanString($this->post('location_label')),
            'supplier_name' => $this->cleanString($this->post('supplier_name')),
        ]);

        (new AuditLog())->create(Auth::id(), 'product_stock_updated', 'inventory', $id, 'Location #' . $locationId);
        flash('success', 'Stock updated for that location.');
        $this->redirect('products/edit/' . $id);
    }

    private function productPayload(?array $existing = null): array
    {
        $existingImage = $existing['image'] ?? null;
        $galleryImages = $this->handleGalleryUploads();
        $hasPrimaryUpload = isset($_FILES['image_file']) && ($_FILES['image_file']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_OK;
        $primaryImage = $this->handleImageUpload($existingImage);
        $primaryChoice = $this->cleanString($this->post('primary_image', 'primary'));

        if ($hasPrimaryUpload || $primaryChoice === '') {
            $primaryChoice = 'primary';
        }

        if (!$primaryImage && $galleryImages) {
            $primaryImage = $galleryImages[0];
        }

        return [
            'name' => $this->cleanString($this->post('name')),
            'category' => $this->cleanString($this->post('category')),
            'brand' => $this->cleanString($this->post('brand')),
            'country_of_origin' => mb_substr($this->cleanString($this->post('country_of_origin')), 0, 80),
            'description' => $this->cleanString($this->post('description')),
            'specifications' => $this->cleanString($this->post('specifications')),
            'price' => (float) $this->post('price', 0),
            'image' => $primaryImage,
            'gallery_images' => $galleryImages,
            'existing_gallery' => $this->existingGalleryPayload($existing['gallery_images'] ?? []),
            'primary_image_choice' => $primaryChoice,
            'status' => in_array($this->post('status'), ['active', 'inactive'], true) ? $this->post('status') : 'active',
            'sku' => $this->cleanString($this->post('sku')),
            'quantity' => max(0, (int) $this->post('quantity', 0)),
            'reorder_level' => max(0, (int) $this->post('reorder_level', 5)),
            'location' => $this->cleanString($this->post('location')),
            'supplier_name' => $this->cleanString($this->post('supplier_name')),
        ];
    }

    private function handleImageUpload(?string $existingImage = null): ?string
    {
        $imageText = $this->cleanString($this->post('image'));

        if (!isset($_FILES['image_file']) || $_FILES['image_file']['error'] === UPLOAD_ERR_NO_FILE) {
            return $imageText !== '' ? $imageText : $existingImage;
        }

        if ($_FILES['image_file']['error'] !== UPLOAD_ERR_OK) {
            return $existingImage;
        }

        $extension = strtolower(pathinfo($_FILES['image_file']['name'], PATHINFO_EXTENSION));
        $allowed = ['jpg', 'jpeg', 'png', 'webp'];

        if (!in_array($extension, $allowed, true)) {
            return $existingImage;
        }

        $uploadDir = ROOT_PATH . '/public/uploads';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $fileName = 'product-' . time() . '-' . random_int(1000, 9999) . '.' . $extension;
        $target = $uploadDir . '/' . $fileName;

        if (move_uploaded_file($_FILES['image_file']['tmp_name'], $target)) {
            Thumbnail::make('uploads/' . $fileName);
            return 'uploads/' . $fileName;
        }

        return $existingImage;
    }

    private function handleGalleryUploads(): array
    {
        if (!isset($_FILES['gallery_files']) || !is_array($_FILES['gallery_files']['name'] ?? null)) {
            return [];
        }

        $images = [];
        $uploadDir = ROOT_PATH . '/public/uploads';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        foreach ($_FILES['gallery_files']['name'] as $index => $name) {
            if (($_FILES['gallery_files']['error'][$index] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
                continue;
            }

            $extension = strtolower(pathinfo((string) $name, PATHINFO_EXTENSION));

            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
                continue;
            }

            $fileName = 'product-gallery-' . time() . '-' . random_int(1000, 9999) . '-' . (int) $index . '.' . $extension;
            $target = $uploadDir . '/' . $fileName;

            if (move_uploaded_file($_FILES['gallery_files']['tmp_name'][$index], $target)) {
                Thumbnail::make('uploads/' . $fileName);
                $images[] = 'uploads/' . $fileName;
            }
        }

        return $images;
    }

    private function existingGalleryPayload(array $currentImages): array
    {
        if (!$currentImages) {
            return [];
        }

        $ids = $this->post('gallery_existing_id', []);
        $captions = $this->post('gallery_caption', []);
        $sortOrders = $this->post('gallery_sort_order', []);
        $deleteFlags = $this->post('gallery_delete', []);
        $payload = [];

        foreach ($ids as $id) {
            $id = (int) $id;

            if ($id < 1) {
                continue;
            }

            $payload[] = [
                'id' => $id,
                'caption' => $this->cleanString((string) ($captions[$id] ?? '')),
                'sort_order' => (int) ($sortOrders[$id] ?? 0),
                'delete' => isset($deleteFlags[$id]),
                'replacement_path' => $this->handleGalleryReplacementUpload($id),
            ];
        }

        return $payload;
    }

    private function handleGalleryReplacementUpload(int $imageId): ?string
    {
        if (!isset($_FILES['gallery_replace']) || !is_array($_FILES['gallery_replace']['name'] ?? null)) {
            return null;
        }

        $error = $_FILES['gallery_replace']['error'][$imageId] ?? UPLOAD_ERR_NO_FILE;

        if ($error === UPLOAD_ERR_NO_FILE) {
            return null;
        }

        if ($error !== UPLOAD_ERR_OK) {
            return null;
        }

        $file = [
            'name' => $_FILES['gallery_replace']['name'][$imageId] ?? '',
            'tmp_name' => $_FILES['gallery_replace']['tmp_name'][$imageId] ?? '',
        ];

        return $this->storeUploadedImage($file, 'product-gallery-replace-' . $imageId);
    }

    private function storeUploadedImage(array $file, string $prefix): ?string
    {
        $extension = strtolower(pathinfo((string) ($file['name'] ?? ''), PATHINFO_EXTENSION));

        if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true)) {
            return null;
        }

        $uploadDir = ROOT_PATH . '/public/uploads';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $safePrefix = preg_replace('/[^a-z0-9-]+/i', '-', $prefix) ?: 'product-image';
        $fileName = strtolower($safePrefix) . '-' . time() . '-' . random_int(1000, 9999) . '.' . $extension;
        $target = $uploadDir . '/' . $fileName;

        if (move_uploaded_file((string) ($file['tmp_name'] ?? ''), $target)) {
            Thumbnail::make('uploads/' . $fileName);
            return 'uploads/' . $fileName;
        }

        return null;
    }
}
