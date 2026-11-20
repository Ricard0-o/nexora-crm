<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LeadRepository;
use App\Repositories\ProjectRepository;

final class SearchService
{
    public function search(string $query): array
    {
        $query = trim($query);
        if (mb_strlen($query) < 2) { return ['query' => $query, 'leads' => [], 'projects' => [], 'searched' => false]; }
        return [
            'query' => $query,
            'leads' => (new LeadRepository())->searchGlobal($query),
            'projects' => (new ProjectRepository())->searchGlobal($query),
            'searched' => true,
        ];
    }
}
