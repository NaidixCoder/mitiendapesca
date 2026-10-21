<?php
/** @var array<string,mixed> $order */
$order = $order ?? [];
?>
<section class="order-show-card order-show-card--sidebar">
  <header class="order-show-card__head">
    <h2 class="order-show-card__title">Resumen</h2>
  </header>

  <div class="order-show-card__body">
    <div class="order-show-summary-total">
      <span class="order-show-summary-total__label">Total del pedido</span>
      <span class="order-show-summary-total__value"><?= e((string)($order['total_fmt'] ?? '—')) ?></span>
    </div>

    <div class="order-show-summary-list">
      <div class="order-show-summary-list__row">
        <span>Subtotal</span>
        <span class="order-show-emphasis"><?= e((string)($order['subtotal_fmt'] ?? '—')) ?></span>
      </div>
      <div class="order-show-summary-list__row">
        <span>Envío</span>
        <span class="order-show-emphasis"><?= e((string)($order['shipping_fmt'] ?? '—')) ?></span>
      </div>
      <?php if (!empty($order['discount_fmt'])): ?>
        <div class="order-show-summary-list__row">
          <span>Descuento</span>
          <span class="order-show-emphasis"><?= e((string)($order['discount_fmt'])) ?></span>
        </div>
      <?php endif; ?>
    </div>

    <div class="order-show-sidebar-actions">
      <a class="v2-btn v2-btn--primary" href="<?= e((string)($order['continue_href'] ?? '/productos')) ?>">Seguir comprando</a>
      <a class="v2-btn v2-btn--ghost" href="<?= e((string)($order['orders_href'] ?? '/cuenta/pedidos')) ?>">Ver pedidos</a>
    </div>
  </div>
</section>



