<?php render_partial('admin/subnav'); ?>
<div class="av3-fiscal-page av3-max-w-6xl av3-mx-auto av3-pad-4">
  <?php
ob_start(); ?>Alertas operativas<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Pendientes tipicos de facturacion para mantener la operacion prolija (sin contabilidad avanzada).<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
$adminHeaderActions='';
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

  <div class="av3-grid av3-grid-cols-1 av3-lg-grid-cols-2 av3-gap-4">
    <div class="card av3-pad-4">
      <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
        <h2 class="av3-weight-strong">Compras cargadas sin validar</h2>
        <span class="av3-type-caption av3-text-gray-500"><?= (int)($loaded_count ?? 0) ?> item(s)</span>
      </div>
      <p class="av3-type-caption av3-text-gray-500 av3-space-bottom-3">Compras en estado <b>Cargada</b>. Recomendacion: revisar y marcar como <b>Validada</b>.</p>

      <div class="max-h-[45vh] av3-overflow-auto">
        <table class="table av3-width-full av3-type-body">
          <thead class="av3-sticky top-0 av3-surface-white">
            <tr>
              <th class="av3-text-left">Fecha</th>
              <th class="av3-text-left">Proveedor</th>
              <th class="av3-text-left">Documento</th>
              <th class="av3-text-right">Total</th>
              <th class="av3-text-right">Accion</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($loaded_rows)): ?>
              <tr><td colspan="5" class="av3-text-center av3-text-gray-500 av3-pad-y6">No hay pendientes.</td></tr>
            <?php else: ?>
              <?php foreach (($loaded_rows ?? []) as $row): ?>
                <tr>
                  <td><?= e((string)($row['issued_at'] ?? '')) ?></td>
                  <td>
                    <div class="av3-weight-medium"><?= e((string)($row['supplier_nombre'] ?? '')) ?></div>
                    <div class="av3-type-caption av3-text-gray-500"><?= e((string)($row['supplier_cuit'] ?? '')) ?></div>
                  </td>
                  <td class="whitespace-nowrap"><?= e((string)($row['document'] ?? '')) ?></td>
                  <td class="av3-text-right"><?= e((string)($row['total_label'] ?? '$ 0,00')) ?></td>
                  <td class="av3-text-right"><a class="btn btn-xs btn-primary" href="<?= e((string)($row['action_url'] ?? '')) ?>">Revisar</a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>

    <div class="card av3-pad-4">
      <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
        <h2 class="av3-weight-strong">Compras sin adjunto</h2>
        <span class="av3-type-caption av3-text-gray-500"><?= (int)($missing_count ?? 0) ?> item(s)</span>
      </div>
      <p class="av3-type-caption av3-text-gray-500 av3-space-bottom-3">Compras sin PDF/imagen adjunta. Recomendacion: adjuntar el comprobante para auditoria.</p>

      <div class="max-h-[45vh] av3-overflow-auto">
        <table class="table av3-width-full av3-type-body">
          <thead class="av3-sticky top-0 av3-surface-white">
            <tr>
              <th class="av3-text-left">Fecha</th>
              <th class="av3-text-left">Proveedor</th>
              <th class="av3-text-left">Estado</th>
              <th class="av3-text-right">Total</th>
              <th class="av3-text-right">Accion</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($missing_rows)): ?>
              <tr><td colspan="5" class="av3-text-center av3-text-gray-500 av3-pad-y6">No hay faltantes.</td></tr>
            <?php else: ?>
              <?php foreach (($missing_rows ?? []) as $row): ?>
                <tr>
                  <td><?= e((string)($row['issued_at'] ?? '')) ?></td>
                  <td>
                    <div class="av3-weight-medium"><?= e((string)($row['supplier_nombre'] ?? '')) ?></div>
                    <div class="av3-type-caption av3-text-gray-500"><?= e((string)($row['supplier_cuit'] ?? '')) ?></div>
                  </td>
                  <td class="whitespace-nowrap"><span class="badge <?= e((string)($row['status_badge_class'] ?? 'badge-warning')) ?>"><?= e((string)($row['status_label'] ?? '')) ?></span></td>
                  <td class="av3-text-right"><?= e((string)($row['total_label'] ?? '$ 0,00')) ?></td>
                  <td class="av3-text-right"><a class="btn btn-xs btn-primary" href="<?= e((string)($row['action_url'] ?? '')) ?>">Adjuntar</a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <div class="card av3-pad-4 av3-space-top-4">
    <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
      <h2 class="av3-weight-strong">Meses con movimientos sin cierre</h2>
      <span class="av3-type-caption av3-text-gray-500"><?= (int)($period_count ?? 0) ?> periodo(s)</span>
    </div>
    <p class="av3-type-caption av3-text-gray-500 av3-space-bottom-3">Si queres trazabilidad (auditoria), cerra el mes una vez terminado. Si no cerras, los reportes siguen en vivo.</p>

    <?php if (empty($period_rows)): ?>
      <div class="av3-type-body av3-text-gray-600">No hay meses pendientes de cierre.</div>
    <?php else: ?>
      <div class="av3-flex av3-flex-wrap av3-gap-2">
        <?php foreach (($period_rows ?? []) as $period): ?>
          <a class="btn btn-xs btn-outline" href="<?= e((string)($period['url'] ?? '')) ?>"><?= e((string)($period['label'] ?? '')) ?></a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card av3-pad-4 av3-space-top-4">
    <h2 class="av3-weight-strong av3-space-bottom-2">Accesos rapidos</h2>
    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-2 av3-gap-2">
      <a class="btn btn-outline av3-justify-start" href="<?= e((string)($links['pedidos_sin_factura'] ?? '')) ?>">Pedidos sin factura</a>
      <a class="btn btn-outline av3-justify-start" href="<?= e((string)($links['comprobantes_pendientes'] ?? '')) ?>">Comprobantes pendientes</a>
      <a class="btn btn-outline av3-justify-start" href="<?= e((string)($links['desvios'] ?? '')) ?>">Desvios</a>
      <a class="btn btn-outline av3-justify-start" href="<?= e((string)($links['duplicados_exactos'] ?? '')) ?>">Duplicados exactos</a>
    </div>
  </div>
</div>
