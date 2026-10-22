<?php
namespace App\Controllers\Webhooks;

use App\Repositories\Payments\PaymentRepository;
use App\Services\Audit\Audit;
use App\Services\Admin\AdminOrderService;
use App\Services\Orders\OrderFromIntentService;
use App\Services\Payments\MercadoPagoService;

final class MercadoPagoWebhookController
{
    public function handle(): void
    {
        // Nunca aceptar webhooks sin alguna credencial de autenticaciÃ³n configurada.
        $secret = (string)env('MP_WEBHOOK_SECRET', '');
        $sigSecret = trim((string)env('MP_WEBHOOK_SIGNATURE_SECRET', ''));
        if ($secret === '' && $sigSecret === '') {
            http_response_code(503);
            echo json_encode(['ok'=>false, 'error'=>'webhook_auth_not_configured']);
            return;
        }
        if ($secret !== '' && (string)($_GET['secret'] ?? '') !== $secret) {
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'forbidden']);
            return;
        }

        $raw = file_get_contents('php://input') ?: '';
        $payload = json_decode($raw, true);
        if (!is_array($payload)) {
            $payload = [];
        }

        if (!$this->isSignatureValid($payload)) {
            $this->logWebhookEvent('invalid_signature', [
                'data_id' => $this->resolveNotificationDataId($payload),
                'request_id' => (string)($_SERVER['HTTP_X_REQUEST_ID'] ?? ''),
            ]);
            http_response_code(403);
            echo json_encode(['ok' => false, 'error' => 'invalid_signature']);
            return;
        }

        // Mercado Pago puede enviar data.id/topic por JSON o por query string.
        $paymentId = $this->resolveNotificationDataId($payload);
        $type = $this->resolveNotificationType($payload);

        if ($paymentId === '') {
            http_response_code(200);
            echo json_encode(['ok' => true, 'ignored' => true]);
            return;
        }

        try {
            $mp = new MercadoPagoService();
            $pay = $mp->getPayment($paymentId);

            $status = strtolower(trim((string)($pay['status'] ?? '')));
            $external = (string)($pay['external_reference'] ?? '');

            $db = db();

            // Si tenemos token de intent, lo buscamos para enlazar el pago (auditoria/idempotencia)
            $intentId = 0;
            $intent = null;
            if ($external !== '') {
                try {
                    $intentRepo = new \App\Repositories\Checkout\CheckoutIntentRepository($db);
                    $intent = $intentRepo->findByToken($external);
                    $intentId = $intent ? (int)($intent['id'] ?? 0) : 0;
                } catch (\Throwable $_) {
                    $intentId = 0;
                }
            }

            $payRepo = new PaymentRepository($db);
            $paymentRowData = [
                'provider' => 'mercadopago',
                'provider_payment_id' => $paymentId,
                'provider_preference_id' => (string)($pay['order']['id'] ?? ''),
                'status' => $status,
                'amount' => (int)round((float)($pay['transaction_amount'] ?? 0)),
                'currency' => (string)($pay['currency_id'] ?? 'ARS'),
                'intent_id' => $intentId,
                'order_id' => null,
                'raw_json' => json_encode($pay, JSON_UNESCAPED_UNICODE),
            ];
            $paymentRowId = $payRepo->createOrUpdateByProviderPaymentId($paymentRowData);
            $attachedOrderId = $payRepo->findAttachedOrderIdByProviderPaymentId('mercadopago', $paymentId);

            if ($status === 'approved') {
                [$trusted, $reason] = \App\Services\Payments\MercadoPago\MercadoPagoPaymentMatchGuard::check(
                    $pay, $intent, $paymentId
                );
                if (!$trusted || $paymentRowId <= 0) {
                    Audit::log('payment_approved_mismatch','payment',(int)$paymentRowId,[
                        'provider'=>'mercadopago', 'payment_id'=>$paymentId,
                        'intent_id'=>$intentId,
                        'reason'=>$trusted ? 'missing_payment_record' : $reason,
                        'manual_reconciliation_required'=>true,
                    ]);
                    $this->logWebhookEvent('approved_mismatch_requires_review', [
                        'payment_id'=>$paymentId,'intent_id'=>$intentId,
                        'reason'=>$reason,
                    ]);
                    http_response_code(202);
                    echo json_encode([
                        'ok'=>false,'code'=>'approved_payment_requires_review',
                        'payment_id'=>$paymentId,'order_id'=>0,
                        'manual_reconciliation_required'=>true,
                    ]);
                    return;
                }
            }

            if ($status === 'approved' && $intentId > 0) {
                $other = $payRepo->findActiveForIntent($intentId, $paymentId);
                if ($other) {
                    Audit::log('payment_duplicate_for_intent','payment',(int)$paymentRowId,[
                        'payment_id'=>$paymentId,'intent_id'=>$intentId,
                        'other_payment_id'=>(string)($other['provider_payment_id'] ?? ''),
                        'manual_reconciliation_required'=>true,
                    ]);
                    http_response_code(202);
                    echo json_encode(['ok'=>false,'code'=>'duplicate_payment_for_intent',
                        'manual_reconciliation_required'=>true,'payment_id'=>$paymentId]);
                    return;
                }
            }
            if ($status !== 'approved') {
                $terminalOrderStatus = $this->mapTerminalOrderStatus($status);
                if ($attachedOrderId > 0 && $terminalOrderStatus !== null) {
                    $orderSvc = new AdminOrderService($db);
                    [$okTransition, $msgTransition, $metaTransition] = $orderSvc->changeStatus(0, $attachedOrderId, $terminalOrderStatus);

                    if ($okTransition) {
                        Audit::log('order_payment_failed_mp', 'order', $attachedOrderId, [
                            'provider' => 'mercadopago',
                            'payment_id' => $paymentId,
                            'payment_row_id' => $paymentRowId,
                            'status' => $status,
                            'external_reference' => $external,
                            'webhook_type' => $type,
                            'to' => $terminalOrderStatus,
                            'source' => 'webhook',
                        ]);
                        cache_forget('feed:audit_recent8');
                    }

                    $this->logWebhookEvent($okTransition ? 'terminal_order_reconciled' : 'terminal_order_reconcile_skipped', [
                        'payment_id' => $paymentId,
                        'order_id' => $attachedOrderId,
                        'payment_row_id' => $paymentRowId,
                        'status' => $status,
                        'external_reference' => $external,
                        'type' => $type,
                        'ok' => $okTransition,
                        'reason' => $msgTransition,
                        'target_status' => $terminalOrderStatus,
                        'noop' => !empty($metaTransition['noop']),
                    ]);
                }

                if (in_array($status, ['rejected', 'cancelled', 'refunded', 'charged_back', 'chargeback'], true)) {
                    $this->logWebhookEvent('terminal_non_approved', [
                        'payment_id' => $paymentId,
                        'order_id' => $attachedOrderId,
                        'status' => $status,
                        'external_reference' => $external,
                        'type' => $type,
                    ]);
                }
                http_response_code(200);
                echo json_encode(['ok' => true, 'status' => $status, 'type' => $type]);
                return;
            }

            if ($attachedOrderId > 0) {
                $orderSvc = new AdminOrderService($db);
                [$okPaid, $msgPaid, $metaPaid] = $orderSvc->markPaidMercadoPago($attachedOrderId, $paymentId, [
                    'payment_row_id' => $paymentRowId,
                    'intent_id' => $intentId,
                    'status' => $status,
                    'external_reference' => $external,
                    'webhook_type' => $type,
                ]);

                $this->logWebhookEvent(!empty($metaPaid['noop']) ? 'approved_already_reconciled' : 'approved_reconciled_existing_order', [
                    'payment_id' => $paymentId,
                    'order_id' => $attachedOrderId,
                    'payment_row_id' => $paymentRowId,
                    'status' => $status,
                    'external_reference' => $external,
                    'type' => $type,
                    'ok' => $okPaid,
                    'reason' => $msgPaid,
                ]);

                if (!$okPaid) {
                    Audit::log('payment_existing_order_requires_review','payment',(int)$paymentRowId,[
                        'provider'=>'mercadopago','payment_id'=>$paymentId,
                        'order_id'=>$attachedOrderId,
                        'manual_reconciliation_required'=>true,
                    ]);
                }
                http_response_code($okPaid ? 200 : 202);
                echo json_encode([
                    'manual_reconciliation_required'=>!$okPaid,
                    'ok' => $okPaid,
                    'reconciled' => $okPaid,
                    'ignored' => !$okPaid,
                    'reason' => $msgPaid,
                    'order_id' => $attachedOrderId,
                    'meta' => $metaPaid,
                    'status' => $status,
                    'type' => $type,
                ]);
                return;
            }

            if ($external === '') {
                $this->logWebhookEvent('approved_missing_external_reference', [
                    'payment_id' => $paymentId,
                    'payment_row_id' => $paymentRowId,
                    'status' => $status,
                    'type' => $type,
                ]);
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'missing_external_reference']);
                return;
            }

            $svc = new OrderFromIntentService($db);
            [$ok, $msg, $orderId] = $svc->createOrderFromIntent($external);

            if ($ok && $orderId > 0) {
                // Enlazamos solo si estaba vacio (idempotente ante carreras con el checkout)
                $didAttach = $payRepo->attachOrderIfEmpty($paymentRowId, $orderId);

                // Auditoria solo si este flujo fue quien enlazo (evita duplicados)
                if ($didAttach) {
                    Audit::log('order_mark_paid_mp', 'order', $orderId, [
                        'provider' => 'mercadopago',
                        'payment_id' => $paymentId,
                        'payment_row_id' => $paymentRowId,
                        'status' => $status,
                        'intent_id' => $intentId,
                        'source' => 'webhook',
                        'reconciliation_mode' => 'created_from_intent',
                        'external_reference' => $external,
                        'webhook_type' => $type,
                    ]);
                    cache_forget('feed:audit_recent8');

                    // Analiticas: compra confirmada por MP (solo si este webhook enlazo)
                    try {
                        if (class_exists(\App\Services\Analytics\AnalyticsService::class)) {
                            (new \App\Services\Analytics\AnalyticsService($db))->trackPurchase((int)$orderId, 'mercadopago');
                        }
                    } catch (\Throwable $e) { /* no bloqueante */ }

                    // Auto-emision ARCA (si esta activada en Ajustes)
                    // Solo se ejecuta si el trigger configurado es "al pagar".
                    try {
                        $cfg = (new \App\Services\Billing\Arca\ArcaConfig($db))->get();
                        if (($cfg['auto_emit_trigger'] ?? 'paid') === 'paid') {
                            $arcaSvc = new \App\Services\Billing\FacturacionArcaService($db);
                            $arcaSvc->emitirAutoDesdePedido(0, (int)$orderId, 'mp_webhook');
                        }
                    } catch (\Throwable $e) { /* no bloqueante */ }
                }

                $this->logWebhookEvent($didAttach ? 'approved_created_and_attached' : 'approved_created_without_attach', [
                    'payment_id' => $paymentId,
                    'order_id' => $orderId,
                    'payment_row_id' => $paymentRowId,
                    'status' => $status,
                    'intent_id' => $intentId,
                    'external_reference' => $external,
                    'type' => $type,
                ]);
            }

            if (!$ok && str_starts_with((string)$msg,'B1_UNAVAILABLE:')) {
                // PaymentRepository arriba ya guardó approved+raw_json+intent:
                // conciliar manualmente; el webhook no puede crear un pedido B1.
                $this->logWebhookEvent('payment_approved_but_product_unavailable', [
                    'payment_id'=>$paymentId, 'payment_row_id'=>$paymentRowId,
                    'intent_id'=>$intentId, 'external_reference'=>$external,
                    'status'=>$status, 'order_id'=>0,'reconciliation_required'=>true,
                ]);
                Audit::log('payment_approved_but_product_unavailable','payment',(int)$paymentRowId,[
                    'provider'=>'mercadopago','payment_id'=>$paymentId,
                    'intent_id'=>$intentId,'manual_reconciliation_required'=>true,
                ]);
                http_response_code(202);
                echo json_encode(['ok'=>false,'status'=>$status,
                    'code'=>'b1_unavailable','order_id'=>0,'payment_id'=>$paymentId,
                    'manual_reconciliation_required'=>true,
                    'msg'=>'Pago aprobado registrado; requiere conciliación manual. Pedido no creado.'],
                    JSON_UNESCAPED_UNICODE);
                return;
            }

            if (!$ok) {
                Audit::log('payment_approved_without_order','payment',(int)$paymentRowId,[
                    'provider'=>'mercadopago','payment_id'=>$paymentId,
                    'intent_id'=>$intentId,'reason'=>$msg,
                    'manual_reconciliation_required'=>true,
                ]);
            }
            http_response_code($ok ? 200 : 503);
            echo json_encode(['ok' => $ok, 'msg' => $msg, 'order_id' => $orderId, 'status' => $status, 'type' => $type]);
        } catch (\Throwable $e) {
            $this->logWebhookEvent('exception', [
                'payment_id' => (string)($paymentId ?? ''),
                'type' => (string)($type ?? ''),
                'message' => $e->getMessage(),
            ]);
            // Los 5xx habilitan reintento del proveedor. Nunca ACK exitoso al fallar
            // el registro o la conciliaciÃ³n del cobro.
            http_response_code(503);
            echo json_encode(['ok'=>false, 'error'=>'webhook_processing_failed']);
        }
    }

    /** @param array<string,mixed> $payload */
    private function isSignatureValid(array $payload): bool
    {
        $secret = trim((string)env('MP_WEBHOOK_SIGNATURE_SECRET', ''));
        if ($secret === '') {
            return true;
        }

        $xSignature = trim((string)($_SERVER['HTTP_X_SIGNATURE'] ?? ''));
        $xRequestId = trim((string)($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
        if ($xSignature === '' || $xRequestId === '') {
            return false;
        }

        $signatureParts = $this->parseSignatureHeader($xSignature);
        $ts = (string)($signatureParts['ts'] ?? '');
        $hash = strtolower((string)($signatureParts['v1'] ?? ''));
        if ($ts === '' || $hash === '') {
            return false;
        }

        $dataId = $this->resolveNotificationDataId($payload);
        $manifest = $this->buildSignatureManifest($dataId, $xRequestId, $ts);
        if ($manifest === '') {
            return false;
        }

        $expected = strtolower(hash_hmac('sha256', $manifest, $secret));
        return hash_equals($expected, $hash);
    }

    /** @return array<string,string> */
    private function parseSignatureHeader(string $header): array
    {
        $parts = explode(',', $header);
        $parsed = [];
        foreach ($parts as $part) {
            $keyValue = explode('=', $part, 2);
            if (count($keyValue) !== 2) {
                continue;
            }
            $key = strtolower(trim((string)$keyValue[0]));
            $value = trim((string)$keyValue[1]);
            if ($key !== '' && $value !== '') {
                $parsed[$key] = $value;
            }
        }
        return $parsed;
    }

    /** @param array<string,mixed> $payload */
    private function resolveNotificationDataId(array $payload): string
    {
        $rawQuery = (string)parse_url((string)($_SERVER['REQUEST_URI'] ?? ''), PHP_URL_QUERY);
        $dataId = '';
        if ($rawQuery !== '') {
            foreach (explode('&', $rawQuery) as $pair) {
                if ($pair === '') {
                    continue;
                }
                [$rawKey, $rawValue] = array_pad(explode('=', $pair, 2), 2, '');
                $key = urldecode($rawKey);
                if ($key === 'data.id' || $key === 'id') {
                    $dataId = urldecode($rawValue);
                    break;
                }
            }
        }

        if ($dataId === '') {
            $dataId = (string)($payload['data']['id'] ?? $payload['id'] ?? '');
        }

        $dataId = trim($dataId);
        return preg_match('/[A-Za-z]/', $dataId) ? strtolower($dataId) : $dataId;
    }

    private function buildSignatureManifest(string $dataId, string $requestId, string $ts): string
    {
        $parts = [];
        if ($dataId !== '') {
            $parts[] = 'id:' . $dataId;
        }
        if ($requestId !== '') {
            $parts[] = 'request-id:' . $requestId;
        }
        if ($ts !== '') {
            $parts[] = 'ts:' . $ts;
        }
        return $parts === [] ? '' : implode(';', $parts) . ';';
    }

    /** @param array<string,mixed> $payload */
    private function resolveNotificationType(array $payload): string
    {
        $type = (string)($payload['type'] ?? $payload['topic'] ?? $_GET['type'] ?? $_GET['topic'] ?? '');
        return strtolower(trim($type));
    }

    private function mapTerminalOrderStatus(string $paymentStatus): ?string
    {
        return match (strtolower(trim($paymentStatus))) {
            'rejected' => 'payment_expired',
            'cancelled' => 'payment_expired',
            default => null,
        };
    }

    /** @param array<string,mixed> $context */
    private function logWebhookEvent(string $event, array $context = []): void
    {
        if (!function_exists('log_error')) {
            return;
        }

        $safe = [];
        foreach ($context as $key => $value) {
            if ($value === null || $value === '') {
                continue;
            }
            if (is_bool($value)) {
                $safe[$key] = $value ? 1 : 0;
                continue;
            }
            if (is_scalar($value)) {
                $safe[$key] = (string)$value;
            }
        }

        $requestId = trim((string)($_SERVER['HTTP_X_REQUEST_ID'] ?? ''));
        if ($requestId !== '') {
            $safe['request_id'] = $requestId;
        }

        log_error('mp_webhook[' . $event . '] ' . json_encode($safe, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }
}
