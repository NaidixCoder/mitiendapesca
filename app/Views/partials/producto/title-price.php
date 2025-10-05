<?php
// Valores seguros / defaults
$title   = $product['title'] ?? ($product['name'] ?? 'Producto');
$sku     = $product['sku']   ?? '—';
$brand   = $product['brand'] ?? '—';
$stock   = (int)($product['stock'] ?? 0);

$priceV  = (float)($product['price'] ?? 0);
$listV   = isset($product['list_price']) && $product['list_price'] !== '' ? (float)$product['list_price'] : null;

$price   = number_format($priceV, 0, ',', '.');
$list    = $listV !== null ? number_format($listV, 0, ',', '.') : null;

// Descuento (mín 5%, máx 70%) solo si list > price
$off     = null;
if ($listV !== null && $priceV < $listV && $listV > 0) {
  $raw = 100 - ($priceV / $listV) * 100;
  $off = max(5, min(70, (int)round($raw)));
}

// Rating / reviews
$rating  = (float)($product['rating']  ?? 0);
$reviews = (int)($product['reviews'] ?? 0);
$stars   = (int)floor($rating);

// Descripción corta: acepta 'short' o 'short_desc'
$short   = $product['short'] ?? ($product['short_desc'] ?? '');
?>
<header class="space-y-2">
  <h1 class="text-2xl sm:text-3xl font-semibold leading-tight"><?= e($title) ?></h1>

  <div class="flex items-center gap-3">
    <?php if ($list !== null && $off !== null): ?>
      <span class="text-gray-400 line-through">$<?= $list ?></span>
    <?php endif; ?>
    <span class="text-2xl font-bold">$<?= $price ?></span>
    <?php if ($off !== null): ?>
      <span class="px-2 py-1 text-xs rounded-full bg-rose-600 text-white">-<?= $off ?>%</span>
    <?php endif; ?>
  </div>

  <p class="text-gray-500 text-sm">
    SKU: <?= e($sku) ?> · Marca: <?= e($brand) ?> · Stock: <?= $stock ?>
  </p>

  <div class="flex items-center gap-1 text-amber-500">
    <?php for ($i = 1; $i <= 5; $i++): ?>
      <span><?= $i <= $stars ? '★' : '☆' ?></span>
    <?php endfor; ?>
    <span class="text-gray-400 text-sm">(<?= $reviews ?>)</span>
  </div>

  <?php if ($short !== ''): ?>
    <p class="text-gray-700"><?= e($short) ?></p>
  <?php endif; ?>
</header>
