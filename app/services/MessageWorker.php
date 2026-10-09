<?php

/**
 * Sends due WhatsApp/SMS messages from the queue. Used by tools/send_messages.php (Task Scheduler,
 * cPanel cron) and by the cron web address (hosts without a command line, e.g. Vercel).
 */
final class MessageWorker
{
    /**
     * @return array{sent: int, failed: int, skipped: bool} skipped = another worker was already running
     */
    public static function run(int $limit = 50): array
    {
        $db = Database::connect();

        // One worker at a time, even if a slow run overlaps the next scheduled one.
        if (!self::lock($db)) {
            return ['sent' => 0, 'failed' => 0, 'skipped' => true];
        }

        $queue = new MessageQueue();
        $sent = 0;
        $failed = 0;

        try {
            foreach ($queue->due($limit) as $message) {
                $error = CustomerMessenger::deliver($message);
                if ($error === null) {
                    $queue->markSent((int) $message['id']);
                    $sent++;
                } else {
                    $queue->markAttemptFailed($message, $error);
                    $failed++;
                }
            }
        } finally {
            self::unlock($db);
        }

        return ['sent' => $sent, 'failed' => $failed, 'skipped' => false];
    }

    private static function lock(PDO $db): bool
    {
        try {
            return (int) $db->query("SELECT GET_LOCK('dcf_message_worker', 0)")->fetchColumn() === 1;
        } catch (PDOException) {
            // Databases without named locks (some cloud MySQL variants): run unlocked.
            return true;
        }
    }

    private static function unlock(PDO $db): void
    {
        try {
            $db->query("SELECT RELEASE_LOCK('dcf_message_worker')");
        } catch (PDOException) {
        }
    }
}
