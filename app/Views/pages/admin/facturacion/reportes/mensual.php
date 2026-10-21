<?php render_partial('admin/subnav'); ?>
<div class="av3-max-w-6xl av3-mx-auto av3-pad-4">
  <?php
ob_start(); ?>Reporte mensual IVA (por emision)<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Resumen por mes usando <b>issued_at</b>. Incluye facturas y notas de credito emitidas.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
    
    <div class="av3-flex av3-items-center av3-gap-2">
      <a class="btn btn-primary" href="<?= e((string)($export_url ?? '')) ?>">Exportar CSV</a>
    </div>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>'Reportes','href'=>url('/admin/facturacion/reportes')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

  <div class="card av3-pad-4 av3-space-bottom-4">
    <form class="av3-grid av3-grid-cols-1 av3-md-grid-cols-4 av3-gap-3" method="get" action="<?= e((string)($apply_url ?? '')) ?>">
      <div>
        <label class="label">Desde</label>
        <input class="input" type="month" name="from_month" value="<?= e((string)($filters['from_month'] ?? '')) ?>">
      </div>
      <div>
        <label class="label">Hasta</label>
        <input class="input" type="month" name="to_month" value="<?= e((string)($filters['to_month'] ?? '')) ?>">
      </div>
      <div>
        <label class="label">Entorno</label>
        <select class="select" name="arca_env">
          <?php foreach (($env_options ?? []) as $option): ?>
            <option value="<?= e((string)($option['value'] ?? '')) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)($option['label'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="av3-flex av3-items-end av3-gap-2">
        <button class="btn btn-primary av3-width-full" type="submit">Aplicar</button>
        <a class="btn btn-outline" href="<?= e((string)($export_url ?? '')) ?>">CSV</a>
      </div>
    </form>
    <div class="av3-space-top-3 av3-type-body av3-text-gray-600">
      <span class="av3-mr-3">Rango: <b><?= e((string)($filters['from_date'] ?? '')) ?></b> -> <b><?= e((string)($filters['to_date'] ?? '')) ?></b></span>
      <span class="av3-mr-3">Comprobantes: <b><?= (int)($totals['comprobantes_count'] ?? 0) ?></b></span>
      <span class="av3-mr-3">Facturas: <b><?= (int)($totals['facturas_count'] ?? 0) ?></b></span>
      <span>NC: <b><?= (int)($totals['notas_credito_count'] ?? 0) ?></b></span>
    </div>
  </div>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3 av3-space-bottom-4">
    <div class="card av3-pad-4">
      <div class="av3-type-body av3-text-gray-500">Neto</div>
      <div class="av3-type-xl av3-weight-strong"><?= e((string)($totals['neto_label'] ?? '$ 0,00')) ?></div>
    </div>
    <div class="card av3-pad-4">
      <div class="av3-type-body av3-text-gray-500">IVA</div>
      <div class="av3-type-xl av3-weight-strong"><?= e((string)($totals['iva_label'] ?? '$ 0,00')) ?></div>
    </div>
    <div class="card av3-pad-4">
      <div class="av3-type-body av3-text-gray-500">Total</div>
      <div class="av3-type-xl av3-weight-strong"><?= e((string)($totals['total_label'] ?? '$ 0,00')) ?></div>
    </div>
  </div>

  <div class="card av3-pad-4">
    <h2 class="av3-type-base av3-weight-strong av3-space-bottom-3">Resumen por mes</h2>
    <div class="max-h-[520px] av3-overflow-auto av3-border av3-radius-sm">
      <table class="table av3-width-full">
        <thead class="av3-sticky top-0 av3-surface-white">
          <tr>
            <th>Periodo</th>
            <th class="av3-text-right">Comprobantes</th>
            <th class="av3-text-right">Facturas</th>
            <th class="av3-text-right">NC</th>
            <th class="av3-text-right">Neto</th>
            <th class="av3-text-right">IVA</th>
            <th class="av3-text-right">Total</th>
          </tr>
        </thead>
        <tbody>
        <?php if (empty($has_rows)): ?>
          <tr><td colspan="7" class="av3-text-center av3-text-gray-500 av3-pad-y6">No hay comprobantes emitidos en el rango.</td></tr>
        <?php else: ?>
          <?php foreach (($rows_prepared ?? []) as $row): ?>
            <tr>
              <td>
                <?php if ((string)($row['libro_url'] ?? '') !== ''): ?>
                  <a class="av3-text-teal-700 hover:underline" href="<?= e((string)($row['libro_url'] ?? '')) ?>" title="Ver detalle en Libro IVA"><?= e((string)($row['ym'] ?? '')) ?></a>
                <?php else: ?>
                  <?= e((string)($row['ym'] ?? '')) ?>
                <?php endif; ?>
              </td>
              <td class="av3-text-right"><?= (int)($row['comprobantes_count'] ?? 0) ?></td>
              <td class="av3-text-right"><?= (int)($row['facturas_count'] ?? 0) ?></td>
              <td class="av3-text-right"><?= (int)($row['notas_credito_count'] ?? 0) ?></td>
              <td class="av3-text-right"><?= e((string)($row['neto_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-right"><?= e((string)($row['iva_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-right"><?= e((string)($row['total_label'] ?? '$ 0,00')) ?></td>
            </tr>
          <?php endforeach; ?>
        <?php endif; ?>
        </tbody>
      </table>
    </div>
    <div class="av3-space-top-3 av3-type-caption av3-text-gray-500">Nota: importes en centavos (ARS) normalizados a moneda local en pantalla.</div>
  </div>
</div>
