<?php

declare(strict_types=1);

namespace App\Helpers;

use App\Middleware\AuthMiddleware;
use App\Middleware\AdminMiddleware;
use App\Middleware\CsrfMiddleware;

final class Router
{
    private array $routes = [];

    public function get(string $path, array $handler, bool $auth = false, bool $admin = false): void { $this->routes['GET'][$path] = [$handler, $auth, $admin]; }
    public function post(string $path, array $handler, bool $auth = false, bool $admin = false, bool $csrf = true): void { $this->routes['POST'][$path] = [$handler, $auth, $admin, $csrf]; }

    public function dispatch(Request $request): void
    {
        $route = $this->routes[$request->method()][$request->path()] ?? null;
        if ($route === null) {
            http_response_code(404);
            View::render('errors/404', [], 'public');
            return;
        }
        [$handler, $requiresAuth, $requiresAdmin, $requiresCsrf] = array_pad($route, 4, true);
        if ($requiresAuth) { AuthMiddleware::handle($request); }
        if ($requiresAdmin) { AdminMiddleware::handle($request); }
        if ($request->method() === 'POST' && $requiresCsrf) { CsrfMiddleware::handle($request); }
        [$class, $method] = $handler;
        (new $class())->{$method}($request);
    }
}
