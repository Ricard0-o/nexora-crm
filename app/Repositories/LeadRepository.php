<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;
use App\Helpers\TenantContext;

final class LeadRepository
{
    public function paginate(int $page = 1, int $perPage = 20, string $search = '', string $status = ''): array
    {
        $perPage = max(1, min(50, $perPage));
        $offset = max(0, ($page - 1) * $perPage);
        $conditions = ['tenant_id = :tenant_id', 'anonimizado_em IS NULL'];
        $params = ['tenant_id' => TenantContext::id()];
        if ($search !== '') {
            $conditions[] = '(nome_empresa LIKE :search_company OR nome LIKE :search_name OR email LIKE :search_email OR telefone LIKE :search_phone)';
            $pattern = '%' . $search . '%';
            $params += ['search_company' => $pattern, 'search_name' => $pattern, 'search_email' => $pattern, 'search_phone' => $pattern];
        }
        if (in_array($status, ['Novo', 'Em Contato', 'Negociação', 'Contratado', 'Recusado'], true)) { $conditions[] = 'status = :status'; $params['status'] = $status; }
        $where = ' WHERE ' . implode(' AND ', $conditions);
        $pdo = Database::connection();
        $count = $pdo->prepare('SELECT COUNT(*) FROM leads' . $where);
        $count->execute($params);
        $stmt = $pdo->prepare('SELECT * FROM leads' . $where . " ORDER BY criado_em DESC LIMIT {$perPage} OFFSET {$offset}");
        $stmt->execute($params);
        return ['items' => $stmt->fetchAll(), 'total' => (int) $count->fetchColumn(), 'page' => $page, 'per_page' => $perPage, 'status' => $status];
    }

    public function findDuplicate(string $email, string $phone, ?int $ignoreId = null): ?array
    {
        $sql = 'SELECT * FROM leads WHERE tenant_id = :tenant_id AND (email = :email OR telefone = :telefone)';
        $params = ['tenant_id' => TenantContext::id(), 'email' => mb_strtolower($email), 'telefone' => $phone];
        if ($ignoreId !== null) { $sql .= ' AND id <> :ignore_id'; $params['ignore_id'] = $ignoreId; }
        $stmt = Database::connection()->prepare($sql . ' LIMIT 1');
        $stmt->execute($params);
        return $stmt->fetch() ?: null;
    }

    public function findById(int $id): ?array
    {
        $stmt = Database::connection()->prepare('SELECT l.*, u.nome AS responsavel_nome FROM leads l LEFT JOIN usuarios u ON u.id = l.responsavel_id AND u.tenant_id = l.tenant_id WHERE l.id = :id AND l.tenant_id = :tenant_id AND l.anonimizado_em IS NULL LIMIT 1');
        $stmt->execute(['id' => $id, 'tenant_id' => TenantContext::id()]);
        return $stmt->fetch() ?: null;
    }

    public function create(array $data): int
    {
        $sql = 'INSERT INTO leads (tenant_id, nome, nome_empresa, ramo_atividade, telefone, email, status, origem, observacao_prospeccao, base_legal, finalidade, responsavel_id) VALUES (:tenant_id, :nome, :empresa, :ramo, :telefone, :email, :status, :origem, :observacao, :base_legal, :finalidade, :responsavel)';
        $stmt = Database::connection()->prepare($sql);
        $stmt->execute([
            'tenant_id' => TenantContext::id(),
            'nome' => $data['nome'] ?? null,
            'empresa' => trim($data['nome_empresa']),
            'ramo' => trim($data['ramo_atividade']),
            'telefone' => trim($data['telefone']),
            'email' => mb_strtolower(trim($data['email'])),
            'status' => $data['status'] ?? 'Novo',
            'origem' => $data['origem'] ?? 'Importação JSON',
            'observacao' => $data['observacao_prospeccao'] ?? null,
            'base_legal' => $data['base_legal'] ?? 'Legítimo interesse',
            'finalidade' => $data['finalidade'] ?? 'Prospecção comercial B2B',
            'responsavel' => $_SESSION['user']['id'] ?? null,
        ]);
        return (int) Database::connection()->lastInsertId();
    }

    public function updateObservation(int $id, string $observation): void
    {
        $stmt = Database::connection()->prepare('UPDATE leads SET observacao_prospeccao = :observacao, atualizado_em = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id');
        $stmt->execute(['observacao' => $observation, 'id' => $id, 'tenant_id' => TenantContext::id()]);
    }

    public function update(int $id, array $data): void
    {
        $stmt = Database::connection()->prepare('UPDATE leads SET nome = :nome, nome_empresa = :empresa, ramo_atividade = :ramo, telefone = :telefone, email = :email, status = :status, origem = :origem, observacao_prospeccao = :observacao, base_legal = :base_legal, finalidade = :finalidade, atualizado_em = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id AND anonimizado_em IS NULL');
        $stmt->execute([
            'nome' => $data['nome'], 'empresa' => $data['nome_empresa'], 'ramo' => $data['ramo_atividade'],
            'telefone' => $data['telefone'], 'email' => $data['email'], 'status' => $data['status'],
            'origem' => $data['origem'], 'observacao' => $data['observacao_prospeccao'],
            'base_legal' => $data['base_legal'], 'finalidade' => $data['finalidade'], 'id' => $id, 'tenant_id' => TenantContext::id(),
        ]);
    }

    public function updateStatus(int $id, string $status): void
    {
        $stmt = Database::connection()->prepare('UPDATE leads SET status = :status, atualizado_em = CURRENT_TIMESTAMP WHERE id = :id AND tenant_id = :tenant_id AND anonimizado_em IS NULL');
        $stmt->execute(['status' => $status, 'id' => $id, 'tenant_id' => TenantContext::id()]);
    }

    public function pipeline(int $limit = 50): array
    {
        $limit = max(1, min(50, $limit));
        $stmt = Database::connection()->prepare("SELECT id, nome, nome_empresa, email, telefone, status, origem, atualizado_em FROM leads WHERE tenant_id = :tenant_id AND anonimizado_em IS NULL ORDER BY atualizado_em DESC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function contractedOptions(): array
    {
        $stmt = Database::connection()->prepare("SELECT id, nome_empresa, email FROM leads WHERE tenant_id = :tenant_id AND status = 'Contratado' AND anonimizado_em IS NULL ORDER BY nome_empresa");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function dashboardMetrics(): array
    {
        $pdo = Database::connection();
        $counts = [];
        $countStmt = $pdo->prepare('SELECT status, COUNT(*) total FROM leads WHERE tenant_id = :tenant_id AND anonimizado_em IS NULL GROUP BY status');
        $countStmt->execute(['tenant_id' => TenantContext::id()]);
        foreach ($countStmt->fetchAll() as $row) { $counts[$row['status']] = (int) $row['total']; }
        $total = array_sum($counts);
        $monthStmt = $pdo->prepare('SELECT COUNT(*) FROM leads WHERE tenant_id = :tenant_id AND criado_em >= :month_start');
        $monthStmt->execute(['tenant_id' => TenantContext::id(), 'month_start' => date('Y-m-01 00:00:00')]);
        $month = (int) $monthStmt->fetchColumn();
        return ['total' => $total, 'month' => $month, 'status' => $counts];
    }

    public function recent(int $limit = 5): array
    {
        $limit = max(1, min(10, $limit));
        $stmt = Database::connection()->prepare("SELECT * FROM leads WHERE tenant_id = :tenant_id AND anonimizado_em IS NULL ORDER BY criado_em DESC LIMIT {$limit}");
        $stmt->execute(['tenant_id' => TenantContext::id()]);
        return $stmt->fetchAll();
    }

    public function searchGlobal(string $term, int $limit = 10): array
    {
        $limit = max(1, min(20, $limit));
        $stmt = Database::connection()->prepare("SELECT id, nome, nome_empresa, email, telefone, status, origem, criado_em FROM leads WHERE tenant_id = :tenant_id AND anonimizado_em IS NULL AND (nome_empresa LIKE :term_company OR nome LIKE :term_name OR email LIKE :term_email OR telefone LIKE :term_phone) ORDER BY atualizado_em DESC LIMIT {$limit}");
        $pattern = '%' . $term . '%';
        $stmt->execute(['tenant_id' => TenantContext::id(), 'term_company' => $pattern, 'term_name' => $pattern, 'term_email' => $pattern, 'term_phone' => $pattern]);
        return $stmt->fetchAll();
    }

    public function chartRows(string $since): array
    {
        $stmt = Database::connection()->prepare('SELECT status, origem, criado_em FROM leads WHERE tenant_id = :tenant_id AND anonimizado_em IS NULL AND criado_em >= :since ORDER BY criado_em');
        $stmt->execute(['tenant_id' => TenantContext::id(), 'since' => $since]);
        return $stmt->fetchAll();
    }
}
