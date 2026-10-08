<?php

/**
 * Queues WhatsApp and SMS copies of a customer's notification. Nothing is sent during the
 * web request: tools/send_messages.php delivers the queue every minute and retries failures.
 */
class CustomerMessenger
{
    /**
     * @return int number of messages queued (0 when no channel is configured or the user is not a reachable customer)
     */
    public static function queue(int $userId, string $title, string $message): int
    {
        $channels = array_keys(array_filter(['whatsapp' => WhatsApp::isEnabled(), 'sms' => Sms::isEnabled()]));
        if (!$channels) {
            return 0;
        }

        try {
            $user = (new User())->findById($userId);
            if (!$user || ($user['role'] ?? '') !== 'customer' || ($user['status'] ?? '') !== 'active') {
                return 0;
            }

            $recipient = whatsapp_number((string) ($user['phone'] ?? ''));
            if ($recipient === '') {
                return 0;
            }

            $queue = new MessageQueue();
            foreach ($channels as $channel) {
                $body = $channel === 'sms' ? Sms::format($title, $message) : $message;
                $queue->enqueue($channel, $userId, $recipient, $title, $body);
            }

            return count($channels);
        } catch (Throwable $exception) {
            // Messaging must never break the action that triggered it.
            error_log('CustomerMessenger: ' . $exception->getMessage());
            return 0;
        }
    }

    /**
     * Delivers one queued message. Returns null on success, or the error text.
     */
    public static function deliver(array $message): ?string
    {
        $sent = match ($message['channel']) {
            'whatsapp' => WhatsApp::send($message['recipient'], $message['title'], $message['body']),
            'sms' => Sms::send($message['recipient'], $message['body']),
            default => false,
        };

        if ($sent) {
            return null;
        }

        return match ($message['channel']) {
            'whatsapp' => WhatsApp::$lastError ?: 'WhatsApp send failed',
            'sms' => Sms::$lastError ?: 'SMS send failed',
            default => 'Unknown channel',
        };
    }
}
