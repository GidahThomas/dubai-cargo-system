<?php

class Customer extends Model
{
    public function all(array $filters = []): array
    {
        $sql = 'SELECT c.*, u.name, u.email, u.phone, u.address, u.status
                FROM customers c
                INNER JOIN users u ON u.id = c.user_id';
        $where = [];
        $params = [];

        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR c.customer_code LIKE :search_code OR c.city LIKE :search_city)';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_email'] = '%' . $filters['search'] . '%';
            $params['search_code'] = '%' . $filters['search'] . '%';
            $params['search_city'] = '%' . $filters['search'] . '%';
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY c.created_at DESC';

        return $this->fetchAll($sql, $params);
    }

    public function findByUserId(int $userId): ?array
    {
        return $this->fetch(
            'SELECT c.*, u.name, u.email, u.phone, u.address
             FROM customers c
             INNER JOIN users u ON u.id = c.user_id
             WHERE c.user_id = :user_id',
            ['user_id' => $userId]
        );
    }

    public function updateDetails(int $userId, array $data): bool
    {
        return $this->execute(
            'UPDATE customers SET city = :city, country = :country WHERE user_id = :user_id',
            [
                'user_id' => $userId,
                'city' => $data['city'] ?? null,
                'country' => $data['country'] ?? 'United Arab Emirates',
            ]
        );
    }
}
