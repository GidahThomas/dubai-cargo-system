<?php

abstract class Model
{
    protected PDO $db;

    public function __construct()
    {
        $this->db = Database::connect();
    }

    protected function fetch(string $sql, array $params = []): ?array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();

        return $row === false ? null : $row;
    }

    protected function fetchAll(string $sql, array $params = []): array
    {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll();
    }

    protected function execute(string $sql, array $params = []): bool
    {
        $stmt = $this->db->prepare($sql);

        return $stmt->execute($params);
    }

    protected function lastInsertId(): int
    {
        return (int) $this->db->lastInsertId();
    }
}
