<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Repositories\LeadRepository;
use App\Repositories\ProjectRepository;
use App\Services\LeadImportService;
use App\Services\LeadService;
use RuntimeException;

final class LeadController
{
    public function index(Request $request): void
    {
        $status = trim((string) $request->input('status', ''));
        $data = (new LeadRepository())->paginate(max(1, (int) $request->input('page', 1)), 20, trim((string) $request->input('q', '')), $status);
        View::render('leads/index', ['result' => $data, 'search' => (string) $request->input('q', ''), 'statusFilter' => $status]);
    }
    public function create(Request $request): void
    {
        View::render('leads/create', ['errors' => $_SESSION['_validation'] ?? [], 'lead' => []]);
        unset($_SESSION['_validation']);
    }
    public function edit(Request $request): void
    {
        $lead = (new LeadRepository())->findById((int) $request->input('id', 0));
        if ($lead === null) { $_SESSION['_flash']['error'] = 'Lead não encontrado.'; Response::redirect('/leads'); }
        View::render('leads/edit', ['errors' => $_SESSION['_validation'] ?? [], 'lead' => $lead]);
        unset($_SESSION['_validation']);
    }
    public function show(Request $request): void
    {
        $id = (int) $request->input('id', 0);
        $lead = (new LeadRepository())->findById($id);
        if ($lead === null) { $_SESSION['_flash']['error'] = 'Lead não encontrado.'; Response::redirect('/leads'); }
        View::render('leads/show', ['lead' => $lead, 'projects' => (new ProjectRepository())->findByLeadId($id)]);
    }
    public function store(Request $request): void
    {
        $_SESSION['_old'] = $request->all();
        try {
            $id = (new LeadService())->create($request->all());
            unset($_SESSION['_old'], $_SESSION['_validation']);
            $_SESSION['_flash']['success'] = "Lead #{$id} criado com sucesso.";
            Response::redirect('/leads');
        } catch (RuntimeException $e) {
            $_SESSION['_flash']['error'] = $e->getMessage();
            Response::redirect('/leads/create');
        }
    }
    public function update(Request $request): void
    {
        $id = (int) $request->input('id', 0);
        $_SESSION['_old'] = $request->all();
        try {
            (new LeadService())->update($id, $request->all());
            unset($_SESSION['_old'], $_SESSION['_validation']);
            $_SESSION['_flash']['success'] = 'Empresa atualizada com sucesso.';
            Response::redirect('/leads');
        } catch (RuntimeException $e) {
            $_SESSION['_flash']['error'] = $e->getMessage();
            Response::redirect('/leads/edit?id=' . $id);
        }
    }
    public function importForm(Request $request): void
    {
        View::render('leads/import');
    }
    public function spreadsheetForm(Request $request): void
    {
        View::render('leads/spreadsheet');
    }
    public function import(Request $request): void
    {
        try {
            $json = (string) $request->input('json_payload', '');
            if (isset($_FILES['json_file']) && is_uploaded_file($_FILES['json_file']['tmp_name'])) {
                if ($_FILES['json_file']['size'] > 5 * 1024 * 1024) { throw new RuntimeException('Arquivo excede o limite de 5 MB.'); }
                $json = (string) file_get_contents($_FILES['json_file']['tmp_name']);
            }
            $result = (new LeadImportService())->import($json, (string) $request->input('duplicate_mode', 'ignore'));
            if ($request->expectsJson()) { Response::json(true, 'Importação concluída.', $result); }
            $_SESSION['_flash']['success'] = "Importação concluída: {$result['created']} criado(s), {$result['updated']} atualizado(s), {$result['ignored']} ignorado(s).";
        } catch (RuntimeException $e) {
            if ($request->expectsJson()) { Response::json(false, $e->getMessage(), [], 422); }
            $_SESSION['_flash']['error'] = $e->getMessage();
        }
        Response::redirect('/leads');
    }
}
