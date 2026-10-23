<?php
/** @var string $back_url */
/** @var string $export_url */
/** @var int $rows_count */
?>
<?php
ob_start(); ?>Pedidos con resultado fiscal relevante<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Casos del periodo: anulaciones, montos distintos, duplicados o entorno mixto.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
  
  <div class="av3-flex av3-items-center av3-gap-2">
    <a class="btn btn-secondary" href="<?= e($back_url) ?>">Volver</a>
    <a class="btn btn-primary" href="<?= e($export_url) ?>">Exportar CSV</a>
  </div>
<?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>'Reportes','href'=>url('/admin/facturacion/reportes')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

<section class="av3-fiscal-panel admin-surface av3-pad-4">
  <div class="av3-type-body av3-text-slate-600 av3-space-bottom-3">Mostrando <span class="av3-weight-medium"><?= (int)$rows_count ?></span> filas</div>
