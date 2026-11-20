<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Helpers\Request;
use App\Helpers\Response;
use App\Helpers\View;
use App\Services\PaymentService;
use RuntimeException;

final class PaymentController
{
    public function checkout(Request $request): void
    {
        try {
            $checkoutUrl = (new PaymentService())->createCheckout($_SESSION['user']);
            header('Location: ' . $checkoutUrl, true, 303);
            exit;
        } catch (RuntimeException $exception) {
            $_SESSION['_flash']['error'] = $exception->getMessage();
            Response::redirect('/subscription');
        }
    }

    public function success(Request $request): void { $this->result($request, 'success'); }
    public function pending(Request $request): void { $this->result($request, 'pending'); }
    public function failure(Request $request): void { $this->result($request, 'failure'); }

    public function webhook(Request $request): void
    {
        $body = json_decode($request->rawBody(), true);
        $type = (string) ($request->rawQueryValue('type') ?: ($body['type'] ?? ''));
        if ($type !== 'payment') { Response::json(true, 'Evento ignorado.'); }

        $dataId = (string) ($request->rawQueryValue('data.id') ?: $request->rawQueryValue('data_id') ?: ($body['data']['id'] ?? ''));
        if ($dataId === '') { Response::json(false, 'Identificador ausente.', [], 400); }
        try {
            (new PaymentService())->processWebhook(
                $dataId,
                $request->header('X-Signature'),
                $request->header('X-Request-Id'),
                $dataId
            );
            Response::json(true, 'Notificação processada.');
        } catch (RuntimeException $exception) {
            $status = str_contains(mb_strtolower($exception->getMessage()), 'assinatura') ? 401 : 500;
            error_log('Mercado Pago webhook: ' . $exception->getMessage());
            Response::json(false, $status === 401 ? 'Assinatura inválida.' : 'Não foi possível processar a notificação.', [], $status);
        }
    }

    private function result(Request $request, string $expectedState): void
    {
        $paymentId = trim((string) $request->input('payment_id', ''));
        $result = ['status' => $expectedState === 'failure' ? 'Rejeitado' : 'Pendente', 'method' => ''];
        $message = match ($expectedState) {
            'success' => 'Recebemos o retorno do checkout e estamos confirmando o pagamento.',
            'pending' => 'O pagamento está pendente de confirmação.',
            default => 'O pagamento não foi concluído.',
        };

        if ($paymentId !== '') {
            try {
                $result = (new PaymentService())->confirmForUser($paymentId, (int) $_SESSION['user']['id']);
                $message = match ($result['status']) {
                    'Aprovado' => 'Pagamento confirmado. Seu acesso ao CRM foi liberado.',
                    'Pendente' => 'Pagamento identificado e ainda aguardando confirmação.',
                    'Rejeitado' => 'O pagamento foi recusado. Você pode tentar novamente.',
                    default => 'O pagamento não foi concluído.',
                };
            } catch (RuntimeException $exception) {
                $message = $exception->getMessage();
            }
        }

        View::render('payment/result', ['paymentStatus' => $result['status'], 'message' => $message], 'auth');
    }
}
