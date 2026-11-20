<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\ProjectRepository;

final class FinanceService
{
    public function data(): array
    {
        $repository = new ProjectRepository();

        return [
            'metrics' => $repository->metrics(),
            'projects' => $repository->all(),
        ];
    }
}
