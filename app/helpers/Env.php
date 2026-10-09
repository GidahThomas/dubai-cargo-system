<?php

function load_env(string $path): void
{
    // Keys this function copied from a .env file into the process environment (not real host settings).
    static $fromDotEnv = [];

    // Hosting dashboards (Vercel, Render, ...) provide settings as real environment variables,
    // which PHP does not always copy into $_ENV. Those win over a .env file.
    foreach ((array) getenv() as $key => $value) {
        if (is_string($key) && !isset($fromDotEnv[$key]) && !array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
        }
    }

    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

    if ($lines === false) {
        return;
    }

    foreach ($lines as $line) {
        $line = trim($line);

        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }

        if (!str_contains($line, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $line, 2);
        $key = trim($key);
        $value = trim($value);

        if ($key === '') {
            continue;
        }

        if (strlen($value) >= 2 && (
            ($value[0] === '"' && str_ends_with($value, '"'))
            || ($value[0] === "'" && str_ends_with($value, "'"))
        )) {
            $value = substr($value, 1, -1);
        }

        if (!array_key_exists($key, $_ENV)) {
            $_ENV[$key] = $value;
            putenv($key . '=' . $value);
            $fromDotEnv[$key] = true;
        }
    }
}
