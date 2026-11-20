<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Repositories\AuditRepository;
use App\Repositories\LeadPrivacyRepository;
use Throwable;

final class PrivacyService
{
    public function __construct(
        private readonly LeadPrivacyRepository $privacy = new LeadPrivacyRepository(),
        private readonly AuditRepository $audit = new AuditRepository(),
    ) {}

    public function anonymizeLead(int $id): void
    {
        $pdo = Database::connection(); $pdo->beginTransaction();
        try { $this->privacy->anonymize($id); $this->audit->log('ANONYMIZE', 'leads', $id); $pdo->commit(); }
        catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
    }

    public function hardDeleteLead(int $id): void
    {
        $pdo = Database::connection(); $pdo->beginTransaction();
        try { $this->audit->log('DELETE', 'leads', $id); $this->privacy->hardDelete($id); $pdo->commit(); }
        catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
    }
}
