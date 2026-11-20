<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Repositories\AuditRepository;
use App\Repositories\TenantRepository;
use App\Repositories\LoginAttemptRepository;
use App\Repositories\UserRepository;
use RuntimeException;

final class AuthService
{
    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly TenantRepository $tenants = new TenantRepository(),
        private readonly LoginAttemptRepository $attempts = new LoginAttemptRepository(),
        private readonly AuditRepository $audit = new AuditRepository(),
    ) {}

    public function login(string $email, string $password, string $ip): array
    {
        $email = mb_strtolower(trim($email));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || $password === '') { throw new RuntimeException('Credenciais inválidas.'); }
        if ($this->attempts->blocked($email, $ip)) { throw new RuntimeException('Acesso temporariamente bloqueado. Tente novamente em 15 minutos.'); }
        $user = $this->users->findByEmail($email);
        $valid = $user !== null && (bool) $user['ativo'] && password_verify($password, $user['senha_hash']);
        $this->attempts->record($email, $ip, $valid);
        if (!$valid) {
            $this->audit->log('LOGIN_FAILURE', 'usuarios', $user ? (int) $user['id'] : null, null, ['email_hash' => hash('sha256', $email)]);
            usleep(random_int(150000, 350000));
            throw new RuntimeException('Credenciais inválidas.');
        }
        if (password_needs_rehash($user['senha_hash'], $this->algorithm())) {
            // Rehash deve ser persistido por rotina administrativa dedicada.
        }
        session_regenerate_id(true);
        $_SESSION['user'] = [
            'id' => (int) $user['id'],
            'name' => $user['nome'],
            'email' => $user['email'],
            'role' => $user['perfil'],
            'tenant_id' => (int) $user['tenant_id'],
            'tenant_name' => $user['tenant_name'],
            'subscription_status' => $user['assinatura_status'] ?? 'Pendente',
            'subscription_valid_until' => $user['assinatura_valida_ate'] ?? null,
        ];
        $_SESSION['last_activity'] = time();
        $this->users->updateLastLogin((int) $user['id']);
        $this->audit->log('LOGIN', 'usuarios', (int) $user['id'], (int) $user['id']);
        return $_SESSION['user'];
    }

    public function register(string $name, string $company, string $email, string $password, string $confirmation, bool $acceptedTerms): int
    {
        $name = trim(preg_replace('/\s+/u', ' ', $name) ?? '');
        $company = trim(preg_replace('/\s+/u', ' ', $company) ?? '');
        $email = mb_strtolower(trim($email));

        if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
            throw new RuntimeException('Informe seu nome completo.');
        }
        if (mb_strlen($company) < 2 || mb_strlen($company) > 160) {
            throw new RuntimeException('Informe o nome da empresa.');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
            throw new RuntimeException('Informe um e-mail válido.');
        }
        if ($this->users->findByEmail($email) !== null) {
            throw new RuntimeException('Não foi possível criar a conta com esses dados.');
        }
        if (mb_strlen($password) < 10 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
            throw new RuntimeException('A senha deve ter ao menos 10 caracteres, com maiúscula, minúscula e número.');
        }
        if (!hash_equals($password, $confirmation)) {
            throw new RuntimeException('A confirmação da senha não corresponde.');
        }
        if (!$acceptedTerms) {
            throw new RuntimeException('Confirme que leu os termos e a política de privacidade.');
        }

        $pdo = Database::connection();
        $pdo->beginTransaction();
        try {
            $tenantId = $this->tenants->create($company);
            $id = $this->users->create($name, $email, password_hash($password, $this->algorithm()), $tenantId);
            $pdo->commit();
        } catch (\Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            if ($exception instanceof RuntimeException) { throw $exception; }
            throw new RuntimeException('Não foi possível criar a conta da empresa. Verifique os dados e tente novamente.');
        }
        $this->audit->log('CREATE_ACCOUNT', 'usuarios', $id, $id, ['subscription_status' => 'Pendente']);
        return $id;
    }

    public static function canUseCrm(array $user): bool
    {
        if (($user['role'] ?? '') === 'Administrador') {
            return true;
        }
        if (($user['subscription_status'] ?? 'Pendente') !== 'Ativa') {
            return false;
        }
        $validUntil = $user['subscription_valid_until'] ?? null;
        return $validUntil === null || $validUntil === '' || strtotime((string) $validUntil) >= time();
    }

    public function logout(): void
    {
        if (isset($_SESSION['user']['id'])) { $this->audit->log('LOGOUT', 'usuarios', (int) $_SESSION['user']['id']); }
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    private function algorithm(): string|int { return defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT; }
}
