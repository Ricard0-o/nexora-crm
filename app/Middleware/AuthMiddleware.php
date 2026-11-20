<?php

declare(strict_types=1);

namespace App\Middleware;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Services\SettingsService;
use App\Services\AuthService;
use App\Repositories\UserRepository;

final class AuthMiddleware
{
    public static function handle(Request $request): void
    {
        if (!isset($_SESSION['user']['id'])) {
            if ($request->expectsJson()) { Response::json(false, 'Autenticação necessária.', [], 401); }
            $_SESSION['_flash']['error'] = 'Entre para acessar esta área.';
            Response::redirect('/login');
        }
        $currentUser = (new UserRepository())->findAccessById((int) $_SESSION['user']['id']);
        if ($currentUser === null || !(bool) $currentUser['ativo']) {
            session_unset();
            session_destroy();
            if ($request->expectsJson()) { Response::json(false, 'Conta indisponível.', [], 401); }
            Response::redirect('/login');
        }
        $_SESSION['user'] = [
            'id' => (int) $currentUser['id'],
            'name' => $currentUser['nome'],
            'email' => $currentUser['email'],
            'role' => $currentUser['perfil'],
            'tenant_id' => (int) $currentUser['tenant_id'],
            'tenant_name' => $currentUser['tenant_name'],
            'subscription_status' => $currentUser['assinatura_status'],
            'subscription_valid_until' => $currentUser['assinatura_valida_ate'],
        ];
        if (!isset($_SESSION['settings'])) {
            $_SESSION['settings'] = (new SettingsService())->values();
        }
        $timezone = (string) ($_SESSION['settings']['timezone'] ?? 'America/Sao_Paulo');
        if (in_array($timezone, \DateTimeZone::listIdentifiers(), true)) {
            date_default_timezone_set($timezone);
        }
        $timeoutMinutes = max(15, min(120, (int) ($_SESSION['settings']['session_timeout_minutes'] ?? 30)));
        if (isset($_SESSION['last_activity']) && time() - (int) $_SESSION['last_activity'] > $timeoutMinutes * 60) {
            session_unset();
            session_destroy();
            Response::redirect('/login');
        }
        $_SESSION['last_activity'] = time();
        $subscriptionRoutes = ['/subscription', '/logout', '/payments/checkout', '/payment/success', '/payment/pending', '/payment/failure'];
        if (!in_array($request->path(), $subscriptionRoutes, true) && !AuthService::canUseCrm($_SESSION['user'])) {
            if ($request->expectsJson()) {
                Response::json(false, 'Assinatura necessária para utilizar o CRM.', ['redirect' => url('/subscription')], 402);
            }
            $_SESSION['_flash']['error'] = 'Seu login foi confirmado, mas a assinatura ainda precisa ser ativada.';
            Response::redirect('/subscription');
        }
    }
}
