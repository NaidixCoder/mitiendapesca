<?php
/** @var array<string,mixed> $item */
$itemKey = (string)($item['view_item_key'] ?? '');
$qty = (int)($item['qty'] ?? 0);
$stock = (int)($item['stock'] ?? 0);
$lineTotal = (int)($item['view_line_total'] ?? 0);
$maxQty = (int)($item['view_max_qty'] ?? 1);
$discountPct = (int)($item['view_discount_pct'] ?? 0);
$hintText = (string)($item['view_hint_text'] ?? '');
$hintClass = trim((string)($item['view_hint_class'] ?? ''));
$isSelectable = !empty($item['view_is_selectable']);
$sku = trim((string)($item['sku'] ?? ''));
$brandName = trim((string)($item['brand_name'] ?? ''));
$shortDesc = trim((string)($item['short_desc'] ?? ''));
$unitListLabel = (string)($item['display_financed_unit_label'] ?? money((int)($item['list_price'] ?? 0)));
$financedLineLabel = (string)($item['display_financed_line_label'] ?? money((int)($item['list_price'] ?? 0) * $qty));
$transferLineLabel = (string)($item['display_transfer_line_label'] ?? money((int)($item['price'] ?? 0) * $qty));
?>
<div
  class="cart-item"
  data-cart-item
  data-key="<?= e($itemKey) ?>"
  data-stock="<?= $stock ?>"
  data-qty="<?= $qty ?>"
  data-line-total="<?= $lineTotal ?>"
>
  <label class="cart-item__check">
    <input type="checkbox" aria-label="Seleccionar producto" data-cart-select-item <?= !empty($item['view_is_selected']) ? 'checked' : '' ?> <?= $isSelectable ? '' : 'disabled aria-disabled="true"' ?>>
  </label>

  <div class="cart-item__body">
    <div class="cart-item__top">
      <h3 class="cart-item__title"><?= e((string)($item['name'] ?? '')) ?></h3>
      <button
        type="button"
        class="cart-item__remove"
        data-cart-remove
        data-product-id="<?= (int)($item['id'] ?? 0) ?>"
        data-variant-id="<?= (int)($item['variant_id'] ?? 0) ?>"
        aria-label="Quitar producto"
      >
        <?= ui_icon('trash') ?>
      </button>
    </div>

    <div class="cart-item__content">
      <div class="cart-item__thumb-wrap">
        <img src="<?= e((string)($item['thumb'] ?? '')) ?>" alt="" class="cart-item__thumb" loading="lazy">
      </div>

      <div class="cart-item__controls">
        <select class="cart-item__qty" data-cart-qty name="qty[<?= e($itemKey) ?>]" <?= $isSelectable ? '' : 'disabled aria-disabled="true"' ?>>
          <?php for ($i = 1; $i <= $maxQty; $i++): ?>
            <option value="<?= $i ?>" <?= $i === $qty ? 'selected' : '' ?>><?= $i ?> u.</option>
          <?php endfor; ?>
        </select>
        <span class="cart-item__qty-unit" data-cart-qty-unit>x <?= e($unitListLabel) ?></span>

        <div class="cart-item__details">
          <?php if ($sku !== '' || $brandName !== ''): ?>
            <p class="cart-item__meta">
              <?php if ($sku !== ''): ?>
                <span><?= e($sku) ?></span>
              <?php endif; ?>
              <?php if ($brandName !== ''): ?>
                <span>- Marca: <?= e($brandName) ?></span>
              <?php endif; ?>
              <span class="cart-item__unit-price" data-cart-unit-price><?= e($unitListLabel) ?></span>
            </p>
          <?php elseif ($unitListLabel !== ''): ?>
            <p class="cart-item__meta">
              <span class="cart-item__unit-price" data-cart-unit-price><?= e($unitListLabel) ?></span>
            </p>
          <?php endif; ?>

          <?php if ($shortDesc !== ''): ?>
            <p class="cart-item__short-desc"><?= e($shortDesc) ?></p>
          <?php endif; ?>
        </div>

        <div class="cart-item__pricing">
          <div class="cart-item__list" data-cart-line-list><?= e($financedLineLabel) ?></div>
          <div class="cart-item__offer-row">
            <?php if ($discountPct > 0): ?>
              <div class="cart-item__discount">Transferencia</div>
            <?php endif; ?>
            <div class="cart-item__transfer" data-cart-line-transfer><?= e($transferLineLabel) ?></div>
          </div>
        </div>
      </div>
    </div>

    <?php if (array_key_exists('is_publicly_available', $item) && empty($item['is_publicly_available'])): ?>
      <p role="alert">No disponible para nuevas compras. Este artículo continúa guardado hasta que decidas quitarlo.</p>
    <?php endif; ?>
    <p class="cart-item__stock<?= $hintClass !=='' ? ' ' . e($hintClass) : '' ?>" data-cart-stock-msg><?= e($hintText) ?></p>
  </div>
</div>
