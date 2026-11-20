<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditRepository;
use App\Repositories\DashboardRepository;
use App\Repositories\LeadRepository;
use App\Repositories\ProjectRepository;
use DateInterval;
use DatePeriod;
use DateTimeImmutable;

final class DashboardService
{
    private const PERIOD_OPTIONS = [
        '1m' => '1 mês',
        '3m' => '3 meses',
        '6m' => '6 meses',
        '12m' => '12 meses',
        'all' => 'Todo o histórico',
    ];
    private const FUNNEL_STATUSES = ['Novo', 'Em Contato', 'Negociação', 'Contratado'];
    private const PROJECT_STATUSES = ['Briefing', 'Layout', 'Desenvolvimento', 'Homologação', 'Entregue'];

    public function data(string $requestedPeriod = ''): array
    {
        $settings = (new SettingsService())->values();
        $defaultPeriod = in_array($settings['dashboard_period'], array_keys(self::PERIOD_OPTIONS), true)
            ? $settings['dashboard_period']
            : '6m';
        $period = array_key_exists($requestedPeriod, self::PERIOD_OPTIONS) ? $requestedPeriod : $defaultPeriod;
        $leads = new LeadRepository();
        $projects = new ProjectRepository();
        $leadMetrics = $leads->dashboardMetrics();
        $projectMetrics = $projects->metrics();
        $charts = $this->charts(new DashboardRepository(), $leadMetrics, $period);
        $contracted = $leadMetrics['status']['Contratado'] ?? 0;
        $conversionRate = $leadMetrics['total'] > 0 ? round(($contracted / $leadMetrics['total']) * 100, 1) : 0.0;

        return [
            'leadMetrics' => $leadMetrics,
            'projectMetrics' => $projectMetrics,
            'conversionRate' => $conversionRate,
            'charts' => $charts,
            'period' => $period,
            'periodOptions' => self::PERIOD_OPTIONS,
            'periodDescription' => $charts['description'],
            'recentLeads' => $leads->recent(),
            'recentProjects' => $projects->recent(4),
            'upcoming' => $projects->upcoming(4),
            'recentAudit' => (new AuditRepository())->recent(5),
        ];
    }

    private function charts(DashboardRepository $repository, array $leadMetrics, string $period): array
    {
        $window = $this->window($repository, $period);
        $buckets = $this->buckets($window['start'], $window['grain']);

        foreach ($repository->commercialSeries($window['queryStart'], $window['grain']) as $row) {
            $key = (string) $row['period_key'];
            if (isset($buckets[$key])) {
                $buckets[$key]['leads'] = (int) $row['leads'];
                $buckets[$key]['sales'] = (int) $row['sales'];
            }
        }
        foreach ($repository->financeSeries($window['queryStart'], $window['grain']) as $row) {
            $key = (string) $row['period_key'];
            if (isset($buckets[$key])) {
                $buckets[$key]['forecast'] = (float) $row['forecast'];
                $buckets[$key]['received'] = (float) $row['received'];
            }
        }

        $origins = $repository->originCounts($window['queryStart']);
        $projectStatuses = $this->normalizeCounts($repository->projectStatusCounts(), self::PROJECT_STATUSES, 'status');
        $leadStatuses = array_fill_keys(self::FUNNEL_STATUSES, 0);
        foreach ($leadMetrics['status'] as $status => $total) {
            if (array_key_exists($status, $leadStatuses)) {
                $leadStatuses[$status] = (int) $total;
            }
        }

        return [
            'labels' => array_column($buckets, 'label'),
            'leads' => array_column($buckets, 'leads'),
            'sales' => array_column($buckets, 'sales'),
            'forecast' => array_column($buckets, 'forecast'),
            'received' => array_column($buckets, 'received'),
            'originLabels' => array_map(static fn (mixed $value): string => (string) ($value ?: 'Não informado'), array_column($origins, 'origem')),
            'originValues' => array_map('intval', array_column($origins, 'total')),
            'projectStatusLabels' => array_keys($projectStatuses),
            'projectStatusValues' => array_values($projectStatuses),
            'funnelLabels' => array_keys($leadStatuses),
            'funnelValues' => array_values($leadStatuses),
            'description' => $window['description'],
            'maxXTicks' => match ($period) {
                '1m' => 8,
                '3m' => 3,
                '6m' => 6,
                default => 12,
            },
        ];
    }

    private function window(DashboardRepository $repository, string $period): array
    {
        $today = new DateTimeImmutable('today');
        if ($period === '1m') {
            $start = $today->modify('-29 days');
            return ['start' => $start, 'queryStart' => $start->format('Y-m-d 00:00:00'), 'grain' => 'day', 'description' => 'Últimos 30 dias'];
        }
        if ($period === 'all') {
            $earliest = $repository->earliestActivityDate();
            $start = $earliest ? new DateTimeImmutable(substr($earliest, 0, 10)) : $today;
            $start = $start->modify('first day of this month');
            return ['start' => $start, 'queryStart' => null, 'grain' => 'month', 'description' => 'Todo o histórico'];
        }

        $months = match ($period) {
            '3m' => 3,
            '12m' => 12,
            default => 6,
        };
        $start = (new DateTimeImmutable('first day of this month'))->modify('-' . ($months - 1) . ' months');
        return [
            'start' => $start,
            'queryStart' => $start->format('Y-m-d 00:00:00'),
            'grain' => 'month',
            'description' => "Últimos {$months} meses",
        ];
    }

    private function buckets(DateTimeImmutable $start, string $grain): array
    {
        $end = new DateTimeImmutable('today');
        $interval = $grain === 'day' ? new DateInterval('P1D') : new DateInterval('P1M');
        if ($grain === 'month') {
            $end = $end->modify('first day of next month');
        } else {
            $end = $end->modify('+1 day');
        }
        $buckets = [];
        foreach (new DatePeriod($start, $interval, $end) as $date) {
            $key = $date->format($grain === 'day' ? 'Y-m-d' : 'Y-m');
            $buckets[$key] = [
                'label' => $date->format($grain === 'day' ? 'd/m' : 'm/Y'),
                'leads' => 0,
                'sales' => 0,
                'forecast' => 0.0,
                'received' => 0.0,
            ];
        }
        return $buckets;
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
}
