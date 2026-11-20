<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\View;
use App\Services\FinanceService;

final class FinanceController
{
    public function index(Request $request): void
    {
        View::render('finance/index', (new FinanceService())->data());
    }
}
