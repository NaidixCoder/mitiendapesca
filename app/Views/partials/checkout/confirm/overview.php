<?php
  $shippingLines = is_array($shippingLines ?? null) ? $shippingLines : [];
  $shippingLinks = is_array($shippingLinks ?? null) ? $shippingLinks : [];
  $paymentSummaryData = is_array($paymentSummaryData ?? null) ? $paymentSummaryData : [];
  $shippingSummaryData = is_array($shippingSummaryData ?? null) ? $shippingSummaryData : [];

  $addressLine = trim((string)($shippingLines[0] ?? ''));
  $addressLine2 = trim((string)($shippingLines[1] ?? ''));
  $addressText = trim($addressLine . ($addressLine2 !== '' ? ', ' . $addressLine2 : ''));

  $shippingService = trim((string)($shippingSummaryData['servicio'] ?? ''));
  $shippingCost = trim((string)($shippingSummaryData['costo_formateado'] ?? 'Pendiente'));
  $shippingText = trim($shippingService . ($shippingCost !== '' ? ' - ' . $shippingCost : ''));
?>

<div class="checkout-confirm-overview">
  <details class="checkout-confirm-overview__item">
    <summary class="checkout-confirm-overview__item-summary">
      <div class="checkout-confirm-overview__item-title-wrap">
        <?= ui_icon('check-line', 'checkout-confirm-overview__item-icon', ['aria-hidden' => 'true']) ?>
        <div class="checkout-confirm-overview__item-title">Direccion</div>
      </div>
      <?= ui_icon('chevron-down-solid', 'checkout-confirm-overview__item-chevron', ['aria-hidden' => 'true']) ?>
    </summary>
    <div class="checkout-confirm-overview__item-body">
      <?php if ($addressLine !== ''): ?>
        <div class="checkout-confirm-overview__item-copy"><?= e($addressLine) ?></div>
      <?php endif; ?>
      <div class="checkout-confirm-overview__item-copy"><?= e($addressLine2 !== '' ? $addressLine2 : 'Sin direccion confirmada') ?></div>
      <a href="<?= e((string)($shippingLinks['address'] ?? base_url('/checkout?step=1'))) ?>" class="checkout-confirm-overview__item-link">Modificar</a>
    </div>
  </details>

  <details class="checkout-confirm-overview__item">
    <summary class="checkout-confirm-overview__item-summary">
      <div class="checkout-confirm-overview__item-title-wrap">
        <?= ui_icon('check-line', 'checkout-confirm-overview__item-icon', ['aria-hidden' => 'true']) ?>
        <div class="checkout-confirm-overview__item-title">Envio</div>
      </div>
      <?= ui_icon('chevron-down-solid', 'checkout-confirm-overview__item-chevron', ['aria-hidden' => 'true']) ?>
    </summary>
    <div class="checkout-confirm-overview__item-body">
      <div class="checkout-confirm-overview__item-copy"><?= e($shippingText !== '' ? $shippingText : 'Sin envio confirmado') ?></div>
      <a href="<?= e((string)($shippingLinks['shipping'] ?? base_url('/checkout?step=2'))) ?>" class="checkout-confirm-overview__item-link">Modificar</a>
    </div>
  </details>

  <details class="checkout-confirm-overview__item">
    <summary class="checkout-confirm-overview__item-summary">
      <div class="checkout-confirm-overview__item-title-wrap">
        <?= ui_icon('check-line', 'checkout-confirm-overview__item-icon', ['aria-hidden' => 'true']) ?>
        <div class="checkout-confirm-overview__item-title">Pago</div>
      </div>
      <?= ui_icon('chevron-down-solid', 'checkout-confirm-overview__item-chevron', ['aria-hidden' => 'true']) ?>
    </summary>
    <div class="checkout-confirm-overview__item-body">
      <div class="checkout-confirm-overview__item-copy"><?= e((string)($paymentSummaryData['method_label'] ?? 'Pendiente')) ?></div>
      <a href="<?= e((string)($shippingLinks['payment'] ?? base_url('/checkout?step=3'))) ?>" class="checkout-confirm-overview__item-link">Modificar</a>
    </div>
  </details>
</div>
