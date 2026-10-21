<?php
/** @var array<string,mixed> $order */
$order = $order ?? [];
$status = (string)($order['status'] ?? 'pending_payment');

$steps = [
  ['key' => 'pending', 'label' => 'Pago pendiente', 'desc' => 'Esperamos tu comprobante para confirmar el pago.'],
  ['key' => 'processing', 'label' => 'Preparando pedido', 'desc' => 'Tu pedido está en preparación.'],
  ['key' => 'shipped', 'label' => 'Enviado', 'desc' => 'Tu pedido está en camino.'],
  ['key' => 'delivered', 'label' => 'Entregado', 'desc' => 'Tu pedido fue entregado.'],
];

$active = 0;
if ($status === 'paid' || in_array($status, ['processing', 'packed'], true)) {
  $active = 1;
} elseif ($status === 'shipped') {
  $active = 2;
} elseif ($status === 'delivered') {
  $active = 3;
}
if (in_array($status, ['cancelled', 'refunded', 'chargeback'], true)) {
  $active = 0;
}

$cancelNote = in_array($status, ['cancelled', 'refunded', 'chargeback'], true)
  ? (string)($order['status_label'] ?? '')
  : '';
?>

<section class="order-show-card order-show-card--sidebar">
  <header class="order-show-card__head order-show-track-head">
    <div class="order-show-track-head__main">
      <span class="order-show-track-head__icon" aria-hidden="true"><?= ui_icon('signal-bars') ?></span>
      <div class="order-show-track-head__copy">
        <h2 class="order-show-card__title">Seguimiento</h2>
        <p class="order-show-card__intro">Estado de tu compra</p>
      </div>
    </div>
    <span class="order-show-track-head__status">
      <span aria-hidden="true"><?= ui_icon('clock') ?></span>
      <?= $active >= 3 ? 'Completado' : 'En progreso' ?>
    </span>
  </header>

  <div class="order-show-card__body">
    <div class="order-show-timeline">
      <?php foreach ($steps as $i => $s):
        $done = $i < $active;
        $isCurrent = $i === $active;
        $tone = $done ? 'done' : ($isCurrent ? 'current' : 'idle');
      ?>
        <div class="order-show-timeline__step order-show-timeline__step--<?= e($tone) ?>">
          <div class="order-show-timeline__rail" aria-hidden="true">
            <span class="order-show-timeline__step-icon">
              <?php if ($done): ?><?= ui_icon('check') ?><?php elseif ($isCurrent): ?><?= ui_icon(($s['key'] === 'pending') ? 'credit-card' : 'clock') ?><?php else: ?><?= ui_icon('circle-active-solid') ?><?php endif; ?>
            </span>
          </div>
          <div class="order-show-timeline__step-main">
            <div class="order-show-timeline__step-copy">
              <span class="order-show-timeline__step-label"><?= e($s['label']) ?></span>
              <span class="order-show-timeline__step-desc"><?= e($s['desc']) ?></span>
            </div>
            <span class="order-show-timeline__step-state">
              <?= $done ? 'Completado' : ($isCurrent ? 'Actual' : 'Próximo') ?>
            </span>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if ($cancelNote !== ''): ?>
      <div class="order-show-alert order-show-alert--warn">
        <div class="order-show-alert__text">Estado especial: <span class="order-show-emphasis"><?= e($cancelNote) ?></span></div>
      </div>
    <?php endif; ?>
  </div>
</section>

