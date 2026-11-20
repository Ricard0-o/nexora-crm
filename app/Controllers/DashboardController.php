<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\View;
use App\Services\DashboardService;

final class DashboardController
{
    public function index(Request $request): void
    {
        View::render('dashboard/index', (new DashboardService())->data((string) $request->input('period', '')));
    }
}
