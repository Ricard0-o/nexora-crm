<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Request;
use App\Helpers\Response;

final class CsrfMiddleware
{
    public static function token(): string
    {
        if (!isset($_SESSION['_csrf'])) { $_SESSION['_csrf'] = bin2hex(random_bytes(32)); }
        return $_SESSION['_csrf'];
    }

    public static function handle(Request $request): void
    {
        $token = (string) ($request->input('_token') ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if ($token === '' || !hash_equals(self::token(), $token)) {
            if ($request->expectsJson()) { Response::json(false, 'Token CSRF inválido ou expirado.', [], 419); }
            http_response_code(419);
            exit('Sessão expirada. Atualize a página e tente novamente.');
        }
    }
}
