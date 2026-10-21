<?php
/** @var array $product */
$saleMode = (string)($product['sale_mode'] ?? 'simple');
$variants = $product['variants'] ?? [];
$productTitle = trim((string)($product['title'] ?? $product['name'] ?? 'Producto'));
$brand = trim((string)($product['brand_name'] ?? $product['brand'] ?? '-'));
$brandSlug = trim((string)($product['brand_slug'] ?? ''));
$brandUrl = $brandSlug !== '' ? base_url('/marca?slug=' . urlencode($brandSlug)) : '';
$stock = (int)($product['stock'] ?? 0);
$hasRange = !empty($product['has_range']);
$transferDisplay = (float)($product['display_transfer_price'] ?? ($product['price_low'] ?? ($product['price'] ?? 0)));
$financedDisplay = (float)($product['display_financed_price'] ?? ($product['list_price'] ?? $transferDisplay));
$discountPct = (int)($product['display_discount_pct'] ?? 0);
$installmentText = (string)($product['display_installment_label'] ?? '');
$installmentHtml = '';
if ($installmentText !== '') {
    $installmentHtml = e($installmentText);
    if (preg_match('/(\$\s?[0-9\.\,]+)/u', $installmentText, $matches)) {
        $amountEscaped = e((string)$matches[1]);
        $installmentHtml = preg_replace(
            '/' . preg_quote($amountEscaped, '/') . '/',
            '<span class="product-buybox__installment-amount">' . $amountEscaped . '</span>',
            $installmentHtml,
            1
        ) ?? $installmentHtml;
    }
}

$fmt = static function (float $value): string {
    return '$' . number_format($value, 0, ',', '.');
};

$variantAttrLabel = 'Opciones';
if ($saleMode === 'variants' && !empty($variants)) {
    $names = [];
    foreach ($variants as $variant) {
        foreach (($variant['attrs'] ?? []) as $attr) {
            $name = trim((string)($attr['attribute'] ?? ''));
            if ($name !== '') {
                $names[$name] = true;
            }
        }
    }
    $keys = array_keys($names);
    if (count($keys) === 1) {
        $variantAttrLabel = (string)$keys[0];
    }
}

?>

<section class="product-buybox <?= $saleMode ==='variants' ? 'product-buybox--variants' : 'product-buybox--simple' ?>">
  <form id="buy-form" action="<?= base_url('/carrito/agregar') ?>" method="post" class="product-buybox__form">
    <?= csrf_field() ?>
    <input type="hidden" name="product_id" value="<?= (int)($product['id'] ?? 0) ?>">
    <input type="hidden" name="_back" value="<?= e($_SERVER['REQUEST_URI'] ?? '') ?>">
    <div class="product-buybox__content">

    <p class="product-buybox__meta">
      <span class="product-buybox__meta-item">
        <span class="product-buybox__meta-label">Marca: <?= e($brand) ?></span>
      </span>
      <span class="product-buybox__meta-item">
        <span class="product-buybox__meta-icon" aria-hidden="true"><?= ui_icon('box') ?></span>
        <span class="product-buybox__meta-label">Stock: <span id="pp-stock"><?= $stock ?></span></span>
      </span>
    </p>

    <?php if ($brand !== '' && $brand !== '-'): ?>
      <p class="product-heading__helper product-buybox__helper">
        <?php if ($brandUrl !== ''): ?>
          <a class="product-heading__helper-link" href="<?= e($brandUrl) ?>">Ver mas productos Marca: <?= e($brand) ?></a>
        <?php else: ?>
          Ver mas productos Marca: <?= e($brand) ?>
        <?php endif; ?>
      </p>
    <?php endif; ?>

    <?php if ($saleMode === 'variants'): ?>
      <div class="product-buybox__variant">
        <p id="pv-hint" class="product-buybox__hint">Elegi una opcion para ver precio, stock y fotos.</p>

        <div class="catalog-qa__row product-buybox__variant-row">
          <label for="variant_id" class="catalog-qa__label product-buybox__variant-label"><?= e($variantAttrLabel) ?>:</label>
          <div class="catalog-qa__control">
            <select id="variant_id" name="variant_id" required class="catalog-qa__select product-buybox__variant-select" aria-hidden="false">
              <option value="" selected disabled>Elegí una opción...</option>
              <?php foreach ($variants as $variant): ?>
                <?php
                $variantId = (int)($variant['id'] ?? 0);
                $variantLabel = trim((string)($variant['title'] ?? $variant['label'] ?? 'Variante'));
                $variantStock = (int)($variant['stock'] ?? 0);
                $disabled = ($variantStock <= 0);
                $optionLabel = $variantLabel !== '' ? $variantLabel : 'Variante';
                if ($disabled) {
                    $optionLabel .= ' - sin stock';
                }
                ?>
                <option value="<?= $variantId ?>" <?= $disabled ? 'disabled' : '' ?>>
                  <?= e($optionLabel) ?>
                </option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div
          id="pv-data"
          class="ui-is-hidden"
          data-variants='<?= e(json_encode($variants, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?>'
          data-installments-count="<?= e((string)($product['display_installments_count'] ?? 0)) ?>"
          data-installments-enabled="<?= !empty($product['display_installments_enabled']) ? '1' : '0' ?>"
        ></div>
      </div>
    <?php else: ?>
      <div class="product-buybox__variant product-buybox__variant--simple">
        <p class="product-buybox__simple-title"><?= e($productTitle) ?></p>
      </div>
    <?php endif; ?>

    <div class="product-buybox__price-box">
      <div class="product-buybox__price-row">
        <span id="pp-price" class="product-buybox__price">
          <?= $financedDisplay > 0 ? $fmt($financedDisplay) : 'Precio a consultar' ?>
        </span>
        <?php if ($installmentText !== ''): ?>
          <p id="pp-installment" class="product-buybox__installment"><?= $installmentHtml ?></p>
        <?php else: ?>
          <p id="pp-installment" class="product-buybox__installment ui-is-hidden"></p>
        <?php endif; ?>
      </div>
      <div class="product-buybox__transfer-row">
        <span id="pp-transfer" class="product-buybox__transfer">
          <?= $transferDisplay > 0 ? $fmt($transferDisplay) : '' ?>
        </span>
        <?php if ($discountPct > 0): ?>
          <span id="pp-badge" class="product-buybox__badge">-<?= $discountPct ?>% con transferencia</span>
        <?php else: ?>
          <span id="pp-badge" class="product-buybox__badge ui-is-hidden"></span>
        <?php endif; ?>
      </div>
    </div>

    <div class="product-buybox__qty">
      <div class="product-buybox__stepper" aria-label="Selector de cantidad">
        <button type="button" class="product-buybox__stepper-btn product-buybox__stepper-btn--minus" data-qty-btn data-delta="-1" aria-label="Restar">
          <?= ui_icon('minus-solid') ?>
        </button>
        <input id="qty" name="qty" type="number" value="1" min="1" step="1" class="product-buybox__stepper-input no-spin" inputmode="numeric">
        <button type="button" class="product-buybox__stepper-btn product-buybox__stepper-btn--plus" data-qty-btn data-delta="1" aria-label="Sumar">
          <?= ui_icon('plus-solid') ?>
        </button>
      </div>
    </div>

    <div class="product-buybox__actions">
      <button type="submit" name="buy_now" value="1" class="product-buybox__action product-buybox__action--primary">
        <span class="product-buybox__action-icon" aria-hidden="true"><?= ui_icon('bag-shopping-solid') ?></span>
        <span>Comprar ahora</span>
      </button>
      <button type="submit" class="product-buybox__action product-buybox__action--secondary">
        <span class="product-buybox__action-icon" aria-hidden="true"><?= ui_icon('cart-outline') ?></span>
        <span>Agregar al carrito</span>
      </button>
    </div>
    <section class="product-buybox__trust" aria-label="Beneficios de compra">
    <article class="product-buybox__trust-item">
      <span class="product-buybox__trust-icon" aria-hidden="true"><?= ui_icon('truck-solid') ?></span>
      <p class="product-buybox__trust-title">Envío rápido</p>
      <p class="product-buybox__trust-copy">A todo el país</p>
    </article>

    <article class="product-buybox__trust-item">
      <span class="product-buybox__trust-icon" aria-hidden="true"><?= ui_icon('shield-solid') ?></span>
      <p class="product-buybox__trust-title">Pago protegido</p>
      <p class="product-buybox__trust-copy">Compra segura</p>
    </article>

    <article class="product-buybox__trust-item">
      <span class="product-buybox__trust-icon" aria-hidden="true"><?= ui_icon('headset-solid') ?></span>
      <p class="product-buybox__trust-title">Te asesoramos</p>
      <p class="product-buybox__trust-copy">Para elegir bien</p>
    </article>
  </section>
  </div>
  </form>
</section>

