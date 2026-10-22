<?php
namespace App\Controllers\Checkout;

use App\Mail\Core\Mailer;
use App\Repositories\Payments\PaymentRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Audit\Audit;
use App\Services\Checkout\CheckoutIntentService;
use App\Services\Checkout\CheckoutOrderService;
use App\Services\Checkout\CheckoutStockPreflightService;
use App\Services\Orders\OrderFromIntentService;
use App\Services\Payments\MercadoPagoService;
use App\Services\Payments\MercadoPago\MercadoPagoStatusMapper;
use App\Services\Payments\MercadoPago\MercadoPagoPaymentMatchGuard;

final class PaymentController
{
    // POST /checkout/pago/seleccionar (JSON)
    public function select(): void
    {
        // checkout requiere usuario logueado
        if (function_exists('auth_required')) auth_required('/checkout');
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        header('Content-Type: application/json; charset=utf-8');

        $method = trim((string)($_POST['method'] ?? ''));
        $allowed = ['tarjeta_credito','tarjeta_debito','transferencia'];

        if ($method === '' || !in_array($method, $allowed, true)) {
            http_response_code(422);
            echo json_encode(['ok' => false, 'error' => 'Método de pago inválido.'], JSON_UNESCAPED_UNICODE);
            return;
        }

        if (!isset($_SESSION)) @session_start();
        $_SESSION['checkout_payment'] = [
            'method' => $method,
            'updated_at' => gmdate('c'),
        ];

        echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
    }

    // POST /checkout/mp/preference
    public function preference(): void
    {
        auth_required('/checkout');
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) {
            json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $addressId = $this->resolveAddressId();
        if ($addressId <= 0) {
            json(['ok' => false, 'error' => 'Seleccioná una dirección para continuar.'], 422);
        }

        try {
            $intentSvc = new CheckoutIntentService(db());
            [$ok, $msg, $intent] = $intentSvc->createIntent($uid, $addressId, $this->resolveCheckoutMethodForIntent());
            if (!$ok) {
                json(['ok' => false, 'error' => (string)$msg], 422);
            }

            $preference = $this->createPreference((array)$intent);
            json([
                'ok' => true,
                'external_reference' => (string)($intent['token'] ?? ''),
                'preference_id' => (string)($preference['id'] ?? ''),
                'init_point' => (string)($preference['init_point'] ?? ''),
                'sandbox_init_point' => (string)($preference['sandbox_init_point'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Checkout PaymentController@preference: '.$e->getMessage());
            }
            json(['ok' => false, 'error' => 'No se pudo iniciar el pago.'], 500);
        }
    }

    // POST /checkout/pagar
    public function pay(): void
    {
        auth_required('/checkout');
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $addressId = $this->resolveAddressId();
        if ($addressId <= 0) {
            flash('error', 'Selecciona una direccion para continuar.');
            redirect('/checkout?step=1');
            return;
        }

        try {
            $intentSvc = new CheckoutIntentService(db());
            [$ok, $msg, $intent] = $intentSvc->createIntent($uid, $addressId, $this->resolveCheckoutMethodForIntent());
            if (!$ok) {
                flash('error', (string)$msg);
                redirect('/checkout?step=4');
                return;
            }

            $preference = $this->createPreference((array)$intent);
            $target = trim((string)($preference['init_point'] ?? $preference['sandbox_init_point'] ?? ''));
            if ($target === '') {
                flash('error', 'No se pudo iniciar el pago.');
                redirect('/checkout?step=4');
                return;
            }

            redirect($target);
        } catch (\Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Checkout PaymentController@pay: '.$e->getMessage());
            }
            flash('error', 'No se pudo iniciar el pago.');
            redirect('/checkout?step=4');
        }
    }

    // POST /checkout/mp/payment (JSON)
    public function payment(): void
    {
        auth_required('/checkout');
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) {
            json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $body = $this->readJsonBody();
        $addressId = (int)($body['address_id'] ?? 0);
        if ($addressId <= 0) {
            json(['ok' => false, 'error' => 'Seleccioná una dirección para continuar.'], 422);
        }

        $cardFormData = is_array($body['cardFormData'] ?? null) ? (array)$body['cardFormData'] : [];
        $token = trim((string)($cardFormData['token'] ?? ''));
        if ($token === '') {
            json(['ok' => false, 'error' => 'No se recibió token de tarjeta.'], 422);
        }

        $chargeAttemptStarted = false;
        $providerPaymentUnresolved = false;
        $providerPaymentId = '';
        try {
            $selectedMethod = trim((string)($body['selectedPaymentMethod'] ?? ($_SESSION['checkout_payment']['method'] ?? '')));
            if (!in_array($selectedMethod, ['tarjeta_credito', 'tarjeta_debito'], true)) {
                $selectedMethod = trim((string)($_SESSION['checkout_payment']['method'] ?? ''));
            }
            if (!in_array($selectedMethod, ['tarjeta_credito', 'tarjeta_debito'], true)) {
                $selectedMethod = 'tarjeta_credito';
            }

            $intentSvc = new CheckoutIntentService(db());
            [$okIntent, $msgIntent, $intent] = $intentSvc->createIntent($uid, $addressId, $selectedMethod);
            if (!$okIntent) {
                $needsReview = (string)($intent['code'] ?? '') === 'payment_requires_review';
                json([
                    'ok'=>false,
                    'code'=>$needsReview ? 'payment_requires_review' : 'checkout_intent_invalid',
                    'manual_reconciliation_required'=>$needsReview,
                    'error'=>(string)$msgIntent,
                ], $needsReview ? 202 : 422);
                return;
            }

            $intentToken = (string)($intent['token'] ?? '');
            $amount = (float)($intent['amount'] ?? 0);
            if ($intentToken === '' || $amount <= 0) {
                json(['ok' => false, 'error' => 'Intent de checkout inválido.'], 422);
            }

            $installments = max(1, (int)($cardFormData['installments'] ?? 1));
            if ($selectedMethod === 'tarjeta_debito') {
                $installments = 1;
            }

            $payload = [
                'transaction_amount' => $amount,
                'token' => $token,
                'description' => 'Compra Mi Tienda Pesca',
                'installments' => $installments,
                'payment_method_id' => (string)($cardFormData['payment_method_id'] ?? ''),
                'issuer_id' => (int)($cardFormData['issuer_id'] ?? 0),
                'external_reference' => $intentToken,
                'payer' => [
                    'email' => (string)($cardFormData['payer']['email'] ?? ($_SESSION['email'] ?? '')),
                    'identification' => [
                        'type' => (string)($cardFormData['payer']['identification']['type'] ?? ''),
                        'number' => (string)($cardFormData['payer']['identification']['number'] ?? ''),
                    ],
                ],
            ];

            if ($payload['payment_method_id'] === '') {
                $payload['payment_method_id'] = (string)($body['selectedPaymentMethod'] ?? '');
            }
            if ((int)$payload['issuer_id'] <= 0) {
                unset($payload['issuer_id']);
            }
            if (trim((string)$payload['payer']['email']) === '') {
                json(['ok' => false, 'error' => 'No se pudo identificar el email del pagador.'], 422);
            }
            if (trim((string)$payload['payer']['identification']['type']) === '' || trim((string)$payload['payer']['identification']['number']) === '') {
                unset($payload['payer']['identification']);
            }

            // Último control antes de la solicitud externa de cobro.
            [$b1Ok,$b1Msg] = (new \App\Services\Checkout\CheckoutProductEligibilityGuard(db()))
                ->checkItems((array)($intent['items'] ?? []));
            if (!$b1Ok) {
                json(['ok'=>false,'code'=>'b1_unavailable','error'=>$b1Msg],409);
                return;
            }

            $idemKey = hash('sha256', (string)$uid . '|' . $intentToken . '|' . $token . '|' . $amount);
            $payRepo = new PaymentRepository(db());
            $intentId = (int)($intent['id'] ?? 0);
            if ($intentId <= 0) {
                json(['ok'=>false,'error'=>'No se pudo verificar el checkout.'], 422);
                return;
            }
            $intentRepo = new \App\Repositories\Checkout\CheckoutIntentRepository(db());
            $persistedIntent = $intentRepo->findByToken($intentToken);
            $cartHash = (string)($persistedIntent['cart_hash'] ?? '');
            if ((int)($persistedIntent['id'] ?? 0) !== $intentId
                || (int)($persistedIntent['user_id'] ?? 0) !== $uid
                || (int)($persistedIntent['address_id'] ?? 0) !== $addressId
                || !preg_match('/^[a-f0-9]{64}$/D', $cartHash)) {
                json(['ok'=>false,'error'=>'Intent de checkout inconsistente.'],422);
                return;
            }
            $paymentLock = \App\Repositories\Checkout\CheckoutIntentRepository::paymentCartLockName(
                $uid,$addressId,$cartHash
            );
            $lockSt = db()->prepare('SELECT GET_LOCK(:key,25)');
            $lockSt->execute([':key'=>$paymentLock]);
            if ((int)$lockSt->fetchColumn() !== 1) {
                json(['ok'=>false,'code'=>'payment_in_progress',
                    'error'=>'Hay un pago en proceso. No intentes pagar de nuevo.'],409);
                return;
            }
            try {
                // Proteger el mismo carrito incluso con intents/campaÃ±as diferentes.
                $inFlight = $intentRepo->findInFlightForCart($uid,$addressId,$cartHash);
                $outstanding = $payRepo->findOutstandingForCart($uid,$addressId,$cartHash);
                if ($inFlight || $outstanding) {
                    json([
                        'ok'=>false,'code'=>'payment_requires_review',
                        'manual_reconciliation_required'=>true,
                        'payment_id'=>(string)($outstanding['provider_payment_id'] ?? ''),
                        'error'=>'Este carrito tiene un pago sin resolver. No vuelvas a pagar hasta verificarlo.',
                    ],202);
                    return;
                }
                $already = $payRepo->findActiveForIntent($intentId);
                if ($already) {
                    $needsReview = (string)($already['status'] ?? '') === 'approved'
                        && (int)($already['order_id'] ?? 0) <= 0;
                    json(['ok'=>false,'code'=>'existing_payment_for_intent',
                        'payment_id'=>(string)($already['provider_payment_id'] ?? ''),
                        'manual_reconciliation_required'=>$needsReview,
                        'error'=>'Ya existe un pago iniciado para este checkout. Verifica su estado antes de pagar nuevamente.'],
                        202);
                    return;
                }
            $mp = new MercadoPagoService();
            $attemptRepo = $intentRepo;
            if (!$attemptRepo->markPaymentInFlight($intentId)) {
                json(['ok'=>false,'code'=>'payment_requires_review',
                    'manual_reconciliation_required'=>true,
                    'error'=>'Ya existe una operacion que necesita revision. No vuelvas a pagar.'],202);
                return;
            }
            $chargeAttemptStarted = true;
            if (function_exists('log_error')) {
                log_error('Checkout PaymentController@payment payload=' . json_encode($this->sanitizeMpPaymentPayload($payload), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            $payment = $mp->createPayment($payload, $idemKey);

            $paymentId = (string)($payment['id'] ?? '');
            $providerPaymentId = $paymentId;
            $status = strtolower(trim((string)($payment['status'] ?? '')));
            $providerPaymentUnresolved = in_array($status, ['approved','pending','in_process','authorized'], true);
            $statusDetail = (string)($payment['status_detail'] ?? '');
            $mapper = new MercadoPagoStatusMapper();

            $paymentRowId = 0;
            if ($paymentId !== '') {
                $paymentRowData = [
                    'provider' => 'mercadopago',
                    'provider_payment_id' => $paymentId,
                    'provider_preference_id' => (string)($payment['order']['id'] ?? ''),
                    'status' => $status,
                    'amount' => (int)round((float)($payment['transaction_amount'] ?? $amount)),
                    'currency' => (string)($payment['currency_id'] ?? ($intent['currency'] ?? 'ARS')),
                    'intent_id' => (int)($intent['id'] ?? 0),
                    'order_id' => null,
                    'raw_json' => json_encode($payment, JSON_UNESCAPED_UNICODE),
                ];
                $paymentRowId = $payRepo->createOrUpdateByProviderPaymentId($paymentRowData);
            }

            // Solo se libera el marcador cuando ya existe evidencia del proveedor
            // guardada localmente. Ante error/crash queda bloqueado el reintento.
            if ($paymentRowId <= 0) {
                json(['ok'=>false,'code'=>'payment_state_uncertain',
                    'manual_reconciliation_required'=>true,'order_id'=>0,
                    'error'=>'El estado del pago necesita verificacion. No intentes pagar otra vez.'],202);
                return;
            }
            $attemptRepo->clearPaymentInFlight($intentId);
            $chargeAttemptStarted = false;
            } finally {
                $release = db()->prepare('SELECT RELEASE_LOCK(:key)');
                $release->execute([':key'=>$paymentLock]);
            }
            $orderId = 0;
            if ($status === 'approved') {
                [$trusted, $trustReason] = MercadoPagoPaymentMatchGuard::check($payment, (array)$intent, $paymentId);
                if (!$trusted || $paymentRowId <= 0) {
                    Audit::log('payment_approved_mismatch', 'checkout', null, [
                        'payment_id'=>$paymentId, 'payment_row_id'=>$paymentRowId,
                        'intent_id'=>(int)($intent['id'] ?? 0),
                        'reason'=>$trusted ? 'missing_payment_record' : $trustReason,
                        'manual_reconciliation_required'=>true,
                    ]);
                    json([
                        'ok'=>false, 'code'=>'approved_payment_requires_review',
                        'payment_id'=>$paymentId, 'order_id'=>0,
                        'manual_reconciliation_required'=>true,
                        'error'=>'El proveedor aprobo un pago que no podemos vincular de forma segura. No pagues nuevamente: requiere conciliacion.',
                    ], 202);
                    return;
                }
                $orderSvc = new OrderFromIntentService(db());
                [$okOrder, $_msgOrder, $orderId] = $orderSvc->createOrderFromIntent($intentToken);
                if ($okOrder && $orderId > 0 && $paymentRowId > 0) {
                    $payRepo->attachOrderIfEmpty($paymentRowId, $orderId);
                }
            }
            if ($orderId <= 0 && in_array($status, ['pending', 'in_process', 'authorized'], true)) {
                $orderSvc = new CheckoutOrderService(db());
                [$okOrder, $_msgOrder, $orderId] = $orderSvc->confirm(
                    $uid,
                    $addressId,
                    $selectedMethod,
                    $intentToken,
                    (array)($intent['totals'] ?? [])
                );
                if ($okOrder && $orderId > 0 && $paymentRowId > 0) {
                    $payRepo->attachOrderIfEmpty($paymentRowId, $orderId);
                }
            }

            if ($orderId <= 0 && str_starts_with((string)($_msgOrder ?? ''), 'B1_UNAVAILABLE:')) {
                // El pago puede estar aprobado. PaymentRepository ya conservó el
                // registro del proveedor y sus datos; nunca informar pedido creado.
                Audit::log('payment_approved_but_product_unavailable', 'checkout', null, [
                    'payment_id'=>$paymentId, 'payment_row_id'=>$paymentRowId,
                    'intent_id'=>(int)($intent['id']??0), 'status'=>$status,
                    'needs_manual_reconciliation'=>($status==='approved'),
                ]);
                json([
                    'ok'=>false,'code'=>'b1_unavailable','status'=>$status,
                    'payment_id'=>$paymentId,'order_id'=>0,
                    'manual_reconciliation_required'=>($status==='approved'),
                    'error'=>$status==='approved'
                        ? 'El pago fue aprobado, pero el producto ya no está disponible. Nuestro equipo debe conciliar el cobro; no realices otro pago.'
                        : \App\Services\Checkout\CheckoutProductEligibilityGuard::ERROR,
                ],$status==='approved' ? 202 : 409);
                return;
            }

            // El cobro aprobado sin pedido no es un checkout exitoso.
            // Conservar evidencia del proveedor; no repetir el pago automaticamente.
            if ($status === 'approved' && $orderId <= 0) {
                Audit::log('payment_approved_without_order', 'checkout', null, [
                    'payment_id' => $paymentId,
                    'payment_row_id' => $paymentRowId,
                    'intent_id' => (int)($intent['id'] ?? 0),
                    'status' => $status,
                    'manual_reconciliation_required' => true,
                ]);
                json([
                    'ok' => false,
                    'code' => 'approved_without_order',
                    'status' => $status,
                    'payment_id' => $paymentId,
                    'order_id' => 0,
                    'manual_reconciliation_required' => true,
                    'error' => 'El pago fue aprobado, pero no se pudo confirmar tu pedido. No vuelvas a pagar: nuestro equipo debe conciliar el cobro.',
                ], 202);
                return;
            }
            $ui = $mapper->map($payment, [
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'orders_url' => url('/cuenta/pedidos'),
                'order_url' => url('/cuenta/pedidos/ver') . ($orderId > 0 ? ('?id=' . $orderId) : ''),
                'checkout_url' => url('/checkout?step=4'),
                'cart_url' => url('/carrito'),
            ]);

            if ($status === 'approved' && $orderId > 0) {
                $ui['actions']['primary']['href'] = url('/cuenta/pedidos/ver') . '?id=' . $orderId;
                $ui['actions']['secondary']['href'] = url('/cuenta/pedidos');
            }
            if (in_array($status, ['pending', 'in_process', 'authorized'], true) && $orderId > 0) {
                $ui['actions']['primary']['href'] = url('/cuenta/pedidos/ver') . '?id=' . $orderId . '&ok=1&metodo=' . rawurlencode($selectedMethod);
                $ui['actions']['secondary']['href'] = url('/cuenta/pedidos');
            }

            json([
                'ok' => true,
                'status' => $status,
                'status_detail' => $statusDetail,
                'payment_id' => $paymentId,
                'order_id' => $orderId,
                'order_url' => url('/cuenta/pedidos/ver') . ($orderId > 0 ? ('?id=' . $orderId . '&ok=1&metodo=' . rawurlencode($selectedMethod)) : ''),
                'payment_method' => $selectedMethod,
                'ui' => $ui,
            ]);
        } catch (\Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Checkout PaymentController@payment: '.$e->getMessage());
            }

            try {
                $exceptionMessage = strtolower((string)$e->getMessage());
                $responseStatus = 500;
                if (preg_match('/\bhttp=(\d{3})\b/i', (string)$e->getMessage(), $matches)) {
                    $responseStatus = (int)($matches[1] ?? 500);
                }
                $responseCode = str_contains($exceptionMessage, 'internal_error') ? 'mp_internal_error' : 'payment_error';

                Audit::log('checkout_mp_payment_error', 'checkout', null, [
                    'code' => $responseCode,
                    'http_status' => $responseStatus > 0 ? $responseStatus : 500,
                    'message' => $e->getMessage(),
                    'payment_method' => (string)($_SESSION['checkout_payment']['method'] ?? ''),
                    'address_id' => (int)($_POST['address_id'] ?? $_GET['address_id'] ?? 0),
                ]);
            } catch (\Throwable $_) {
                // no-op
            }

            if ($chargeAttemptStarted || $providerPaymentUnresolved) {
                // El pago puede estar confirmado aun cuando falle la creaciÃ³n del pedido.
                // Conservar el identificador del proveedor y bloquear la repeticiÃ³n del cargo.
                Audit::log('checkout_payment_requires_reconciliation', 'checkout', null, [
                    'payment_id'=>$providerPaymentId,
                    'uncertain_before_persist'=>$chargeAttemptStarted,
                    'manual_reconciliation_required'=>true,
                ]);
                json([
                    'ok'=>false,'code'=>'payment_state_uncertain',
                    'payment_id'=>$providerPaymentId,'order_id'=>0,
                    'manual_reconciliation_required'=>true,
                    'error'=>'No pudimos confirmar el resultado del pago. No vuelvas a pagar hasta que nuestro equipo lo revise.',
                ],202);
                return;
            }
            $message = 'No se pudo procesar el pago.';
            $code = 'payment_error';
            $status = 500;

            $exceptionMessage = strtolower((string)$e->getMessage());
            if (str_contains($exceptionMessage, 'internal_error')) {
                $message = 'Mercado Pago tuvo un error temporal. Probá nuevamente en unos minutos.';
                $code = 'mp_internal_error';
                $status = 502;
            }

            json([
                'ok' => false,
                'error' => $message,
                'code' => $code,
            ], $status);
        }
    }

    // POST /checkout/mp/brick-failed (JSON)
    public function brickFailed(): void
    {
        auth_required('/checkout');
        verify_csrf_or_abort();

        header('Content-Type: application/json; charset=utf-8');

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) {
            json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $body = $this->readJsonBody();
        $reason = substr(trim((string)($body['reason'] ?? 'brick_failed')), 0, 120);
        if ($reason === '') {
            $reason = 'brick_failed';
        }

        $sessionKey = 'checkout_mp_brick_failed_mail';
        $now = time();
        $last = (int)($_SESSION[$sessionKey] ?? 0);
        if ($last > 0 && ($now - $last) < 900) {
            json(['ok' => true, 'deduped' => true], 200);
        }

        try {
            $settings = new AppSettingsRepository(db());
            $to = trim((string)($settings->get('contact_support_email', '') ?? ''));
            if ($to === '') {
                $to = trim((string)env('MAIL_FROM_EMAIL', ''));
            }

            if ($to === '') {
                json(['ok' => true, 'skipped' => 'missing_recipient'], 200);
            }

            $userEmail = trim((string)($_SESSION['email'] ?? ''));
            $method = substr(trim((string)($body['method'] ?? '')), 0, 60);
            $step = (int)($body['step'] ?? 0);
            $addressId = (int)($body['address_id'] ?? 0);
            $url = substr(trim((string)($body['url'] ?? '')), 0, 500);
            $ua = substr(trim((string)($_SERVER['HTTP_USER_AGENT'] ?? '')), 0, 500);

            $content = Mailer::render('checkout/mp_brick_failed_internal', [
                'userId' => $uid,
                'userEmail' => $userEmail,
                'method' => $method,
                'step' => $step,
                'addressId' => $addressId,
                'reason' => $reason,
                'url' => $url,
                'userAgent' => $ua,
                'occurredAt' => date('d/m/Y H:i:s'),
            ]);
            $html = Mailer::wrapLayout('Alerta checkout Mercado Pago', $content);
            $subject = 'Alerta checkout MP: fallo de brick';

            $ok = Mailer::sendHtml($to, $subject, $html);
            if ($ok) {
                $_SESSION[$sessionKey] = $now;
            }

            try {
                Audit::log('checkout_mp_brick_failed', 'user', $uid, [
                    'email' => $userEmail,
                    'method' => $method,
                    'step' => $step,
                    'address_id' => $addressId,
                    'reason' => $reason,
                    'url' => $url,
                ]);
                cache_forget('feed:audit_recent8');
            } catch (\Throwable $_) {}

            json(['ok' => true, 'sent' => $ok], 200);
        } catch (\Throwable $e) {
            if (function_exists('log_error')) {
                log_error('Checkout PaymentController@brickFailed: ' . $e->getMessage());
            }
            json(['ok' => true, 'sent' => false], 200);
        }
    }

    // POST /checkout/stock/preflight (JSON)
    public function stockPreflight(): void
    {
        auth_required('/checkout');
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) {
            json(['ok' => false, 'error' => 'unauthorized'], 401);
        }

        $body = $this->readJsonBody();
        $addressId = (int)($body['address_id'] ?? 0);
        if ($addressId <= 0) {
            json(['ok' => false, 'error' => 'Selecciona una direccion para continuar.'], 422);
        }

        $selectedMethod = trim((string)($body['selectedPaymentMethod'] ?? ($_SESSION['checkout_payment']['method'] ?? '')));
        if (!in_array($selectedMethod, ['tarjeta_credito', 'tarjeta_debito'], true)) {
            $selectedMethod = 'tarjeta_credito';
        }

        $svc = new CheckoutStockPreflightService(db());
        [$ok, $msg, $meta] = $svc->validate($uid, $addressId, $selectedMethod);
        if (!$ok) {
            $status = (($meta['code'] ?? '') === 'stock_changed') ? 409 : 422;
            json([
                'ok' => false,
                'error' => $msg,
                'code' => (string)($meta['code'] ?? 'stock_preflight_failed'),
                'redirect_url' => (string)($meta['redirect_url'] ?? ''),
            ], $status);
        }

        json(['ok' => true], 200);
    }

    // GET /pago/resultado
    public function result(): void
    {
        auth_required('/checkout');

        $paymentId = trim((string)($_GET['payment_id'] ?? $_GET['collection_id'] ?? $_GET['pago'] ?? ''));
        $status = strtolower(trim((string)($_GET['status'] ?? $_GET['collection_status'] ?? '')));

        if ($paymentId !== '') {
            redirect(url('/cuenta/pedidos') . '?pago=' . rawurlencode($paymentId));
            return;
        }

        if (in_array($status, ['rejected', 'failure', 'cancelled'], true)) {
            flash('error', 'El pago no fue aprobado. Intenta nuevamente.');
            redirect('/checkout?step=4');
            return;
        }

        flash('info', 'Estamos procesando tu pago.');
        redirect('/cuenta/pedidos');
    }

    private function createPreference(array $intent): array
    {
        $items = is_array($intent['items'] ?? null) ? (array)$intent['items'] : [];
        $mpItems = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $qty = max(1, (int)($item['qty'] ?? 1));
            $unit = (int)($item['price'] ?? 0);
            $mpItems[] = [
                'title' => (string)($item['name'] ?? 'Producto'),
                'quantity' => $qty,
                'unit_price' => round($unit, 2),
                'currency_id' => (string)($intent['currency'] ?? 'ARS'),
            ];
        }

        if ($mpItems === []) {
            throw new \RuntimeException('No hay items para generar preferencia.');
        }

        $externalReference = (string)($intent['token'] ?? '');
        if ($externalReference === '') {
            throw new \RuntimeException('Intent sin token.');
        }

        $secret = trim((string)env('MP_WEBHOOK_SECRET', ''));
        $notificationUrl = url('/webhooks/mercadopago') . ($secret !== '' ? ('?secret=' . rawurlencode($secret)) : '');
        $backUrl = url('/pago/resultado');

        $mp = new MercadoPagoService();
        return $mp->createPreference($mpItems, $externalReference, $notificationUrl, $backUrl);
    }

    private function resolveAddressId(): int
    {
        return (int)($_POST['address_id'] ?? $_GET['address_id'] ?? 0);
    }

    /** @return array<string,mixed> */
    private function readJsonBody(): array
    {
        $raw = file_get_contents('php://input');
        if (!is_string($raw) || trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($raw, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function resolveCheckoutMethodForIntent(): string
    {
        $method = trim((string)($_SESSION['checkout_payment']['method'] ?? ''));
        if (in_array($method, ['tarjeta_credito', 'tarjeta_debito', 'transferencia'], true)) {
            return $method;
        }

        return 'tarjeta_credito';
    }

    /**
     * @param array<string,mixed> $payload
     * @return array<string,mixed>
     */
    private function sanitizeMpPaymentPayload(array $payload): array
    {
        $token = trim((string)($payload['token'] ?? ''));
        $email = trim((string)($payload['payer']['email'] ?? ''));
        $docNumber = trim((string)($payload['payer']['identification']['number'] ?? ''));

        return [
            'transaction_amount' => (float)($payload['transaction_amount'] ?? 0),
            'installments' => (int)($payload['installments'] ?? 0),
            'payment_method_id' => (string)($payload['payment_method_id'] ?? ''),
            'issuer_id' => isset($payload['issuer_id']) ? (int)$payload['issuer_id'] : null,
            'external_reference' => (string)($payload['external_reference'] ?? ''),
            'token' => $this->maskToken($token),
            'description' => (string)($payload['description'] ?? ''),
            'payer' => [
                'email' => $this->maskEmail($email),
                'identification' => [
                    'type' => (string)($payload['payer']['identification']['type'] ?? ''),
                    'number' => $this->maskDocument($docNumber),
                ],
            ],
        ];
    }

    private function maskToken(string $token): string
    {
        if ($token === '') {
            return '';
        }

        return substr($token, 0, 6) . '…' . substr($token, -4);
    }

    private function maskEmail(string $email): string
    {
        if ($email === '' || !str_contains($email, '@')) {
            return $email;
        }

        [$local, $domain] = explode('@', $email, 2);
        $local = substr($local, 0, 2);
        return $local . '…@' . $domain;
    }

    private function maskDocument(string $document): string
    {
        $digits = preg_replace('/\D+/', '', $document) ?: '';
        if ($digits === '') {
            return $document;
        }

        if (strlen($digits) <= 4) {
            return str_repeat('•', strlen($digits));
        }

        return str_repeat('•', max(0, strlen($digits) - 4)) . substr($digits, -4);
    }
}
