<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Services\SettingsService;
use RuntimeException;

final class SettingsController
{
    public function index(Request $request): void
    {
        View::render('settings/index', (new SettingsService())->data());
    }

    public function update(Request $request): never
    {
        try {
            (new SettingsService())->update($request->all(), (int) $_SESSION['user']['id']);
            $_SESSION['_flash']['success'] = 'Configurações atualizadas com sucesso.';
        } catch (RuntimeException $exception) {
            $_SESSION['_flash']['error'] = $exception->getMessage();
            $_SESSION['_old'] = $request->all();
        }
        Response::redirect('/settings');
    }
}
