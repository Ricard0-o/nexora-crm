<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Helpers\TenantContext;

final class ProjectRepository
{
    public function all(): array
    {
        $stmt = Database::connection()->prepare('SELECT p.*, l.nome_empresa FROM projetos p INNER JOIN leads l ON l.id = p.lead_id AND l.tenant_id = p.tenant_id WHERE p.tenant_id = :tenant_id ORDER BY p.atualizado_em DESC LIMIT 50');
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function metrics(): array
    {
        $pdo = Database::connection();
        $tenantId = TenantContext::id();
        $count = $pdo->prepare("SELECT COUNT(*) FROM projetos WHERE tenant_id = :tenant_id AND status <> 'Entregue'");
        $count->execute(['tenant_id' => $tenantId]);
        $forecast = $pdo->prepare('SELECT COALESCE(SUM(valor_total),0) FROM projetos WHERE tenant_id = :tenant_id');
        $forecast->execute(['tenant_id' => $tenantId]);
        $received = $pdo->prepare('SELECT COALESCE(SUM(valor_pago),0) FROM projetos WHERE tenant_id = :tenant_id');
        $received->execute(['tenant_id' => $tenantId]);
        return ['ativos' => (int) $count->fetchColumn(), 'prevista' => (float) $forecast->fetchColumn(), 'recebida' => (float) $received->fetchColumn()];
    }

    public function searchGlobal(string $term, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = Database::connection()->prepare("SELECT p.id, p.codigo, p.nome, p.status, p.progresso, p.data_entrega, l.nome_empresa FROM projetos p INNER JOIN leads l ON l.id = p.lead_id AND l.tenant_id = p.tenant_id WHERE p.tenant_id = :tenant_id AND (p.codigo LIKE :term_code OR p.nome LIKE :term_name OR l.nome_empresa LIKE :term_company) ORDER BY p.atualizado_em DESC LIMIT {$limit}");
        $pattern = '%' . $term . '%';
        $stmt->execute(['tenant_id' => TenantContext::id(), 'term_code' => $pattern, 'term_name' => $pattern, 'term_company' => $pattern]);
        return $stmt->fetchAll();
    }

    public function chartRows(string $since): array
    {
        $stmt = Database::connection()->prepare('SELECT status, valor_total, valor_pago, data_contratacao FROM projetos WHERE tenant_id = :tenant_id AND data_contratacao >= :since ORDER BY data_contratacao');
        $stmt->execute(['tenant_id' => TenantContext::id(), 'since' => $since]);
        return $stmt->fetchAll();
    }

    public function recent(int $limit = 5): array
    {
        $limit = max(1, min(10, $limit));
        $stmt = Database::connection()->prepare("SELECT p.*, l.nome_empresa FROM projetos p INNER JOIN leads l ON l.id = p.lead_id AND l.tenant_id = p.tenant_id WHERE p.tenant_id = :tenant_id ORDER BY p.atualizado_em DESC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function upcoming(int $limit = 5): array
    {
        $limit = max(1, min(10, $limit));
        $stmt = Database::connection()->prepare("SELECT p.id, p.codigo, p.nome, p.status, p.data_vencimento, p.saldo, l.nome_empresa FROM projetos p INNER JOIN leads l ON l.id = p.lead_id AND l.tenant_id = p.tenant_id WHERE p.tenant_id = :tenant_id AND p.data_vencimento IS NOT NULL AND p.saldo > 0 ORDER BY p.data_vencimento ASC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT p.*, l.nome_empresa FROM projetos p INNER JOIN leads l ON l.id = p.lead_id AND l.tenant_id = p.tenant_id WHERE p.id = :id AND p.tenant_id = :tenant_id LIMIT 1');
        $stmt->execute(['id' => $id, 'tenant_id' => TenantContext::id()]);
        return $stmt->fetch() ?: null;
    }

    public function findByLeadId(int $leadId): array
    {
        $stmt = Database::connection()->prepare('SELECT id, codigo, nome, status, progresso, valor_total, valor_pago, saldo, data_contratacao, data_entrega, data_vencimento, atualizado_em FROM projetos WHERE lead_id = :lead_id AND tenant_id = :tenant_id ORDER BY atualizado_em DESC LIMIT 50');
        $stmt->execute(['lead_id' => $leadId, 'tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function nextCode(): string
    {
        $stmt = Database::connection()->prepare('SELECT COALESCE(MAX(id), 0) + 1 FROM projetos WHERE tenant_id = :tenant_id');
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        $next = (int) $stmt->fetchColumn();
        return 'NX-' . date('Y') . '-' . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }

    public function codeExists(string $code, ?int $ignoreId = null): bool
    {
        $sql = 'SELECT COUNT(*) FROM projetos WHERE tenant_id = :tenant_id AND codigo = :codigo';
        $params = ['tenant_id' => TenantContext::id(), 'codigo' => $code];
        if ($ignoreId !== null) { $sql .= ' AND id <> :ignore_id'; $params['ignore_id'] = $ignoreId; }
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute($params);
        return (int) $stmt->fetchColumn() > 0;
    }

    public function create(array $data): int
    {
        $stmt = Database::connection()->prepare('INSERT INTO projetos (tenant_id, lead_id, codigo, nome, status, progresso, valor_total, valor_pago, saldo, data_contratacao, data_entrega, data_vencimento) VALUES (:tenant_id, :lead_id, :codigo, :nome, :status, :progresso, :valor_total, :valor_pago, :saldo, :data_contratacao, :data_entrega, :data_vencimento)');
        $stmt->execute(['tenant_id' => TenantContext::id()] + $data);
        return (int) Database::connection()->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['id'] = $id;
        $data['tenant_id'] = TenantContext::id();
        $stmt = Database::connection()->prepare('UPDATE projetos SET lead_id = :lead_id, codigo = :codigo, nome = :nome, status = :status, progresso = :progresso, valor_total = :valor_total, valor_pago = :valor_pago, saldo = :saldo, data_contratacao = :data_contratacao, data_entrega = :data_entrega, data_vencimento = :data_vencimento, atualizado_em = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute($data);
    }
}
