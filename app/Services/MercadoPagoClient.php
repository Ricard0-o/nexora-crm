<?php

declare(strict_types=1);

namespace App\Services;

use App\Helpers\Env;
use RuntimeException;

final class MercadoPagoClient
{
    private const BASE_URL = 'https://api.mercadopago.com';

    public function createPreference(array $payload, string $idempotencyKey): array
    {
        return $this->request('POST', '/checkout/preferences', $payload, $idempotencyKey);
    }

    public function payment(string $paymentId): array
    {
        if (!preg_match('/^[A-Za-z0-9_-]{1,120}$/', $paymentId)) {
            throw new RuntimeException('Identificador de pagamento inválido.');
        }
        return $this->request('GET', '/v1/payments/' . rawurlencode($paymentId));
    }

    private function request(string $method, string $path, ?array $payload = null, ?string $idempotencyKey = null): array
    {
        $token = Env::get('MP_ACCESS_TOKEN');
        if ($token === '') {
            throw new RuntimeException('Credencial do Mercado Pago ainda não configurada.');
        }
        if (!extension_loaded('curl')) {
            throw new RuntimeException('A extensão cURL do PHP precisa estar habilitada.');
        }

        $headers = ['Authorization: Bearer ' . $token, 'Accept: application/json'];
        if ($payload !== null) { $headers[] = 'Content-Type: application/json'; }
        if ($idempotencyKey !== null) { $headers[] = 'X-Idempotency-Key: ' . $idempotencyKey; }

        $curl = curl_init(self::BASE_URL . $path);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        if ($payload !== null) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($payload, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }
        $body = curl_exec($curl);
        $status = (int) curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($body === false || $error !== '') {
            throw new RuntimeException('Não foi possível conectar ao Mercado Pago. Tente novamente.');
        }
        $decoded = json_decode($body, true);
        if (!is_array($decoded)) {
            throw new RuntimeException('O Mercado Pago retornou uma resposta inválida.');
        }
        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('O Mercado Pago não aceitou a solicitação. Verifique as credenciais e configurações.');
        }
        return $decoded;
    }
}
