<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Helpers\Database;

final class LoginAttemptRepository
{
    public function blocked(string $email, string $ip): bool
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM tentativas_login WHERE sucesso = 0 AND criado_em >= :cutoff AND (email = :email OR ip = :ip)');
        $stmt->execute(['cutoff' => date('Y-m-d H:i:s', time() - 900), 'email' => $email, 'ip' => $ip]);
        return (int) $stmt->fetchColumn() >= 5;
    }

    public function record(string $email, string $ip, bool $success): void
    {
        $stmt = Database::connection()->prepare('INSERT INTO tentativas_login (email, ip, sucesso, user_agent) VALUES (:email, :ip, :sucesso, :ua)');
        $stmt->execute(['email' => $email, 'ip' => $ip, 'sucesso' => $success ? 1 : 0, 'ua' => mb_substr($_SERVER['HTTP_USER_AGENT'] ?? 'desconhecido', 0, 255)]);
    }
}
