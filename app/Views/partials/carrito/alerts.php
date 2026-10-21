<?php if (!empty($mergeRecovered)): ?>
  <div class="cart-merge-modal" data-cart-auto-dismiss="3500" aria-live="polite">
    <div class="cart-merge-modal__dialog" role="status" aria-label="Recuperando productos">
      <div class="cart-merge-modal__scene" aria-hidden="true">
        <?= ui_icon('cart-arrow', 'cart-merge-modal__cart ui-icon--primary') ?>
        <?= ui_icon('box', 'cart-merge-modal__box ui-icon--soft-primary') ?>
      </div>
      <p class="cart-merge-modal__title"><?= e((string)($recoveryTitle ?? 'Recuperamos productos de tu cuenta')) ?></p>
      <p class="cart-merge-modal__copy"><?= e((string)($recoveryCopy ?? 'Los estamos agregando a tu carrito.')) ?></p>
      <div class="cart-merge-modal__spinner" aria-hidden="true"></div>
    </div>
  </div>
<?php endif; ?>

<?php if (!empty($checkoutChanged)): ?>
  <div class="cart-change-overlay" data-cart-auto-dismiss="5000" aria-live="polite">
    <div class="cart-change-overlay__dialog" role="status" aria-label="Actualizando carrito">
      <p class="cart-change-overlay__title">Uno de tus productos se agotó hace instantes</p>
      <img
        src="<?= e(asset('img/undraw_delivery-truck_mjui.svg')) ?>"
        alt="Actualizacion de stock"
        class="cart-change-overlay__image"
        loading="eager"
        decoding="async"
      >
      <div class="cart-change-overlay__progress" aria-hidden="true">
        <span class="cart-change-overlay__bar"></span>
      </div>
      <p class="cart-change-overlay__copy">Revisá el carrito y seguí con tu compra</p>
      <button type="button" class="cart-change-overlay__cta" data-cart-overlay-close>Ver carrito</button>
    </div>
  </div>
<?php endif; ?>
