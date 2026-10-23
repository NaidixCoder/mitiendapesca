<?php
/** @var array $crumbs */
/** @var array $filters */
/** @var array $env_options */
/** @var array $kind_options */
/** @var bool $is_ri */
/** @var string $export_url */
/** @var string $total_neto */
/** @var string $total_iva */
/** @var string $total_total */
/** @var array $totals_by_type_rows */
/** @var int $totals_by_type_count */
/** @var array $rows_prepared */
/** @var int $rows_count */
?>

<?php render_partial('admin/subnav'); ?>
<?php if (!$is_ri): ?>
  <div class="av3-fiscal-page av3-max-w-6xl av3-mx-auto av3-space-bottom-4">
    <div class="alert-info">Este reporte aplica a <b>Responsable Inscripto</b>. En <b>Monotributo</b> normalmente no se usa Libro IVA.</div>
  </div>
<?php endif; ?>

<div class="av3-fiscal-page av3-max-w-6xl av3-mx-auto">
  <?php
ob_start(); ?>Libro IVA Ventas<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Período por <strong>fecha de emisión</strong> (issued_at).<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
    
    <div class="av3-flex av3-items-center av3-gap-2">
      <a class="btn btn-primary" href="<?= e($export_url) ?>">Exportar CSV</a>
    </div>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

  <form class="card av3-pad-4 av3-space-bottom-4" method="get" action="<?= url('/admin/facturacion/libro-iva') ?>">
    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-5 av3-gap-3">
      <div>
        <label class="label">Desde (emisión)</label>
        <input class="input" type="date" name="from" value="<?= e((string)($filters['from'] ?? '')) ?>">
      </div>
      <div>
        <label class="label">Hasta (emisión)</label>
        <input class="input" type="date" name="to" value="<?= e((string)($filters['to'] ?? '')) ?>">
      </div>
      <div>
        <label class="label">Entorno</label>
        <select class="select" name="arca_env">
          <?php foreach ($env_options as $option): ?>
            <option value="<?= e((string)$option['value']) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>>
              <?= e((string)$option['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="label">Tipo</label>
        <select class="select" name="kind">
          <?php foreach ($kind_options as $option): ?>
            <option value="<?= e((string)$option['value']) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>>
              <?= e((string)$option['label']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="av3-flex av3-items-end av3-gap-2">
        <button class="btn btn-primary" type="submit">Aplicar</button>
        <a class="btn btn-outline" href="<?= url('/admin/facturacion/libro-iva') ?>">Limpiar</a>
      </div>
    </div>
  </form>

  <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-3 av3-space-bottom-4">
    <div class="card av3-pad-4">
      <div class="av3-type-body av3-text-gray-500">Neto gravado</div>
      <div class="av3-type-xl av3-weight-strong"><?= e($total_neto) ?></div>
    </div>
    <div class="card av3-pad-4">
      <div class="av3-type-body av3-text-gray-500">IVA</div>
      <div class="av3-type-xl av3-weight-strong"><?= e($total_iva) ?></div>
    </div>
    <div class="card av3-pad-4">
      <div class="av3-type-body av3-text-gray-500">Total</div>
      <div class="av3-type-xl av3-weight-strong"><?= e($total_total) ?></div>
    </div>
  </div>

  <div class="card av3-pad-4 av3-space-bottom-4">
    <div class="av3-flex av3-items-center av3-justify-between av3-gap-3 av3-space-bottom-2">
      <h2 class="av3-weight-strong">Totales por tipo</h2>
      <div class="av3-type-body av3-text-gray-500"><?= (int)$totals_by_type_count ?> filas</div>
    </div>
    <div class="max-h-64 av3-overflow-auto av3-border av3-radius-card">
      <table class="table av3-width-full">
        <thead class="av3-sticky top-0 av3-surface-white">
          <tr>
            <th>Tipo</th>
            <th>Clase</th>
            <th class="av3-text-right">Cantidad</th>
            <th class="av3-text-right">Neto</th>
            <th class="av3-text-right">IVA</th>
            <th class="av3-text-right">Total</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($totals_by_type_rows as $row): ?>
            <tr>
              <td><?= e((string)$row['cbte_label']) ?></td>
              <td><?= e((string)$row['kind_label']) ?></td>
              <td class="av3-text-right"><?= (int)$row['count'] ?></td>
              <td class="av3-text-right"><?= e((string)$row['neto']) ?></td>
              <td class="av3-text-right"><?= e((string)$row['iva']) ?></td>
              <td class="av3-text-right"><?= e((string)$row['total']) ?></td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($totals_by_type_rows)): ?>
            <tr><td colspan="6" class="av3-type-body av3-text-gray-500">Sin datos para el período seleccionado.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>

  <div class="card av3-pad-4">
    <div class="av3-flex av3-items-center av3-justify-between av3-gap-3 av3-space-bottom-2">
      <h2 class="av3-weight-strong">Comprobantes emitidos</h2>
      <div class="av3-type-body av3-text-gray-500">Total: <?= (int)$rows_count ?></div>
    </div>

    <div class="max-h-[520px] av3-overflow-auto av3-border av3-radius-card">
      <table class="table av3-width-full">
        <thead class="av3-sticky top-0 av3-surface-white">
          <tr>
            <th>Emisión</th>
            <th>Pedido</th>
            <th>Tipo</th>
            <th>PV / N°</th>
            <th>Doc</th>
            <th class="av3-text-right">Neto</th>
            <th class="av3-text-right">IVA</th>
            <th class="av3-text-right">Total</th>
            <th>CAE</th>
            <th></th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rows_prepared as $row): ?>
            <tr>
              <td class="whitespace-nowrap"><?= e((string)$row['issued_at']) ?></td>
              <td><a class="link" href="<?= e((string)$row['order_url']) ?>">#<?= (int)$row['order_id'] ?></a></td>
              <td><?= e((string)$row['cbte_label']) ?></td>
              <td class="whitespace-nowrap"><?= e((string)$row['pv_nro']) ?></td>
              <td class="whitespace-nowrap"><?= e((string)$row['doc_value']) ?></td>
              <td class="av3-text-right"><?= e((string)$row['imp_neto']) ?></td>
              <td class="av3-text-right"><?= e((string)$row['imp_iva']) ?></td>
              <td class="av3-text-right"><?= e((string)$row['imp_total']) ?></td>
              <td class="whitespace-nowrap"><?= e((string)$row['cae']) ?></td>
              <td class="av3-text-right">
                <a class="btn btn-sm btn-outline" href="<?= e((string)$row['show_url']) ?>">Abrir</a>
              </td>
            </tr>
          <?php endforeach; ?>
          <?php if (empty($rows_prepared)): ?>
            <tr><td colspan="10" class="av3-type-body av3-text-gray-500">Sin comprobantes emitidos en el período.</td></tr>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

