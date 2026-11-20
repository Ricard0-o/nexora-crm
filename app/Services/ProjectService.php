<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditRepository;
use App\Repositories\LeadRepository;
use App\Repositories\ProjectRepository;
use App\Validators\ProjectValidator;
use RuntimeException;

final class ProjectService
{
    public function __construct(
        private readonly ProjectRepository $projects = new ProjectRepository(),
        private readonly LeadRepository $leads = new LeadRepository(),
        private readonly ProjectValidator $validator = new ProjectValidator(),
        private readonly AuditRepository $audit = new AuditRepository(),
    ) {}

    public function formData(?int $id = null): array
    {
        $project = $id ? $this->projects->findById($id) : null;
        if ($id && $project === null) { throw new RuntimeException('Projeto não encontrado.'); }
        return ['project' => $project ?? [], 'leads' => $this->leads->contractedOptions(), 'errors' => $_SESSION['_validation'] ?? []];
    }

    public function create(array $input): int
    {
        $data = $this->sanitize($input);
        $data['codigo'] = trim((string)($input['codigo'] ?? '')) ?: $this->projects->nextCode();
        $this->validate($data);
        if ($this->projects->codeExists($data['codigo'])) { $_SESSION['_validation']=['codigo'=>'Código já utilizado.']; throw new RuntimeException('Código de projeto já utilizado.'); }
        $id = $this->projects->create($data);
        $this->audit->log('CREATE', 'projetos', $id);
        return $id;
    }

    public function update(int $id, array $input): void
    {
        if ($this->projects->findById($id) === null) { throw new RuntimeException('Projeto não encontrado.'); }
        $data = $this->sanitize($input);
        $data['codigo'] = trim((string)($input['codigo'] ?? ''));
        $this->validate($data);
        if ($this->projects->codeExists($data['codigo'], $id)) { $_SESSION['_validation']=['codigo'=>'Código já utilizado.']; throw new RuntimeException('Código de projeto já utilizado.'); }
        $this->projects->update($id, $data);
        $this->audit->log('UPDATE', 'projetos', $id, null, ['status'=>$data['status'],'progresso'=>$data['progresso']]);
    }

    private function sanitize(array $input): array
    {
        $total = (float)str_replace(',', '.', (string)($input['valor_total'] ?? '0'));
        $paid = (float)str_replace(',', '.', (string)($input['valor_pago'] ?? '0'));
        return [
            'lead_id'=>(int)($input['lead_id']??0), 'nome'=>trim((string)($input['nome']??'')),
            'status'=>(string)($input['status']??'Briefing'), 'progresso'=>(int)($input['progresso']??0),
            'valor_total'=>$total, 'valor_pago'=>$paid, 'saldo'=>max(0,$total-$paid),
            'data_contratacao'=>(string)($input['data_contratacao']??''),
            'data_entrega'=>trim((string)($input['data_entrega']??'')) ?: null,
            'data_vencimento'=>trim((string)($input['data_vencimento']??'')) ?: null,
        ];
    }

    private function validate(array $data): void
    {
        $lead = $this->leads->findById($data['lead_id']);
        $errors = $this->validator->validate($data);
        if ($lead === null || $lead['status'] !== 'Contratado') { $errors['lead_id'] = 'Projetos só podem ser vinculados a empresas contratadas.'; }
        if ($errors !== []) { $_SESSION['_validation']=$errors; throw new RuntimeException('Revise os campos destacados.'); }
    }
}
