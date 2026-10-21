<?php
$emptyCartImage = asset('img/empty/empty-cart.png');
?>

<section class="cart-empty-v2">
  <article class="cart-empty-v2__hero">
    <div class="cart-empty-v2__media">
      <img
        src="<?= e($emptyCartImage) ?>"
        alt="Equipo de pesca con caña, caja y señuelo"
        class="cart-empty-v2__image"
        loading="lazy"
      >
    </div>

    <div class="cart-empty-v2__content">
      <div class="cart-empty-v2__titleblock">
        <span class="cart-empty-v2__title-icon" aria-hidden="true"><?= ui_icon('cart-empty') ?></span>
        <h1 class="cart-empty-v2__title">Tu carrito está vacío</h1>
      </div>

      <div class="cart-empty-v2__copyblock">
        <p class="cart-empty-v2__lead">Parece que todavía no agregaste nada.</p>
        <p class="cart-empty-v2__copy">Explorá nuestros productos y equipate para tu próxima salida.</p>
      </div>

      <div class="cart-empty-v2__actions">
        <a href="<?= url('/productos') ?>" class="cart-empty-v2__btn cart-empty-v2__btn--primary">
          <span class="cart-empty-v2__btn-icon" aria-hidden="true"><?= ui_icon('search') ?></span>
          <span>Explorar productos</span>
        </a>
      </div>
    </div>
  </article>

  <?php section('partials/carrito/recent-searches'); ?>
  <?php section('partials/components/related-products', ['related' => $related ?? []]); ?>
  <?php section('partials/home/trust-bar', ['homeBrands' => $homeBrands ?? []]); ?>
  <?php section('partials/home/categorias-destacadas', ['homeCategories' => $homeCategories ?? []]); ?>
</section>
