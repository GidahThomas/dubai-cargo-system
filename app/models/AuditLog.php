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

    public function recentFailedLogins(string $ipAddress, int $minutes): int
    {
        $minutes = max(1, $minutes);

        $row = $this->fetch(
            'SELECT COUNT(*) AS total
             FROM audit_logs
             WHERE action = "login_failed"
               AND ip_address = :ip_address
               AND created_at >= (NOW() - INTERVAL ' . $minutes . ' MINUTE)',
            ['ip_address' => $ipAddress]
        );

        return (int) ($row['total'] ?? 0);
    }

    public function all(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        [$where, $params] = $this->filterClause($filters);

        return $this->fetchAll(
            'SELECT a.*, u.name AS user_name, u.email AS user_email, u.role AS user_role
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id'
            . $where
            . ' ORDER BY a.created_at DESC, a.id DESC LIMIT ' . max(1, $limit) . ' OFFSET ' . max(0, $offset),
            $params
        );
    }

    public function count(array $filters = []): int
    {
        [$where, $params] = $this->filterClause($filters);
        $row = $this->fetch('SELECT COUNT(*) AS total FROM audit_logs a' . $where, $params);

        return (int) ($row['total'] ?? 0);
    }

    /**
     * @return string[] every action name that has been logged, for the filter dropdown
     */
    public function actions(): array
    {
        return array_column($this->fetchAll('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC'), 'action');
    }

    private function filterClause(array $filters): array
    {
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

        if (!empty($filters['date_from'])) {
            $where[] = 'a.created_at >= :date_from';
            $params['date_from'] = $filters['date_from'] . ' 00:00:00';
        }

        if (!empty($filters['date_to'])) {
            $where[] = 'a.created_at <= :date_to';
            $params['date_to'] = $filters['date_to'] . ' 23:59:59';
        }

        if (!empty($filters['search'])) {
            $where[] = '(a.details LIKE :search OR a.ip_address LIKE :search_ip)';
            $params['search'] = '%' . $filters['search'] . '%';
            $params['search_ip'] = '%' . $filters['search'] . '%';
        }

        return [$where ? ' WHERE ' . implode(' AND ', $where) : '', $params];
    }
}
