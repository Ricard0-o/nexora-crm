<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Env;
use App\Repositories\AuditRepository;
use App\Repositories\SettingsRepository;
use DateTimeZone;
use RuntimeException;

final class SettingsService
{
    private const PERIODS = ['1m', '3m', '6m', '12m', 'all'];
    private const SESSION_TIMEOUTS = ['15', '30', '60', '120'];

    public function values(): array
    {
        return array_merge($this->defaults(), (new SettingsRepository())->values());
    }

    public function data(): array
    {
        return [
            'settings' => $this->values(),
            'timezones' => DateTimeZone::listIdentifiers(DateTimeZone::AMERICA),
            'periods' => ['1m' => '1 mês', '3m' => '3 meses', '6m' => '6 meses', '12m' => '12 meses', 'all' => 'Todo o histórico'],
            'sessionTimeouts' => ['15' => '15 minutos', '30' => '30 minutos', '60' => '1 hora', '120' => '2 horas'],
        ];
    }

    public function update(array $input, int $userId): void
    {
        $organizationName = trim((string) ($input['organization_name'] ?? ''));
        $timezone = trim((string) ($input['timezone'] ?? ''));
        $dashboardPeriod = (string) ($input['dashboard_period'] ?? '');
        $sessionTimeout = (string) ($input['session_timeout_minutes'] ?? '');

        if (mb_strlen($organizationName) < 2 || mb_strlen($organizationName) > 120) {
            throw new RuntimeException('O nome da organização deve ter entre 2 e 120 caracteres.');
        }
        if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
            throw new RuntimeException('Selecione um fuso horário válido.');
        }
        if (!in_array($dashboardPeriod, self::PERIODS, true)) {
            throw new RuntimeException('Selecione um período padrão válido.');
        }
        if (!in_array($sessionTimeout, self::SESSION_TIMEOUTS, true)) {
            throw new RuntimeException('Selecione um tempo de sessão válido.');
        }

        $values = [
            'organization_name' => $organizationName,
            'timezone' => $timezone,
            'dashboard_period' => $dashboardPeriod,
            'session_timeout_minutes' => $sessionTimeout,
        ];
        (new SettingsRepository())->save($values, $userId);
        $_SESSION['settings'] = $values;
        date_default_timezone_set($timezone);
        (new AuditRepository())->log('UPDATE', 'configuracoes', null, $userId, ['campos' => array_keys($values)]);
    }

    private function defaults(): array
    {
        return [
            'organization_name' => (string) ($_SESSION['user']['tenant_name'] ?? Env::get('APP_NAME', 'Nelber Code')),
            'timezone' => Env::get('TIMEZONE', 'America/Sao_Paulo'),
            'dashboard_period' => '6m',
            'session_timeout_minutes' => '30',
        ];
    }
}
