<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\LeadRepository;

final class PipelineService
{
    public function board(): array
    {
        $stages = ['Novo', 'Em Contato', 'Negociação', 'Contratado', 'Recusado'];
        $columns = array_fill_keys($stages, []);
        foreach ((new LeadRepository())->pipeline() as $lead) { $columns[$lead['status']][] = $lead; }
        return ['columns' => $columns, 'stages' => $stages, 'total' => array_sum(array_map('count', $columns))];
    }
}
