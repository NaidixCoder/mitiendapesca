<?php
declare(strict_types=1);
/** Tests puros, sin base de datos, red ni configuracion productiva. */
require_once dirname(__DIR__, 2) . '/vendor/autoload.php';

use App\Services\Cart\CartSignatureService;
use App\Services\Checkout\CheckoutFlowService;

$checks = 0;
$assert = static function (bool $valid, string $message) use (&$checks): void {
    ++$checks;
    if (!$valid) { fwrite(STDERR, "[test] ERROR: $message\n"); exit(1); }
};
$items = [['product_id'=>2, 'variant_id'=>3, 'qty'=>1], ['product_id'=>1, 'variant_id'=>0, 'qty'=>2]];
$signature = CartSignatureService::build($items);
$assert(strlen($signature) === 40 && $signature !== 'err', 'firma de carrito valida');
$assert($signature === CartSignatureService::build(array_reverse($items)), 'firma independiente del orden');
$changed = $items;
$changed[0]['qty'] = 2;
$assert($signature !== CartSignatureService::build($changed), 'cambio de cantidad invalida firma');
$changed = $items;
$changed[0]['variant_id'] = 4;
$assert($signature !== CartSignatureService::build($changed), 'cambio de variante invalida firma');

// Estos metodos no usan la dependencia PDO; no se toca una base de datos.
$flow = (new ReflectionClass(CheckoutFlowService::class))->newInstanceWithoutConstructor();
foreach ([0, -1, 5, 999] as $invalid) {
    $assert($flow->resolveCurrentStep($invalid) === 1, 'paso fuera de rango vuelve al inicio');
}
foreach ([1, 2, 3, 4] as $valid) {
    $assert($flow->resolveCurrentStep($valid) === $valid, 'paso valido conservado');
}
$assert(!$flow->validateShippingSnapshot(null), 'no acepta envio ausente');
$assert(!$flow->validateShippingSnapshot(['mode'=>'free', 'cost'=>5]), 'gratis requiere costo cero');
$assert($flow->validateShippingSnapshot(['mode'=>'free', 'cost'=>0]), 'envio gratis valido');
$assert(!$flow->validateShippingSnapshot(['mode'=>'d','cost'=>100]), 'envio a domicilio exige codigo');
$assert(!$flow->validateShippingSnapshot(['mode'=>'s','cost'=>100,'service_code'=>'CP']), 'sucursal exige agencia');
$assert($flow->validateShippingSnapshot(['mode'=>'s','cost'=>100,'service_code'=>'CP','agency_id'=>'123']), 'sucursal valida');
$assert(!$flow->validateShippingSnapshot(['mode'=>'d','cost'=>-1,'service_code'=>'CP']), 'costo negativo rechazado');
$cart = ['subtotal'=>10000, 'discount'=>2000, 'total'=>8000];
$flow->applyShippingToCart($cart, ['mode'=>'d','cost'=>1500,'service_code'=>'CP']);
$assert($cart['shipping'] === 1500 && $cart['total'] === 9500, 'total incorpora envio y descuento');
$before = $cart;
$flow->applyShippingToCart($cart, ['mode'=>'d','cost'=>-100,'service_code'=>'CP']);
$assert($cart === $before, 'snapshot invalido no altera carrito');
// Contrato de variables entre el view model y el partial de email marketing.
$runnerView = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Views/partials/admin/settings/email_marketing/web_runner_card.php');
$runnerModel = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Services/Admin/Settings/ViewModels/SettingsEmailMarketingViewModel.php');
$assert(str_contains($runnerView, '$run_url') && str_contains($runnerModel, "'run_url'"), 'link runner usa nombre del view model');
$assert(str_contains($runnerView, '$web_runner_enabled_checked') && str_contains($runnerModel, "'web_runner_enabled_checked'"), 'checkbox runner usa nombre del view model');
$capsView=(string)file_get_contents(dirname(__DIR__,2).'/app/Views/partials/admin/settings/email_marketing/caps_card.php');
$assert(str_contains($capsView,'$open_tracking_checked') && str_contains($runnerModel,"'open_tracking_checked'"), 'checkbox de aperturas coincide con view model');
echo "[test] OK: $checks verificaciones\n";
