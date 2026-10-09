<?php

/**
 * Records unexpected errors in storage/logs/app-errors.log and, when ERROR_ALERT_EMAIL is set,
 * emails the owner. The same error is emailed at most once every 30 minutes so a broken page
 * cannot flood the inbox.
 */
final class ErrorReporter
{
    public const LOG_DIR = ROOT_PATH . '/storage/logs';
    private const ALERT_INTERVAL_SECONDS = 1800;

    /**
     * Folder for log files: LOG_DIR from .env, else storage/logs. Null when nothing is writable
     * (e.g. Vercel), in which case errors go to the host's own log stream instead.
     */
    public static function logDir(): ?string
    {
        static $dir = false;

        if ($dir === false) {
            $dir = rtrim((string) ($_ENV['LOG_DIR'] ?? ''), '/\\') ?: self::LOG_DIR;
            if (!is_dir($dir)) {
                @mkdir($dir, 0775, true);
            }
            $dir = is_dir($dir) && is_writable($dir) ? $dir : null;
        }

        return $dir;
    }

    public static function register(): void
    {
        ini_set('log_errors', '1');
        if (self::logDir() !== null) {
            ini_set('error_log', self::logDir() . '/php-errors.log');
        }
        ini_set('display_errors', APP_DEBUG ? '1' : '0');

        set_exception_handler(static function (Throwable $exception): void {
            self::report($exception);
            http_response_code(500);
            echo APP_DEBUG ? '<pre>' . htmlspecialchars((string) $exception) . '</pre>' : 'Something went wrong. Please contact the system administrator.';
        });

        register_shutdown_function(static function (): void {
            $error = error_get_last();
            if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
                self::report(new ErrorException($error['message'], 0, $error['type'], $error['file'], $error['line']));
            }
        });
    }

    public static function report(Throwable $exception): void
    {
        try {
            $where = sprintf('%s:%d', str_replace(ROOT_PATH, '', $exception->getFile()), $exception->getLine());
            $request = (PHP_SAPI === 'cli' ? 'CLI ' . implode(' ', $_SERVER['argv'] ?? []) : ($_SERVER['REQUEST_METHOD'] ?? 'GET') . ' ' . ($_SERVER['REQUEST_URI'] ?? ''));
            $user = class_exists('Auth', false) && Auth::check() ? (Auth::user()['email'] ?? 'user #' . Auth::id()) : 'guest';

            $entry = sprintf(
                "[%s] %s: %s at %s | %s | %s\n%s\n\n",
                date('Y-m-d H:i:s'),
                $exception::class,
                $exception->getMessage(),
                $where,
                $request,
                $user,
                $exception->getTraceAsString()
            );
            if (self::logDir() !== null) {
                @file_put_contents(self::logDir() . '/app-errors.log', $entry, FILE_APPEND | LOCK_EX);
            } else {
                error_log(rtrim($entry));
            }

            self::alert($exception, $where, $request, $user);
        } catch (Throwable) {
            // Reporting must never cause a second failure.
        }
    }

    private static function alert(Throwable $exception, string $where, string $request, string $user): void
    {
        $to = trim((string) ($_ENV['ERROR_ALERT_EMAIL'] ?? ''));
        if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return;
        }

        $stateFile = (self::logDir() ?? sys_get_temp_dir()) . '/alert-state.json';
        $state = is_file($stateFile) ? (json_decode((string) file_get_contents($stateFile), true) ?: []) : [];
        $signature = sha1($exception::class . $where . $exception->getMessage());
        $now = time();

        if (($state[$signature] ?? 0) > $now - self::ALERT_INTERVAL_SECONDS) {
            return;
        }

        $state[$signature] = $now;
        $state = array_filter($state, static fn (int $time): bool => $time > $now - 86400);
        @file_put_contents($stateFile, json_encode($state), LOCK_EX);

        $subject = '[' . (function_exists('company_name') ? company_name() : 'System') . '] Error: ' . mb_strimwidth($exception->getMessage(), 0, 80, '...');
        $body = "An error occurred in the system.\n\n"
            . 'Error: ' . $exception->getMessage() . "\n"
            . 'Where: ' . $where . "\n"
            . 'Request: ' . $request . "\n"
            . 'User: ' . $user . "\n"
            . 'Time: ' . date('Y-m-d H:i:s') . "\n\n"
            . "Full details are in storage/logs/app-errors.log on the server.\n";

        Mailer::send($to, $subject, $body);
    }
}
