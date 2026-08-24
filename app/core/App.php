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
        if (!class_exists($controllerName)) {
            http_response_code(404);
            echo 'Controller not found.';
            return;
        }

        $controller = new $controllerName();

        if (!method_exists($controller, $method)) {
            http_response_code(404);
            echo 'Page not found.';
            return;
        }

        call_user_func_array([$controller, $method], $params);
    }
}
