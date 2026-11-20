<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Helpers\TenantContext;
use PDO;

final class DashboardRepository
{
    public function commercialSeries(?string $start, string $grain): array
    {
        $pdo = Database::connection();
        $period = $this->periodExpression($pdo, 'criado_em', $grain);
        $where = 'tenant_id = :tenant_id AND anonimizado_em IS NULL';
        $params = ['tenant_id' => TenantContext::id()];
        if ($start !== null) { $where .= ' AND criado_em >= :start_date'; $params['start_date'] = $start; }
        $stmt = $pdo->prepare("SELECT {$period} AS period_key, COUNT(*) AS leads, SUM(CASE WHEN status = 'Contratado' THEN 1 ELSE 0 END) AS sales FROM leads WHERE {$where} GROUP BY {$period} ORDER BY {$period}");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function financeSeries(?string $start, string $grain): array
    {
        $pdo = Database::connection();
        $period = $this->periodExpression($pdo, 'data_contratacao', $grain);
        $where = 'tenant_id = :tenant_id';
        $params = ['tenant_id' => TenantContext::id()];
        if ($start !== null) { $where .= ' AND data_contratacao >= :start_date'; $params['start_date'] = substr($start, 0, 10); }
        $stmt = $pdo->prepare("SELECT {$period} AS period_key, COALESCE(SUM(valor_total), 0) AS forecast, COALESCE(SUM(valor_pago), 0) AS received FROM projetos WHERE {$where} GROUP BY {$period} ORDER BY {$period}");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function originCounts(?string $start, int $limit = 6): array
    {
        $limit = max(1, min(12, $limit));
        $where = 'tenant_id = :tenant_id AND anonimizado_em IS NULL';
        $params = ['tenant_id' => TenantContext::id()];
        if ($start !== null) { $where .= ' AND criado_em >= :start_date'; $params['start_date'] = $start; }
        $stmt = Database::connection()->prepare("SELECT origem, COUNT(*) AS total FROM leads WHERE {$where} GROUP BY origem ORDER BY total DESC LIMIT {$limit}");
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function projectStatusCounts(): array
    {
        $stmt = Database::connection()->prepare('SELECT status, COUNT(*) AS total FROM projetos WHERE tenant_id = :tenant_id GROUP BY status ORDER BY status');
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function earliestActivityDate(): ?string
    {
        $tenantId = TenantContext::id();
        $leadStmt = Database::connection()->prepare('SELECT MIN(criado_em) FROM leads WHERE tenant_id = :tenant_id AND anonimizado_em IS NULL');
        $leadStmt->execute(['tenant_id' => $tenantId]);
        $projectStmt = Database::connection()->prepare('SELECT MIN(data_contratacao) FROM projetos WHERE tenant_id = :tenant_id');
        $projectStmt->execute(['tenant_id' => $tenantId]);
        $dates = array_values(array_filter([(string) $leadStmt->fetchColumn(), (string) $projectStmt->fetchColumn()]));
        sort($dates);
        return $dates[0] ?? null;
    }

    private function periodExpression(PDO $pdo, string $column, string $grain): string
    {
        $daily = $grain === 'day';
        return $pdo->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
            ? "TO_CHAR({$column}, '" . ($daily ? 'YYYY-MM-DD' : 'YYYY-MM') . "')"
            : "DATE_FORMAT({$column}, '" . ($daily ? '%Y-%m-%d' : '%Y-%m') . "')";
    }
}
