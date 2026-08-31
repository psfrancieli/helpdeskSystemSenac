<?php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, callable|array $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable|array $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function dispatch(string $method, string $uri): void
{
    $path = parse_url($uri, PHP_URL_PATH) ?: '/';

    echo '<pre>';
    var_dump([
        'method' => $method,
        'uri' => $uri,
        'path' => $path,
        'script' => $_SERVER['SCRIPT_NAME'] ?? null,
        'base' => dirname($_SERVER['SCRIPT_NAME'] ?? ''),
        'routes' => $this->routes[$method] ?? [],
    ]);
    echo '</pre>';
    exit;

    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';

    if ($scriptName !== '' && str_starts_with($path, $scriptName)) {
        $path = substr($path, strlen($scriptName));
    }

    if ($path === '') {
        $path = '/';
    }

    $handler = $this->routes[$method][$path] ?? null;

    if ($handler === null) {
        http_response_code(404);
        echo 'Route not found';
        return;
    }

    if (is_array($handler)) {
        [$class, $action] = $handler;

        (new $class())->$action();

        return;
    }

    $handler();
}
}
