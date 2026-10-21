<?php
/**
 * Contenido del modal Quick Add.
 *
 * Espera:
 * - $product: array{id,name,brand_name?,category_name?,price?}
 * - $variants: array<int, array{id,title,price,list_price,transfer_price?,financed_price?,installment_inline?,stock,in_stock,thumb_url?,attribute_name?,attribute_value?}>
 * - $optionLabel: string
 */

$product = $product ?? [];
$variants = $variants ?? [];
$optionLabel = trim((string)($optionLabel ?? 'Variante'));

$productName = (string)($product['name'] ?? 'Producto');
$productId = (int)($product['id'] ?? 0);
$productSlug = trim((string)($product['slug'] ?? ''));
$productShortDescription = trim((string)($product['description'] ?? $product['short_desc'] ?? $product['short_description'] ?? $product['excerpt'] ?? $product['description_short'] ?? $product['long_desc'] ?? ''));
$thumbSrc = asset('img/placeholder.svg');
$productUrl = $productSlug !== ''
    ? base_url('/producto?slug=' . rawurlencode($productSlug))
    : base_url('/productos');

$selectedVariant = null;
foreach ($variants as $variant) {
    if (!empty($variant['in_stock'])) {
        $selectedVariant = $variant;
        break;
    }
}
if ($selectedVariant === null && !empty($variants)) {
    $selectedVariant = $variants[0];
}

$selectedTransferPrice = (int)($selectedVariant['transfer_price'] ?? $selectedVariant['price'] ?? ($product['price'] ?? 0));
$selectedFinancedPrice = (int)($selectedVariant['financed_price'] ?? $selectedVariant['list_price'] ?? $selectedTransferPrice);
if ($selectedFinancedPrice <= 0) {
    $selectedFinancedPrice = $selectedTransferPrice;
}

$selectedInstallmentInline = trim((string)($selectedVariant['installment_inline'] ?? ''));
$selectedOptionValue = trim((string)($selectedVariant['attribute_value'] ?? $selectedVariant['title'] ?? ''));
if ($selectedOptionValue === '') {
    $selectedOptionValue = 'Elegi tu opcion';
}
$selectedVariantTitle = trim((string)($selectedVariant['title'] ?? ''));
$selectedVariantExtra = '';
if ($selectedVariantTitle !== '' && strcasecmp($selectedVariantTitle, $selectedOptionValue) !== 0) {
    $selectedVariantExtra = $selectedVariantTitle;
}
$selectedDiscountPct = 0;
if ($selectedFinancedPrice > 0 && $selectedTransferPrice > 0 && $selectedTransferPrice < $selectedFinancedPrice) {
    $selectedDiscountPct = (int)round((($selectedFinancedPrice - $selectedTransferPrice) / $selectedFinancedPrice) * 100);
}

// Estructura preparada para atributos multiples (ej: Color + Talle).
// Por ahora, si no se inyectan ejes adicionales, se usa el selector primario variant_id.
$variantAxes = $variantAxes ?? [];
if (!is_array($variantAxes) || $variantAxes === []) {
    $variantAxes = [[
        'id' => 'variant_id',
        'name' => 'variant_id',
        'label' => $optionLabel,
        'is_primary' => true,
    ]];
}
?>

<div class="catalog-qa" data-quick-add-content>
  <div class="catalog-qa__summary">
    <div class="catalog-qa__card">
      <a class="catalog-qa__headline" href="<?= e($productUrl) ?>" data-quick-add-name>
        <?= e($productName) ?>
      </a>
      <a class="catalog-qa__thumb-wrap" href="<?= e($productUrl) ?>" aria-label="Ver producto: <?= e($productName) ?>">
        <img data-quick-add-thumb class="catalog-qa__thumb" src="<?= e($thumbSrc) ?>" alt="<?= e($productName) ?>">
      </a>

      <div class="catalog-qa__intro">
        <?php if ($productShortDescription !== ''): ?>
          <div class="catalog-qa__short-desc"><?= e($productShortDescription) ?></div>
        <?php endif; ?>
        <div class="catalog-qa__top-row">
          <div class="catalog-qa__variant-line">
            <span class="catalog-qa__variant-key"><?= e($optionLabel) ?>:</span>
            <span data-quick-add-option-value><?= e($selectedOptionValue) ?></span>
          </div>
          <div class="catalog-qa__price" data-quick-add-financed-price><?= e(money($selectedFinancedPrice)) ?></div>
        </div>

        <?php if ($selectedVariantExtra !== ''): ?>
          <div class="catalog-qa__variant-extra" data-quick-add-variant-extra><?= e($selectedVariantExtra) ?></div>
        <?php else: ?>
          <div class="catalog-qa__variant-extra" data-quick-add-variant-extra hidden></div>
        <?php endif; ?>

        <?php if ($selectedInstallmentInline !== ''): ?>
          <div class="catalog-qa__price-note" data-quick-add-installment-inline><?= e($selectedInstallmentInline) ?></div>
        <?php else: ?>
          <div class="catalog-qa__price-note" data-quick-add-installment-inline hidden></div>
        <?php endif; ?>

      </div>

      <div class="catalog-qa__transfer-row">
        <span class="catalog-qa__transfer-label">
          Pagando con transferencia
          <?php if ($selectedDiscountPct > 0): ?>
            <span class="catalog-card__badge catalog-qa__discount-badge" data-quick-add-discount-badge>-<?= e((string)$selectedDiscountPct) ?>%</span>
          <?php else: ?>
            <span class="catalog-card__badge catalog-qa__discount-badge" data-quick-add-discount-badge hidden></span>
          <?php endif; ?>
          off:
        </span>
        <span class="catalog-qa__transfer-price" data-quick-add-transfer-price><?= e(money($selectedTransferPrice)) ?></span>
      </div>
    </div>
  </div>

  <?php if (empty($variants)): ?>
    <div class="catalog-qa__empty">
      No hay variantes activas para este producto.
    </div>
  <?php else: ?>
    <form method="post" action="<?= e(base_url('carrito/agregar')) ?>" data-quick-add-form class="catalog-qa__form">
      <?= csrf_field() ?>
      <input type="hidden" name="product_id" value="<?= e((string)$productId) ?>">
      <input type="hidden" name="_ajax" value="1">

      <?php foreach ($variantAxes as $axisIndex => $axis): ?>
        <?php
        $axisName = trim((string)($axis['name'] ?? ''));
        $axisLabel = trim((string)($axis['label'] ?? ('Opcion ' . ($axisIndex + 1))));
        $axisId = trim((string)($axis['id'] ?? ('variant_axis_' . $axisIndex)));
        $isPrimaryAxis = !empty($axis['is_primary']) || $axisName === 'variant_id' || $axisIndex === 0;
        ?>
        <div class="catalog-qa__row">
          <label class="catalog-qa__label" for="<?= e($axisId) ?>"><?= e($axisLabel) ?>:</label>
          <div class="catalog-qa__control">
            <?php if ($isPrimaryAxis): ?>
              <select id="<?= e($axisId) ?>" name="variant_id" class="catalog-qa__select" data-quick-add-variant-select>
                <?php foreach ($variants as $variant): ?>
                  <?php
                  $variantId = (int)($variant['id'] ?? 0);
                  $title = trim((string)($variant['title'] ?? ('Variante #' . $variantId)));
                  $attributeValue = trim((string)($variant['attribute_value'] ?? ''));
                  $optionValue = $attributeValue !== '' ? $attributeValue : $title;
                  $transferPrice = (int)($variant['transfer_price'] ?? $variant['price'] ?? 0);
                  $financedPrice = (int)($variant['financed_price'] ?? $variant['list_price'] ?? $transferPrice);
                  if ($financedPrice <= 0) {
                      $financedPrice = $transferPrice;
                  }
                  $discountPct = 0;
                  if ($financedPrice > 0 && $transferPrice > 0 && $transferPrice < $financedPrice) {
                      $discountPct = (int)round((($financedPrice - $transferPrice) / $financedPrice) * 100);
                  }
                  $installmentInline = trim((string)($variant['installment_inline'] ?? ''));
                  $disabled = empty($variant['in_stock']);
                  $thumb = trim((string)($variant['thumb_url'] ?? ''));
                  ?>
                  <option
                    value="<?= e((string)$variantId) ?>"
                    <?= $disabled ? 'disabled' : '' ?>
                    <?= $thumb !== '' ? ('data-thumb="' . e($thumb) . '"') : '' ?>
                    data-title="<?= e($title) ?>"
                    data-attribute-value="<?= e($optionValue) ?>"
                    data-variant-extra="<?= e($title) ?>"
                    data-transfer-price="<?= e(money($transferPrice)) ?>"
                    data-financed-price="<?= e(money($financedPrice)) ?>"
                    data-discount-pct="<?= e((string)$discountPct) ?>"
                    data-installment-inline="<?= e($installmentInline) ?>"
                  >
                    <?= e($optionValue) ?> - <?= e(money($transferPrice)) ?><?= $disabled ? ' - sin stock' : '' ?>
                  </option>
                <?php endforeach; ?>
              </select>
            <?php else: ?>
              <select id="<?= e($axisId) ?>" class="catalog-qa__select" data-quick-add-axis="<?= e((string)$axisIndex) ?>" disabled>
                <option value="">Sin opciones disponibles</option>
              </select>
            <?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>

      <div class="catalog-qa__row">
        <label class="catalog-qa__label">Cantidad:</label>
        <div class="catalog-qa__control">
          <div class="catalog-qa__qty">
            <button type="button" class="catalog-qa__qty-btn" data-qty-dec aria-label="Restar">-</button>
            <input
              type="number"
              name="qty"
              value="1"
              min="1"
              class="catalog-qa__qty-input"
            />
            <button type="button" class="catalog-qa__qty-btn" data-qty-inc aria-label="Sumar">+</button>
          </div>
        </div>
      </div>

      <div class="catalog-qa__actions">
        <button type="submit" class="catalog-card__btn" data-quick-add-submit>
          <span class="catalog-qa__btn-icon" aria-hidden="true"><?= ui_icon('cart-arrow') ?></span>
          <span>Agregar al carrito</span>
        </button>

        <div class="catalog-qa__trust">
          <span class="catalog-qa__trust-icon" aria-hidden="true"><?= ui_icon('shield-solid') ?></span>
          <span>Compra protegida</span>
        </div>

        <button type="button" class="catalog-qa__btn-link" data-quick-add-close>
          <span class="catalog-qa__btn-link-icon" aria-hidden="true"><?= ui_icon('search') ?></span>
          <span>Seguir viendo productos</span>
        </button>
      </div>

      <div class="catalog-qa__feedback" data-quick-add-feedback></div>
    </form>
  <?php endif; ?>
</div>
