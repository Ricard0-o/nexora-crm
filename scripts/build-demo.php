<?php
declare(strict_types=1);
// Renderiza apenas a demonstração fictícia já presente no projeto. Não carrega .env ou banco.
$root = dirname(__DIR__);
$target = $root . '/demo';
if (!is_dir($target)) { mkdir($target, 0777, true); }
function e(?string $v): string { return htmlspecialchars($v ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function asset(string $path): string { return './' . ltrim($path, '/'); }
function url(string $path = ''): string { return 'https://github.com/Ricard0-o/nexora-crm#instalação'; }
ob_start(); require $root . '/resources/views/demo/index.php'; $content = ob_get_clean();
$content = str_replace(['Entrar no sistema','Voltar ao site institucional'], ['Ver código e instalação','Ver código no GitHub'], $content);
ob_start(); require $root . '/resources/views/layouts/demo.php'; $html = ob_get_clean();
file_put_contents($target . '/index.html', $html);
$copy = function (string $src, string $dest) use (&$copy): void {
    if (is_dir($src)) { if (!is_dir($dest)) { mkdir($dest, 0777, true); } foreach (scandir($src) as $name) { if ($name !== '.' && $name !== '..') { $copy($src . '/' . $name, $dest . '/' . $name); } } }
    else { copy($src, $dest); }
};
foreach (['css','images'] as $dir) { $copy($root . '/public/' . $dir, $target . '/' . $dir); }
$copy($root . '/licenses', $target . '/licenses');
copy($root . '/THIRD_PARTY_NOTICES.md', $target . '/THIRD_PARTY_NOTICES.md');
if (!is_dir($target . '/js')) { mkdir($target . '/js'); }
foreach (['app.js','chart.umd.min.js'] as $name) { copy($root . '/public/js/' . $name, $target . '/js/' . $name); }
echo "Demonstração estática gerada sem acessar banco ou configuração pessoal.\n";
