<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/bootstrap.php';
use App\Helpers\Database;
$pdo = Database::connection();
if ($pdo->query('SHOW TABLES')->fetchAll() !== []) {
    fwrite(STDERR, "O banco não está vazio. A instalação foi cancelada sem alterar tabelas.\n"); exit(1);
}
$sql = file_get_contents(dirname(__DIR__) . '/database/install.sql');
$sql = preg_replace('/^\s*--.*$/m', '', $sql) ?? $sql;
foreach (explode(';', $sql) as $statement) {
    $statement = trim($statement);
    if ($statement !== '') { $pdo->exec($statement); }
}
echo "Estrutura instalada. Crie o administrador com scripts/create-admin.php.\n";
