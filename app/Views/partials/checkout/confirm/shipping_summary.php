<div class="checkout-step__panel checkout-summary-card">
  <div class="checkout-summary-card__header">
    <h3 class="checkout-summary-card__title">Detalles de envio</h3>
    <a href="<?= e((string)($shippingLinks['shipping'] ?? base_url('/checkout?step=2'))) ?>" class="checkout-summary-card__link">Cambiar envio</a>
  </div>

  <div class="checkout-summary-card__rows">
    <div class="checkout-summary-card__row">
      <span class="checkout-summary-card__label">Tipo de entrega</span>
      <span class="checkout-summary-card__value"><?= e((string)($shippingSummaryData['label_tipo'] ?? 'Sin envio confirmado')) ?></span>
    </div>
    <div class="checkout-summary-card__row">
      <span class="checkout-summary-card__label">Costo de envio</span>
      <span class="checkout-summary-card__value"><?= e((string)($shippingSummaryData['costo_formateado'] ?? 'Pendiente')) ?></span>
    </div>
    <?php if (!empty($shippingSummaryData['servicio'])): ?>
      <div class="checkout-summary-card__row">
        <span class="checkout-summary-card__label">Servicio</span>
        <span class="checkout-summary-card__value"><?= e((string)$shippingSummaryData['servicio']) ?></span>
      </div>
    <?php endif; ?>
  </div>

  <?php if (!empty($shippingLines)): ?>
    <div class="checkout-summary-card__box">
      <div class="checkout-summary-card__box-label">Destino</div>
      <div class="checkout-summary-card__box-copy">
        <?php foreach ($shippingLines as $line): ?>
          <?php if (!is_string($line) || trim($line) === '') continue; ?>
          <div><?= e($line) ?></div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

  <?php if (!empty($shippingSummaryData['updated_at'])): ?>
    <div class="checkout-summary-card__updated">Ultima actualizacion: <?= e((string)$shippingSummaryData['updated_at']) ?></div>
  <?php endif; ?>

  <div class="checkout-summary-card__links">
    <a href="<?= e((string)($shippingLinks['address'] ?? base_url('/checkout?step=1'))) ?>" class="checkout-summary-card__link">Cambiar direccion</a>
    <a href="<?= e((string)($shippingLinks['shipping'] ?? base_url('/checkout?step=2'))) ?>" class="checkout-summary-card__link">Cambiar envio</a>
    <a href="<?= e((string)($shippingLinks['payment'] ?? base_url('/checkout?step=3'))) ?>" class="checkout-summary-card__link">Cambiar pago</a>
  </div>
</div>
