<?php
/** @var array $product */

$shortDesc = trim((string)($product['description'] ?? $product['short_desc'] ?? ''));
$longDesc = trim((string)($product['long_desc'] ?? ''));

if ($shortDesc === '' && $longDesc === '') {
    return;
}
?>

<section class="product-mobile-accordion" aria-label="Informacion del producto">
  <?php if ($shortDesc !== ''): ?>
    <details class="product-mobile-accordion__item">
      <summary class="product-mobile-accordion__summary">
        <span class="product-mobile-accordion__lead">
          <span class="product-mobile-accordion__icon" aria-hidden="true"><?= ui_icon('box') ?></span>
          <span class="product-mobile-accordion__title">Descripci&oacute;n del producto</span>
        </span>
        <span class="product-mobile-accordion__chevron" aria-hidden="true"><?= ui_icon('chevron-down-solid') ?></span>
      </summary>
      <div class="product-mobile-accordion__content">
        <?= nl2br(h($shortDesc)) ?>
      </div>
    </details>
  <?php endif; ?>

  <?php if ($longDesc !== ''): ?>
    <details class="product-mobile-accordion__item">
      <summary class="product-mobile-accordion__summary">
        <span class="product-mobile-accordion__lead">
          <span class="product-mobile-accordion__icon" aria-hidden="true"><?= ui_icon('tag-solid') ?></span>
          <span class="product-mobile-accordion__title">Especificaciones</span>
        </span>
        <span class="product-mobile-accordion__chevron" aria-hidden="true"><?= ui_icon('chevron-down-solid') ?></span>
      </summary>
      <div class="product-mobile-accordion__content">
        <?= nl2br(h($longDesc)) ?>
      </div>
    </details>
  <?php endif; ?>
</section>
