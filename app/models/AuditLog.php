<?php

class AuditLog extends Model
{
    public function create(?int $userId, string $action, ?string $tableName = null, ?int $recordId = null, ?string $details = null): bool
    {
        try {
            return $this->execute(
                'INSERT INTO audit_logs (user_id, action, table_name, record_id, ip_address, user_agent, details)
                 VALUES (:user_id, :action, :table_name, :record_id, :ip_address, :user_agent, :details)',
                [
                    'user_id' => $userId,
                    'action' => $action,
                    'table_name' => $tableName,
                    'record_id' => $recordId,
                    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                    'user_agent' => substr($_SERVER['HTTP_USER_AGENT'] ?? 'CLI', 0, 255),
                    'details' => $details,
                ]
            );
        } catch (Throwable) {
            return false;
        }
    }

    public function all(array $filters = []): array
    {
        $sql = 'SELECT a.*, u.name AS user_name, u.email AS user_email
                FROM audit_logs a
                LEFT JOIN users u ON u.id = a.user_id';
        $where = [];
        $params = [];

        if (!empty($filters['action'])) {
            $where[] = 'a.action LIKE :action';
            $params['action'] = '%' . $filters['action'] . '%';
        }

        if (!empty($filters['user_id'])) {
            $where[] = 'a.user_id = :user_id';
            $params['user_id'] = (int) $filters['user_id'];
        }

        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY a.created_at DESC LIMIT 100';

        return $this->fetchAll($sql, $params);
    }
}
