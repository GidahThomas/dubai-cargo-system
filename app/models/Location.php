<?php

class Location extends Model
{
    public function all(bool $activeOnly = false): array
    {
        $sql = 'SELECT * FROM locations';

        if ($activeOnly) {
            $sql .= ' WHERE is_active = 1';
        }

        $sql .= ' ORDER BY name ASC';

        return $this->fetchAll($sql);
    }

    public function find(int $id): ?array
    {
        return $this->fetch('SELECT * FROM locations WHERE id = :id', ['id' => $id]);
    }

    public function create(array $data): int
    {
        $this->execute(
            'INSERT INTO locations (name, code, address, phone, is_active)
             VALUES (:name, :code, :address, :phone, :is_active)',
            [
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'address' => ($data['address'] ?? null) ?: null,
                'phone' => ($data['phone'] ?? null) ?: null,
                'is_active' => 1,
            ]
        );

        return $this->lastInsertId();
    }

    public function update(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE locations SET name = :name, code = :code, address = :address, phone = :phone WHERE id = :id',
            [
                'id' => $id,
                'name' => $data['name'],
                'code' => strtoupper($data['code']),
                'address' => ($data['address'] ?? null) ?: null,
                'phone' => ($data['phone'] ?? null) ?: null,
            ]
        );
    }

    public function setStatus(int $id, bool $isActive): bool
    {
        return $this->execute(
            'UPDATE locations SET is_active = :is_active WHERE id = :id',
            ['id' => $id, 'is_active' => $isActive ? 1 : 0]
        );
    }

    public function seedInventoryRow(int $productId, int $locationId): bool
    {
        return $this->execute(
            'INSERT IGNORE INTO inventory (product_id, location_id, sku, quantity, reorder_level)
             SELECT :product_id, :location_id, sku, 0, 5
             FROM inventory WHERE product_id = :product_id_where LIMIT 1',
            ['product_id' => $productId, 'location_id' => $locationId, 'product_id_where' => $productId]
        );
    }
}
