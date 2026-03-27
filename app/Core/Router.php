<?php
declare(strict_types=1);

namespace Core;

class Router
{
    private array $routes = [];

    public function get(string $path, string $action): void
    {
        $this->routes['GET'][$path] = $action;
    }

    public function post(string $path, string $action): void
    {
        $this->routes['POST'][$path] = $action;
    }

    public function dispatch(string $method, string $uri): void
    {
        $scriptName = dirname($_SERVER['SCRIPT_NAME'] ?? '');
        if ($scriptName && $scriptName !== '/' && str_starts_with($uri, $scriptName)) {
            $uri = substr($uri, strlen($scriptName));
        }
        $uri = $uri ?: '/';

        $action = $this->routes[$method][$uri] ?? null;
        if (!$action) {
            http_response_code(404);
            echo '<h1>404 - Page not found</h1>';
            return;
        }

        [$controller, $methodName] = explode('@', $action);
        $class = 'Controllers\\' . $controller;
        $instance = new $class();
        $instance->$methodName();
    }
}
