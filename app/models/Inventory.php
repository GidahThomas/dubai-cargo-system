<?php

class Inventory extends Model
{
    public function all(): array
    {
        return $this->fetchAll(
            'SELECT i.*, p.name, p.category, p.brand, p.price, p.status
             FROM inventory i
             INNER JOIN products p ON p.id = i.product_id
             ORDER BY p.name ASC'
        );
    }

    public function findByProduct(int $productId, int $locationId): ?array
    {
        return $this->fetch(
            'SELECT * FROM inventory WHERE product_id = :product_id AND location_id = :location_id',
            ['product_id' => $productId, 'location_id' => $locationId]
        );
    }

    public function adjustStock(int $productId, int $locationId, int $quantityChange): bool
    {
        $updated = $this->execute(
            'UPDATE inventory
             SET quantity = GREATEST(quantity + :quantity_change, 0)
             WHERE product_id = :product_id AND location_id = :location_id',
            [
                'product_id' => $productId,
                'location_id' => $locationId,
                'quantity_change' => $quantityChange,
            ]
        );
        StockAlert::check($productId, $locationId);

        return $updated;
    }

    public function totalUnits(?int $locationId = null): int
    {
        if ($locationId !== null) {
            $row = $this->fetch(
                'SELECT COALESCE(SUM(quantity), 0) AS total FROM inventory WHERE location_id = :location_id',
                ['location_id' => $locationId]
            );

            return (int) $row['total'];
        }

        $row = $this->fetch('SELECT COALESCE(SUM(quantity), 0) AS total FROM inventory');

        return (int) $row['total'];
    }
}
