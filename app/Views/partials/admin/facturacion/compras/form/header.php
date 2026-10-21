<?php
/** @var bool $is_edit */
?>
<?php
ob_start(); ?><?= $is_edit ? 'Editar compra' : 'Nueva compra' ?><?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
$adminHeaderDescription='';
ob_start(); ?>
  
  <div class="av3-flex av3-gap-2">
    <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/compras')) ?>">Volver</a>
  </div>
<?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>
