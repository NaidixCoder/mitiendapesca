<?php
declare(strict_types=1);

require_once dirname(__DIR__,2).'/app/Services/Payments/MercadoPago/MercadoPagoPaymentMatchGuard.php';
use App\Services\Payments\MercadoPago\MercadoPagoPaymentMatchGuard as Guard;

$root=dirname(__DIR__,2);
$payment=(string)file_get_contents($root.'/app/Controllers/Checkout/PaymentController.php');
$webhook=(string)file_get_contents($root.'/app/Controllers/Webhooks/MercadoPagoWebhookController.php');
$repo=(string)file_get_contents($root.'/app/Repositories/Payments/PaymentRepository.php');
$intent=(string)file_get_contents($root.'/app/Services/Checkout/CheckoutIntentService.php');
$intentRepo=(string)file_get_contents($root.'/app/Repositories/Checkout/CheckoutIntentRepository.php');
$checks=[
 'payment matches trusted provider data before creating order' =>
    str_contains($payment,'MercadoPagoPaymentMatchGuard::check(')
    && strpos($payment,'MercadoPagoPaymentMatchGuard::check(')<strpos($payment,'createOrderFromIntent('),
 'webhook matches provider and intent before order' =>
    str_contains($webhook,'MercadoPagoPaymentMatchGuard::check(')
    && strpos($webhook,'MercadoPagoPaymentMatchGuard::check(')<strpos($webhook,'createOrderFromIntent('),
 'no unsigned/unsecret webhook when both secrets absent'=>
    str_contains($webhook,'webhook_auth_not_configured'),
 'signature validation preserved'=>str_contains($webhook,'invalid_signature')
    && str_contains($webhook,'hash_hmac('),
 'processing exceptions are retryable, not acknowledged success'=>
    str_contains($webhook,"'error'=>'webhook_processing_failed'")
    && str_contains($webhook,'http_response_code(503)'),
 'orphan payment requires manual review'=>
    str_contains($payment,'approved_without_order')
    && str_contains($webhook,'payment_approved_without_order'),
 'one active payment per intent before charging'=>
    str_contains($payment,'findActiveForIntent($intentId)')
    && strpos($payment,'findActiveForIntent($intentId)')<strpos($payment,'->createPayment('),
 'double distinct MP payments require human review'=>
    str_contains($webhook,'duplicate_payment_for_intent'),
 'provider payment unique by ID preserved'=>
    str_contains($repo,'createOrUpdateByProviderPaymentId')
    && str_contains($repo,'findActiveForIntent('),
 'serialized intent lookup and create'=>
    str_contains($intent,'acquireReuseLock(')
    && str_contains($intent,'releaseReuseLock(')
    && str_contains($intentRepo,'GET_LOCK('),
 'durable pre-provider marker before external charge'=>
    str_contains($payment,'markPaymentInFlight($intentId)')
    && strpos($payment,'markPaymentInFlight($intentId)') < strpos($payment,'->createPayment('),
 'only clear marker after local provider payment record'=>
    str_contains($payment,'clearPaymentInFlight($intentId)')
    && strpos($payment,'createOrUpdateByProviderPaymentId(')<strpos($payment,'clearPaymentInFlight($intentId)'),
 'approved provider/order exception remains reconciliation, never retry success'=>
    str_contains($payment,'$chargeAttemptStarted || $providerPaymentUnresolved')
    && str_contains($payment,"'code'=>'payment_state_uncertain'"),
 'same cart payment lock independent from intent/campaign'=>
    str_contains($payment,'paymentCartLockName(')
    && str_contains($intentRepo,'public static function paymentCartLockName(')
    && str_contains($intentRepo,'[$userId,$addressId,$cartHash]'),
 'another intent with same cart is checked before the charge'=>
    str_contains($payment,'findOutstandingForCart($uid,$addressId,$cartHash)')
    && str_contains($payment,'findInFlightForCart($uid,$addressId,$cartHash)'),
 'failed reconciliation of existing order not acknowledged as success'=>
    str_contains($webhook,'http_response_code($okPaid ? 200 : 202)')
    && str_contains($webhook,'payment_existing_order_requires_review'),
 'no automated refund method introduced'=>
    !str_contains($payment,'->refundPayment(')
    && !str_contains($webhook,'->refundPayment('),
];
$total=0;
foreach($checks as $title=>$pass) {
    $total++;
    if(!$pass){fwrite(STDERR,"[checkout-integrity] FAIL $title\n");exit(1);}
}
$intentRow=['id'=>1,'token'=>'qa-token','amount'=>1520,'currency'=>'ARS'];
$paymentData=['id'=>'qa-payment','status'=>'approved','transaction_amount'=>1520,
 'currency_id'=>'ARS','external_reference'=>'qa-token'];
[$yes]=Guard::check($paymentData,$intentRow,'qa-payment');
if(!$yes){fwrite(STDERR,"[checkout-integrity] valid payment rejected\n");exit(1);}
$total++;
foreach([
 ['transaction_amount'=>1520.01],['transaction_amount'=>1519.99],
 ['currency_id'=>'USD'],['external_reference'=>'wrong'],
 ['id'=>'other'],['status'=>'pending'],['transaction_amount'=>0]
] as $mutation) {
    [$ok]=Guard::check(array_replace($paymentData,$mutation),$intentRow,'qa-payment');
    if($ok){fwrite(STDERR,"[checkout-integrity] failed to reject unsafe payment\n");exit(1);}
    $total++;
}
[$ok]=Guard::check($paymentData,null,'qa-payment');
if($ok){fwrite(STDERR,"[checkout-integrity] missing intent not rejected\n");exit(1);}
$total++;
echo "[checkout-integrity] OK $total contract checks\n";
