<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\View;

final class ModuleController
{
    public function settings(Request $r): void { View::render('settings/index'); }
}
