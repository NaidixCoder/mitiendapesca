<?php
$card = $product ?? ($card ?? []);
if (!is_array($card)) {
  $card = [];
}

$id = (int)($card['id'] ?? 0);
$name = (string)($card['name'] ?? 'Producto');
$href = (string)($card['href'] ?? base_url('producto'));
$img = (string)($card['image'] ?? asset('img/placeholder.svg'));
$brandName = (string)($card['brand_name'] ?? '');
$catName = (string)($card['category_name'] ?? '');
$priceLabel = (string)($card['price_label'] ?? '');
$transferLabel = (string)($card['transfer_label'] ?? '');
$transferNoteLabel = (string)($card['transfer_note_label'] ?? '');
$transferDetailLabel = (string)($card['transfer_detail_label'] ?? '');
$installmentLabel = (string)($card['installment_label'] ?? '');
$installmentDetailLabel = (string)($card['installment_detail_label'] ?? '');
$hasOffer = !empty($card['has_offer']);
$discountPct = (int)($card['discount_pct'] ?? 0);
$hasVariants = !empty($card['has_variants']);
$inStock = !array_key_exists('in_stock', $card) || !empty($card['in_stock']);
$backUrl = (string)($card['back_url'] ?? ($_SERVER['REQUEST_URI'] ?? ''));
?>

<article class="catalog-card js-card">
  <a class="catalog-card__link" href="<?= e($href) ?>" aria-label="Ver <?= e($name) ?>">
    <div class="catalog-card__media">
      <?php if ($hasOffer): ?>
        <span class="catalog-card__badge">
          <?= $discountPct ? (' -' . $discountPct . '%') : '' ?>
        </span>
      <?php endif; ?>
      <img class="catalog-card__image" src="<?= e($img) ?>" alt="<?= e($name) ?>" loading="lazy">
    </div>

    <div class="catalog-card__body">
      <?php if ($brandName !== '' || $catName !== ''): ?>
        <div class="catalog-card__meta">
          <?= e($brandName) ?>
          <?php if ($brandName !== '' && $catName !== ''): ?> · <?php endif; ?>
          <?= e($catName) ?>
        </div>
      <?php endif; ?>

      <h3 class="catalog-card__title"><?= e($name) ?></h3>

      <div class="catalog-card__price-row">
        <div class="catalog-card__pricing">
          <div class="catalog-card__price">
            <?= e($priceLabel) ?>
            <?php if ($installmentLabel !== ''): ?>
              <span class="catalog-card__price-note"><?= e($installmentLabel) ?></span>
            <?php endif; ?>
          </div>
          <?php if ($installmentDetailLabel !== ''): ?>
            <div class="catalog-card__installment"><?= e($installmentDetailLabel) ?></div>
          <?php endif; ?>
          <?php if ($transferLabel !== ''): ?>
            <div class="catalog-card__transfer">
              <?= e($transferLabel) ?>
              <?php if ($transferNoteLabel !== ''): ?>
                <span class="catalog-card__transfer-note"><?= e($transferNoteLabel) ?></span>
              <?php endif; ?>
            </div>
            <?php if ($transferDetailLabel !== ''): ?>
              <div class="catalog-card__transfer-detail"><?= e($transferDetailLabel) ?></div>
            <?php endif; ?>
          <?php endif; ?>

        </div>
      </div>
    </div>
  </a>

  <div class="catalog-card__actions">
    <?php if (!$inStock): ?>
      <button type="button" class="catalog-card__btn" disabled aria-disabled="true">Sin stock</button>
    <?php elseif ($hasVariants): ?>
      <button
        type="button"
        class="catalog-card__btn"
        data-quick-add-trigger
        data-product-id="<?= e((string)$id) ?>"
      ><span class="catalog-card__btn-icon" aria-hidden="true"><?= ui_icon('cart-arrow') ?></span>Agregar
      </button>
    <?php else: ?>
      <form method="post" action="<?= e(base_url('carrito/agregar')) ?>" data-cart-add-card>
        <?= csrf_field() ?>
        <input type="hidden" name="product_id" value="<?= e((string)$id) ?>">
        <input type="hidden" name="qty" value="1">
        <input type="hidden" name="_back" value="<?= e($backUrl) ?>">
        <button class="catalog-card__btn" type="submit"><span class="catalog-card__btn-icon" aria-hidden="true"><?= ui_icon('cart-arrow') ?></span>Agregar</button>
      </form>
    <?php endif; ?>
  </div>
</article>
