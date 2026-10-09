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

$result = MessageWorker::run();

if ($result['sent'] || $result['failed']) {
    echo date('Y-m-d H:i:s') . " sent {$result['sent']}, failed {$result['failed']}\n";
}
