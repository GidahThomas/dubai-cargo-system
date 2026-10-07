<?php

class App
{
    private array $aliases = [
        'auth' => 'AuthController',
        'public' => 'PublicController',
        'dashboard' => 'DashboardController',
        'products' => 'ProductController',
        'orders' => 'OrderController',
        'payments' => 'PaymentController',
        'invoices' => 'InvoiceController',
        'shipments' => 'ShipmentController',
        'store' => 'StoreController',
        'notifications' => 'NotificationController',
        'reports' => 'ReportController',
        'users' => 'UserController',
        'quotations' => 'QuotationController',
        'deliveries' => 'DeliveryController',
        'locations' => 'LocationController',
    ];

    private array $directRoutes = [
        '' => ['PublicController', 'home'],
        'home' => ['PublicController', 'home'],
        'login' => ['AuthController', 'login'],
        'register' => ['AuthController', 'register'],
        'logout' => ['AuthController', 'logout'],
        'dashboard' => ['DashboardController', 'index'],
        'track-shipment' => ['PublicController', 'trackShipment'],
        'contact' => ['PublicController', 'contact'],
        'request-quotation' => ['PublicController', 'requestQuotation'],
        'request-invoice' => ['PublicController', 'requestInvoice'],
    ];

    public function dispatch(): void
    {
        $route = trim((string) ($_GET['url'] ?? ''), '/');

        try {
            if (isset($this->directRoutes[$route])) {
                [$controllerName, $method] = $this->directRoutes[$route];
                $this->call($controllerName, $method);
                return;
            }

            $segments = $route === '' ? [] : explode('/', $route);
            $key = $segments[0] ?? 'dashboard';
            $controllerName = $this->aliases[$key] ?? ucfirst($key) . 'Controller';
            $method = $segments[1] ?? 'index';
            $params = array_slice($segments, 2);

            $this->call($controllerName, $method, $params);
        } catch (Throwable $exception) {
            http_response_code(500);

            if (defined('APP_DEBUG') && APP_DEBUG) {
                echo '<pre>' . h($exception->getMessage()) . "\n" . h($exception->getTraceAsString()) . '</pre>';
                return;
            }

            echo 'Something went wrong. Please contact the system administrator.';
        }
    }

    private function call(string $controllerName, string $method, array $params = []): void
    {
        if (!preg_match('/^[A-Za-z]+Controller$/', $controllerName)
            || !class_exists($controllerName)
            || !is_subclass_of($controllerName, 'Controller')) {
            http_response_code(404);
            echo 'Controller not found.';
            return;
        }

        $arguments = $this->resolveArguments($controllerName, $method, $params);

        if ($arguments === null) {
            http_response_code(404);
            echo 'Page not found.';
            return;
        }

        $controller = new $controllerName();
        $controller->{$method}(...$arguments);
    }

    /**
     * Only public, non-static actions declared on the concrete controller are routable.
     * Returns the coerced arguments, or null when the URL does not match the action signature.
     */
    private function resolveArguments(string $controllerName, string $method, array $params): ?array
    {
        if (!preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $method) || !method_exists($controllerName, $method)) {
            return null;
        }

        $reflection = new ReflectionMethod($controllerName, $method);

        if (!$reflection->isPublic()
            || $reflection->isStatic()
            || $reflection->isConstructor()
            || $reflection->getDeclaringClass()->getName() === 'Controller') {
            return null;
        }

        $parameters = $reflection->getParameters();

        if (count($params) < $reflection->getNumberOfRequiredParameters() || count($params) > count($parameters)) {
            return null;
        }

        $arguments = [];

        foreach ($params as $index => $value) {
            $type = $parameters[$index]->getType();
            $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

            if ($typeName === 'int') {
                if (!ctype_digit((string) $value)) {
                    return null;
                }
                $value = (int) $value;
            }

            $arguments[] = $value;
        }

        return $arguments;
    }
}
