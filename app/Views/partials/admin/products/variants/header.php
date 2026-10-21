<?php
ob_start(); ?>Variantes<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
      Producto: <span class="av3-weight-medium"><?= e((string)($product['name'] ?? '')) ?></span>
      � Total: <?= (int)$variantsCount ?>
    <?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
  <div>
    <div class="av3-flex av3-items-center av3-gap-2">
      <a class="btn-ghost btn-sm" href="<?= url('/admin/products') ?>">? Volver</a>
    </div>
    
    
  </div>
  <a class="btn-secondary" href="<?= url('/admin/products/edit?id='.(int)$productId) ?>">Editar producto</a>
<?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
