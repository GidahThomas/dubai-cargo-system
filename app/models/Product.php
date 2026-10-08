<?php

class Product extends Model
{
    /**
     * @param int|null $limit page size; null returns every match
     */
    public function all(array $filters = [], bool $activeOnly = false, ?int $locationId = null, ?int $limit = null, int $offset = 0): array
    {
        [$sql, $params] = $this->listQuery($filters, $activeOnly, $locationId);

        if ($limit !== null) {
            $sql .= ' LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset);
        }

        return $this->fetchAll($sql, $params);
    }

    /**
     * Groups products into catalogue sections, in the order of $categories (largest first).
     * A category with at least $featureMinimum products gets its own section (first product shown
     * large), capped at $perSection; smaller categories share one "More Products" section capped at
     * $moreLimit. Each section reports its full 'total' so the page can offer "View all".
     *
     * @return array<int, array{name: string, products: array, total: int, featured: bool, mixed: bool}>
     */
    public static function catalogGroups(array $products, array $categories, int $featureMinimum = 3, int $perSection = 7, int $moreLimit = 12): array
    {
        $key = static fn (?string $name): string => strtolower(trim((string) $name));
        $groups = [];
        foreach ($categories as $category) {
            $groups[$key($category['name'])] = ['name' => $category['name'], 'products' => []];
        }
        foreach ($products as $product) {
            $groupKey = $key($product['category'] ?? '');
            $groups[$groupKey] ??= ['name' => ($product['category'] ?? '') ?: 'Other', 'products' => []];
            $groups[$groupKey]['products'][] = $product;
        }

        $sections = [];
        $small = [];
        foreach ($groups as $group) {
            $count = count($group['products']);
            if ($count === 0) {
                continue;
            }
            if ($count >= $featureMinimum) {
                $sections[] = ['name' => $group['name'], 'products' => array_slice($group['products'], 0, $perSection), 'total' => $count, 'featured' => true, 'mixed' => false];
            } else {
                $small[] = $group;
            }
        }

        if (count($small) === 1 && !$sections) {
            $only = $small[0];
            $sections[] = ['name' => $only['name'], 'products' => $only['products'], 'total' => count($only['products']), 'featured' => false, 'mixed' => false];
        } elseif ($small) {
            $merged = array_merge(...array_column($small, 'products'));
            $sections[] = ['name' => $sections ? 'More Products' : 'All Products', 'products' => array_slice($merged, 0, $moreLimit), 'total' => count($merged), 'featured' => false, 'mixed' => true];
        }

        return $sections;
    }

    public function countAll(array $filters = [], bool $activeOnly = false, ?int $locationId = null): int
    {
        [$sql, $params] = $this->listQuery($filters, $activeOnly, $locationId);
        $row = $this->fetch('SELECT COUNT(*) AS total FROM (' . $sql . ') matches', $params);

        return (int) ($row['total'] ?? 0);
    }

    private function listQuery(array $filters, bool $activeOnly, ?int $locationId): array
    {
        $joinCondition = 'i.product_id = p.id';
        $groupBy = '';
        $inventorySelect = 'i.sku, i.quantity, i.reorder_level, i.location, i.supplier_name';
        $params = [];

        if ($locationId !== null) {
            $joinCondition .= ' AND i.location_id = :join_location_id';
            $params['join_location_id'] = $locationId;
        } else {
            $inventorySelect = 'MAX(i.sku) AS sku, COALESCE(SUM(i.quantity), 0) AS quantity,
                MAX(i.reorder_level) AS reorder_level, MAX(i.location) AS location, MAX(i.supplier_name) AS supplier_name';
            $groupBy = ' GROUP BY p.id';
        }

        $sql = "SELECT p.*, {$inventorySelect}
                FROM products p
                LEFT JOIN inventory i ON {$joinCondition}";
        $where = [];

        if ($activeOnly) {
            $where[] = 'p.status = "active"';
        }

        if (!empty($filters['search'])) {
            $where[] = '(p.name LIKE :search_name OR p.category LIKE :search_category OR p.brand LIKE :search_brand OR i.sku LIKE :search_sku)';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_category'] = '%' . $filters['search'] . '%';
            $params['search_brand'] = '%' . $filters['search'] . '%';
            $params['search_sku'] = '%' . $filters['search'] . '%';
        }

        if (!empty($filters['name'])) {
            $where[] = 'p.name LIKE :name';
            $params['name'] = '%' . $filters['name'] . '%';
        }

        if (!empty($filters['category'])) {
            $where[] = 'p.category LIKE :category';
            $params['category'] = '%' . $filters['category'] . '%';
        }

        if (!empty($filters['brand'])) {
            $where[] = 'p.brand LIKE :brand';
            $params['brand'] = '%' . $filters['brand'] . '%';
        }

        if (($filters['min_price'] ?? null) !== null && ($filters['min_price'] ?? '') !== '') {
            $where[] = 'p.price >= :min_price';
            $params['min_price'] = (float) $filters['min_price'];
        }

        if (($filters['max_price'] ?? null) !== null && ($filters['max_price'] ?? '') !== '') {
            $where[] = 'p.price <= :max_price';
            $params['max_price'] = (float) $filters['max_price'];
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['active', 'inactive'], true)) {
            $where[] = 'p.status = :status';
            $params['status'] = $filters['status'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= $groupBy . ' ORDER BY p.created_at DESC, p.id DESC';

        return [$sql, $params];
    }

    public function find(int $id, ?int $locationId = null): ?array
    {
        if ($locationId !== null) {
            $product = $this->fetch(
                'SELECT p.*, i.sku, i.quantity, i.reorder_level, i.location, i.supplier_name
                 FROM products p
                 LEFT JOIN inventory i ON i.product_id = p.id AND i.location_id = :location_id
                 WHERE p.id = :id',
                ['id' => $id, 'location_id' => $locationId]
            );
        } else {
            $product = $this->fetch(
                'SELECT p.*, MAX(i.sku) AS sku, COALESCE(SUM(i.quantity), 0) AS quantity,
                        MAX(i.reorder_level) AS reorder_level, MAX(i.location) AS location, MAX(i.supplier_name) AS supplier_name
                 FROM products p
                 LEFT JOIN inventory i ON i.product_id = p.id
                 WHERE p.id = :id
                 GROUP BY p.id',
                ['id' => $id]
            );
        }

        if ($product) {
            $product['gallery_images'] = $this->images($id);
            $product['stock_by_location'] = $this->stockByLocation($id);
        }

        return $product;
    }

    public function stockByLocation(int $productId): array
    {
        return $this->fetchAll(
            'SELECT l.id AS location_id, l.name AS location_name, l.code AS location_code,
                    i.id AS inventory_id, i.sku, i.quantity, i.reorder_level, i.location, i.supplier_name
             FROM locations l
             LEFT JOIN inventory i ON i.location_id = l.id AND i.product_id = :product_id
             WHERE l.is_active = 1
             ORDER BY l.name ASC',
            ['product_id' => $productId]
        );
    }

    public function updateStockForLocation(int $productId, int $locationId, array $data): bool
    {
        $updated = $this->execute(
            'INSERT INTO inventory (product_id, location_id, sku, quantity, reorder_level, location, supplier_name)
             VALUES (:product_id, :location_id, :sku, :quantity, :reorder_level, :location_label, :supplier_name)
             ON DUPLICATE KEY UPDATE
                sku = VALUES(sku),
                quantity = VALUES(quantity),
                reorder_level = VALUES(reorder_level),
                location = VALUES(location),
                supplier_name = VALUES(supplier_name)',
            [
                'product_id' => $productId,
                'location_id' => $locationId,
                'sku' => ($data['sku'] ?? null) ?: $this->makeSku((string) ($data['product_name'] ?? ''), $productId),
                'quantity' => max(0, (int) ($data['quantity'] ?? 0)),
                'reorder_level' => max(0, (int) ($data['reorder_level'] ?? 5)),
                'location_label' => ($data['location'] ?? null) ?: null,
                'supplier_name' => ($data['supplier_name'] ?? null) ?: null,
            ]
        );
        StockAlert::check($productId, $locationId);

        return $updated;
    }

    public function create(array $data, int $userId, ?int $stockingLocationId = null): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO products (name, category, brand, country_of_origin, description, specifications, price, image, status, created_by)
                 VALUES (:name, :category, :brand, :country_of_origin, :description, :specifications, :price, :image, :status, :created_by)'
            );

            $stmt->execute([
                'name' => $data['name'],
                'category' => $data['category'],
                'brand' => $data['brand'],
                'country_of_origin' => ($data['country_of_origin'] ?? null) ?: null,
                'description' => $data['description'] ?? null,
                'specifications' => $data['specifications'] ?? null,
                'price' => (float) $data['price'],
                'image' => $data['image'] ?? null,
                'status' => $data['status'] ?? 'active',
                'created_by' => $userId,
            ]);

            $productId = (int) $this->db->lastInsertId();
            $sku = ($data['sku'] ?? null) ?: $this->makeSku($data['name'], $productId);

            $locationIds = array_column($this->fetchAll('SELECT id FROM locations WHERE is_active = 1'), 'id');
            $stockingLocationId ??= $locationIds[0] ?? null;

            $inventory = $this->db->prepare(
                'INSERT INTO inventory (product_id, location_id, sku, quantity, reorder_level, location, supplier_name)
                 VALUES (:product_id, :location_id, :sku, :quantity, :reorder_level, :location, :supplier_name)'
            );

            foreach ($locationIds as $locationId) {
                $inventory->execute([
                    'product_id' => $productId,
                    'location_id' => $locationId,
                    'sku' => $sku,
                    'quantity' => (int) $locationId === (int) $stockingLocationId ? (int) ($data['quantity'] ?? 0) : 0,
                    'reorder_level' => (int) ($data['reorder_level'] ?? 5),
                    'location' => $data['location'] ?? null,
                    'supplier_name' => $data['supplier_name'] ?? null,
                ]);
            }

            $primaryImage = $this->syncGalleryImages($productId, $data);
            $this->execute(
                'UPDATE products SET image = :image WHERE id = :id',
                ['id' => $productId, 'image' => $primaryImage ?: ($data['image'] ?? null)]
            );

            $this->db->commit();

            return $productId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update(int $id, array $data): bool
    {
        $this->db->beginTransaction();

        try {
            $product = $this->db->prepare(
                'UPDATE products
                 SET name = :name, category = :category, brand = :brand, country_of_origin = :country_of_origin, description = :description,
                     specifications = :specifications,
                     price = :price, image = :image, status = :status
                 WHERE id = :id'
            );

            $product->execute([
                'id' => $id,
                'name' => $data['name'],
                'category' => $data['category'],
                'brand' => $data['brand'],
                'country_of_origin' => ($data['country_of_origin'] ?? null) ?: null,
                'description' => $data['description'] ?? null,
                'specifications' => $data['specifications'] ?? null,
                'price' => (float) $data['price'],
                'image' => $data['image'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            $primaryImage = $this->syncGalleryImages($id, $data);
            $this->execute(
                'UPDATE products SET image = :image WHERE id = :id',
                ['id' => $id, 'image' => $primaryImage ?: ($data['image'] ?? null)]
            );

            $this->db->commit();

            return true;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function setStatus(int $id, string $status): bool
    {
        return $this->execute(
            'UPDATE products SET status = :status WHERE id = :id',
            ['id' => $id, 'status' => $status]
        );
    }

    public function categories(int $limit = 8): array
    {
        return $this->fetchAll(
            'SELECT MAX(p.category) AS name, COUNT(*) AS total
             FROM products p
             WHERE p.status = "active" AND p.category IS NOT NULL AND p.category != ""
             GROUP BY LOWER(TRIM(p.category))
             ORDER BY total DESC, name ASC
             LIMIT ' . (int) $limit
        );
    }

    public function brands(): array
    {
        return array_column($this->fetchAll(
            'SELECT MAX(brand) AS brand
             FROM products
             WHERE status = "active" AND brand IS NOT NULL AND brand != ""
             GROUP BY LOWER(TRIM(brand))
             ORDER BY brand ASC'
        ), 'brand');
    }

    public function countActive(): int
    {
        $row = $this->fetch('SELECT COUNT(*) AS total FROM products WHERE status = "active"');

        return (int) $row['total'];
    }

    public function lowStock(int $limit = 10, ?int $locationId = null): array
    {
        $params = [];
        $where = 'i.quantity <= i.reorder_level';

        if ($locationId !== null) {
            $where .= ' AND i.location_id = :location_id';
            $params['location_id'] = $locationId;
        }

        return $this->fetchAll(
            'SELECT p.name, p.brand, i.sku, i.quantity, i.reorder_level
             FROM inventory i
             INNER JOIN products p ON p.id = i.product_id
             WHERE ' . $where . '
             ORDER BY i.quantity ASC
             LIMIT ' . (int) $limit,
            $params
        );
    }

    public function searchForAjax(array $filters, bool $activeOnly = false): array
    {
        $products = $this->all($filters, $activeOnly);

        return array_map(static fn (array $product): array => [
            'id' => (int) $product['id'],
            'name' => $product['name'],
            'category' => $product['category'],
            'brand' => $product['brand'],
            'description' => $product['description'] ?? '',
            'specifications' => $product['specifications'] ?? '',
            'price' => (float) $product['price'],
            'image' => $product['image'] ?? '',
            'quantity' => (int) ($product['quantity'] ?? 0),
            'status' => $product['status'],
            'sku' => $product['sku'] ?? '',
        ], $products);
    }

    public function images(int $productId): array
    {
        $images = $this->fetchAll(
            'SELECT id, image_path, caption, is_primary, sort_order
             FROM product_images
             WHERE product_id = :product_id
             ORDER BY is_primary DESC, sort_order ASC, id ASC',
            ['product_id' => $productId]
        );

        if (!$images) {
            $product = $this->fetch('SELECT image, name FROM products WHERE id = :id', ['id' => $productId]);

            if (!empty($product['image'])) {
                return [[
                    'id' => null,
                    'image_path' => $product['image'],
                    'caption' => $product['name'] ?? null,
                    'is_primary' => 1,
                    'sort_order' => 0,
                ]];
            }
        }

        return $images;
    }

    private function insertGalleryImages(int $productId, ?string $primaryImage, array $galleryImages): void
    {
        $this->syncGalleryImages($productId, [
            'image' => $primaryImage,
            'gallery_images' => $galleryImages,
            'primary_image_choice' => 'primary',
        ]);
    }

    private function syncGalleryImages(int $productId, array $data): ?string
    {
        $current = $this->fetchAll(
            'SELECT id, image_path, is_primary, sort_order
             FROM product_images
             WHERE product_id = :product_id
             ORDER BY is_primary DESC, sort_order ASC, id ASC',
            ['product_id' => $productId]
        );
        $currentById = [];

        foreach ($current as $image) {
            $currentById[(int) $image['id']] = $image;
        }

        $this->execute('UPDATE product_images SET is_primary = 0 WHERE product_id = :product_id', ['product_id' => $productId]);

        $primaryChoice = (string) ($data['primary_image_choice'] ?? '');
        $primaryPath = null;
        $seenPaths = [];
        $sort = 0;

        foreach ($data['existing_gallery'] ?? [] as $gallery) {
            $id = (int) ($gallery['id'] ?? 0);

            if ($id < 1 || !isset($currentById[$id])) {
                continue;
            }

            if (!empty($gallery['delete'])) {
                $this->execute(
                    'DELETE FROM product_images WHERE id = :id AND product_id = :product_id',
                    ['id' => $id, 'product_id' => $productId]
                );
                continue;
            }

            $imagePath = trim((string) ($gallery['replacement_path'] ?? ''));

            if ($imagePath === '') {
                $imagePath = (string) $currentById[$id]['image_path'];
            }

            $isPrimary = $primaryChoice === 'existing:' . $id ? 1 : 0;
            $sortOrder = (int) ($gallery['sort_order'] ?? $sort);

            $this->execute(
                'UPDATE product_images
                 SET image_path = :image_path, caption = :caption, is_primary = :is_primary, sort_order = :sort_order
                 WHERE id = :id AND product_id = :product_id',
                [
                    'id' => $id,
                    'product_id' => $productId,
                    'image_path' => $imagePath,
                    'caption' => trim((string) ($gallery['caption'] ?? '')) ?: null,
                    'is_primary' => $isPrimary,
                    'sort_order' => $sortOrder,
                ]
            );

            if ($isPrimary) {
                $primaryPath = $imagePath;
            }

            $seenPaths[$imagePath] = true;
            $sort = max($sort, $sortOrder + 1);
        }

        $mainImage = trim((string) ($data['image'] ?? ''));

        if ($mainImage !== '' && ($primaryChoice === 'primary' || !isset($seenPaths[$mainImage]))) {
            $isPrimary = ($primaryChoice === 'primary' || $primaryPath === null) ? 1 : 0;
            $this->upsertGalleryImage($productId, $mainImage, null, $isPrimary, $isPrimary ? 0 : $sort);

            if ($isPrimary) {
                $primaryPath = $mainImage;
            }

            $sort++;
        }

        foreach (($data['gallery_images'] ?? []) as $index => $image) {
            $image = trim((string) $image);

            if ($image !== '') {
                $choice = 'new:' . $index;
                $isPrimary = ($primaryChoice === $choice || $primaryPath === null) ? 1 : 0;
                $this->upsertGalleryImage($productId, $image, null, $isPrimary, $sort);

                if ($isPrimary) {
                    $primaryPath = $image;
                }

                $sort++;
            }
        }

        if ($primaryPath === null) {
            $fallback = $this->fetch(
                'SELECT id, image_path
                 FROM product_images
                 WHERE product_id = :product_id
                 ORDER BY sort_order ASC, id ASC
                 LIMIT 1',
                ['product_id' => $productId]
            );

            if ($fallback) {
                $this->execute(
                    'UPDATE product_images SET is_primary = 1 WHERE id = :id AND product_id = :product_id',
                    ['id' => (int) $fallback['id'], 'product_id' => $productId]
                );
                $primaryPath = (string) $fallback['image_path'];
            }
        }

        return $primaryPath;
    }

    private function upsertGalleryImage(int $productId, string $path, ?string $caption, int $isPrimary, int $sortOrder): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO product_images (product_id, image_path, caption, is_primary, sort_order)
             VALUES (:product_id, :image_path, :caption, :is_primary, :sort_order)
             ON DUPLICATE KEY UPDATE
                caption = VALUES(caption),
                is_primary = VALUES(is_primary),
                sort_order = VALUES(sort_order)'
        );

        $stmt->execute([
            'product_id' => $productId,
            'image_path' => $path,
            'caption' => $caption,
            'is_primary' => $isPrimary,
            'sort_order' => $sortOrder,
        ]);
    }

    private function makeSku(string $name, int $id): string
    {
        $prefix = strtoupper(preg_replace('/[^A-Z0-9]+/i', '-', $name));
        $prefix = trim(substr($prefix, 0, 18), '-');

        return ($prefix ?: 'PRODUCT') . '-' . str_pad((string) $id, 4, '0', STR_PAD_LEFT);
    }
}
