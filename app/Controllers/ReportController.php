<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Services\ReportService;
use InvalidArgumentException;

final class ReportController
{
    public function index(Request $request): void
    {
        View::render('reports/index', (new ReportService())->data());
    }

    public function export(Request $request): never
    {
        try {
            $export = (new ReportService())->export((string) $request->input('type', ''));
        } catch (InvalidArgumentException $exception) {
            $_SESSION['_flash']['error'] = $exception->getMessage();
            Response::redirect('/reports');
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $export['filename'] . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');
        $output = fopen('php://output', 'wb');
        if ($output === false) {
            throw new \RuntimeException('Não foi possível gerar o arquivo.');
        }
        fwrite($output, "\xEF\xBB\xBF");
        fputcsv($output, $export['headers'], ';', '"', '\\');
        foreach ($export['rows'] as $row) {
            fputcsv($output, $row, ';', '"', '\\');
        }
        fclose($output);
        exit;
    }
}
