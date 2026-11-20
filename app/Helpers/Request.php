<?php

declare(strict_types=1);

namespace App\Helpers;

final class Request
{
    public function method(): string { return strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET'); }
    public function path(): string
    {
        $path = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
        $base = rtrim(parse_url(Env::get('APP_URL'), PHP_URL_PATH) ?: '', '/');
        if ($base !== '' && str_starts_with($path, $base)) {
            $path = substr($path, strlen($base)) ?: '/';
        }
        return '/' . ltrim($path, '/');
    }
    public function input(string $key, mixed $default = null): mixed { return $_POST[$key] ?? $_GET[$key] ?? $default; }
    public function all(): array { return array_merge($_GET, $_POST); }
    public function header(string $name, string $default = ''): string
    {
        $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
        return (string) ($_SERVER[$key] ?? $default);
    }
    public function rawBody(): string { return (string) file_get_contents('php://input'); }
    public function rawQueryValue(string $key, string $default = ''): string
    {
        foreach (explode('&', (string) ($_SERVER['QUERY_STRING'] ?? '')) as $pair) {
            [$name, $value] = array_pad(explode('=', $pair, 2), 2, '');
            if (rawurldecode($name) === $key) { return rawurldecode($value); }
        }
        return $default;
    }
    public function expectsJson(): bool
    {
        return str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') || str_starts_with($this->path(), '/api/');
    }
    public function ip(): string { return ClientIp::resolve(); }
}
