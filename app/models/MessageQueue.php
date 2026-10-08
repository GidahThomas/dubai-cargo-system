<?php

class MessageQueue extends Model
{
    /** Minutes to wait before each retry; after the last one the message is marked failed. */
    public const RETRY_MINUTES = [1, 5, 15, 60, 240];

    public function enqueue(string $channel, ?int $userId, string $recipient, string $title, string $body): int
    {
        $this->execute(
            'INSERT INTO message_queue (channel, user_id, recipient, title, body) VALUES (:channel, :user_id, :recipient, :title, :body)',
            [
                'channel' => $channel,
                'user_id' => $userId,
                'recipient' => mb_substr($recipient, 0, 32),
                'title' => mb_substr($title, 0, 190),
                'body' => $body,
            ]
        );

        return $this->lastInsertId();
    }

    public function due(int $limit = 50): array
    {
        return $this->fetchAll(
            'SELECT * FROM message_queue
             WHERE status = "pending" AND next_attempt_at <= NOW()
             ORDER BY next_attempt_at ASC, id ASC
             LIMIT ' . max(1, $limit)
        );
    }

    public function markSent(int $id): void
    {
        $this->execute(
            'UPDATE message_queue SET status = "sent", attempts = attempts + 1, sent_at = NOW(), last_error = NULL WHERE id = :id',
            ['id' => $id]
        );
    }

    public function markAttemptFailed(array $message, string $error): void
    {
        $attempts = (int) $message['attempts'] + 1;
        $retryIn = self::RETRY_MINUTES[$attempts - 1] ?? null;

        $this->execute(
            'UPDATE message_queue
             SET attempts = :attempts,
                 last_error = :error,
                 status = :status,
                 next_attempt_at = DATE_ADD(NOW(), INTERVAL :minutes MINUTE)
             WHERE id = :id',
            [
                'id' => (int) $message['id'],
                'attempts' => $attempts,
                'error' => mb_substr($error, 0, 500),
                'status' => $retryIn === null ? 'failed' : 'pending',
                'minutes' => $retryIn ?? 0,
            ]
        );
    }

    public function retryFailed(): int
    {
        $stmt = $this->db->prepare('UPDATE message_queue SET status = "pending", attempts = 0, next_attempt_at = NOW() WHERE status = "failed"');
        $stmt->execute();

        return $stmt->rowCount();
    }

    public function recent(int $limit = 200, ?string $status = null): array
    {
        $params = [];
        $where = '';
        if (in_array($status, ['pending', 'sent', 'failed'], true)) {
            $where = 'WHERE q.status = :status';
            $params['status'] = $status;
        }

        return $this->fetchAll(
            'SELECT q.*, u.name AS user_name
             FROM message_queue q
             LEFT JOIN users u ON u.id = q.user_id
             ' . $where . '
             ORDER BY q.created_at DESC, q.id DESC
             LIMIT ' . max(1, $limit),
            $params
        );
    }

    public function counts(): array
    {
        $rows = $this->fetchAll('SELECT status, COUNT(*) AS total FROM message_queue GROUP BY status');

        return array_merge(['pending' => 0, 'sent' => 0, 'failed' => 0], array_map('intval', array_column($rows, 'total', 'status')));
    }
}
