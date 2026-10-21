<?php
/** @var array<string,mixed> $order */
$order = $order ?? [];
$id = (int)($order['id'] ?? 0);
$status = (string)($order['status'] ?? 'pending_payment');
$label = (string)($order['status_label'] ?? 'Pendiente');
$total = (string)($order['total_fmt'] ?? '');
$ok = (string)($_GET['ok'] ?? '');
$metodo = (string)($_GET['metodo'] ?? '');

$paymentMethodKey = strtolower(trim((string)($order['payment_method'] ?? '')));
$isTransfer = in_array($paymentMethodKey, ['transferencia', 'transfer', 'transfer_bank', 'bank_transfer'], true);
$isPendingPay = in_array($status, ['pending_payment', 'pending', 'payment_failed', 'payment_expired'], true);
$heroStatusTone = $isPendingPay ? 'warn' : 'ok';
$reserveUntil = trim((string)($order['transfer_reservation_until'] ?? ''));

$hint = match ($status) {
    'pending_payment', 'payment_failed', 'payment_expired' =>
        ($metodo === 'transferencia'
            ? 'Tu pedido quedó pendiente de pago por transferencia. Abajo tenés los datos para completar el pago.'
            : 'Estamos esperando la confirmación del pago. Si pagaste recién, puede demorar unos instantes en reflejarse.'),
    'paid' => 'Pago confirmado. ¡Excelente! Ahora estamos preparando tu pedido.',
    'processing', 'packed' => 'Estamos preparando tu pedido. Te avisaremos cuando lo despachemos.',
    'shipped' => 'Tu pedido ya fue despachado. Pronto vas a recibir el seguimiento si corresponde.',
    'delivered' => 'Pedido entregado. ¡Gracias por comprar en Mi Tienda Pesca!',
    'cancelled' => 'Este pedido fue cancelado.',
    'refunded', 'chargeback' => 'Este pedido fue reembolsado.',
    default => 'Estamos trabajando en tu pedido.',
};

$heroMethodLabel = trim((string)($order['payment_method_label'] ?? ''));
if ($heroMethodLabel === '') {
    $heroMethodLabel = match ($paymentMethodKey) {
        'tarjeta_debito' => 'Tarjeta debito',
        'tarjeta_credito' => 'Tarjeta credito',
        'mercadopago_card', 'card', 'tarjeta' => 'Tarjeta',
        'mercadopago_pro', 'checkout_pro' => 'Mercado Pago',
        'transferencia', 'transfer', 'transfer_bank', 'bank_transfer' => 'Transferencia bancaria',
        default => 'Pago',
    };
}
if ($isTransfer) {
    $heroMethodLabel = 'Transferencia bancaria';
}

$heroMethodIcon = $isTransfer ? 'bank-solid' : 'card';
$heroAmountLabel = $isPendingPay ? 'Total a pagar' : 'Total pagado';
$heroReserveText = $isTransfer
    ? ($isPendingPay
        ? ($reserveUntil !== ''
            ? 'Reserva de stock hasta ' . $reserveUntil
            : 'Tu stock está reservado mientras se acredita la transferencia.')
        : 'Pago acreditado')
    : ($isPendingPay
        ? 'Estamos esperando la autorización de la operación.'
        : 'Pago aprobado');
$heroReserveIcon = $isPendingPay ? 'info-circle' : 'shield-solid';
?>

<header class="order-show-hero">
  <div class="order-show-hero__main">
    <div class="order-show-hero__payment-stack">
      <div class="order-show-hero__title-row">
        <h1 class="order-show-hero__title">Pedido #<?= $id ?></h1>
        <span class="order-show-status-badge order-show-status-badge--<?= e($heroStatusTone) ?>"><?= e($label) ?></span>
      </div>

      <div class="order-show-hero__amount-row">
        <div class="order-show-hero__amount-block">
          <span class="order-show-hero__amount-label"><?= e($heroAmountLabel) ?></span>
          <span class="order-show-hero__amount-value"><?= e($total) ?></span>
        </div>

        <div class="order-show-hero__method">
          <span aria-hidden="true"><?= ui_icon($heroMethodIcon) ?></span>
          <span><?= e($heroMethodLabel) ?></span>
        </div>
      </div>

      <div class="order-show-hero__reserve-note">
        <span><?= e($heroReserveText) ?></span>
      </div>
    </div>
  </div>
</header>

<?php if ($ok !== ''): ?>
  <section class="order-show-banner order-show-banner--success">
    <div class="order-show-banner__icon" aria-hidden="true">✓</div>
    <div class="order-show-banner__body">
      <h2 class="order-show-banner__title">¡Listo!</h2>
      <p class="order-show-banner__text">Tu compra se registró correctamente. Podés seguir el estado de tu pedido desde acá.</p>
    </div>
  </section>
<?php endif; ?>

<?php if ($isTransfer && $isPendingPay): ?>
  <section class="order-show-transfer-status">
    <div class="order-show-transfer-status__media">
      <div class="order-show-transfer-status__eyebrow">Acción requerida</div>
      <h2 class="order-show-transfer-status__title">Falta confirmar tu pago</h2>
      <img src="<?= e(asset('img/account/bank-transfer.jpg')) ?>" alt="Pago por transferencia" loading="lazy">
    </div>

    <div class="order-show-transfer-status__body">
      <p class="order-show-transfer-status__text">
        Transferí <span class="order-show-emphasis"><?= e($total) ?></span> y subí el comprobante para acelerar el proceso. Si ya lo hiciste, no te preocupes, estamos revisando tu pago y te vamos a avisar apenas quede confirmado.
      </p>
      <div class="order-show-transfer-status__actions">
      </div>
    </div>
  </section>
<?php else: ?>
  <section class="order-show-banner order-show-banner--info">
    <div class="order-show-banner__icon" aria-hidden="true">i</div>
    <div class="order-show-banner__body">
      <h2 class="order-show-banner__title">¿Qué sigue ahora?</h2>
      <p class="order-show-banner__text"><?= e($hint) ?></p>
    </div>
  </section>
<?php endif; ?>
