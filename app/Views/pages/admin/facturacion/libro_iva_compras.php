<?php render_partial('admin/subnav'); ?>
<?php if (empty($is_ri)): ?>
  <div class="av3-fiscal-page av3-max-w-6xl av3-mx-auto av3-space-bottom-4">
    <div class="alert-info">Este reporte aplica a <b>Responsable Inscripto</b>. En <b>Monotributo</b> normalmente no se usa Libro IVA.</div>
  </div>
<?php endif; ?>

<div class="av3-max-w-6xl av3-mx-auto av3-pad-4">
  <?php
ob_start(); ?>Libro IVA Compras (por emision)<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
$adminHeaderDescription='';
ob_start(); ?>
    
    <div class="av3-flex av3-gap-2 av3-flex-wrap">
      <a class="btn btn-primary" href="<?= e((string)($export_url ?? '')) ?>">Exportar CSV</a>
    </div>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

  <div class="card av3-pad-4 av3-space-bottom-4">
    <form method="get" action="<?= e((string)($apply_url ?? '')) ?>" class="av3-grid av3-grid-cols-1 av3-md-grid-cols-5 av3-gap-3">
      <div>
        <label class="label">Desde</label>
        <input class="input" type="date" name="from" value="<?= e((string)(($filters['from'] ?? ''))) ?>">
      </div>
      <div>
        <label class="label">Hasta</label>
        <input class="input" type="date" name="to" value="<?= e((string)(($filters['to'] ?? ''))) ?>">
      </div>
      <div>
        <label class="label">Proveedor</label>
        <select class="input" name="supplier_id">
          <?php foreach (($supplier_options ?? []) as $option): ?>
            <option value="<?= e((string)($option['value'] ?? '')) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)($option['label'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="label">Tipo</label>
        <select class="input" name="kind">
          <?php foreach (($kind_options ?? []) as $option): ?>
            <option value="<?= e((string)($option['value'] ?? '')) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)($option['label'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="label">Estado</label>
        <select class="input" name="status">
          <?php foreach (($status_options ?? []) as $option): ?>
            <option value="<?= e((string)($option['value'] ?? '')) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)($option['label'] ?? '')) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="av3-md-col-span-5 av3-flex av3-gap-2 av3-justify-end">
        <button class="btn btn-primary" type="submit">Aplicar</button>
        <a class="btn btn-secondary" href="<?= e((string)($reset_url ?? '')) ?>">Limpiar</a>
      </div>
    </form>
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

  <div class="card av3-p-0 av3-overflow-hidden av3-space-bottom-4">
    <div class="max-h-[40vh] av3-overflow-auto">
      <table class="table av3-width-full">
        <thead class="av3-sticky top-0 av3-surface-white av3-z-10">
          <tr>
            <th>Tipo</th>
            <th class="av3-text-right">Neto</th>
            <th class="av3-text-right">IVA</th>
            <th class="av3-text-right">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($has_rows_by_type)): ?>
            <tr><td colspan="4" class="av3-text-center av3-pad-y6 av3-text-gray-500">Sin datos para el periodo.</td></tr>
          <?php endif; ?>
          <?php foreach (($rows_by_type_prepared ?? []) as $row): ?>
            <tr class="<?= !empty($row['is_nc']) ? 'av3-bg-amber-50' : '' ?>">
              <td class="whitespace-nowrap"><?= e((string)($row['kind_label'] ?? '')) ?> (<?= (int)($row['cbte_tipo'] ?? 0) ?>)</td>
              <td class="av3-text-right"><?= e((string)($row['neto_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-right"><?= e((string)($row['iva_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-right"><?= e((string)($row['total_label'] ?? '$ 0,00')) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card av3-p-0 av3-overflow-hidden">
    <div class="max-h-[70vh] av3-overflow-auto">
      <table class="table av3-width-full">
        <thead class="av3-sticky top-0 av3-surface-white av3-z-10">
          <tr>
            <th>Fecha</th>
            <th>Proveedor</th>
            <th>Comprobante</th>
            <th class="av3-text-right">Neto</th>
            <th class="av3-text-right">IVA</th>
            <th class="av3-text-right">Total</th>
            <th>Estado</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($has_rows)): ?>
            <tr><td colspan="7" class="av3-text-center av3-pad-y6 av3-text-gray-500">No hay compras para esos filtros.</td></tr>
          <?php endif; ?>

          <?php foreach (($rows_prepared ?? []) as $row): ?>
            <tr class="<?= !empty($row['is_nc']) ? 'av3-bg-amber-50' : '' ?>">
              <td class="whitespace-nowrap"><?= e((string)($row['issued_at'] ?? '')) ?></td>
              <td>
                <div class="av3-weight-medium"><?= e((string)($row['supplier_nombre'] ?? '')) ?></div>
                <div class="av3-type-caption av3-text-gray-500"><?= e((string)($row['supplier_cuit'] ?? '')) ?></div>
              </td>
              <td class="whitespace-nowrap">Tipo <?= (int)($row['cbte_tipo'] ?? 0) ?> � PV <?= (int)($row['pto_vta'] ?? 0) ?> � N� <?= (int)($row['cbte_nro'] ?? 0) ?></td>
              <td class="av3-text-right"><?= e((string)($row['neto_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-right"><?= e((string)($row['iva_label'] ?? '$ 0,00')) ?></td>
              <td class="av3-text-right"><?= e((string)($row['total_label'] ?? '$ 0,00')) ?></td>
              <td class="whitespace-nowrap"><span class="badge <?= e((string)($row['status_badge_class'] ?? 'badge-secondary')) ?>"><?= e((string)($row['status_label'] ?? '')) ?></span></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>

  <p class="av3-type-caption av3-text-gray-500 av3-space-top-3">Nota: las Notas de credito se muestran en negativo (restan).</p>
</div>
