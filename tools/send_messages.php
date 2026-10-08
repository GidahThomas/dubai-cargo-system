<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * Sends queued WhatsApp and SMS messages. Scheduled every minute (see tools/schedule_tasks.ps1).
 *
 *   C:\xampp\php\php.exe tools\send_messages.php
 */

require __DIR__ . '/bootstrap.php';

$db = Database::connect();

// One worker at a time, even if a slow run overlaps the next scheduled one.
if ((int) $db->query("SELECT GET_LOCK('dcf_message_worker', 0)")->fetchColumn() !== 1) {
    exit(0);
}

$queue = new MessageQueue();
$sent = 0;
$failed = 0;

foreach ($queue->due(50) as $message) {
    $error = CustomerMessenger::deliver($message);
    if ($error === null) {
        $queue->markSent((int) $message['id']);
        $sent++;
    } else {
        $queue->markAttemptFailed($message, $error);
        $failed++;
    }
}

$db->query("SELECT RELEASE_LOCK('dcf_message_worker')");

if ($sent || $failed) {
    echo date('Y-m-d H:i:s') . " sent {$sent}, failed {$failed}\n";
}
