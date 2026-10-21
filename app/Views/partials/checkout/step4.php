<!-- PASO 4: Confirmacion (pago + resumen) -->
<section data-checkout-step="4" class="checkout-step checkout-step--confirm <?= ((int)($currentStep ?? 1) === 4) ?'' : 'hidden' ?>">
  <?php
    $effectiveCartData = is_array($effectiveCheckoutCart ?? null) ? $effectiveCheckoutCart : $cart;
    $effectiveItems = is_array($effectiveCartData['items'] ?? null) ? $effectiveCartData['items'] : ($items ?? []);
    $cartSubtotal = (int)(($summarySubtotal ?? null) ?? (($effectiveCartData['subtotal'] ?? null) ?? ($subtotal ?? 0)));
    $cartShipping = (int)(($summaryShipping ?? null) ?? ($effectiveCartData['shipping'] ?? ($cart['shipping'] ?? 0)));
    $cartDiscount = (int)(($summaryDiscount ?? null) ?? ($effectiveCartData['discount'] ?? ($cart['discount'] ?? 0)));
    $cartTotal = (int)(($summaryTotal ?? null) ?? ($effectiveCartData['total'] ?? max(0, $cartSubtotal + $cartShipping - $cartDiscount)));
    $tt = is_array($taxTotals ?? null) ? $taxTotals : ['total_net' => $cartTotal, 'total_vat' => 0];
    $shippingSummaryData = is_array($shippingSummary ?? null) ? $shippingSummary : [];
    $paymentSummaryData = is_array($paymentSummary ?? null) ? $paymentSummary : [];
    $shippingLines = is_array($shippingSummaryData['destino_lineas'] ?? null) ? $shippingSummaryData['destino_lineas'] : [];
    $shippingLinks = is_array($shippingSummaryData['editable_links'] ?? null) ? $shippingSummaryData['editable_links'] : [];
    $selectedPaymentKey = trim((string)($paymentSummaryData['method_key'] ?? ($selectedPaymentMethod ?? '')));
    $emailVerifiedState = (int)($emailVerified ?? ($_SESSION['email_verified'] ?? 0));
    $itemCount = 0;
    foreach ($effectiveItems as $item) {
      $itemCount += max(1, (int)($item['cantidad'] ?? 1));
    }
  ?>

  <div class="checkout-confirm__layout">
    <div class="checkout-confirm__main">
      <div class="checkout-step__panel checkout-confirm">

        <div class="checkout-step__heading">
          <div class="checkout-step__index">4</div>
          <div class="checkout-step__heading-copy">
            <h2 class="checkout-step__title">Confirma tu compra</h2>
            <p class="checkout-step__subtitle">Compra segura</p>
          </div>
        </div>

        <div id="checkout-primary-hint" class="checkout-step__hint ui-is-hidden"></div>

        <div class="checkout-confirm__content">
          <?php if ($transferOk && $transferOrderId > 0): ?>
            <div class="checkout-step__notice checkout-step__notice--success">
              <div class="checkout-step__notice-title">Transferencia bancaria - Pendiente de pago</div>
              <div class="checkout-step__notice-text">Tu pedido <b>#<?= (int)$transferOrderId ?></b> quedo reservado por <?= (int)($transferReservationHours ?? 24) ?> horas. Cuando recibamos el pago pasara a preparacion.</div>
            </div>
          <?php endif; ?>

          <div class="checkout-confirm-shell">
            <?php section('partials/checkout/confirm/overview', compact('shippingSummaryData', 'paymentSummaryData', 'shippingLinks', 'shippingLines')); ?>

            <div class="checkout-confirm-shell__payment">
              <?php if ($selectedPaymentKey === 'transferencia'): ?>
                <?php section('partials/checkout/payment/bank_transfer', compact('bank', 'cartTotal', 'emailVerifiedState', 'transferReservationHours')); ?>
              <?php else: ?>
                <?php section('partials/checkout/payment/card', compact('cartTotal', 'selectedPaymentKey')); ?>
              <?php endif; ?>
            </div>
          </div>
        </div>
      </div>
    </div>

    <aside class="checkout-confirm__sidebar">
      <?php section('partials/checkout/confirm/order_summary', compact('cartSubtotal', 'cartShipping', 'cartDiscount', 'cartTotal', 'shippingSummaryData', 'itemCount')); ?>
      <?php if (!empty($effectiveItems)): ?>
        <?php section('partials/checkout/confirm/cart_mini', compact('effectiveItems')); ?>
      <?php endif; ?>
    </aside>
  </div>

</section>
