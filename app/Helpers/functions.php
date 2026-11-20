<?php

declare(strict_types=1);

use App\Helpers\Env;

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return rtrim(Env::get('APP_URL', 'http://localhost/nelber-code-crm/public'), '/') . '/' . ltrim($path, '/');
}

function asset(string $path): string
{
    return url($path);
}

function csrf_field(): string
{
    $token = App\Middleware\CsrfMiddleware::token();
    return '<input type="hidden" name="_token" value="' . e($token) . '">';
}

function old(string $key, string $default = ''): string
{
    return e((string) ($_SESSION['_old'][$key] ?? $default));
}

function flash(string $key): ?string
{
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($value) ? $value : null;
}

function app_setting(string $key, string $default = ''): string
{
    return (string) ($_SESSION['settings'][$key] ?? $default);
}
