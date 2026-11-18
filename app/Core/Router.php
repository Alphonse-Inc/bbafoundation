<?php

declare(strict_types=1);

namespace BBAF\Core;

use BBAF\Controllers\PublicController;
use BBAF\Services\RedirectMap;

final class Router
{
    private array $routes = [];

    public function load(string $file): void
    {
        $register = require $file;
        if (!is_callable($register)) {
            throw new \RuntimeException('Route file must return a callable.');
        }
        $register($this);
    }

    public function add(string $method, string $path, array $handler, array $middleware = []): void
    {
        $this->routes[] = compact('method', 'path', 'handler', 'middleware');
    }

    public function dispatch(Request $request): void
    {
        foreach ($this->routes as $route) {
            if ($route['method'] !== $request->method()) {
                continue;
            }
            $pattern = preg_replace('#\{([A-Za-z_][A-Za-z0-9_]*)\}#', '(?P<$1>[^/]+)', $route['path']);
            if (!is_string($pattern) || !preg_match('#^' . $pattern . '$#', $request->path(), $matches)) {
                continue;
            }
            $params = [];
            foreach ($matches as $key => $value) {
                if (is_string($key)) {
                    $params[$key] = (string) $value;
                }
            }
            $request->setParams($params);
            if (in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                Middleware::run('csrf', $request);
            }
            foreach ($route['middleware'] as $name) {
                Middleware::run($name, $request);
            }
            [$class, $action] = $route['handler'];
            (new $class())->{$action}($request);
            return;
        }

        if ($request->method() === 'GET') {
            $slug = trim($request->path(), '/');
            if ($slug !== '' && !str_contains($slug, '/')) {
                $trustee = Database::one(
                    'SELECT id FROM trustees WHERE slug = ? AND is_active = 1',
                    [$slug]
                );
                if ($trustee) {
                    (new PublicController())->trustee($request);
                    return;
                }
            }
            if (RedirectMap::attempt($request->path())) {
                return;
            }
        }

        (new PublicController())->notFound($request);
    }
}
