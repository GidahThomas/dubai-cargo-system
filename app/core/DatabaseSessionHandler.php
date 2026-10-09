<?php

/**
 * Stores PHP sessions (logins, carts, CSRF tokens) in the `sessions` table instead of files,
 * so they survive on hosts where each request may run on a different server.
 * Enabled with SESSION_DRIVER=database (see Auth::startSecureSession).
 */
class DatabaseSessionHandler implements SessionHandlerInterface
{
    public function __construct(private PDO $db, private int $lifetimeSeconds = 7200)
    {
    }

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->db->prepare('SELECT data, last_activity FROM sessions WHERE id = ?');
        $stmt->execute([$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row || (int) $row['last_activity'] < time() - $this->lifetimeSeconds) {
            return '';
        }

        return (string) $row['data'];
    }

    public function write(string $id, string $data): bool
    {
        $stmt = $this->db->prepare(
            'INSERT INTO sessions (id, data, last_activity) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE data = VALUES(data), last_activity = VALUES(last_activity)'
        );

        return $stmt->execute([$id, $data, time()]);
    }

    public function destroy(string $id): bool
    {
        return $this->db->prepare('DELETE FROM sessions WHERE id = ?')->execute([$id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE last_activity < ?');
        $stmt->execute([time() - max($max_lifetime, $this->lifetimeSeconds)]);

        return $stmt->rowCount();
    }
}
