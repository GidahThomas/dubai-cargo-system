<?php

class Notification extends Model
{
    public function create(int $userId, string $title, string $message, string $type = 'info'): bool
    {
        return $this->execute(
            'INSERT INTO notifications (user_id, title, message, type)
             VALUES (:user_id, :title, :message, :type)',
            [
                'user_id' => $userId,
                'title' => $title,
                'message' => $message,
                'type' => $type,
            ]
        );
    }

    public function forUser(int $userId, int $limit = 10): array
    {
        return $this->fetchAll(
            'SELECT *
             FROM notifications
             WHERE user_id = :user_id
             ORDER BY created_at DESC
             LIMIT ' . (int) $limit,
            ['user_id' => $userId]
        );
    }

    public function unreadCount(int $userId): int
    {
        $row = $this->fetch(
            'SELECT COUNT(*) AS total FROM notifications WHERE user_id = :user_id AND is_read = 0',
            ['user_id' => $userId]
        );

        return (int) $row['total'];
    }

    public function markAllRead(int $userId): bool
    {
        return $this->execute(
            'UPDATE notifications SET is_read = 1 WHERE user_id = :user_id',
            ['user_id' => $userId]
        );
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        return $this->execute(
            'UPDATE notifications SET is_read = 1 WHERE id = :id AND user_id = :user_id',
            ['id' => $notificationId, 'user_id' => $userId]
        );
    }
}
