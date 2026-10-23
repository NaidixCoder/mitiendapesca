<div class="av3-fiscal-page av3-container av3-max-w-6xl av3-mx-auto av3-pad-4">
  <?php render_partial('admin/subnav'); ?>
  <?php
ob_start(); ?>Cierre mensual IVA - <?= e((string)($period ?? '')) ?><?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Snapshot congelado para auditoria. Este periodo no se recalcula en los reportes.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
    
    <div class="av3-flex av3-items-center av3-gap-2">
      <a class="btn btn-outline" href="<?= e((string)($back_url ?? '')) ?>">Volver</a>
      <a class="btn btn-primary" href="<?= e((string)($export_url ?? '')) ?>">Descargar CSV</a>
    </div>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3 av3-space-bottom-4">
    <div class="card av3-pad-4">
      <div class="av3-type-caption av3-text-gray-500">IVA Ventas</div>
      <div class="av3-type-lg av3-weight-strong"><?= e((string)($summary['iva_ventas_label'] ?? '$ 0,00')) ?></div>
    </div>
    <div class="card av3-pad-4">
      <div class="av3-type-caption av3-text-gray-500">IVA Compras</div>
      <div class="av3-type-lg av3-weight-strong"><?= e((string)($summary['iva_compras_label'] ?? '$ 0,00')) ?></div>
    </div>
    <div class="card av3-pad-4">
      <div class="av3-type-caption av3-text-gray-500">Saldo IVA</div>
      <div class="av3-type-lg av3-weight-strong"><?= e((string)($summary['saldo_iva_label'] ?? '$ 0,00')) ?></div>
    </div>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-3">
    <div class="card av3-pad-4">
      <h2 class="av3-weight-strong av3-space-bottom-2">Ventas (por emision)</h2>
      <div class="av3-type-body av3-flow-1">
        <div class="av3-flex av3-justify-between"><span>Neto</span><span><?= e((string)($ventas['neto_label'] ?? '$ 0,00')) ?></span></div>
        <div class="av3-flex av3-justify-between"><span>IVA</span><span><?= e((string)($ventas['iva_label'] ?? '$ 0,00')) ?></span></div>
        <div class="av3-flex av3-justify-between"><span>Total</span><span><?= e((string)($ventas['total_label'] ?? '$ 0,00')) ?></span></div>
      </div>

      <div class="av3-space-top-3">
        <div class="av3-type-caption av3-text-gray-500 av3-space-bottom-1">Breakdown (totales)</div>
        <div class="av3-grid av3-grid-cols-2 av3-gap-2 av3-type-body">
          <div class="av3-border av3-radius-sm av3-pad-2"><div class="av3-type-caption av3-text-gray-500">Factura A</div><div class="av3-weight-medium"><?= e((string)($ventas['fa_total_label'] ?? '$ 0,00')) ?></div></div>
          <div class="av3-border av3-radius-sm av3-pad-2"><div class="av3-type-caption av3-text-gray-500">Factura B</div><div class="av3-weight-medium"><?= e((string)($ventas['fb_total_label'] ?? '$ 0,00')) ?></div></div>
          <div class="av3-border av3-radius-sm av3-pad-2"><div class="av3-type-caption av3-text-gray-500">Factura C</div><div class="av3-weight-medium"><?= e((string)($ventas['fc_total_label'] ?? '$ 0,00')) ?></div></div>
          <div class="av3-border av3-radius-sm av3-pad-2"><div class="av3-type-caption av3-text-gray-500">NC A/B/C</div><div class="av3-weight-medium"><?= e((string)($ventas['nc_total_label'] ?? '$ 0,00')) ?></div></div>
        </div>
      </div>
    </div>

    <div class="card av3-pad-4">
      <h2 class="av3-weight-strong av3-space-bottom-2">Compras</h2>
      <div class="av3-type-body av3-flow-1">
        <div class="av3-flex av3-justify-between"><span>Neto</span><span><?= e((string)($compras['neto_label'] ?? '$ 0,00')) ?></span></div>
        <div class="av3-flex av3-justify-between"><span>IVA</span><span><?= e((string)($compras['iva_label'] ?? '$ 0,00')) ?></span></div>
        <div class="av3-flex av3-justify-between"><span>Total</span><span><?= e((string)($compras['total_label'] ?? '$ 0,00')) ?></span></div>
      </div>

      <div class="av3-space-top-3 av3-type-caption av3-text-gray-500">
        Cerrado por user_id: <?= e((string)($closed_by_user_id ?? '')) ?><br>
        Cerrado el: <?= e((string)($closed_at ?? '')) ?>
      </div>
    </div>
  </div>
</div>
