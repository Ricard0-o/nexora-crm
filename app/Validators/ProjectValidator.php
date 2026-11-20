<?php

declare(strict_types=1);

namespace App\Validators;

use DateTimeImmutable;

final class ProjectValidator
{
    public function validate(array $data): array
    {
        $errors = [];
        if (trim((string)($data['codigo'] ?? '')) === '' || mb_strlen((string)$data['codigo']) > 30) { $errors['codigo'] = 'Informe um código de até 30 caracteres.'; }
        if ((int)($data['lead_id'] ?? 0) < 1) { $errors['lead_id'] = 'Selecione uma empresa contratada.'; }
        if (trim((string)($data['nome'] ?? '')) === '') { $errors['nome'] = 'Informe o nome do projeto.'; }
        if (mb_strlen((string)($data['nome'] ?? '')) > 180) { $errors['nome'] = 'Máximo de 180 caracteres.'; }
        if (!in_array($data['status'] ?? '', ['Briefing','Layout','Desenvolvimento','Homologação','Entregue'], true)) { $errors['status'] = 'Status inválido.'; }
        $progress = (int)($data['progresso'] ?? -1);
        if ($progress < 0 || $progress > 100) { $errors['progresso'] = 'O progresso deve estar entre 0 e 100.'; }
        $total = (float)($data['valor_total'] ?? -1); $paid = (float)($data['valor_pago'] ?? -1);
        if ($total < 0) { $errors['valor_total'] = 'O valor total não pode ser negativo.'; }
        if ($paid < 0 || $paid > $total) { $errors['valor_pago'] = 'O valor pago deve estar entre zero e o valor total.'; }
        if (!$this->validDate((string)($data['data_contratacao'] ?? ''))) { $errors['data_contratacao'] = 'Informe uma data de contratação válida.'; }
        foreach (['data_entrega','data_vencimento'] as $field) { if (($data[$field] ?? null) !== null && !$this->validDate((string)$data[$field])) { $errors[$field] = 'Data inválida.'; } }
        return $errors;
    }

    private function validDate(string $date): bool
    {
        if ($date === '') { return false; }
        $parsed = DateTimeImmutable::createFromFormat('Y-m-d', $date);
        return $parsed !== false && $parsed->format('Y-m-d') === $date;
    }
}
