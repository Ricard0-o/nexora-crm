<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditRepository;
use App\Repositories\UserRepository;
use DateTimeImmutable;
use RuntimeException;

final class UserService
{
    private const STATUSES = ['Pendente', 'Ativa', 'Inadimplente', 'Cancelada'];

    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly AuditRepository $audit = new AuditRepository(),
    ) {}

    public function administrationData(): array
    {
        return ['users' => $this->users->allForAdministration(), 'statuses' => self::STATUSES];
    }

    public function updateSubscription(int $id, string $status, string $validUntil): void
    {
        if ($id < 1 || !in_array($status, self::STATUSES, true)) {
            throw new RuntimeException('Dados de assinatura inválidos.');
        }

        $normalizedDate = null;
        if ($validUntil !== '') {
            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $validUntil);
            $errors = DateTimeImmutable::getLastErrors();
            if (!$date || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0))) {
                throw new RuntimeException('Informe uma data de validade válida.');
            }
            $normalizedDate = $date->format('Y-m-d 23:59:59');
        }

        $this->users->updateSubscription($id, $status, $normalizedDate);
        $this->audit->log('UPDATE_SUBSCRIPTION', 'usuarios', $id, null, [
            'status' => $status,
            'valid_until' => $normalizedDate,
        ]);
    }
}
