<?php
/** @var array $product */
$general = $product['images_general'] ?? ($product['images'] ?? []);
$byVariant = $product['images_by_variant'] ?? [];
$brand = trim((string)($product['brand_name'] ?? $product['brand'] ?? ''));

$byVariantNorm = [];
foreach ($byVariant as $vid => $arr) {
    $k = (string)(int)$vid;
    $byVariantNorm[$k] = array_values(array_filter(array_map('strval', (array)$arr)));
}

$initial = !empty($general) ? $general : [];
$totalInitial = count($initial) > 0 ? count($initial) : 1;
$favoriteActive = !empty($favoriteActive);
$favoriteProductId = (int)($product['id'] ?? 0);
$favoriteNext = current_url(true);
$initialJson = e(json_encode(array_values($initial), JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: '[]');
?>

<section class="product-gallery" data-gallery>
  <div class="product-gallery__stage">
    <span id="pg-counter" class="product-gallery__counter">1/<?= (int)$totalInitial ?></span>
    <button
      type="button"
      class="product-gallery__favorite<?= $favoriteActive ?' is-active' : '' ?>"
      data-gallery-favorite
      data-product-id="<?= $favoriteProductId ?>"
      data-favorite-endpoint="<?= e(url('/cuenta/favoritos/toggle')) ?>"
      data-login-url="<?= e(url('/login') . '?next=' . urlencode($favoriteNext)) ?>"
      data-next="<?= e($favoriteNext) ?>"
      aria-label="<?= $favoriteActive ? 'Quitar de favoritos' : 'Agregar a favoritos' ?>"
      aria-pressed="<?= $favoriteActive ? 'true' : 'false' ?>"
    >
      <span class="product-gallery__favorite-icon product-gallery__favorite-icon--outline" aria-hidden="true">
        <?= ui_icon('heart') ?>
      </span>
      <span class="product-gallery__favorite-icon product-gallery__favorite-icon--filled" aria-hidden="true">
        <?= ui_icon('heart-solid') ?>
      </span>
    </button>

    <?php if (!empty($initial)): ?>
      <div id="pg-main-viewport" class="product-gallery__viewport" data-gallery-images="<?= $initialJson ?>">
        <div id="pg-main-track" class="product-gallery__track"></div>
      </div>
      <button type="button" class="product-gallery__nav product-gallery__nav--prev" data-gallery-prev aria-label="Imagen anterior"><?= ui_icon('chevron-left-solid') ?></button>
      <button type="button" class="product-gallery__nav product-gallery__nav--next" data-gallery-next aria-label="Imagen siguiente"><?= ui_icon('chevron-right-solid') ?></button>
    <?php else: ?>
      <img class="product-gallery__image" src="<?= asset('img/placeholder.jpg') ?>" alt="Sin imagen">
    <?php endif; ?>

  </div>

  <div id="pg-dots" class="product-gallery__dots" role="tablist" aria-label="Selector de imagenes"></div>

  <?php
  $hasAnyThumbs = (count($general) > 0);
  if (!$hasAnyThumbs) {
      foreach ($byVariantNorm as $arr) {
          if (count($arr) > 0) {
              $hasAnyThumbs = true;
              break;
          }
      }
  }
  ?>

  <?php if ($hasAnyThumbs): ?>
    <div class="product-gallery__thumbs-wrap">
      <div id="pg-thumbs" class="product-gallery__thumbs">
        <div class="product-gallery__group" data-gallery-group data-variant-id="general">
          <?php foreach ($general as $i => $path): ?>
            <button
              type="button"
              class="product-gallery__thumb<?= $i === 0 ?' is-active' : '' ?>"
              data-gallery-thumb
              data-idx="<?= $i ?>"
            >
              <img class="product-gallery__thumb-image" src="<?= public_url($path) ?>" alt="Miniatura <?= $i + 1 ?>">
            </button>
          <?php endforeach; ?>
        </div>

        <?php foreach ($byVariantNorm as $vid => $arr): ?>
          <div class="product-gallery__group ui-is-hidden" data-gallery-group data-variant-id="<?= e($vid) ?>">
            <?php foreach ($arr as $i => $path): ?>
              <button
                type="button"
                class="product-gallery__thumb<?= $i === 0 ?' is-active' : '' ?>"
                data-gallery-thumb
                data-idx="<?= $i ?>"
              >
                <img class="product-gallery__thumb-image" src="<?= public_url($path) ?>" alt="Miniatura <?= $i + 1 ?>">
              </button>
            <?php endforeach; ?>
          </div>
        <?php endforeach; ?>
      </div>
    </div>
  <?php endif; ?>

<div id="pg-modal" class="product-gallery-modal ui-is-hidden" aria-hidden="true">
  <div class="product-gallery-modal__backdrop" data-gallery-modal-close></div>
  <div class="product-gallery-modal__dialog" role="dialog" aria-modal="true" aria-label="Galeria del producto">
    <div class="product-gallery-modal__header">
      <span id="pg-modal-counter" class="product-gallery-modal__counter">1/<?= (int)$totalInitial ?></span>
      <div class="product-gallery-modal__title-wrap">
        <p class="product-gallery-modal__title">Galeria del producto</p>
      </div>
      <button type="button" class="product-gallery-modal__close" data-gallery-modal-close aria-label="Cerrar imagen">
        <?= ui_icon('close') ?>
      </button>
    </div>

    <div class="product-gallery-modal__stage">
      <div id="pg-modal-viewport" class="product-gallery-modal__viewport">
        <div id="pg-modal-track" class="product-gallery-modal__track"></div>
      </div>
    </div>

    <div id="pg-modal-dots" class="product-gallery-modal__dots" role="tablist" aria-label="Selector de imagenes ampliadas"></div>

    <?php if ($hasAnyThumbs): ?>
      <div class="product-gallery-modal__thumbs-shell">
        <button type="button" class="product-gallery-modal__thumb-nav product-gallery-modal__thumb-nav--prev" data-gallery-modal-prev aria-label="Imagen anterior"><?= ui_icon('chevron-left-solid') ?></button>
        <div id="pg-modal-thumbs" class="product-gallery-modal__thumbs" aria-label="Miniaturas de galeria"></div>
        <button type="button" class="product-gallery-modal__thumb-nav product-gallery-modal__thumb-nav--next" data-gallery-modal-next aria-label="Imagen siguiente"><?= ui_icon('chevron-right-solid') ?></button>
      </div>
    <?php endif; ?>
  </div>
</div>
</section>
