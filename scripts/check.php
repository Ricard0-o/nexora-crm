<?php
declare(strict_types=1);
$root = dirname(__DIR__);
$errors = 0;
$count = 0;
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->getExtension() !== 'php' || str_contains($file->getPathname(), DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR)) { continue; }
    exec(escapeshellarg(PHP_BINARY) . ' -l ' . escapeshellarg($file->getPathname()) . ' 2>&1', $output, $code);
    $count++;
    if ($code !== 0) { echo implode("\n", $output), "\n"; $errors++; }
    $output = [];
}
echo "PHP: {$count} arquivos conferidos; {$errors} erros.\n";
exit($errors > 0 ? 1 : 0);
