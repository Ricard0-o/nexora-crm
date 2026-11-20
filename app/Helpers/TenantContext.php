<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

/** Tenant identity is loaded from the authenticated server-side session only. */
final class TenantContext
{
    public static function id(): int
    {
        $tenantId = filter_var($_SESSION['user']['tenant_id'] ?? null, FILTER_VALIDATE_INT);
        if ($tenantId === false || $tenantId === null || $tenantId < 1) {
            throw new RuntimeException('Contexto da empresa indisponível. Entre novamente.');
        }

        return $tenantId;
    }

    public static function sessionId(): ?int
    {
        $tenantId = filter_var($_SESSION['user']['tenant_id'] ?? null, FILTER_VALIDATE_INT);
        return $tenantId !== false && $tenantId !== null && $tenantId > 0 ? $tenantId : null;
    }
}
