<div class="checkout-step__panel checkout-summary-card">
  <div class="checkout-summary-card__header">
    <h3 class="checkout-summary-card__title">Pago</h3>
    <a href="<?= e((string)($shippingLinks['payment'] ?? base_url('/checkout?step=3'))) ?>" class="checkout-summary-card__link">Cambiar</a>
  </div>
  <div class="checkout-summary-card__copy"><?= e((string)($paymentSummaryData['method_label'] ?? 'Pendiente')) ?></div>
</div>
