<?php

class RoutesTest extends TestCase
{
    /**
     * Every sidebar link must reach a real controller action (a missing alias once sent
     * "Message Log" to "Controller not found").
     */
    public function testEverySidebarLinkResolvesToAnAction(): void
    {
        $app = new ReflectionClass(App::class);
        $instance = $app->newInstanceWithoutConstructor();
        $aliases = $app->getProperty('aliases')->getValue($instance);
        $directRoutes = $app->getProperty('directRoutes')->getValue($instance);

        preg_match_all("/'url' => '([a-z\/-]+)'/", (string) file_get_contents(ROOT_PATH . '/app/views/layouts/sidebar.php'), $matches);
        $this->assertTrue(count($matches[1]) > 10, 'Sidebar links should be found.');

        foreach (array_unique($matches[1]) as $route) {
            if (isset($directRoutes[$route])) {
                [$controller, $method] = $directRoutes[$route];
            } else {
                $segments = explode('/', $route);
                $controller = $aliases[$segments[0]] ?? ucfirst($segments[0]) . 'Controller';
                $method = $segments[1] ?? 'index';
            }

            $this->assertTrue(class_exists($controller), "Sidebar route '{$route}' needs class {$controller}.");
            $this->assertTrue(method_exists($controller, $method), "Sidebar route '{$route}' needs {$controller}::{$method}().");
        }
    }
}
