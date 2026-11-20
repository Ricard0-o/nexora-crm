<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Database;
use App\Repositories\AuditRepository;
use App\Repositories\LeadRepository;
use App\Validators\LeadValidator;
use JsonException;
use RuntimeException;
use Throwable;

final class LeadImportService
{
    public function __construct(
        private readonly LeadRepository $leads = new LeadRepository(),
        private readonly LeadValidator $validator = new LeadValidator(),
        private readonly AuditRepository $audit = new AuditRepository(),
    ) {}

    public function import(string $json, string $duplicateMode = 'ignore'): array
    {
        if (strlen($json) > 5 * 1024 * 1024) { throw new RuntimeException('Arquivo excede o limite de 5 MB.'); }
        try {
            $schemaPayload = json_decode($json, false, 64, JSON_THROW_ON_ERROR);
            $records = json_decode($json, true, 64, JSON_THROW_ON_ERROR);
        }
        catch (JsonException) { throw new RuntimeException('JSON inválido. Verifique a estrutura do arquivo.'); }
        if (class_exists(\Opis\JsonSchema\Validator::class)) {
            $schema = json_decode((string) file_get_contents(BASE_PATH . '/config/lead-import.schema.json'));
            $validation = (new \Opis\JsonSchema\Validator())->validate($schemaPayload, $schema);
            if (!$validation->isValid()) { throw new RuntimeException('O conteúdo não atende ao JSON Schema de leads.'); }
        }
        if (!is_array($records) || !array_is_list($records)) { throw new RuntimeException('O JSON deve conter uma lista de leads.'); }
        if (count($records) > 1000) { throw new RuntimeException('Limite de 1.000 registros por importação.'); }

        $pdo = Database::connection();
        $result = ['created' => 0, 'updated' => 0, 'ignored' => 0, 'errors' => []];
        $pdo->beginTransaction();
        try {
            foreach ($records as $index => $record) {
                if (!is_array($record)) { $result['errors'][] = ['linha' => $index + 1, 'erros' => ['Registro deve ser um objeto.']]; continue; }
                $errors = $this->validator->validate($record);
                if ($errors !== []) { $result['errors'][] = ['linha' => $index + 1, 'erros' => $errors]; continue; }
                $duplicate = $this->leads->findDuplicate((string) $record['email'], (string) $record['telefone']);
                if ($duplicate) {
                    if ($duplicateMode === 'update' && !empty($record['observacao_prospeccao'])) {
                        $this->leads->updateObservation((int) $duplicate['id'], (string) $record['observacao_prospeccao']);
                        $result['updated']++;
                    } else { $result['ignored']++; }
                    continue;
                }
                $this->leads->create($record);
                $result['created']++;
            }
            $pdo->commit();
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            throw new RuntimeException('A importação foi revertida com segurança.', 0, $exception);
        }
        $this->audit->log('IMPORT', 'leads', null, null, array_diff_key($result, ['errors' => true]));
        return $result;
    }
}
