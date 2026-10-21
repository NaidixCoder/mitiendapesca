<header class="cart-page__hero">
  <div class="cart-page__hero-inner">
    <label class="cart-page__select-all">
      <input type="checkbox" aria-label="Todos los productos" data-cart-select-all <?= !empty($allSelected) ? 'checked' : '' ?>>
      <span>Todos los productos</span>
    </label>

    <div class="cart-page__title-row">
      <form method="post" action="<?= base_url('/carrito/vaciar') ?>" class="cart-page__clear-form">
        <?= csrf_field() ?>
        <button type="submit" class="cart-page__clear">Vaciar</button>
      </form>
    </div>
  </div>
</header>
