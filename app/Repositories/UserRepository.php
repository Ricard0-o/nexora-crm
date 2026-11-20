<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

final class UserRepository
{
    public function findByEmail(string $email): ?array
    {
        $stmt = Database::connection()->prepare('SELECT u.id, u.nome, u.email, u.senha_hash, u.perfil, u.ativo, u.tenant_id, t.nome AS tenant_name, u.assinatura_status, u.assinatura_valida_ate FROM usuarios u INNER JOIN tenants t ON t.id = u.tenant_id AND t.ativo = 1 WHERE u.email = :email LIMIT 1');
        $stmt->execute(['email' => mb_strtolower($email)]);
        return $stmt->fetch() ?: null;
    }

    public function findAccessById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT u.id, u.nome, u.email, u.perfil, u.ativo, u.tenant_id, t.nome AS tenant_name, u.assinatura_status, u.assinatura_valida_ate FROM usuarios u INNER JOIN tenants t ON t.id = u.tenant_id AND t.ativo = 1 WHERE u.id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        return $stmt->fetch() ?: null;
    }

    public function create(string $name, string $email, string $passwordHash, int $tenantId): int
    {
        $stmt = Database::connection()->prepare(
            "INSERT INTO usuarios (tenant_id, nome, email, senha_hash, perfil, ativo, assinatura_status) VALUES (:tenant_id, :nome, :email, :senha, 'Cliente', 1, 'Pendente')"
        );
        $stmt->execute([
            'tenant_id' => $tenantId,
            'nome' => $name,
            'email' => mb_strtolower($email),
            'senha' => $passwordHash,
        ]);

        return (int) Database::connection()->lastInsertId();
    }

    public function updateLastLogin(int $id): void
    {
        $stmt = Database::connection()->prepare('UPDATE usuarios SET ultimo_login_em = CURRENT_TIMESTAMP WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function allForAdministration(int $limit = 50): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = Database::connection()->prepare("SELECT id, nome, email, perfil, ativo, assinatura_status, assinatura_valida_ate, ultimo_login_em, criado_em FROM usuarios WHERE tenant_id = :tenant_id ORDER BY criado_em DESC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => \App\Helpers\TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function updateSubscription(int $id, string $status, ?string $validUntil): void
    {
        $stmt = Database::connection()->prepare('UPDATE usuarios SET assinatura_status = :status, assinatura_valida_ate = :validade WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['status' => $status, 'validade' => $validUntil, 'id' => $id, 'tenant_id' => \App\Helpers\TenantContext::id()]);
    }
}
