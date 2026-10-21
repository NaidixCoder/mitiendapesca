<?php
/** @var array<string,mixed> $order */
$order = $order ?? [];

$name  = trim((string)($order['shipping_name'] ?? $order['name'] ?? ''));
$addr1 = trim((string)($order['shipping_address'] ?? ''));
$city  = trim((string)($order['shipping_city'] ?? ''));
$state = trim((string)($order['shipping_state'] ?? ''));
$phone = trim((string)($order['shipping_phone'] ?? ''));
$street = trim((string)($order['shipping_street'] ?? ''));
$number = trim((string)($order['shipping_number'] ?? ''));
$zip = trim((string)($order['shipping_zip'] ?? ''));
$canEditAddress = true;
$shipmentStatus = strtolower(trim((string)(($shipment ?? [])['status'] ?? '')));
if (in_array($shipmentStatus, ['shipped', 'delivered', 'enviado', 'despachado', 'entregado'], true)) {
  $canEditAddress = false;
}
?>
<section class="order-show-card order-show-card--sidebar">
  <header class="order-show-card__head">
    <div class="order-show-side-head">
      <div class="order-show-side-title">
        <span class="order-show-section-icon" aria-hidden="true"><?= ui_icon('pin') ?></span>
        <h2 class="order-show-card__title">Entrega</h2>
      </div>
      <?php if ($canEditAddress): ?>
        <button
          type="button"
          class="order-show-upload-change-btn order-show-address-change-btn"
          aria-label="Cambiar direccion de entrega"
          data-order-address-edit-open
          data-order-id="<?= (int)($order['id'] ?? 0) ?>"
          data-name="<?= e($name) ?>"
          data-phone="<?= e($phone) ?>"
          data-street="<?= e($street) ?>"
          data-number="<?= e($number) ?>"
          data-city="<?= e($city) ?>"
          data-state="<?= e($state) ?>"
          data-zip="<?= e($zip) ?>"
          data-floor="<?= e((string)($order['shipping_floor'] ?? '')) ?>"
          data-apartment="<?= e((string)($order['shipping_apartment'] ?? '')) ?>"
          data-notes="<?= e((string)($order['shipping_notes'] ?? '')) ?>"
        >
          <span class="order-show-upload-change-btn__icon order-show-upload-change-btn__icon--edit" aria-hidden="true"><?= ui_icon('note-edit') ?></span>
          <span class="order-show-upload-change-btn__text">Cambiar</span>
        </button>
      <?php endif; ?>
    </div>
  </header>

  <div class="order-show-card__body">

    <div class="order-show-address">
      <?php if ($name !== ''): ?>
        <div class="order-show-address__line">Nombre: <span class="order-show-emphasis"><?= e($name) ?></span></div>
      <?php endif; ?>

      <?php if ($city !== ''): ?>
        <div class="order-show-address__line">Localidad: <span class="order-show-emphasis"><?= e($city) ?></span></div>
      <?php endif; ?>

      <?php if ($state !== ''): ?>
        <div class="order-show-address__line">Provincia: <span class="order-show-emphasis"><?= e($state) ?></span></div>
      <?php endif; ?>

      <?php if ($addr1 !== ''): ?>
        <div class="order-show-address__line">Direccion: <span class="order-show-emphasis"><?= e($addr1) ?></span></div>
      <?php endif; ?>

      <?php if ($phone !== ''): ?>
        <div class="order-show-address__line">Telefono: <span class="order-show-emphasis"><?= e($phone) ?></span></div>
      <?php endif; ?>
    </div>
  </div>
</section>
