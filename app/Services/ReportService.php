<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Env;
use App\Repositories\AuditRepository;
use App\Repositories\LeadRepository;
use App\Repositories\ProjectRepository;
use App\Repositories\ReportRepository;
use DateTimeImmutable;
use InvalidArgumentException;

final class ReportService
{
    private const LEAD_STATUSES = ['Novo', 'Em Contato', 'Negociação', 'Contratado', 'Recusado'];
    private const PROJECT_STATUSES = ['Briefing', 'Layout', 'Desenvolvimento', 'Homologação', 'Entregue'];

    public function data(): array
    {
        $reports = new ReportRepository();
        $leadMetrics = (new LeadRepository())->dashboardMetrics();
        $projectRepository = new ProjectRepository();
        $projectMetrics = $projectRepository->metrics();
        $leadStatuses = $this->normalizeCounts($reports->leadStatusCounts(), self::LEAD_STATUSES, 'status');
        $projectStatuses = $this->normalizeCounts($reports->projectStatusCounts(), self::PROJECT_STATUSES, 'status');
        $auditCounts = $this->counts($reports->auditActionCounts((new DateTimeImmutable('-30 days'))->format('Y-m-d H:i:s')), 'acao');
        $commercial = $this->monthlySeries($reports);
        $totalLeads = (int) $leadMetrics['total'];
        $contracts = (int) ($leadStatuses['Contratado'] ?? 0);
        $originRows = $reports->leadOriginCounts();

        return [
            'metrics' => [
                'leads' => $totalLeads,
                'conversion' => $totalLeads > 0 ? round(($contracts / $totalLeads) * 100, 1) : 0.0,
                'forecast' => (float) $projectMetrics['prevista'],
                'received' => (float) $projectMetrics['recebida'],
                'balance' => (float) $projectMetrics['prevista'] - (float) $projectMetrics['recebida'],
                'activeProjects' => (int) $projectMetrics['ativos'],
                'overdueProjects' => $reports->overdueProjects(date('Y-m-d')),
                'loginFailures' => (int) ($auditCounts['LOGIN_FAILURE'] ?? 0),
            ],
            'charts' => [
                ...$commercial,
                'leadStatusLabels' => array_keys($leadStatuses),
                'leadStatusValues' => array_values($leadStatuses),
                'projectStatusLabels' => array_keys($projectStatuses),
                'projectStatusValues' => array_values($projectStatuses),
                'originLabels' => array_map(static fn (mixed $value): string => (string) ($value ?: 'Não informado'), array_column($originRows, 'origem')),
                'originValues' => array_map('intval', array_column($originRows, 'total')),
                'auditLabels' => array_keys($auditCounts),
                'auditValues' => array_values($auditCounts),
            ],
            'leadStatuses' => $leadStatuses,
            'projectStatuses' => $projectStatuses,
            'recentProjects' => $projectRepository->recent(8),
            'recentAudit' => (new AuditRepository())->recent(8),
            'generatedAt' => date('d/m/Y H:i'),
        ];
    }

    public function export(string $type): array
    {
        $allowed = ['leads', 'projects', 'finance', 'audit'];
        if (!in_array($type, $allowed, true)) {
            throw new InvalidArgumentException('Tipo de relatório inválido.');
        }

        $limit = max(1, min(5000, (int) Env::get('REPORT_EXPORT_MAX_ROWS', '5000')));
        $repository = new ReportRepository();
        $definition = match ($type) {
            'leads' => [
                ['Empresa', 'Contato', 'E-mail', 'Telefone', 'Status', 'Origem', 'Base legal', 'Finalidade', 'Criado em'],
                $repository->leadExportRows($limit),
            ],
            'projects' => [
                ['Código', 'Projeto', 'Cliente', 'Status', 'Progresso (%)', 'Valor total', 'Valor pago', 'Saldo', 'Contratação', 'Entrega', 'Vencimento'],
                $repository->projectExportRows($limit),
            ],
            'finance' => [
                ['Código', 'Projeto', 'Cliente', 'Valor total', 'Valor pago', 'Saldo', 'Vencimento', 'Status'],
                $repository->financeExportRows($limit),
            ],
            'audit' => [
                ['Data', 'Usuário', 'Ação', 'Entidade', 'ID da entidade', 'IP', 'User agent', 'Metadados'],
                $repository->auditExportRows($limit),
            ],
        };

        $rows = array_map(fn (array $row): array => array_map([$this, 'safeCsvCell'], array_values($row)), $definition[1]);
        (new AuditRepository())->log('EXPORT', 'relatorio', null, null, ['tipo' => $type, 'registros' => count($rows)]);

        return [
            'filename' => 'nelber-code-' . $type . '-' . date('Y-m-d-His') . '.csv',
            'headers' => $definition[0],
            'rows' => $rows,
        ];
    }

    private function monthlySeries(ReportRepository $repository): array
    {
        $start = (new DateTimeImmutable('first day of this month'))->modify('-5 months');
        $monthNames = [1 => 'Jan', 2 => 'Fev', 3 => 'Mar', 4 => 'Abr', 5 => 'Mai', 6 => 'Jun', 7 => 'Jul', 8 => 'Ago', 9 => 'Set', 10 => 'Out', 11 => 'Nov', 12 => 'Dez'];
        $series = ['labels' => [], 'leads' => [], 'contracts' => [], 'forecast' => [], 'received' => []];

        for ($i = 0; $i < 6; $i++) {
            $month = $start->modify("+{$i} months");
            $next = $month->modify('+1 month');
            $commercial = $repository->monthlyCommercial($month->format('Y-m-d 00:00:00'), $next->format('Y-m-d 00:00:00'));
            $finance = $repository->monthlyFinance($month->format('Y-m-d'), $next->format('Y-m-d'));
            $series['labels'][] = $monthNames[(int) $month->format('n')] . '/' . $month->format('y');
            $series['leads'][] = (int) $commercial['leads'];
            $series['contracts'][] = (int) $commercial['contracts'];
            $series['forecast'][] = (float) $finance['forecast'];
            $series['received'][] = (float) $finance['received'];
        }

        return $series;
    }

    private function normalizeCounts(array $rows, array $knownKeys, string $keyColumn): array
    {
        $counts = array_fill_keys($knownKeys, 0);
        foreach ($rows as $row) {
            $key = (string) $row[$keyColumn];
            if (array_key_exists($key, $counts)) {
                $counts[$key] = (int) $row['total'];
            }
        }
        return $counts;
    }

    private function counts(array $rows, string $keyColumn): array
    {
        $counts = [];
        foreach ($rows as $row) {
            $counts[(string) $row[$keyColumn]] = (int) $row['total'];
        }
        return $counts;
    }

    private function safeCsvCell(mixed $value): string
    {
        $cell = (string) ($value ?? '');
        return preg_match('/^[=+\-@]/', $cell) === 1 ? "'" . $cell : $cell;
    }
}
