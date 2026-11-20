<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Helpers\TenantContext;
use PDO;
use RuntimeException;

final class PaymentRepository
{
    public function create(int $userId, string $reference, string $amount, string $environment): int
    {
        $stmt = Database::connection()->prepare(
            'INSERT INTO pagamentos (tenant_id, usuario_id, referencia_externa, valor, ambiente) VALUES (:tenant_id, :usuario, :referencia, :valor, :ambiente)'
        );
        $stmt->execute(['tenant_id' => TenantContext::id(), 'usuario' => $userId, 'referencia' => $reference, 'valor' => $amount, 'ambiente' => $environment]);
        return (int) Database::connection()->lastInsertId();
    }

    public function attachPreference(string $reference, string $preferenceId): void
    {
        $stmt = Database::connection()->prepare("UPDATE pagamentos SET preferencia_provedor_id = :preferencia, status = 'Pendente' WHERE referencia_externa = :referencia");
        $stmt->execute(['preferencia' => $preferenceId, 'referencia' => $reference]);
    }

    public function markCreationFailure(string $reference, string $detail): void
    {
        $stmt = Database::connection()->prepare("UPDATE pagamentos SET status = 'Falha', status_detalhe = :detalhe WHERE referencia_externa = :referencia");
        $stmt->execute(['detalhe' => mb_substr($detail, 0, 100), 'referencia' => $reference]);
    }

    public function findByReference(string $reference): ?array
    {
        $stmt = Database::connection()->prepare('SELECT * FROM pagamentos WHERE referencia_externa = :referencia LIMIT 1');
        $stmt->execute(['referencia' => $reference]);
        return $stmt->fetch() ?: null;
    }

    public function updateProviderStatus(string $reference, array $providerData): void
    {
        $stmt = Database::connection()->prepare(
            'UPDATE pagamentos SET pagamento_provedor_id = :pagamento_id, status = :status, status_detalhe = :detalhe, meio_pagamento = :meio, tipo_pagamento = :tipo, dados_provedor = :dados WHERE referencia_externa = :referencia'
        );
        $stmt->execute([
            'pagamento_id' => $providerData['id'],
            'status' => $providerData['local_status'],
            'detalhe' => $providerData['status_detail'],
            'meio' => $providerData['payment_method_id'],
            'tipo' => $providerData['payment_type_id'],
            'dados' => json_encode($providerData['safe_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'referencia' => $reference,
        ]);
    }

    public function approveAndActivate(string $reference, array $providerData, string $validUntil): bool
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT id, usuario_id, status FROM pagamentos WHERE referencia_externa = :referencia FOR UPDATE');
            $lock->execute(['referencia' => $reference]);
            $payment = $lock->fetch(PDO::FETCH_ASSOC);
            if (!$payment) {
                throw new RuntimeException('Pagamento local não encontrado.');
            }
            if ($payment['status'] === 'Aprovado') {
                $pdo->commit();
                return false;
            }

            $updatePayment = $pdo->prepare(
                "UPDATE pagamentos SET pagamento_provedor_id = :pagamento_id, status = 'Aprovado', status_detalhe = :detalhe, meio_pagamento = :meio, tipo_pagamento = :tipo, dados_provedor = :dados, aprovado_em = CURRENT_TIMESTAMP WHERE id = :id"
            );
            $updatePayment->execute([
                'pagamento_id' => $providerData['id'],
                'detalhe' => $providerData['status_detail'],
                'meio' => $providerData['payment_method_id'],
                'tipo' => $providerData['payment_type_id'],
                'dados' => json_encode($providerData['safe_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'id' => $payment['id'],
            ]);
            $activate = $pdo->prepare("UPDATE usuarios SET assinatura_status = 'Ativa', assinatura_valida_ate = :validade WHERE id = :usuario");
            $activate->execute(['validade' => $validUntil, 'usuario' => $payment['usuario_id']]);
            $pdo->commit();
            return true;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }

    public function cancelAndMaybeSuspend(string $reference, array $providerData): bool
    {
        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $lock = $pdo->prepare('SELECT id, usuario_id, status FROM pagamentos WHERE referencia_externa = :referencia FOR UPDATE');
            $lock->execute(['referencia' => $reference]);
            $payment = $lock->fetch(PDO::FETCH_ASSOC);
            if (!$payment) { throw new RuntimeException('Pagamento local não encontrado.'); }
            $wasApproved = $payment['status'] === 'Aprovado';

            $update = $pdo->prepare(
                "UPDATE pagamentos SET pagamento_provedor_id = :pagamento_id, status = 'Cancelado', status_detalhe = :detalhe, meio_pagamento = :meio, tipo_pagamento = :tipo, dados_provedor = :dados WHERE id = :id"
            );
            $update->execute([
                'pagamento_id' => $providerData['id'],
                'detalhe' => $providerData['status_detail'],
                'meio' => $providerData['payment_method_id'],
                'tipo' => $providerData['payment_type_id'],
                'dados' => json_encode($providerData['safe_data'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'id' => $payment['id'],
            ]);
            if ($wasApproved) {
                $suspend = $pdo->prepare("UPDATE usuarios SET assinatura_status = 'Inadimplente' WHERE id = :usuario");
                $suspend->execute(['usuario' => $payment['usuario_id']]);
            }
            $pdo->commit();
            return $wasApproved;
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw $exception;
        }
    }
}
