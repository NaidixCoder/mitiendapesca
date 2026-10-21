<?php
  $itemCount = max(1, (int)($itemCount ?? 0));
  $shippingValue = (string)($shippingSummaryData['costo_formateado'] ?? money_ar($cartShipping));
  $hasDiscount = $cartDiscount > 0;
?>
<div class="checkout-summary-card">
  <div class="checkout-summary-card__header">
    <span class="checkout-summary-card__header-icon-wrap" aria-hidden="true">
      <?= ui_icon('receipt-solid', 'checkout-summary-card__header-icon') ?>
    </span>
    <div class="checkout-summary-card__header-copy">
      <div class="checkout-summary-card__title">Resumen del pedido</div>
    </div>
  </div>

  <div class="checkout-summary-card__rows">
    <div class="checkout-summary-card__row">
      <div class="checkout-summary-card__row-main">
        <span class="checkout-summary-card__row-icon-wrap" aria-hidden="true">
          <?= ui_icon('box', 'checkout-summary-card__row-icon') ?>
        </span>
        <div class="checkout-summary-card__row-copy">
          <span class="checkout-summary-card__row-title">Productos</span>
          <span class="checkout-summary-card__row-meta">(<?= $itemCount ?> articulos)</span>
        </div>
      </div>
      <span id="checkout-summary-subtotal" data-value="<?= (int)$cartSubtotal ?>" class="checkout-summary-card__value"><?= money_ar($cartSubtotal) ?></span>
    </div>

    <div class="checkout-summary-card__row">
      <div class="checkout-summary-card__row-main">
        <span class="checkout-summary-card__row-icon-wrap" aria-hidden="true">
          <?= ui_icon('truck-solid', 'checkout-summary-card__row-icon') ?>
        </span>
        <div class="checkout-summary-card__row-copy">
          <span class="checkout-summary-card__row-title">Envio</span>
        </div>
      </div>
      <span id="checkout-summary-shipping" class="checkout-summary-card__value"><?= e($shippingValue) ?></span>
    </div>

    <div class="checkout-summary-card__row checkout-summary-card__row--discount">
      <div class="checkout-summary-card__row-main">
        <span class="checkout-summary-card__row-icon-wrap checkout-summary-card__row-icon-wrap--discount" aria-hidden="true">
          <?= ui_icon('tag-solid', 'checkout-summary-card__row-icon') ?>
        </span>
        <div class="checkout-summary-card__row-copy">
          <span class="checkout-summary-card__row-title">Descuento</span>
        </div>
      </div>
      <span id="checkout-summary-discount" data-value="<?= (int)$cartDiscount ?>" class="checkout-summary-card__value checkout-summary-card__value--discount"><?= $hasDiscount ? ('-' . money_ar($cartDiscount)) : '-' ?></span>
    </div>
  </div>

  <div class="checkout-summary-card__total-box">
    <div class="checkout-summary-card__total-copy">
      <span class="checkout-summary-card__total-label">Total a pagar</span>
    </div>
    <div class="checkout-summary-card__total-meta">
      <span id="checkout-summary-total" class="checkout-summary-card__total-value"><?= money_ar($cartTotal) ?></span>
    </div>
  </div>

  <div class="checkout-summary-card__footer">
    <?= ui_icon('circle-check-solid', 'checkout-summary-card__footer-icon', ['aria-hidden' => 'true']) ?>
    <span class="checkout-summary-card__caption">Impuestos incluidos en el total</span>
  </div>
</div>
