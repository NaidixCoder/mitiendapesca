<?php
/**
 * /admin/products/variants?product_id=XX
 * @var array $product
 * @var int $productId
 * @var array $variantsCtx
 */

$pageTitle = 'Variantes — '.((string)($product['name'] ?? 'Producto'));
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$variants = $variantsCtx['variants'] ?? [];
$valuesByVariant = $variantsCtx['values_by_variant'] ?? [];

$attrsMap = [];
foreach ($valuesByVariant as $row) {
  $vid = (int)($row['variant_id'] ?? 0);
  if ($vid <= 0) continue;
  $attrsMap[$vid] ??= [];
  $attrsMap[$vid][] = [
    'attribute' => (string)($row['attribute_name'] ?? ''),
    'value' => (string)($row['value_text'] ?? ''),
  ];
}

$variantsCount = is_array($variants) ? count($variants) : 0;
?>

<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/products/variants/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/products/variants/table.php'; ?>

  </div>
</section>
