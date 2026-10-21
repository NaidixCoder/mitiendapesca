<?php /** @var int $total */ ?>
<?php
ob_start(); ?>Productos<?php $adminHeaderTitle=trim(strip_tags(ob_get_clean()));
ob_start(); ?>Gestioná tu catálogo, precios, disponibilidad y variantes desde un solo lugar.<?php $adminHeaderDescription=trim(strip_tags(ob_get_clean()));
ob_start(); ?>
    <a class="catalog-button catalog-button--outline" href="<?= url('/admin/inventario') ?>">Ver inventario</a>
    <a class="catalog-button catalog-button--primary" href="<?= url('/admin/products/new') ?>"><span aria-hidden="true">+</span> Nuevo producto</a>
  <?php $adminHeaderActions=ob_get_clean();
$adminHeaderBreadcrumbs=(isset($crumbs) && is_array($crumbs) && $crumbs) ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Productos','href'=>url('/admin/products')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
<div class="catalog-summary" aria-label="Resumen de productos">
  <div class="catalog-summary__icon" aria-hidden="true">▦</div>
  <div><span class="catalog-summary__label">PRODUCTOS EN EL CATÁLOGO</span><strong><?= (int)$total ?></strong></div>
  <span class="catalog-summary__hint">Administración del inventario comercial</span>
</div>
