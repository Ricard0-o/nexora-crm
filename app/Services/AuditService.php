<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditRepository;

final class AuditService
{
    public function data(int $limit = 50): array
    {
        return ['events' => (new AuditRepository())->recent($limit)];
    }
}
