<?php
$title = $product['title'] ?? ($product['name'] ?? 'Producto');
$brand = trim((string)($product['brand_name'] ?? $product['brand'] ?? ''));
$brandSlug = trim((string)($product['brand_slug'] ?? ''));
$brandUrl = $brandSlug !== '' ? base_url('/marca?slug=' . urlencode($brandSlug)) : '';
?>

<header class="product-heading">
  <div class="product-heading__row">
    <h1 class="product-heading__title"><?= htmlspecialchars($title) ?></h1>
  </div>
</header>
