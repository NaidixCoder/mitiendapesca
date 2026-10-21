<!-- Overlay global (Tarjeta/Transferencia) -->
<div id="mp-overlay" class="checkout-loading-screen ui-is-hidden">
  <div class="checkout-loading-screen__viewport">
    <div class="checkout-loading-screen__dialog">
      <div class="checkout-loading-screen__content">
        <div class="checkout-loading-screen__scene" aria-hidden="true">
          <?= ui_icon('cart-arrow', 'checkout-loading-screen__cart ui-icon--primary') ?>
          <?= ui_icon('box', 'checkout-loading-screen__box ui-icon--soft-primary') ?>
        </div>
        <div id="mp-overlay-title" class="checkout-loading-screen__title">Preparando todo para tu compra</div>
        <div id="mp-overlay-text" class="checkout-loading-screen__text">Estamos preparando el siguiente paso.</div>
        <div class="checkout-loading-screen__spinner-wrap">
          <div class="checkout-loading-screen__spinner" aria-hidden="true"></div>
        </div>
      </div>
    </div>
  </div>
</div>
