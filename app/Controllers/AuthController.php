<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Services\AuthService;
use App\Services\PaymentService;
use RuntimeException;

final class AuthController
{
    public function show(Request $request): void
    {
        if (isset($_SESSION['user'])) {
            Response::redirect(AuthService::canUseCrm($_SESSION['user']) ? '/dashboard' : '/subscription');
        }
        View::render('auth/login', [], 'auth');
    }

    public function registerForm(Request $request): void
    {
        if (isset($_SESSION['user'])) {
            Response::redirect(AuthService::canUseCrm($_SESSION['user']) ? '/dashboard' : '/subscription');
        }
        View::render('auth/register', [], 'auth');
    }

    public function register(Request $request): void
    {
        $_SESSION['_old'] = [
            'name' => (string) $request->input('name', ''),
            'company' => (string) $request->input('company', ''),
            'email' => (string) $request->input('email', ''),
        ];
        try {
            (new AuthService())->register(
                (string) $request->input('name', ''),
                (string) $request->input('company', ''),
                (string) $request->input('email', ''),
                (string) $request->input('password', ''),
                (string) $request->input('password_confirmation', ''),
                $request->input('terms') === '1'
            );
            unset($_SESSION['_old']);
            $_SESSION['_flash']['success'] = 'Conta criada. Entre para confirmar seu acesso e escolher a forma de pagamento.';
            Response::redirect('/login');
        } catch (RuntimeException $e) {
            $_SESSION['_flash']['error'] = $e->getMessage();
            Response::redirect('/register');
        }
    }
    public function login(Request $request): void
    {
        $_SESSION['_old'] = ['email' => (string) $request->input('email', '')];
        try {
            $user = (new AuthService())->login((string) $request->input('email'), (string) $request->input('password'), $request->ip());
            unset($_SESSION['_old']);
            $_SESSION['_flash']['success'] = 'Login confirmado com segurança.';
            Response::redirect(AuthService::canUseCrm($user) ? '/dashboard' : '/subscription');
        } catch (RuntimeException $e) {
            $_SESSION['_flash']['error'] = $e->getMessage();
            Response::redirect('/login');
        }
    }
    public function subscription(Request $request): void
    {
        if (AuthService::canUseCrm($_SESSION['user'])) {
            Response::redirect('/dashboard');
        }
        View::render('auth/subscription', [
            'payment' => (new PaymentService())->configuration(),
            'contactEmail' => 'contato@example.test',
            'whatsappUrl' => 'https://wa.me/5500000000000?text=' . rawurlencode('Olá, Ricardo! Já criei minha conta no CRM e quero ativar meu acesso.'),
        ], 'auth');
    }
    public function logout(Request $request): void { (new AuthService())->logout(); Response::redirect('/login'); }
}
