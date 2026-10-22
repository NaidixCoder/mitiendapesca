<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$get=static fn(string $p): string => (string)file_get_contents($root.'/'.$p);
$guard=$get('app/Services/Checkout/CheckoutProductEligibilityGuard.php');
$cart=$get('app/Services/Cart/Legacy/LegacyCartEngine.php');
$vm=$get('app/Services/Cart/CartViewModelService.php');
$selection=$get('app/Services/Cart/CartSelectionService.php');
$cartController=$get('app/Controllers/Cart/CartController.php');
$cartItem=$get('app/Views/partials/carrito/item.php');
$summary=$get('app/Views/partials/carrito/summary.php');
$checkout=$get('app/Controllers/Checkout/CheckoutController.php');
$intent=$get('app/Services/Checkout/CheckoutIntentService.php');
$preflight=$get('app/Services/Checkout/CheckoutStockPreflightService.php');
$order=$get('app/Services/Checkout/CheckoutOrderService.php');
$fromIntent=$get('app/Services/Orders/OrderFromIntentService.php');
$payment=$get('app/Controllers/Checkout/PaymentController.php');
$webhook=$get('app/Controllers/Webhooks/MercadoPagoWebhookController.php');
$checks=[
 'new guard is central' => str_contains($guard,'final class CheckoutProductEligibilityGuard'),
 'guard uses public policy' => str_contains($guard,'new ProductPublicVisibilityService($this->db)'),
 'guard demands transaction for locking' => str_contains($guard,'$lock && !$this->db->inTransaction()'),
 'guard locks fresh B1 flag' => str_contains($guard,"setting_key = 'catalog_hide_inactive_category_products' FOR UPDATE"),
 'guard locks category ancestry under transaction' => str_contains($guard,'FROM categories ORDER BY id FOR UPDATE'),
 'guard locks product state and assignment' => str_contains($guard,'FROM products WHERE id IN ($ph) ORDER BY id FOR UPDATE'),
 'guard locks variants and checks owner' => str_contains($guard,'product_variants WHERE id IN ($ph)') && str_contains($guard,'$variants[$vid]'),
 'guard rejects cycles and inactive ancestors' => str_contains($guard,'isset($seen[$at])') && str_contains($guard,'!$nodes[$at][\'active\']'),
 'guard does not write catalog flags or price' => !preg_match('/\bUPDATE\s+(products|categories|inventory)\b/i',$guard),
 'cart validates addition' => str_contains($cart,'->checkItems([[') && str_contains($cart,'public function add('),
 'cart validates quantity updates' => str_contains($cart,'public function bulkUpdate(') && str_contains($cart,'if ($qty === 0)'),
 'cart retains historical unavailable items' => str_contains($cart,"is_publicly_available"),
 'cart merge add paths still exist' => str_contains($cart,'public function mergeItems(') && str_contains($cart,'public function replaceWithItems('),
 'cart view model disallows unavailable' => str_contains($vm,'is_publicly_available'),
 'cart selection raw/VM disallows unavailable' => str_contains($selection,'is_publicly_available'),
 'cart feedback includes unavailable' => str_contains($cartController,'dejaron de estar disponibles') && str_contains($cartController,'Este producto está oculto'),
 'view lets user remove' => str_contains($cartItem,'data-cart-remove'),
 'view declares B1 unavailable' => str_contains($cartItem,'is_publicly_available'),
 'summary warns user' => str_contains($summary,'$hasB1Unavailable'),
 'checkout GET redirects old B1 cart' => str_contains($checkout,'is_publicly_available') && str_contains($checkout,'checkout_cambio=1'),
 'intent guards before findReusable' => strpos($intent,'->checkItems(') < strpos($intent,'->findReusable('),
 'stock preflight locks B1 inside transaction' => strpos($preflight,'$this->db->beginTransaction()') < strpos($preflight,'->checkItems(') && str_contains($preflight,'b1_unavailable'),
 'direct order guards under same transaction' => strpos($order,'$this->db->beginTransaction()') < strpos($order,'->checkItems(') && strpos($order,'->checkItems(') < strpos($order,'->lockVariantStockForUpdate('),
 'intent order respects idempotency before guard' => strpos($fromIntent,"=== 'used'") < strpos($fromIntent,'->checkItems('),
 'intent order checks B1 before order and stock' => strpos($fromIntent,'->checkItems(') < strpos($fromIntent,'->lockVariantStockForUpdate('),
 'webhook approved payment persisted first' => strpos($webhook,'createOrUpdateByProviderPaymentId(') < strpos($webhook,'createOrderFromIntent('),
 'webhook reconciliation explicit' => str_contains($webhook,'payment_approved_but_product_unavailable') && str_contains($webhook,'http_response_code(202)'),
 'payment before charging guard' => strpos($payment,'->checkItems(') < strpos($payment,'->createPayment('),
 'payment approved without order is not commercial success' => str_contains($payment,'manual_reconciliation_required') && str_contains($payment,'payment_approved_but_product_unavailable'),
 'payment handlers preserve CSRF' => substr_count($payment,'verify_csrf_or_abort()') >= 4,
 'cart handlers preserve CSRF' => substr_count($cartController,'verify_csrf_or_abort()') >= 2,
];
foreach($checks as $name=>$ok)if(!$ok){fwrite(STDERR,"[categories-checkout-b1] FAIL $name\n");exit(1);}
echo '[categories-checkout-b1] OK '.count($checks)." contracts\n";
