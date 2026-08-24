<?php

final class Auth
{
    public static function startSecureSession(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');

        session_name('DCFCS_SESSION');
        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'domain' => '',
            'secure' => $isHttps,
            'httponly' => true,
            'samesite' => 'Lax',
        ]);

        session_start();

        if (!isset($_SESSION['created_at'])) {
            $_SESSION['created_at'] = time();
        }

        if (!isset($_SESSION['last_regenerated_at'])) {
            $_SESSION['last_regenerated_at'] = time();
            session_regenerate_id(true);
        }

        if (time() - (int) $_SESSION['last_regenerated_at'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['last_regenerated_at'] = time();
        }
    }

    public static function login(array $user): void
    {
        session_regenerate_id(true);

        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['name'],
            'email' => $user['email'],
            'role' => $user['role'],
        ];

        $_SESSION['last_regenerated_at'] = time();
    }

    public static function logout(): void
    {
        $_SESSION = [];

        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }

        session_destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user']['id']);
    }

    public static function user(): ?array
    {
        return $_SESSION['user'] ?? null;
    }

    public static function id(): ?int
    {
        return self::check() ? (int) $_SESSION['user']['id'] : null;
    }

    public static function role(): ?string
    {
        return self::check() ? $_SESSION['user']['role'] : null;
    }

    public static function staffRoles(): array
    {
        return ['manager', 'admin', 'sales_officer', 'store_manager', 'accountant', 'cargo_officer', 'super_admin', 'company_owner'];
    }

    public static function isStaff(): bool
    {
        return self::check() && in_array((string) self::role(), self::staffRoles(), true);
    }

    public static function hasRole(array|string $roles): bool
    {
        $roles = (array) $roles;

        if (!self::check()) {
            return false;
        }

        if (in_array(self::role(), $roles, true)) {
            return true;
        }

        if (in_array('admin', $roles, true) && in_array(self::role(), ['super_admin', 'company_owner'], true)) {
            return true;
        }

        return in_array('manager', $roles, true) && self::isStaff();
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Please sign in to continue.');
            header('Location: ' . url('login'));
            exit;
        }
    }

    public static function requireRole(array|string $roles): void
    {
        self::requireLogin();

        if (!self::hasRole($roles)) {
            http_response_code(403);
            echo 'Access denied.';
            exit;
        }
    }

    public static function csrfToken(): string
    {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        return $_SESSION['csrf_token'];
    }

    public static function csrfField(): string
    {
        return '<input type="hidden" name="csrf_token" value="' . h(self::csrfToken()) . '">';
    }

    public static function verifyCsrf(?string $token): bool
    {
        return is_string($token)
            && isset($_SESSION['csrf_token'])
            && hash_equals($_SESSION['csrf_token'], $token);
    }
}

function h(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function base_url(): string
{
    $scriptDir = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? ''));
    $scriptDir = rtrim($scriptDir, '/');

    return $scriptDir === '' || $scriptDir === '/' ? '' : $scriptDir;
}

function url(string $path = ''): string
{
    $base = base_url() . '/index.php';
    $path = trim($path, '/');

    return $path === '' ? $base : $base . '?url=' . $path;
}

function asset(string $path): string
{
    return base_url() . '/assets/' . ltrim($path, '/');
}

function versioned_asset(string $path): string
{
    $relative = ltrim($path, '/');
    $file = ROOT_PATH . '/public/assets/' . $relative;
    $version = file_exists($file) ? filemtime($file) : time();

    return asset($relative) . '?v=' . $version;
}

function public_url(string $path): string
{
    if (preg_match('/^https?:\/\//i', $path)) {
        return $path;
    }

    return base_url() . '/' . ltrim($path, '/');
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['flash'][$key] = $message;
        return null;
    }

    if (!isset($_SESSION['flash'][$key])) {
        return null;
    }

    $value = $_SESSION['flash'][$key];
    unset($_SESSION['flash'][$key]);

    return $value;
}

function company_name(): string
{
    return 'Dubai Computer Fast Cargo';
}

function company_logo_path(): string
{
    return 'images/LOGO.png';
}

function company_phone(): string
{
    return '0652532646';
}

function company_email(): string
{
    return 'dubaicomputers14@14gmail.com';
}

function customer_contact_email(): string
{
    return 'gidamasaudathomas@gmail.com';
}

function company_social_handle(): string
{
    return '@dubai_computers';
}

function company_instagram_url(): string
{
    return 'https://www.instagram.com/dubai_computers/';
}

function default_currency_code(): string
{
    return 'TZS';
}

function money(float|int|string|null $amount): string
{
    return currency_money($amount, default_currency_code());
}

function currency_money(float|int|string|null $amount, string $currency = 'TZS'): string
{
    $currency = strtoupper($currency ?: default_currency_code());
    $decimals = in_array($currency, ['TZS', 'UGX', 'RWF'], true) ? 0 : 2;

    return $currency . ' ' . number_format((float) $amount, $decimals);
}

function money_parts(float|int|string|null $amount, ?string $currency = null): array
{
    $currency = strtoupper($currency ?: default_currency_code());
    $decimals = in_array($currency, ['TZS', 'UGX', 'RWF'], true) ? 0 : 2;

    return [
        'prefix' => $currency,
        'value' => number_format((float) $amount, $decimals),
    ];
}

function badge_class(?string $status): string
{
    return match ($status) {
        'active', 'confirmed', 'delivered', 'success', 'ready_for_pickup', 'paid' => 'text-bg-success',
        'pending', 'order_received', 'unpaid', 'draft' => 'text-bg-warning',
        'rejected', 'cancelled', 'inactive', 'danger' => 'text-bg-danger',
        'processing', 'in_transit', 'ordered_from_supplier', 'shipped_from_origin', 'shipped', 'arrived_at_port', 'cleared', 'ready', 'payment_confirmed' => 'text-bg-info',
        default => 'text-bg-secondary',
    };
}

function readable_status(?string $status): string
{
    return ucwords(str_replace('_', ' ', (string) $status));
}

function status_color(?string $status): string
{
    return match ($status) {
        'active', 'confirmed', 'delivered', 'success', 'ready_for_pickup', 'paid' => '#16a34a',
        'pending', 'order_received', 'unpaid', 'draft' => '#d97706',
        'rejected', 'cancelled', 'inactive', 'danger' => '#dc2626',
        'processing', 'in_transit', 'ordered_from_supplier', 'shipped_from_origin', 'shipped', 'arrived_at_port', 'cleared', 'ready', 'payment_confirmed' => '#1D9E75',
        default => '#94a3b8',
    };
}

function status_breakdown_bars(array $rows, string $labelKey = 'label', string $totalKey = 'total'): array
{
    $grandTotal = array_sum(array_column($rows, $totalKey));
    $bars = [];

    foreach ($rows as $row) {
        $count = (int) $row[$totalKey];
        $bars[] = [
            'label' => readable_status((string) $row[$labelKey]),
            'count' => $count,
            'percentage' => $grandTotal > 0 ? (int) round(($count / $grandTotal) * 100) : 0,
            'color' => status_color((string) $row[$labelKey]),
        ];
    }

    return $bars;
}

function chart_json(array $data): string
{
    return htmlspecialchars(json_encode($data, JSON_THROW_ON_ERROR), ENT_QUOTES, 'UTF-8');
}
