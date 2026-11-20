<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Helpers\TenantContext;
use Throwable;

final class SettingsRepository
{
    public function values(): array
    {
        $stmt = Database::connection()->prepare('SELECT chave, valor FROM tenant_configuracoes WHERE tenant_id = :tenant_id');
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        $rows = $stmt->fetchAll();
        $values = [];
        foreach ($rows as $row) {
            $values[(string) $row['chave']] = (string) $row['valor'];
        }
        return $values;
    }

    public function save(array $values, int $userId): void
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $tenantId = TenantContext::id();
            $save = $pdo->prepare('INSERT INTO tenant_configuracoes (tenant_id, chave, valor, atualizado_por) VALUES (:tenant_id, :chave, :valor, :usuario) ON DUPLICATE KEY UPDATE valor = VALUES(valor), atualizado_por = VALUES(atualizado_por), atualizado_em = CURRENT_TIMESTAMP');
            foreach ($values as $key => $value) {
                $save->execute(['tenant_id' => $tenantId, 'chave' => $key, 'valor' => $value, 'usuario' => $userId]);
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            throw $exception;
        }
    }
}
