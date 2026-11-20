<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Env;
use App\Repositories\AuditRepository;
use App\Repositories\PaymentRepository;
use App\Repositories\UserRepository;
use DateInterval;
use DateTimeImmutable;
use RuntimeException;

final class PaymentService
{
    public function __construct(
        private readonly PaymentRepository $payments = new PaymentRepository(),
        private readonly UserRepository $users = new UserRepository(),
        private readonly AuditRepository $audit = new AuditRepository(),
        private readonly MercadoPagoClient $client = new MercadoPagoClient(),
        private readonly WebhookSignatureService $signatures = new WebhookSignatureService(),
    ) {}

    public function configuration(): array
    {
        $amount = $this->configuredAmount();
        $missing = [];
        if (Env::get('MP_ACCESS_TOKEN') === '') { $missing[] = 'Access Token do Mercado Pago'; }
        if ($amount === null) { $missing[] = 'valor do plano'; }
        if (Env::get('MP_WEBHOOK_SECRET') === '') { $missing[] = 'chave secreta do webhook'; }

        return [
            'ready' => $missing === [],
            'checkoutReady' => Env::get('MP_ACCESS_TOKEN') !== '' && $amount !== null,
            'missing' => $missing,
            'planName' => Env::get('CRM_PLAN_NAME', 'Plano CRM Nelber Code'),
            'amount' => $amount,
            'accessDays' => max(1, min(365, (int) Env::get('CRM_ACCESS_DAYS', '30'))),
            'environment' => in_array(Env::get('MP_ENVIRONMENT', 'sandbox'), ['sandbox', 'production'], true) ? Env::get('MP_ENVIRONMENT', 'sandbox') : 'sandbox',
        ];
    }

    public function createCheckout(array $user): string
    {
        $configuration = $this->configuration();
        if (!$configuration['checkoutReady']) {
            throw new RuntimeException('O pagamento ainda precisa das credenciais de teste e do valor do plano.');
        }
        if (($user['role'] ?? '') === 'Administrador') {
            throw new RuntimeException('A conta administrativa não precisa contratar um plano.');
        }

        $reference = $this->uuidV4();
        $amount = number_format((float) $configuration['amount'], 2, '.', '');
        $this->payments->create((int) $user['id'], $reference, $amount, $configuration['environment']);

        $appUrl = rtrim(Env::get('APP_URL'), '/');
        $payload = [
            'items' => [[
                'id' => 'crm-access-' . $configuration['accessDays'] . 'd',
                'title' => $configuration['planName'],
                'description' => 'Acesso ao CRM Nelber Code por ' . $configuration['accessDays'] . ' dias',
                'quantity' => 1,
                'currency_id' => 'BRL',
                'unit_price' => (float) $amount,
            ]],
            'payer' => ['email' => (string) $user['email']],
            'external_reference' => $reference,
            'back_urls' => [
                'success' => $appUrl . '/payment/success',
                'pending' => $appUrl . '/payment/pending',
                'failure' => $appUrl . '/payment/failure',
            ],
            'auto_return' => 'approved',
            'payment_methods' => [
                'excluded_payment_types' => [['id' => 'ticket'], ['id' => 'atm']],
                'installments' => max(1, min(12, (int) Env::get('CRM_MAX_INSTALLMENTS', '12'))),
            ],
        ];
        $webhookUrl = Env::get('MP_WEBHOOK_URL');
        if (filter_var($webhookUrl, FILTER_VALIDATE_URL) && str_starts_with($webhookUrl, 'https://')) {
            $payload['notification_url'] = $webhookUrl;
        }

        try {
            $preference = $this->client->createPreference($payload, $reference);
            $preferenceId = (string) ($preference['id'] ?? '');
            $urlKey = $configuration['environment'] === 'production' ? 'init_point' : 'sandbox_init_point';
            $checkoutUrl = (string) ($preference[$urlKey] ?? $preference['init_point'] ?? '');
            if ($preferenceId === '' || !$this->validCheckoutUrl($checkoutUrl)) {
                throw new RuntimeException('O Mercado Pago não retornou uma URL de checkout válida.');
            }
            $this->payments->attachPreference($reference, $preferenceId);
            $this->audit->log('PAYMENT_CREATED', 'pagamentos', null, (int) $user['id'], ['reference' => $reference, 'environment' => $configuration['environment']]);
            return $checkoutUrl;
        } catch (RuntimeException $exception) {
            $this->payments->markCreationFailure($reference, $exception->getMessage());
            throw $exception;
        }
    }

    public function confirmForUser(string $paymentId, int $userId): array
    {
        $providerPayment = $this->client->payment($paymentId);
        $reference = (string) ($providerPayment['external_reference'] ?? '');
        $localPayment = $this->payments->findByReference($reference);
        if ($localPayment === null || (int) $localPayment['usuario_id'] !== $userId) {
            throw new RuntimeException('O pagamento não pertence a esta conta.');
        }
        return $this->processProviderPayment($providerPayment, $localPayment);
    }

    public function processWebhook(string $paymentId, string $xSignature, string $xRequestId, string $dataId): void
    {
        if (!$this->signatures->verifyMercadoPago($xSignature, $xRequestId, $dataId, Env::get('MP_WEBHOOK_SECRET'))) {
            throw new RuntimeException('Assinatura do webhook inválida.');
        }
        if (!hash_equals($dataId, $paymentId)) {
            throw new RuntimeException('Identificador do webhook inconsistente.');
        }
        $providerPayment = $this->client->payment($paymentId);
        $reference = (string) ($providerPayment['external_reference'] ?? '');
        $localPayment = $this->payments->findByReference($reference);
        if ($localPayment === null) {
            throw new RuntimeException('Pagamento local não encontrado.');
        }
        $this->processProviderPayment($providerPayment, $localPayment);
    }

    private function processProviderPayment(array $providerPayment, array $localPayment): array
    {
        $amount = (float) ($providerPayment['transaction_amount'] ?? -1);
        $currency = (string) ($providerPayment['currency_id'] ?? '');
        if (abs($amount - (float) $localPayment['valor']) > 0.001 || $currency !== $localPayment['moeda']) {
            throw new RuntimeException('Valor ou moeda do pagamento não corresponde ao pedido.');
        }

        $providerStatus = (string) ($providerPayment['status'] ?? 'unknown');
        $localStatus = match ($providerStatus) {
            'approved' => 'Aprovado',
            'pending', 'in_process', 'in_mediation' => 'Pendente',
            'rejected' => 'Rejeitado',
            'cancelled', 'refunded', 'charged_back' => 'Cancelado',
            default => 'Pendente',
        };
        $safe = [
            'id' => (string) ($providerPayment['id'] ?? ''),
            'status' => $providerStatus,
            'status_detail' => (string) ($providerPayment['status_detail'] ?? ''),
            'payment_method_id' => (string) ($providerPayment['payment_method_id'] ?? ''),
            'payment_type_id' => (string) ($providerPayment['payment_type_id'] ?? ''),
            'transaction_amount' => $amount,
            'currency_id' => $currency,
            'date_approved' => $providerPayment['date_approved'] ?? null,
        ];
        $normalized = [
            'id' => $safe['id'],
            'local_status' => $localStatus,
            'status_detail' => $safe['status_detail'],
            'payment_method_id' => $safe['payment_method_id'],
            'payment_type_id' => $safe['payment_type_id'],
            'safe_data' => $safe,
        ];

        if ($localStatus === 'Aprovado') {
            $validUntil = $this->newValidityDate((int) $localPayment['usuario_id']);
            $changed = $this->payments->approveAndActivate($localPayment['referencia_externa'], $normalized, $validUntil);
            if ($changed) {
                $this->audit->log('PAYMENT_APPROVED', 'pagamentos', (int) $localPayment['id'], (int) $localPayment['usuario_id'], ['payment_id' => $safe['id'], 'method' => $safe['payment_method_id']]);
            }
        } elseif ($localStatus === 'Cancelado') {
            $suspended = $this->payments->cancelAndMaybeSuspend($localPayment['referencia_externa'], $normalized);
            $this->audit->log('PAYMENT_CANCELLED', 'pagamentos', (int) $localPayment['id'], (int) $localPayment['usuario_id'], ['suspended' => $suspended]);
        } else {
            $this->payments->updateProviderStatus($localPayment['referencia_externa'], $normalized);
            $this->audit->log('PAYMENT_STATUS', 'pagamentos', (int) $localPayment['id'], (int) $localPayment['usuario_id'], ['status' => $localStatus]);
        }
        return ['status' => $localStatus, 'method' => $safe['payment_method_id']];
    }

    private function newValidityDate(int $userId): string
    {
        $user = $this->users->findAccessById($userId);
        $base = new DateTimeImmutable('now');
        $current = $user['assinatura_valida_ate'] ?? null;
        if ($current && strtotime((string) $current) > time()) { $base = new DateTimeImmutable((string) $current); }
        $days = max(1, min(365, (int) Env::get('CRM_ACCESS_DAYS', '30')));
        return $base->add(new DateInterval('P' . $days . 'D'))->format('Y-m-d 23:59:59');
    }

    private function configuredAmount(): ?float
    {
        $raw = str_replace(',', '.', trim(Env::get('CRM_PLAN_PRICE')));
        if ($raw === '' || !is_numeric($raw)) { return null; }
        $amount = round((float) $raw, 2);
        return $amount >= 1.00 && $amount <= 999999.99 ? $amount : null;
    }

    private function validCheckoutUrl(string $url): bool
    {
        if (!filter_var($url, FILTER_VALIDATE_URL) || !str_starts_with($url, 'https://')) { return false; }
        $host = mb_strtolower((string) parse_url($url, PHP_URL_HOST));
        return $host === 'mercadopago.com.br' || str_ends_with($host, '.mercadopago.com.br') || $host === 'mercadopago.com' || str_ends_with($host, '.mercadopago.com');
    }

    private function uuidV4(): string
    {
        $bytes = random_bytes(16);
        $bytes[6] = chr((ord($bytes[6]) & 0x0f) | 0x40);
        $bytes[8] = chr((ord($bytes[8]) & 0x3f) | 0x80);
        $hex = bin2hex($bytes);
        return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20);
    }
}
