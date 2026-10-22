<?php
declare(strict_types=1);

/** Static safeguards for approved provider payments with no confirmed order. */
$root = dirname(__DIR__, 2);
$payment = (string)file_get_contents($root . '/app/Controllers/Checkout/PaymentController.php');
$webhook = (string)file_get_contents($root . '/app/Controllers/Webhooks/MercadoPagoWebhookController.php');
$guard = strpos($payment, "if (\$status === 'approved' && \$orderId <= 0)");
$success = $guard === false ? false : strpos($payment, "'ok' => true,", $guard);
$mapper = strpos($payment, '$ui = $mapper->map($payment');
$persist = strpos($payment, 'createOrUpdateByProviderPaymentId(');
$charge = strpos($payment, '->createPayment($payload');
$check = [
    'approved orphan guard present' => $guard !== false,
    'guard after provider charge and persistent payment record' => $guard !== false && $persist !== false && $charge !== false && $charge < $persist && $persist < $guard,
    'guard before happy-path mapper' => $mapper !== false && $guard < $mapper,
    'guard before success JSON' => $success !== false && $guard < $success,
    'generic orphan has distinct non-success code' => str_contains($payment, "'code' => 'approved_without_order'"),
    'manual reconciliation field exposed' => str_contains($payment, "'manual_reconciliation_required' => true"),
    'safe 202 response' => str_contains($payment, "                ], 202);"),
    'no order ID invented' => str_contains($payment, "'order_id' => 0,"),
    'B1 special case still preserved' => str_contains($payment, "str_starts_with((string)(\$_msgOrder ?? ''), 'B1_UNAVAILABLE:')"),
    'checkout CSRF still guarded' => substr_count($payment, 'verify_csrf_or_abort()') >= 4,
    'webhook persists provider payment before order attempt' => strpos($webhook, 'createOrUpdateByProviderPaymentId(') < strpos($webhook, 'createOrderFromIntent('),
    'webhook B1 orphan audit preserved' => str_contains($webhook, 'payment_approved_but_product_unavailable') && str_contains($webhook, 'manual_reconciliation_required'),
];
foreach ($check as $title => $ok) {
    if (!$ok) { fwrite(STDERR, "[checkout-orphan] FAIL: $title\n"); exit(1); }
}
echo '[checkout-orphan] OK ', count($check), " checks\n";
