<?php

abstract class Controller
{
    protected function model(string $model): Model
    {
        if (!class_exists($model)) {
            throw new RuntimeException("Model {$model} was not found.");
        }

        return new $model();
    }

    protected function view(string $view, array $data = []): void
    {
        $viewFile = ROOT_PATH . '/app/views/' . $view . '.php';

        if (!file_exists($viewFile)) {
            throw new RuntimeException("View {$view} was not found.");
        }

        extract($data, EXTR_SKIP);

        require ROOT_PATH . '/app/views/layouts/header.php';

        // Public pages keep the public header and footer even for signed-in users;
        // the sidebar and top bar belong to the signed-in app layout only.
        $appShell = Auth::check() && ($layout ?? 'app') !== 'public';

        if ($appShell) {
            require ROOT_PATH . '/app/views/layouts/sidebar.php';
            echo '<main class="app-main">';
            require ROOT_PATH . '/app/views/layouts/topbar.php';
            echo '<div class="app-content">';
        }

        require $viewFile;

        if ($appShell) {
            echo '</div></main>';
        }

        require ROOT_PATH . '/app/views/layouts/footer.php';
    }

    protected function redirect(string $path = ''): void
    {
        header('Location: ' . url($path));
        exit;
    }

    protected function requireLogin(): void
    {
        Auth::requireLogin();
    }

    protected function requireRole(array|string $roles): void
    {
        Auth::requireRole($roles);
    }

    /**
     * The location the current staff member is viewing/operating in.
     * Null means "all locations" (no filter).
     */
    protected function activeLocationId(): ?int
    {
        return active_location_id();
    }

    protected function requestMethod(): string
    {
        return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    protected function isPost(): bool
    {
        return $this->requestMethod() === 'POST';
    }

    protected function post(string $key, mixed $default = null): mixed
    {
        return $_POST[$key] ?? $default;
    }

    protected function get(string $key, mixed $default = null): mixed
    {
        return $_GET[$key] ?? $default;
    }

    protected function cleanString(?string $value): string
    {
        return trim((string) $value);
    }

    protected function validateCsrf(): void
    {
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? null)) {
            flash('error', 'Your session token expired. Please try again.');
            $this->redirect('dashboard');
        }
    }

    protected function isAjax(): bool
    {
        return strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest'
            || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');
    }

    protected function json(array $payload, int $statusCode = 200): void
    {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_THROW_ON_ERROR);
        exit;
    }

    protected function validateAjaxCsrf(): void
    {
        if (!Auth::verifyCsrf($_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
            $this->json([
                'success' => false,
                'message' => 'Invalid or expired session token.',
            ], 419);
        }
    }

    protected function requireAjaxLogin(): void
    {
        if (!Auth::check()) {
            $this->json([
                'success' => false,
                'message' => 'Authentication required.',
            ], 401);
        }
    }

    protected function requireAjaxRole(array|string $roles): void
    {
        $this->requireAjaxLogin();

        if (!Auth::hasRole($roles)) {
            $this->json([
                'success' => false,
                'message' => 'Access denied.',
            ], 403);
        }
    }
}
