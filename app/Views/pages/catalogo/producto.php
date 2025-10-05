<?php
/** @var array $product */
/** @var array $related */
/** @var array $trail */
/** @var string $pageTitle */
/** @var string $pageDesc */
/** @var string $extraHead */
?>

<?php section('breadcrumbs', ['trail'=>$trail]); ?>

<section class="container mx-auto px-3 sm:px-4 lg:px-6 py-6">
  <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <?php section('producto/gallery', ['product'=>$product]); ?>
    <div class="space-y-6">
      <?php section('producto/title-price', ['product'=>$product]); ?>
      <?php section('producto/buy-box', ['product'=>$product]); ?>
      <?php section('producto/specs', ['product'=>$product]); ?>
      <?php section('producto/shipping-info', ['product'=>$product]); ?>
    </div>
  </div>

  <?php section('producto/related', ['related'=>$related]); ?>
</section>
