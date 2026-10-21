<?php
ob_start(); ?>Control fiscal<?php $adminHeaderTitle=trim(strip_tags(ob_get_clean()));
ob_start(); ?>Revision, alertas, cierres y exportacion de libros IVA.<?php $adminHeaderDescription=trim(strip_tags(ob_get_clean()));
ob_start(); ?>
    <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/comprobantes')) ?>">Ir a operacion diaria</a>
    <a class="btn btn-secondary" href="<?= e(base_url('admin/facturacion/reportes')) ?>">Exportar / reportes</a>
    <a class="btn btn-primary" href="<?= e(base_url('admin/facturacion/cierres-mensuales')) ?>">Iniciar cierre</a>
  <?php $adminHeaderActions=ob_get_clean();
$adminHeaderBreadcrumbs=(isset($crumbs) && is_array($crumbs) && $crumbs) ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>
