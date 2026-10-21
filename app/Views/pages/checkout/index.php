<?php
/** @var array $cart */
/** @var array $addresses */
/** @var int|null $defaultId */
/** @var array $bank */

$items = $cart['items'] ?? [];
$subtotal = (int)($cart['subtotal'] ?? 0);
function money_ar(int $v): string { return '$ '.number_format((float)$v, 0, ',', '.'); }

$transferOk = isset($_GET['transferencia']) && (string)($_GET['transferencia'] ?? '') === '1';
$transferOrderId = (int)($_GET['pedido'] ?? 0);
?>

<section class="checkout-page" data-checkout-top data-checkout-cart-sig="<?= e((string)($cartSig ?? '')) ?>" data-checkout-stepper-max="<?= (int)($stepperMax ?? 2) ?>" data-payment-select-url="<?= e((string)base_url('/checkout/pago/seleccionar')) ?>" data-address-select-url="<?= e((string)base_url('/checkout/direccion/seleccionar')) ?>" data-checkout-selected-method="<?= e((string)($selectedPaymentMethod ?? '')) ?>" data-checkout-current-step="<?= (int)($currentStep ?? 1) ?>">
  <div class="checkout-page__shell">
  <?php section('/partials/flash_inline'); ?>
  <?php section('/partials/checkout/stepper', [
    'currentStep' => (int)($currentStep ?? 1),
    'canStep2' => (bool)($canStep2 ?? false),
    'canStep3' => (bool)($canStep3 ?? false),
    'canStep4' => (bool)($canStep4 ?? false),
  ]); ?>

  <?php if (empty($addresses)): ?>
    <?php section('/partials/checkout/address-inline', [
      'provinces' => $provinces ?? [],
    ]); ?>
  <?php else: ?>

  <form id="checkout-payment" method="post" action="<?= base_url('/checkout/pagar') ?>" class="checkout-page__form">
    <?= csrf_field() ?>
    <input type="hidden" name="selectedPaymentMethod" id="checkout-selected-payment-method" value="<?= e((string)($selectedPaymentMethod ?? '')) ?>">

    <?php section('/partials/checkout/step1', [
      'currentStep' => (int)($currentStep ?? 1),
      'addresses' => $addresses,
      'defaultId' => $defaultId,
      'selectedAddressId' => (int)($selectedAddressId ?? 0),
      'fiscal'    => $fiscal ?? null,
      'sellerTaxRegime' => $sellerTaxRegime ?? '',
      'freeMinAmount' => $freeMinAmount ?? 0,
    ]); ?>

  <?php section('/partials/checkout/step2', [
    'currentStep' => (int)($currentStep ?? 1),
    'freeMinAmount' => (int)($freeMinAmount ?? 0),
    'hasShipSnapshot' => !empty($hasShipSnapshot ?? false),
    'shippingMethod' => (string)($shippingMethod ?? ''),
    'shippingAgencyId' => (string)($shippingAgencyId ?? ''),
  ]); ?>

    <?php section('/partials/checkout/step3', [
      'currentStep' => (int)($currentStep ?? 1),
      'items'=>$items,
      'subtotal'=>$subtotal,
      'cart'=>$cart,
      'selectedPaymentMethod' => $selectedPaymentMethod ?? '',
      'paymentOptionCards' => $paymentOptionCards ?? [],
      'taxTotals'=>$taxTotals ?? null,
      'bank'=>$bank,
      'transferOk'=>$transferOk,
      'transferOrderId'=>$transferOrderId,
    ]); ?>

    <?php section('/partials/checkout/step4', [
      'currentStep' => (int)($currentStep ?? 1),
      'items'=>$items,
      'subtotal'=>$subtotal,
      'cart'=>$cart,
      'effectiveCheckoutCart' => $effectiveCheckoutCart ?? $cart,
      'summarySubtotal' => $summarySubtotal ?? null,
      'summaryShipping' => $summaryShipping ?? null,
      'summaryDiscount' => $summaryDiscount ?? null,
      'summaryTotal' => $summaryTotal ?? null,
      'shippingSummary' => $shippingSummary ?? [],
      'paymentSummary' => $paymentSummary ?? [],
      'taxTotals'=>$taxTotals ?? null,
      'bank'=>$bank,
      'emailVerified' => (int)($emailVerified ?? ($_SESSION['email_verified'] ?? 0)),
      'transferReservationHours' => (int)($transferReservationHours ?? 24),
      'transferOk'=>$transferOk,
      'transferOrderId'=>$transferOrderId,
    ]); ?>

    <?php section('/partials/checkout/overlay'); ?>

  </form>

  <?php endif; ?>
  </div>
</section>

<?php section('/partials/checkout/address-modal', [
  'provinces' => $provinces ?? [],
]); ?>
