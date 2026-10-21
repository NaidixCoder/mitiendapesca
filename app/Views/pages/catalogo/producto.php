<?php
/** @var array $product */
/** @var array $related */
/** @var array $trail */
/** @var string $pageTitle */
/** @var string $pageDesc */
/** @var string $extraHead */
/** @var bool $favoriteActive */
?>

<?php section('partials/breadcrumbs', ['trail' => $trail]); ?>

<section class="product-page">
  <div class="product-page__inner">
    <?php section('partials/producto/title-price', ['product' => $product]); ?>

    <div class="product-page__body">
      <?php section('partials/producto/gallery', ['product' => $product, 'favoriteActive' => $favoriteActive]); ?>
      <?php section('partials/producto/buy-box', ['product' => $product]); ?>
    </div>

    <?php section('partials/producto/mobile-accordions', ['product' => $product]); ?>
    <?php section('partials/producto/long-desc', ['product' => $product]); ?>
    <?php section('partials/components/related-products', ['related' => $related]); ?>
    <?php section('partials/producto/specs', ['product' => $product]); ?>
    
  </div>
</section>
