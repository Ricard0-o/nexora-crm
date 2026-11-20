<?php

declare(strict_types=1);

namespace App\Services;

final class WebhookSignatureService
{
    public function verify(string $payload, string $signature, string $secret, int $timestamp, int $tolerance = 300): bool
    {
        if (abs(time() - $timestamp) > $tolerance) { return false; }
        $expected = hash_hmac('sha256', $timestamp . '.' . $payload, $secret);
        return hash_equals($expected, $signature);
    }

    public function verifyMercadoPago(string $xSignature, string $xRequestId, string $dataId, string $secret): bool
    {
        if ($xSignature === '' || $xRequestId === '' || $dataId === '' || $secret === '') {
            return false;
        }
        $parts = [];
        foreach (explode(',', $xSignature) as $part) {
            [$key, $value] = array_pad(explode('=', trim($part), 2), 2, '');
            if ($key !== '' && $value !== '') { $parts[$key] = $value; }
        }
        $timestamp = $parts['ts'] ?? '';
        $receivedHash = $parts['v1'] ?? '';
        if ($timestamp === '' || !ctype_digit($timestamp) || !preg_match('/^[a-f0-9]{64}$/i', $receivedHash)) {
            return false;
        }

        $manifest = 'id:' . mb_strtolower($dataId) . ';request-id:' . $xRequestId . ';ts:' . $timestamp . ';';
        $expected = hash_hmac('sha256', $manifest, $secret);
        return hash_equals($expected, mb_strtolower($receivedHash));
    }
}
