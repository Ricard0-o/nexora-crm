<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Request;
use App\Helpers\Response;

final class AdminMiddleware
{
    public static function handle(Request $request): void
    {
        if (($_SESSION['user']['role'] ?? '') === 'Administrador') {
            return;
        }
        if ($request->expectsJson()) {
            Response::json(false, 'Acesso permitido somente ao administrador.', [], 403);
        }
        http_response_code(403);
        exit('Acesso permitido somente ao administrador.');
    }
}
