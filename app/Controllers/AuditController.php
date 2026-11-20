<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\View;
use App\Services\AuditService;

final class AuditController
{
    public function index(Request $request): void
    {
        View::render('audit/index', (new AuditService())->data());
    }
}
