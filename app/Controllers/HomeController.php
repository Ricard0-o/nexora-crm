<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\View;

final class HomeController
{
    public function index(Request $request): void { View::render('home', [], 'public'); }

    public function demo(Request $request): void
    {
        View::render('demo/index', [], 'demo');
    }
}
