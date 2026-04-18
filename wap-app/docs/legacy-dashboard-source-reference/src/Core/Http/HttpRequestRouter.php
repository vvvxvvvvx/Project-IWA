<?php

declare(strict_types=1);

namespace App\Core\Http;

final class HttpRequestRouter
{
    /** @var array<string, array<int, array{pattern:string, handler:callable, middleware:list<callable>}>> */
    private array $routes = [];

    public function get(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->map('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, callable $handler, array $middleware = []): void
    {
        $this->map('POST', $pattern, $handler, $middleware);
    }

    private function map(string $method, string $pattern, callable $handler, array $middleware): void
    {
        $this->routes[$method][] = compact('pattern', 'handler', 'middleware');
    }

    public function dispatch(HttpRequest $request): void
    {
        foreach ($this->routes[$request->method] ?? [] as $route) {
            $pattern = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['pattern']);
            $regex = '#^' . $pattern . '$#';
            if (!preg_match($regex, $request->path, $matches)) {
                continue;
            }

            $routeParameters = array_filter($matches, static fn ($key) => is_string($key), ARRAY_FILTER_USE_KEY);

            foreach ($route['middleware'] as $middleware) {
                $middleware($request);
            }

            ($route['handler'])($request, $routeParameters);
            return;
        }

        HttpResponse::html('<h1>404</h1><p>Route not found.</p>', 404);
    }
}
