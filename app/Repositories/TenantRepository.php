<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

final class TenantRepository
{
    public function create(string $name): int
    {
        $slug = 'workspace-' . bin2hex(random_bytes(16));
        $stmt = Database::connection()->prepare('INSERT INTO tenants (nome, slug) VALUES (:nome, :slug)');
        $stmt->execute(['nome' => $name, 'slug' => $slug]);
        return (int) Database::connection()->lastInsertId();
    }
}
