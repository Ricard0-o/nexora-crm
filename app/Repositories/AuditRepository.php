<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\ClientIp;
use App\Helpers\Database;
use App\Helpers\TenantContext;

final class AuditRepository
{
    public function log(string $action, string $entity, ?int $entityId = null, ?int $userId = null, array $metadata = []): void
    {
        $actorId = $userId ?? ($_SESSION['user']['id'] ?? null);
        $tenantId = TenantContext::sessionId();
        if ($tenantId === null && $actorId !== null) {
            $lookup = Database::connection()->prepare('SELECT tenant_id FROM usuarios WHERE id = :id LIMIT 1');
            $lookup->execute(['id' => $actorId]);
            $tenantId = ($resolved = $lookup->fetchColumn()) !== false ? (int) $resolved : null;
        }
        if ($tenantId === null && $entity === 'usuarios' && $entityId !== null) {
            $lookup = Database::connection()->prepare('SELECT tenant_id FROM usuarios WHERE id = :id LIMIT 1');
            $lookup->execute(['id' => $entityId]);
            $tenantId = ($resolved = $lookup->fetchColumn()) !== false ? (int) $resolved : null;
        }
        $stmt = Database::connection()->prepare('INSERT INTO auditoria (tenant_id, id_usuario, acao, entidade, id_entidade, ip, user_agent, metadados) VALUES (:tenant_id, :usuario, :acao, :entidade, :entidade_id, :ip, :ua, :meta)');
        $stmt->execute([
            'tenant_id' => $tenantId,
            'usuario' => $actorId,
            'acao' => $action,
            'entidade' => $entity,
            'entidade_id' => $entityId,
            'ip' => ClientIp::resolve(),
            'ua' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido', 0, 255),
            'meta' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function recent(int $limit = 20): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = Database::connection()->prepare("SELECT a.*, u.nome AS usuario_nome FROM auditoria a LEFT JOIN usuarios u ON u.id = a.id_usuario AND u.tenant_id = a.tenant_id WHERE a.tenant_id = :tenant_id ORDER BY a.criado_em DESC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }
}
