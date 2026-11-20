<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Services\UserService;
use RuntimeException;

final class UserController
{
    public function index(Request $request): void
    {
        View::render('users/index', (new UserService())->administrationData());
    }

    public function updateSubscription(Request $request): void
    {
        try {
            (new UserService())->updateSubscription(
                (int) $request->input('user_id', 0),
                (string) $request->input('subscription_status', ''),
                trim((string) $request->input('subscription_valid_until', ''))
            );
            $_SESSION['_flash']['success'] = 'Assinatura atualizada e registrada na auditoria.';
        } catch (RuntimeException $exception) {
            $_SESSION['_flash']['error'] = $exception->getMessage();
        }
        Response::redirect('/users');
    }
}
