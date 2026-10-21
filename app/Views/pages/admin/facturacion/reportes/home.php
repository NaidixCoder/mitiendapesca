<?php
/** @var bool $isRI */

$crumbs = [
  ['label' => 'Admin', 'href' => base_url('admin')],
  ['label' => 'Facturación', 'href' => base_url('admin/facturacion/control')],
  ['label' => 'Reportes', 'href' => base_url('admin/facturacion/reportes')],
];
?>

<?php render_partial('admin/subnav'); ?>
<div class="av3-max-w-6xl av3-mx-auto">
  <div class="admin-surface av3-pad-6">
    <?php
ob_start(); ?>Reportes<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Elegí un reporte. Acá no se operan comprobantes: solo lectura y exportación.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
      

      <a class="btn btn-outline" href="<?= e(base_url('admin/facturacion/control')) ?>">Volver a Control fiscal</a>
    <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>'Reportes','href'=>url('/admin/facturacion/reportes')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-4 av3-space-top-6">
      <div class="av3-border av3-radius-card av3-pad-4">
        <div class="av3-type-body av3-text-slate-500">Principal</div>
        <div class="av3-type-lg av3-weight-medium av3-space-top-1">Resumen y conciliación</div>
        <div class="av3-type-body av3-text-slate-600 av3-space-top-1">Totales, agrupación por día/mes, y conciliación pedidos ↔ comprobantes.</div>
        <div class="av3-space-top-3">
          <a class="btn" href="<?= e(base_url('admin/facturacion/reportes/resumen')) ?>">Abrir</a>
        </div>
      </div>

      <div class="av3-border av3-radius-card av3-pad-4">
        <div class="av3-type-body av3-text-slate-500">Operativo</div>
        <div class="av3-type-lg av3-weight-medium av3-space-top-1">Comprobantes pendientes / errores</div>
        <div class="av3-type-body av3-text-slate-600 av3-space-top-1">Lista enfocada para revisar pendientes, errores y reintentos.</div>
        <div class="av3-space-top-3 av3-flex av3-gap-2 av3-flex-wrap">
          <a class="btn btn-outline" href="<?= e(base_url('admin/facturacion/reportes/comprobantes-pendientes')) ?>">Ver pendientes</a>
          <a class="btn btn-outline" href="<?= e(base_url('admin/facturacion/alertas')) ?>">Ver alertas</a>
        </div>
      </div>

      <?php if ($isRI): ?>
        <div class="av3-border av3-radius-card av3-pad-4">
          <div class="av3-type-body av3-text-slate-500">IVA</div>
          <div class="av3-type-lg av3-weight-medium av3-space-top-1">Reporte mensual IVA</div>
          <div class="av3-type-body av3-text-slate-600 av3-space-top-1">Totales por período (IVA ventas, IVA compras y saldo). Respeta cierres mensuales.</div>
          <div class="av3-space-top-3 av3-flex av3-gap-2 av3-flex-wrap">
            <a class="btn" href="<?= e(base_url('admin/facturacion/reportes/mensual')) ?>">Abrir</a>
            <a class="btn btn-outline" href="<?= e(base_url('admin/facturacion/cierres-mensuales')) ?>">Cierres</a>
          </div>
        </div>

        <div class="av3-border av3-radius-card av3-pad-4">
          <div class="av3-type-body av3-text-slate-500">IVA</div>
          <div class="av3-type-lg av3-weight-medium av3-space-top-1">Saldo IVA (resumen)</div>
          <div class="av3-type-body av3-text-slate-600 av3-space-top-1">Vista compacta del saldo IVA por mes.</div>
          <div class="av3-space-top-3">
            <a class="btn btn-outline" href="<?= e(base_url('admin/facturacion/reportes/saldo-iva')) ?>">Abrir</a>
          </div>
        </div>
      <?php else: ?>
        <div class="av3-border av3-radius-card av3-pad-4 av3-md-col-span-2">
          <div class="av3-type-body av3-text-slate-500">Monotributo</div>
          <div class="av3-type-lg av3-weight-medium av3-space-top-1">Sugerencia</div>
          <div class="av3-type-body av3-text-slate-600 av3-space-top-1">Para monotributo, normalmente no necesitás Libro IVA ni saldo IVA. Usá “Resumen y conciliación”, “Pendientes” y “Cierres mensuales” (si querés congelar totales por mes).</div>
        </div>
      <?php endif; ?>
    </div>
  </div>
</div>
