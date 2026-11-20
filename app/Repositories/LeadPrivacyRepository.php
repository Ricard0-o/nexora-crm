<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Helpers\TenantContext;

final class LeadPrivacyRepository
{
    public function anonymize(int $id): void
    {
        $token = bin2hex(random_bytes(12));
        $stmt = Database::connection()->prepare("UPDATE leads SET nome = NULL, nome_empresa = 'Anonimizado', ramo_atividade = 'Não aplicável', telefone = :telefone, email = :email, observacao_prospeccao = NULL, status = 'Recusado', base_legal = 'Anonimizado', finalidade = 'Registro estatístico', anonimizado_em = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id AND anonimizado_em IS NULL");
        $stmt->execute(['telefone' => 'anon-' . $token, 'email' => 'anon-' . $token . '@invalid.local', 'id' => $id, 'tenant_id' => TenantContext::id()]);
    }

    public function hardDelete(int $id): void
    {
        $stmt = Database::connection()->prepare('DELETE FROM leads WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['id' => $id, 'tenant_id' => TenantContext::id()]);
    }
}
