<?php

class User extends Model
{
    public function findByEmail(string $email): ?array
    {
        return $this->fetch('SELECT * FROM users WHERE email = :email LIMIT 1', [
            'email' => $email,
        ]);
    }

    public function findById(int $id): ?array
    {
        return $this->fetch('SELECT * FROM users WHERE id = :id LIMIT 1', [
            'id' => $id,
        ]);
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT u.*, c.customer_code, c.city, c.country
                FROM users u
                LEFT JOIN customers c ON c.user_id = u.id';
        $where = [];
        $params = [];

        if (!empty($filters['role'])) {
            $where[] = 'u.role = :role';
            $params['role'] = $filters['role'];
        }

        if (!empty($filters['search'])) {
            $where[] = '(u.name LIKE :search_name OR u.email LIKE :search_email OR u.phone LIKE :search_phone)';
            $params['search_name'] = '%' . $filters['search'] . '%';
            $params['search_email'] = '%' . $filters['search'] . '%';
            $params['search_phone'] = '%' . $filters['search'] . '%';
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY u.created_at DESC';

        return $this->fetchAll($sql, $params);
    }

    public function create(array $data): int
    {
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO users (name, email, password, role, phone, address, status)
                 VALUES (:name, :email, :password, :role, :phone, :address, :status)'
            );

            $stmt->execute([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => password_hash($data['password'], PASSWORD_DEFAULT),
                'role' => $data['role'] ?? 'customer',
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
                'status' => $data['status'] ?? 'active',
            ]);

            $userId = (int) $this->db->lastInsertId();

            if (($data['role'] ?? 'customer') === 'customer') {
                $customerCode = 'CUS-' . date('Y') . '-' . str_pad((string) $userId, 4, '0', STR_PAD_LEFT);
                $customerStmt = $this->db->prepare(
                    'INSERT INTO customers (user_id, customer_code, city, country)
                     VALUES (:user_id, :customer_code, :city, :country)'
                );
                $customerStmt->execute([
                    'user_id' => $userId,
                    'customer_code' => $customerCode,
                    'city' => $data['city'] ?? null,
                    'country' => $data['country'] ?? 'United Arab Emirates',
                ]);
            }

            $this->db->commit();

            return $userId;
        } catch (Throwable $exception) {
            $this->db->rollBack();
            throw $exception;
        }
    }

    public function update(int $id, array $data): bool
    {
        $fields = [
            'name = :name',
            'email = :email',
            'role = :role',
            'phone = :phone',
            'address = :address',
            'status = :status',
        ];

        $params = [
            'id' => $id,
            'name' => $data['name'],
            'email' => $data['email'],
            'role' => $data['role'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'status' => $data['status'] ?? 'active',
        ];

        if (!empty($data['password'])) {
            $fields[] = 'password = :password';
            $params['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
        }

        return $this->execute(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = :id',
            $params
        );
    }

    public function updateProfile(int $id, array $data): bool
    {
        return $this->execute(
            'UPDATE users
             SET name = :name, phone = :phone, address = :address
             WHERE id = :id',
            [
                'id' => $id,
                'name' => $data['name'],
                'phone' => $data['phone'] ?? null,
                'address' => $data['address'] ?? null,
            ]
        );
    }

    public function updatePassword(int $id, string $password): bool
    {
        return $this->execute(
            'UPDATE users SET password = :password WHERE id = :id',
            [
                'id' => $id,
                'password' => password_hash($password, PASSWORD_DEFAULT),
            ]
        );
    }

    public function setStatus(int $id, string $status): bool
    {
        return $this->execute(
            'UPDATE users SET status = :status WHERE id = :id',
            ['id' => $id, 'status' => $status]
        );
    }

    public function byRoles(array $roles): array
    {
        $placeholders = [];
        $params = [];

        foreach ($roles as $index => $role) {
            $key = 'role' . $index;
            $placeholders[] = ':' . $key;
            $params[$key] = $role;
        }

        return $this->fetchAll(
            'SELECT * FROM users WHERE role IN (' . implode(',', $placeholders) . ') AND status = "active"',
            $params
        );
    }

    public function countByRole(?string $role = null): int
    {
        if ($role === null) {
            $row = $this->fetch('SELECT COUNT(*) AS total FROM users');
            return (int) $row['total'];
        }

        $row = $this->fetch('SELECT COUNT(*) AS total FROM users WHERE role = :role', [
            'role' => $role,
        ]);

        return (int) $row['total'];
    }
}
