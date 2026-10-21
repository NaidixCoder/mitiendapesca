<?php
/**
 * Grid de productos.
 * Acepta tanto $products como $items por compatibilidad.
 */
$rows = $products ?? ($items ?? []);
if (!is_array($rows)) {
  $rows = [];
}
?>

<?php if (empty($rows)): ?>
  <?php section('partials/catalogo/empty-state'); ?>
<?php else: ?>
  <div id="catalog-grid" class="catalog-grid">
    <?php foreach ($rows as $p): ?>
      <?php section('partials/catalogo/product-card', ['product' => $p]); ?>
    <?php endforeach; ?>
  </div>

  <?php section('partials/catalogo/quick-add-modal'); ?>
<?php endif; ?>
