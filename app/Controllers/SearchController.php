<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\View;
use App\Services\SearchService;

final class SearchController
{
    public function index(Request $request): void
    {
        View::render('search/index', (new SearchService())->search((string) $request->input('q', '')));
    }
}
