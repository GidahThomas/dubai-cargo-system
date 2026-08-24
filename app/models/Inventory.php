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

    public function findByProduct(int $productId): ?array
    {
        return $this->fetch(
            'SELECT * FROM inventory WHERE product_id = :product_id',
            ['product_id' => $productId]
        );
    }

    public function adjustStock(int $productId, int $quantityChange): bool
    {
        return $this->execute(
            'UPDATE inventory
             SET quantity = GREATEST(quantity + :quantity_change, 0)
             WHERE product_id = :product_id',
            [
                'product_id' => $productId,
                'quantity_change' => $quantityChange,
            ]
        );
    }

    public function totalUnits(): int
    {
        $row = $this->fetch('SELECT COALESCE(SUM(quantity), 0) AS total FROM inventory');

        return (int) $row['total'];
    }
}
