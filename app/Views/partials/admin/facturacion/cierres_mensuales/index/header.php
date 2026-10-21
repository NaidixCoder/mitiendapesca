<?php
/** @var bool $is_ri */
/** @var string $reportes_url */
?>
<?php
ob_start(); ?><?= !empty($is_ri) ? 'Cierres mensuales IVA' : 'Cierres mensuales' ?><?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Congela el resultado fiscal de un periodo (IVA Ventas, IVA Compras y saldo) para auditoria y trazabilidad.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
  
  <div class="av3-flex av3-items-center av3-gap-2">
    <a class="btn btn-outline" href="<?= e($reportes_url) ?>">Volver a Reportes</a>
  </div>
<?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>
