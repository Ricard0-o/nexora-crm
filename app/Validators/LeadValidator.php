<?php

declare(strict_types=1);

namespace App\Validators;

final class LeadValidator
{
    public function validate(array $data): array
    {
        $errors = [];
        foreach (['nome_empresa', 'ramo_atividade', 'email', 'telefone'] as $field) {
            if (!isset($data[$field]) || trim((string) $data[$field]) === '') { $errors[$field] = 'Campo obrigatório.'; }
        }
        $limits = ['nome' => 120, 'nome_empresa' => 180, 'ramo_atividade' => 120, 'email' => 190, 'telefone' => 30, 'origem' => 80, 'base_legal' => 100, 'finalidade' => 255];
        foreach ($limits as $field => $limit) {
            if (isset($data[$field]) && mb_strlen(trim((string) $data[$field])) > $limit) { $errors[$field] = "Máximo de {$limit} caracteres."; }
        }
        if (isset($data['email']) && !filter_var($data['email'], FILTER_VALIDATE_EMAIL)) { $errors['email'] = 'E-mail inválido.'; }
        if (isset($data['telefone']) && !preg_match('/^[0-9+()\-\s]{8,20}$/', (string) $data['telefone'])) { $errors['telefone'] = 'Telefone inválido.'; }
        $statuses = ['Novo', 'Em Contato', 'Negociação', 'Contratado', 'Recusado'];
        if (isset($data['status']) && !in_array($data['status'], $statuses, true)) { $errors['status'] = 'Status inválido.'; }
        if (mb_strlen((string) ($data['observacao_prospeccao'] ?? '')) > 3000) { $errors['observacao_prospeccao'] = 'Observação muito longa.'; }
        return $errors;
    }
}
