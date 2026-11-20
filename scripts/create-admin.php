<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';
use App\Helpers\Database;

$email = getenv('CRM_ADMIN_EMAIL') ?: '';
$password = getenv('CRM_ADMIN_PASSWORD') ?: '';
$name = getenv('CRM_ADMIN_NAME') ?: 'Administrador';
if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 12 || !preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/\d/', $password)) {
    fwrite(STDERR, "Defina CRM_ADMIN_EMAIL e CRM_ADMIN_PASSWORD no ambiente. A senha precisa de pelo menos 12 caracteres, maiúscula, minúscula e número.\n"); exit(1);
}
$pdo = Database::connection();
if ((int) $pdo->query('SELECT COUNT(*) FROM usuarios')->fetchColumn() > 0) {
    fwrite(STDERR, "Instalação já possui usuários. Nenhum dado foi alterado.\n"); exit(1);
}
$pdo->beginTransaction();
try {
    $tenant = (int) $pdo->query("SELECT id FROM tenants WHERE slug='nelber-code'")->fetchColumn();
    if (!$tenant) { throw new RuntimeException('Importe database/install.sql em banco vazio primeiro.'); }
    $stmt = $pdo->prepare("INSERT INTO usuarios (tenant_id,nome,email,senha_hash,perfil,ativo,assinatura_status) VALUES (?,?,?,?,'Administrador',1,'Ativa')");
    $stmt->execute([$tenant,$name,$email,password_hash($password, defined('PASSWORD_ARGON2ID') ? PASSWORD_ARGON2ID : PASSWORD_BCRYPT)]);
    $pdo->commit(); echo "Administrador inicial criado. Nenhuma senha foi impressa.\n";
} catch (Throwable $e) { if ($pdo->inTransaction()) { $pdo->rollBack(); } throw $e; }
