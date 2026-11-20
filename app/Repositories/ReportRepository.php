<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Helpers\TenantContext;

final class ReportRepository
{
    public function leadStatusCounts(): array
    {
        $stmt = Database::connection()->prepare('SELECT status, COUNT(*) AS total FROM leads WHERE tenant_id = :tenant_id AND anonimizado_em IS NULL GROUP BY status ORDER BY status');
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function leadOriginCounts(int $limit = 8): array
    {
        $limit = max(1, min(12, $limit));
        $stmt = Database::connection()->prepare("SELECT origem, COUNT(*) AS total FROM leads WHERE tenant_id = :tenant_id AND anonimizado_em IS NULL GROUP BY origem ORDER BY total DESC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function projectStatusCounts(): array
    {
        $stmt = Database::connection()->prepare('SELECT status, COUNT(*) AS total FROM projetos WHERE tenant_id = :tenant_id GROUP BY status ORDER BY status');
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function monthlyCommercial(string $start, string $end): array
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) AS leads, SUM(CASE WHEN status = 'Contratado' THEN 1 ELSE 0 END) AS contracts FROM leads WHERE tenant_id = :tenant_id AND anonimizado_em IS NULL AND criado_em >= :start_date AND criado_em < :end_date");
        $stmt->execute(['tenant_id' => TenantContext::id(), 'start_date' => $start, 'end_date' => $end]);
        return $stmt->fetch() ?: ['leads' => 0, 'contracts' => 0];
    }

    public function monthlyFinance(string $start, string $end): array
    {
        $stmt = Database::connection()->prepare('SELECT COALESCE(SUM(valor_total), 0) AS forecast, COALESCE(SUM(valor_pago), 0) AS received FROM projetos WHERE tenant_id = :tenant_id AND data_contratacao >= :start_date AND data_contratacao < :end_date');
        $stmt->execute(['tenant_id' => TenantContext::id(), 'start_date' => $start, 'end_date' => $end]);
        return $stmt->fetch() ?: ['forecast' => 0, 'received' => 0];
    }

    public function overdueProjects(string $today): int
    {
        $stmt = Database::connection()->prepare("SELECT COUNT(*) FROM projetos WHERE tenant_id = :tenant_id AND saldo > 0 AND data_vencimento IS NOT NULL AND data_vencimento < :today AND status <> 'Entregue'");
        $stmt->execute(['tenant_id' => TenantContext::id(), 'today' => $today]);
        return (int) $stmt->fetchColumn();
    }

    public function auditActionCounts(string $since): array
    {
        $stmt = Database::connection()->prepare('SELECT acao, COUNT(*) AS total FROM auditoria WHERE tenant_id = :tenant_id AND criado_em >= :since GROUP BY acao ORDER BY total DESC');
        $stmt->execute(['tenant_id' => TenantContext::id(), 'since' => $since]);
        return $stmt->fetchAll();
    }

    public function leadExportRows(int $limit): array
    {
        $limit = max(1, min(5000, $limit));
        $stmt = Database::connection()->prepare("SELECT nome_empresa, nome, email, telefone, status, origem, base_legal, finalidade, criado_em FROM leads WHERE tenant_id = :tenant_id AND anonimizado_em IS NULL ORDER BY criado_em DESC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function projectExportRows(int $limit): array
    {
        $limit = max(1, min(5000, $limit));
        $stmt = Database::connection()->prepare("SELECT p.codigo, p.nome, l.nome_empresa, p.status, p.progresso, p.valor_total, p.valor_pago, p.saldo, p.data_contratacao, p.data_entrega, p.data_vencimento FROM projetos p INNER JOIN leads l ON l.id = p.lead_id AND l.tenant_id = p.tenant_id WHERE p.tenant_id = :tenant_id ORDER BY p.atualizado_em DESC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function financeExportRows(int $limit): array
    {
        $limit = max(1, min(5000, $limit));
        $stmt = Database::connection()->prepare("SELECT p.codigo, p.nome, l.nome_empresa, p.valor_total, p.valor_pago, p.saldo, p.data_vencimento, p.status FROM projetos p INNER JOIN leads l ON l.id = p.lead_id AND l.tenant_id = p.tenant_id WHERE p.tenant_id = :tenant_id ORDER BY p.data_vencimento IS NULL, p.data_vencimento ASC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function auditExportRows(int $limit): array
    {
        $limit = max(1, min(5000, $limit));
        $stmt = Database::connection()->prepare("SELECT a.criado_em, u.nome AS usuario_nome, a.acao, a.entidade, a.id_entidade, a.ip, a.user_agent, a.metadados FROM auditoria a LEFT JOIN usuarios u ON u.id = a.id_usuario AND u.tenant_id = a.tenant_id WHERE a.tenant_id = :tenant_id ORDER BY a.criado_em DESC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }
}
