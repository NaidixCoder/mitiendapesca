<?php
  $selectedPaymentKey = trim((string)($selectedPaymentKey ?? ''));
  $cardTitle = $selectedPaymentKey === 'tarjeta_debito' ? 'Tarjeta de debito' : 'Tarjeta de credito';
  $cardIntro = $selectedPaymentKey === 'tarjeta_debito'
    ? 'Ingresa los datos de tu tarjeta de debito y presiona "Pagar" para confirmar.'
    : 'Ingresa los datos de tu tarjeta de credito y presiona "Pagar" para confirmar.';
?>

<div class="checkout-payment-section">
  <div class="checkout-card">
    <div class="checkout-card__header checkout-card__header--section">
      <div class="checkout-card__title-wrap">
        <?= ui_icon('check-line', 'checkout-card__section-icon', ['aria-hidden' => 'true']) ?>
        <div class="checkout-card__section-title"><?= e($cardTitle) ?></div>
      </div>
      <div class="checkout-card__meta">Pago seguro</div>
    </div>

    <div class="checkout-card__divider"></div>

    <div id="mp-card-intro" class="checkout-card__section-block">
      <div class="checkout-card__section-heading">Completa el pago</div>
      <div class="checkout-card__text"><?= e($cardIntro) ?></div>
    </div>

    <div class="checkout-card__actions">
      <button id="btn-mp-start" type="button" class="v2-btn v2-btn--brand ui-is-hidden">
        Preparar pago
      </button>
      <div id="mp-status" class="checkout-card__status"></div>
    </div>

    <div
      id="mp-config"
      class="ui-is-hidden"
      data-mp-payment-url="<?= base_url('/checkout/mp/payment') ?>"
      data-mp-brick-failed-url="<?= base_url('/checkout/mp/brick-failed') ?>"
      data-mp-stock-preflight-url="<?= base_url('/checkout/stock/preflight') ?>"
      data-amount="<?= htmlspecialchars((string)number_format((float)$cartTotal, 2, '.', ''), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?>"
    ></div>

    <div id="mp-card-brick" class="checkout-card__brick ui-is-hidden" data-cart-clear-url="<?= base_url('/carrito/vaciar') ?>"></div>

    <?php section('/partials/checkout/payment/mp_result'); ?>

    <div id="mp-fallback" class="checkout-card__fallback ui-is-hidden">
      <div class="checkout-card__text">No pudimos cargar el formulario de pago en esta pantalla.</div>
      <div class="checkout-card__actions checkout-card__fallback-actions">
        <button id="btn-mp-reload" type="button" class="v2-btn v2-btn--ghost">
          Recargar pagina
        </button>
        <a href="<?= e((string)base_url('/ayuda#contacto')) ?>" class="v2-btn v2-btn--ghost">
          Informar un problema
        </a>
        <button id="btn-mp-fallback" type="submit" formaction="<?= base_url('/checkout/pagar') ?>" class="v2-btn v2-btn--brand">
          Pagar en Mercado Pago
        </button>
      </div>
    </div>
  </div>
</div>
