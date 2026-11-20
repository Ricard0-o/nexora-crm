<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\AuditRepository;
use App\Repositories\LeadRepository;
use App\Validators\LeadValidator;
use RuntimeException;

final class LeadService
{
    public function __construct(
        private readonly LeadRepository $leads = new LeadRepository(),
        private readonly LeadValidator $validator = new LeadValidator(),
        private readonly AuditRepository $audit = new AuditRepository(),
    ) {}

    public function create(array $input): int
    {
        $data = $this->sanitize($input);
        $this->validate($data);
        if ($this->leads->findDuplicate($data['email'], $data['telefone'])) {
            $_SESSION['_validation'] = ['email' => 'Já existe um lead com este e-mail ou telefone.'];
            throw new RuntimeException('Este lead já está cadastrado.');
        }
        $id = $this->leads->create($data);
        $this->audit->log('CREATE', 'leads', $id);
        return $id;
    }

    public function update(int $id, array $input): void
    {
        if ($this->leads->findById($id) === null) { throw new RuntimeException('Lead não encontrado.'); }
        $data = $this->sanitize($input);
        $this->validate($data);
        if ($this->leads->findDuplicate($data['email'], $data['telefone'], $id)) {
            $_SESSION['_validation'] = ['email' => 'Outro lead já utiliza este e-mail ou telefone.'];
            throw new RuntimeException('E-mail ou telefone já cadastrado em outro lead.');
        }
        $this->leads->update($id, $data);
        $this->audit->log('UPDATE', 'leads', $id, null, ['status' => $data['status']]);
    }

    public function changeStatus(int $id, string $status): void
    {
        $allowed = ['Novo', 'Em Contato', 'Negociação', 'Contratado', 'Recusado'];
        if (!in_array($status, $allowed, true)) { throw new RuntimeException('Status comercial inválido.'); }
        if ($this->leads->findById($id) === null) { throw new RuntimeException('Lead não encontrado.'); }
        $this->leads->updateStatus($id, $status);
        $this->audit->log('UPDATE', 'leads', $id, null, ['campo' => 'status', 'valor' => $status]);
    }

    private function sanitize(array $input): array
    {
        return [
            'nome' => trim((string) ($input['nome'] ?? '')) ?: null,
            'nome_empresa' => trim((string) ($input['nome_empresa'] ?? '')),
            'ramo_atividade' => trim((string) ($input['ramo_atividade'] ?? '')),
            'email' => mb_strtolower(trim((string) ($input['email'] ?? ''))),
            'telefone' => trim((string) ($input['telefone'] ?? '')),
            'status' => (string) ($input['status'] ?? 'Novo'),
            'origem' => trim((string) ($input['origem'] ?? 'Manual')) ?: 'Manual',
            'observacao_prospeccao' => trim((string) ($input['observacao_prospeccao'] ?? '')) ?: null,
            'base_legal' => trim((string) ($input['base_legal'] ?? 'Legítimo interesse')),
            'finalidade' => trim((string) ($input['finalidade'] ?? 'Prospecção comercial B2B')),
        ];
    }

    private function validate(array $data): void
    {
        $errors = $this->validator->validate($data);
        if ($errors !== []) {
            $_SESSION['_validation'] = $errors;
            throw new RuntimeException('Revise os campos destacados.');
        }
    }
}
