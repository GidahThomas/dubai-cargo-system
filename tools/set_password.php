<?php

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

/**
 * Reset a user's password from the command line (e.g. a forgotten owner password).
 *
 * Usage (from the project folder):
 *   C:\xampp\php\php.exe tools\set_password.php user@example.com            (generates a strong password)
 *   C:\xampp\php\php.exe tools\set_password.php user@example.com "my new passphrase"
 */

require __DIR__ . '/bootstrap.php';

$email = $argv[1] ?? '';
$password = $argv[2] ?? '';

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Usage: php tools/set_password.php <email> [new-password]\n");
    exit(1);
}

$generated = $password === '';
if ($generated) {
    // 4 groups of 4 from an alphabet without look-alike characters, e.g. "Kx7q-M2pa-9Rtw-hZ4e".
    $alphabet = 'abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ23456789';
    $groups = [];
    for ($g = 0; $g < 4; $g++) {
        $chunk = '';
        for ($i = 0; $i < 4; $i++) {
            $chunk .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $groups[] = $chunk;
    }
    $password = implode('-', $groups);
}

if (strlen($password) < Auth::MIN_PASSWORD_LENGTH) {
    fwrite(STDERR, 'Passwords must be at least ' . Auth::MIN_PASSWORD_LENGTH . " characters.\n");
    exit(1);
}

$userModel = new User();
$user = $userModel->findByEmail($email);
if (!$user) {
    fwrite(STDERR, "No user with email {$email}.\n");
    exit(1);
}

$userModel->updatePassword((int) $user['id'], $password);
(new AuditLog())->create(null, 'password_reset_cli', 'users', (int) $user['id'], $email);

echo "Password updated for {$user['name']} <{$email}> ({$user['role']}).\n";
if ($generated) {
    echo "New password: {$password}\n";
}
