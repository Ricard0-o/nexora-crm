<?php

declare(strict_types=1);

use App\Services\WebhookSignatureService;
use PHPUnit\Framework\TestCase;

final class WebhookSignatureServiceTest extends TestCase
{
    public function testValidSignature(): void
    {
        $timestamp=time(); $payload='{"event":"test"}'; $secret='secret';
        $signature=hash_hmac('sha256',$timestamp.'.'.$payload,$secret);
        self::assertTrue((new WebhookSignatureService())->verify($payload,$signature,$secret,$timestamp));
    }

    public function testValidMercadoPagoSignature(): void
    {
        $secret = 'webhook-secret';
        $dataId = '123456789';
        $requestId = 'request-abc';
        $timestamp = '1742505638';
        $manifest = "id:{$dataId};request-id:{$requestId};ts:{$timestamp};";
        $hash = hash_hmac('sha256', $manifest, $secret);

        self::assertTrue((new WebhookSignatureService())->verifyMercadoPago(
            "ts={$timestamp},v1={$hash}",
            $requestId,
            $dataId,
            $secret
        ));
    }
}
