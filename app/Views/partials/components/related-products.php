<?php
/** @var array $related */
$related = $related ?? [];
if (empty($related)) {
    return;
}

$mobileSlides = array_chunk($related, 2);
$desktopSlides = array_chunk($related, 4);

if (count($related) > 4 && !empty($desktopSlides)) {
    $lastIndex = count($desktopSlides) - 1;
    $lastSlide = $desktopSlides[$lastIndex];
    $missing = 4 - count($lastSlide);

    if ($missing > 0) {
        $fallbackItems = array_slice($related, 0, $missing);
        $desktopSlides[$lastIndex] = array_merge($lastSlide, $fallbackItems);
    }
}
?>

<section class="related-products">
  <h2 class="related-products__title">Elegidos para vos</h2>

  <div class="related-products__carousel related-products__carousel--mobile" data-home-carousel data-home-carousel-mobile-only="1" data-home-carousel-interval="3200">
    <div class="related-products__track" data-home-carousel-track>
      <?php foreach ($mobileSlides as $idx => $slideItems): ?>
        <div class="related-products__slide <?= $idx === 0 ?'is-active' : '' ?>" data-home-carousel-slide>
          <div class="related-products__slide-grid">
            <?php foreach ($slideItems as $p): ?>
              <?php section('partials/catalogo/product-card', ['product' => $p]); ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (count($mobileSlides) > 1): ?>
      <div class="related-products__dots" role="tablist" aria-label="Selector de productos relacionados">
        <?php foreach ($mobileSlides as $idx => $_): ?>
          <button
            class="related-products__dot <?= $idx === 0 ?'is-active' : '' ?>"
            type="button"
            data-home-carousel-dot="<?= (int)$idx ?>"
            aria-label="Ver producto relacionado <?= (int)$idx + 1 ?>"
            aria-current="<?= $idx === 0 ? 'true' : 'false' ?>"
          ></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="related-products__carousel related-products__carousel--desktop" data-home-carousel data-home-carousel-interval="3600">
    <div class="related-products__track" data-home-carousel-track>
      <?php foreach ($desktopSlides as $idx => $slideItems): ?>
        <div class="related-products__slide <?= $idx === 0 ?'is-active' : '' ?>" data-home-carousel-slide>
          <div class="related-products__slide-grid related-products__slide-grid--desktop">
            <?php foreach ($slideItems as $p): ?>
              <?php section('partials/catalogo/product-card', ['product' => $p]); ?>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>

    <?php if (count($desktopSlides) > 1): ?>
      <div class="related-products__dots" role="tablist" aria-label="Selector de productos relacionados">
        <?php foreach ($desktopSlides as $idx => $_): ?>
          <button
            class="related-products__dot <?= $idx === 0 ?'is-active' : '' ?>"
            type="button"
            data-home-carousel-dot="<?= (int)$idx ?>"
            aria-label="Ver grupo de productos relacionados <?= (int)$idx + 1 ?>"
            aria-current="<?= $idx === 0 ? 'true' : 'false' ?>"
          ></button>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php section('partials/catalogo/quick-add-modal'); ?>
