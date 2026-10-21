<?php
/** @var array $cart */
$items = $cart['items'] ?? [];
?>

<section class="cart-page">
  <div class="cart-page__inner">
    <?php section('partials/carrito/alerts', [
      'mergeRecovered' => !empty($mergeRecovered),
      'recoveryTitle' => $recoveryTitle ?? '',
      'recoveryCopy' => $recoveryCopy ?? '',
      'checkoutChanged' => !empty($checkoutChanged),
    ]); ?>

    <?php if (empty($items)): ?>
      <?php section('/partials/carrito/empty', [
        'related' => $related ?? [],
        'homeBrands' => $homeBrands ?? [],
        'homeCategories' => $homeCategories ?? [],
      ]); ?>
    <?php else: ?>
      <div class="cart-page__layout">
        <div class="cart-page__main">
          <?php section('partials/carrito/hero', [
            'allSelected' => !empty($allSelected),
          ]); ?>

          <form id="cart-form" method="post" action="<?= base_url('/carrito/actualizar') ?>" class="cart-page__form" data-cart-form>
            <?= csrf_field() ?>

            <div class="cart-list">
              <?php foreach ((array)($viewItems ?? []) as $item): ?>
                <?php section('partials/carrito/item', ['item' => $item]); ?>
              <?php endforeach; ?>
            </div>

            <button type="submit" class="ui-is-hidden" data-cart-noscript-submit>Actualizar</button>
          </form>
        </div>

        <div class="cart-page__sidebar">
          <div class="cart-summary-anchor" data-cart-summary-anchor>
            <?php section('partials/carrito/summary', [
              'selectedCart' => $selectedCart ?? $cart,
              'selectedSummary' => $selectedSummary ?? [],
            ]); ?>
          </div>

          <?php section('partials/carrito/trust'); ?>
        </div>
      </div>

      <section class="cart-page__recommend">
        <?php section('partials/components/related-products', ['related' => $related ?? []]); ?>
      </section>
    <?php endif; ?>
  </div>
</section>
