<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Services\LeadService;
use App\Services\PipelineService;
use RuntimeException;

final class PipelineController
{
    public function index(Request $request): void { View::render('pipeline/index', (new PipelineService())->board()); }

    public function updateStatus(Request $request): void
    {
        try {
            (new LeadService())->changeStatus((int) $request->input('id', 0), (string) $request->input('status', ''));
            $_SESSION['_flash']['success'] = 'Etapa comercial atualizada.';
        } catch (RuntimeException $e) { $_SESSION['_flash']['error'] = $e->getMessage(); }
        Response::redirect('/pipeline');
    }
}
