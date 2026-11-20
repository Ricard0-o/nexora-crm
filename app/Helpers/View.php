<?php

declare(strict_types=1);

namespace App\Helpers;

use RuntimeException;

final class View
{
    public static function render(string $view, array $data = [], string $layout = 'app'): void
    {
        $file = BASE_PATH . '/resources/views/' . $view . '.php';
        if (!is_file($file)) {
            throw new RuntimeException("View não encontrada: {$view}");
        }
        extract($data, EXTR_SKIP);
        ob_start();
        require $file;
        $content = (string) ob_get_clean();
        require BASE_PATH . '/resources/views/layouts/' . $layout . '.php';
        unset($_SESSION['_old']);
    }
}
