<?php
$selectedCart = is_array($selectedCart ?? null) ? $selectedCart : [];
$selectedSummary = is_array($selectedSummary ?? null) ? $selectedSummary : [];
$selectedQty = (int)($selectedCart['total_qty'] ?? 0);
$financedSubtotal = (int)($selectedSummary['financed_subtotal'] ?? 0);
$transferSubtotal = (int)($selectedSummary['transfer_subtotal'] ?? 0);
$transferSavings = (int)($selectedSummary['transfer_savings'] ?? 0);
$installmentsCopy = (string)($selectedSummary['installments_copy'] ?? '');
$showFreeShipping = !empty($selectedSummary['show_free_shipping']);
$freeShippingLabel = (string)($selectedSummary['free_shipping_label'] ?? 'Gratis');
?>
<aside class="cart-summary">
  <div class="cart-summary__rows<?= $selectedQty <= 0 ?' cart-summary__rows--hidden' : '' ?>" data-cart-summary-rows>
    <div class="cart-summary__row cart-summary__row--products">
      <span>Productos (<span data-cart-selected-qty><?= $selectedQty ?></span>)</span>
    </div>
    <div class="cart-summary__row<?= !$showFreeShipping ?' cart-summary__row--hidden' : '' ?>" data-cart-free-shipping-row>
      <span>Envio</span>
      <span class="cart-summary__value cart-summary__value-shipping" data-cart-free-shipping-label><?= e($freeShippingLabel) ?></span>
    </div>
    <div class="cart-summary__row cart-summary__row--total">
      <span>Total</span>
      <div class="cart-summary__total-main">
        <p class="cart-summary__microcopy" data-cart-installments-copy><?= e($installmentsCopy) ?></p>
        <span class="cart-summary__value" data-cart-financed-subtotal><?= money($financedSubtotal) ?></span>
      </div>
    </div>
    <div class="cart-summary__transfer-block">
      <p class="cart-summary__transfer-copy">
        <?= ui_icon('check', 'cart-summary__transfer-icon ui-icon--primary') ?>
        Ahorrás <span class="cart-summary__ammount-saving" data-cart-transfer-savings><?= money($transferSavings) ?></span> con transferencia
      </p>
      <p class="cart-summary__transfer-value" data-cart-transfer-subtotal><?= money($transferSubtotal) ?></p>
    </div>
  </div>

  <p class="cart-summary__helper<?= $selectedQty > 0 ?' cart-summary__helper--hidden' : '' ?>" data-cart-selection-helper>
    Marcá al menos un producto para continuar la compra.
  </p>

  <div class="cart-summary__actions">
    <a
      href="<?= url('/checkout') ?>"
      class="cart-summary__btn cart-summary__btn--primary<?= $selectedQty <= 0 ?' cart-summary__btn--disabled' : '' ?>"
      data-cart-checkout-btn
      aria-disabled="<?= $selectedQty <= 0 ? 'true' : 'false' ?>"
    >Continuar compra</a>
    <a href="<?= url('/productos') ?>" class="cart-summary__btn cart-summary__btn--secondary">Seguir comprando</a>
  </div>
</aside>
