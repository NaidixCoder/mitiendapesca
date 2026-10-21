<?php
/** @var array $crumbs */
/** @var array $filters */
/** @var string $tab */
/** @var array $env_options */
/** @var array $group_options */
/** @var string $apply_url */
/** @var string $reset_url */
/** @var string $export_summary_url */
/** @var string $export_detail_url */
/** @var string $tab_resumen_url */
/** @var string $tab_conciliacion_url */
/** @var array $summary_cards */
/** @var array $series_rows */
/** @var int $series_count */
/** @var array $conc_orders_missing_invoice */
/** @var array $conc_comprobantes_not_issued */
/** @var array $conc_deviations */
/** @var array $conc_duplicates_exact */
/** @var array $conc_non_billable_with_invoice */
?>

<?php render_partial('admin/subnav'); ?>
<div class="av3-max-w-6xl av3-mx-auto">
  <?php
ob_start(); ?>Reportes fiscales<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Resumen por período, export y conciliación (pedidos â†” comprobantes).<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
    
    <div class="av3-flex av3-items-center av3-gap-2">
      <a class="btn btn-secondary" href="<?= e($export_summary_url) ?>">Exportar resumen CSV</a>
      <a class="btn btn-secondary" href="<?= e($export_detail_url) ?>">Exportar detalle CSV</a>
    </div>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>'Reportes','href'=>url('/admin/facturacion/reportes')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

  <div class="admin-surface av3-pad-4 av3-space-bottom-4">
    <form method="get" action="<?= e($apply_url) ?>" class="av3-grid av3-grid-cols-1 av3-md-grid-cols-5 av3-gap-3">
      <div>
        <label class="form-label">Desde</label>
        <input type="date" name="from" value="<?= e((string)($filters['from'] ?? '')) ?>" class="input av3-width-full" />
      </div>
      <div>
        <label class="form-label">Hasta</label>
        <input type="date" name="to" value="<?= e((string)($filters['to'] ?? '')) ?>" class="input av3-width-full" />
      </div>
      <div>
        <label class="form-label">Entorno ARCA</label>
        <select name="arca_env" class="form-select">
          <?php foreach ($env_options as $option): ?>
            <option value="<?= e((string)$option['value']) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)$option['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div>
        <label class="form-label">Agrupar</label>
        <select name="group" class="form-select">
          <?php foreach ($group_options as $option): ?>
            <option value="<?= e((string)$option['value']) ?>" <?= !empty($option['selected']) ? 'selected' : '' ?>><?= e((string)$option['label']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="av3-flex av3-items-end av3-gap-2">
        <input type="hidden" name="tab" value="<?= e($tab) ?>" />
        <button class="btn btn-primary" type="submit">Aplicar</button>
        <a class="btn btn-secondary" href="<?= e($reset_url) ?>">Hoy / mes</a>
      </div>
    </form>
  </div>

  <div class="av3-flex av3-items-center av3-gap-2 av3-space-bottom-3">
    <a class="btn <?= $tab ==='resumen' ? 'btn-primary' : 'btn-secondary' ?>" href="<?= e($tab_resumen_url) ?>">Resumen</a>
    <a class="btn <?= $tab ==='conciliacion' ? 'btn-primary' : 'btn-secondary' ?>" href="<?= e($tab_conciliacion_url) ?>">Conciliación</a>
  </div>

  <?php if ($tab === 'conciliacion'): ?>

    <div class="av3-grid av3-grid-cols-1 av3-lg-grid-cols-2 av3-gap-4">
      <div class="admin-surface av3-pad-4">
        <h2 class="av3-type-base av3-weight-strong av3-space-bottom-1">Pedidos sin factura emitida</h2>
        <p class="av3-type-body av3-text-slate-600 av3-space-bottom-3">Heurística: pedidos en estados facturables dentro del período que no tienen comprobante <span class="av3-weight-medium">emitido</span>.</p>
        <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
          <div class="av3-type-body av3-text-slate-600">Mostrando <span class="av3-weight-medium"><?= (int)$conc_orders_missing_invoice['count'] ?></span> filas</div>
          <div class="av3-flex av3-items-center av3-gap-2">
            <a class="btn btn-secondary btn-sm" href="<?= e((string)$conc_orders_missing_invoice['view_all_url']) ?>">Ver todos</a>
            <a class="btn btn-secondary btn-sm" href="<?= e((string)$conc_orders_missing_invoice['export_url']) ?>">Exportar CSV</a>
          </div>
        </div>
        <div class="max-h-[420px] av3-overflow-auto av3-radius-sm av3-border av3-border-slate-200">
          <table class="av3-min-w-full av3-type-body">
            <thead class="av3-sticky top-0 av3-surface-white">
              <tr class="av3-text-left av3-border-bottom">
                <th class="av3-pad-2">Pedido</th>
                <th class="av3-pad-2">Fecha</th>
                <th class="av3-pad-2">Estado</th>
                <th class="av3-pad-2">Total</th>
                <th class="av3-pad-2">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($conc_orders_missing_invoice['rows'])): ?>
                <tr><td class="av3-pad-3 av3-text-slate-600" colspan="5">No hay pendientes en el período.</td></tr>
              <?php else: ?>
                <?php foreach ($conc_orders_missing_invoice['rows'] as $row): ?>
                  <tr class="av3-border-bottom">
                    <td class="av3-pad-2 av3-weight-medium">#<?= (int)$row['order_id'] ?></td>
                    <td class="av3-pad-2 av3-text-slate-600"><?= e((string)$row['created_at']) ?></td>
                    <td class="av3-pad-2"><?= e((string)$row['order_status_label']) ?></td>
                    <td class="av3-pad-2"><?= e((string)$row['total_label']) ?></td>
                    <td class="av3-pad-2"><a class="underline" href="<?= e((string)$row['order_url']) ?>">Abrir pedido</a></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="admin-surface av3-pad-4">
        <h2 class="av3-type-base av3-weight-strong av3-space-bottom-1">Comprobantes en pendiente / error</h2>
        <p class="av3-type-body av3-text-slate-600 av3-space-bottom-3">Comprobantes creados en el período con estado <span class="av3-weight-medium">pendiente</span> o <span class="av3-weight-medium">error</span>.</p>
        <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
          <div class="av3-type-body av3-text-slate-600">Mostrando <span class="av3-weight-medium"><?= (int)$conc_comprobantes_not_issued['count'] ?></span> filas</div>
          <div class="av3-flex av3-items-center av3-gap-2">
            <a class="btn btn-secondary btn-sm" href="<?= e((string)$conc_comprobantes_not_issued['view_all_url']) ?>">Ver todos</a>
            <a class="btn btn-secondary btn-sm" href="<?= e((string)$conc_comprobantes_not_issued['export_url']) ?>">Exportar CSV</a>
          </div>
        </div>
        <div class="max-h-[420px] av3-overflow-auto av3-radius-sm av3-border av3-border-slate-200">
          <table class="av3-min-w-full av3-type-body">
            <thead class="av3-sticky top-0 av3-surface-white">
              <tr class="av3-text-left av3-border-bottom">
                <th class="av3-pad-2">ID</th>
                <th class="av3-pad-2">Pedido</th>
                <th class="av3-pad-2">Tipo</th>
                <th class="av3-pad-2">Estado</th>
                <th class="av3-pad-2">Total</th>
                <th class="av3-pad-2">Acciones</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($conc_comprobantes_not_issued['rows'])): ?>
                <tr><td class="av3-pad-3 av3-text-slate-600" colspan="6">No hay comprobantes pendientes/error en el período.</td></tr>
              <?php else: ?>
                <?php foreach ($conc_comprobantes_not_issued['rows'] as $row): ?>
                  <tr class="av3-border-bottom">
                    <td class="av3-pad-2 av3-weight-medium">#<?= (int)$row['id'] ?></td>
                    <td class="av3-pad-2">#<?= (int)$row['order_id'] ?></td>
                    <td class="av3-pad-2"><?= e((string)$row['kind_label']) ?></td>
                    <td class="av3-pad-2"><?= e((string)$row['status_label']) ?></td>
                    <td class="av3-pad-2"><?= e((string)$row['total_label']) ?></td>
                    <td class="av3-pad-2">
                      <a class="underline" href="<?= e((string)$row['doc_url']) ?>">Abrir</a>
                      <a class="underline av3-ml-2" href="<?= e((string)$row['order_url']) ?>">Pedido</a>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>

  <?php else: ?>

    <div class="av3-grid av3-grid-cols-1 av3-md-grid-cols-3 av3-gap-4 av3-space-bottom-4">
      <?php foreach ($summary_cards as $card): ?>
        <div class="admin-surface av3-pad-4">
          <div class="av3-type-body av3-text-slate-600"><?= e((string)$card['title']) ?></div>
          <div class="av3-type-2xl av3-weight-strong av3-space-top-1">
            <?php if (!empty($card['count_url'])): ?>
              <a class="underline" href="<?= e((string)$card['count_url']) ?>"><?= (int)$card['count'] ?></a>
            <?php else: ?>
              <?= e((string)$card['total_label']) ?>
            <?php endif; ?>
          </div>
          <?php if (empty($card['is_net'])): ?>
            <div class="av3-type-body av3-space-top-2">Total: <span class="av3-weight-medium"><?= e((string)$card['total_label']) ?></span></div>
          <?php else: ?>
            <div class="av3-type-caption av3-text-slate-500 av3-space-top-2">(Facturas âˆ’ Notas de crédito)</div>
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>

    <div class="admin-surface av3-pad-4">
      <div class="av3-flex av3-items-center av3-justify-between av3-gap-3 av3-space-bottom-3">
        <h2 class="av3-type-base av3-weight-strong">Detalle por período</h2>
        <div class="av3-type-body av3-text-slate-600">Mostrando <span class="av3-weight-medium"><?= (int)$series_count ?></span> filas</div>
      </div>
      <div class="max-h-[520px] av3-overflow-auto av3-radius-sm av3-border av3-border-slate-200">
        <table class="av3-min-w-full av3-type-body">
          <thead class="av3-sticky top-0 av3-surface-white">
            <tr class="av3-text-left av3-border-bottom">
              <th class="av3-pad-2">Período</th>
              <th class="av3-pad-2">Facturas</th>
              <th class="av3-pad-2">Total facturado</th>
              <th class="av3-pad-2">NC</th>
              <th class="av3-pad-2">Total NC</th>
              <th class="av3-pad-2">Neto</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($series_rows)): ?>
              <tr><td class="av3-pad-3 av3-text-slate-600" colspan="6">No hay comprobantes emitidos en el período.</td></tr>
            <?php else: ?>
              <?php foreach ($series_rows as $row): ?>
                <tr class="av3-border-bottom">
                  <td class="av3-pad-2 av3-weight-medium"><a class="underline" href="<?= e((string)$row['period_url']) ?>"><?= e((string)$row['period']) ?></a></td>
                  <td class="av3-pad-2"><a class="underline" href="<?= e((string)$row['invoices_count_url']) ?>"><?= (int)$row['invoices_count'] ?></a></td>
                  <td class="av3-pad-2"><?= e((string)$row['invoices_total_label']) ?></td>
                  <td class="av3-pad-2"><a class="underline" href="<?= e((string)$row['credit_notes_count_url']) ?>"><?= (int)$row['credit_notes_count'] ?></a></td>
                  <td class="av3-pad-2"><?= e((string)$row['credit_notes_total_label']) ?></td>
                  <td class="av3-pad-2 av3-weight-medium"><?= e((string)$row['net_label']) ?></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="av3-type-caption av3-text-slate-500 av3-space-top-3">
        * Los totales se calculan desde <span class="av3-weight-medium">issued_at</span> y sólo incluyen comprobantes con estado <span class="av3-weight-medium">emitido</span>.
      </div>
    </div>

    <div class="admin-surface av3-pad-4 av3-space-top-4">
      <h2 class="av3-type-base av3-weight-strong av3-space-bottom-1">Pedidos con resultado fiscal relevante</h2>
      <p class="av3-type-body av3-text-slate-600 av3-space-bottom-3">
        Muestra pedidos donde el <span class="av3-weight-medium">resultado final en ARCA</span> es relevante para revisar:
        <span class="av3-weight-medium">anulaciones</span>, <span class="av3-weight-medium">montos distintos</span>, <span class="av3-weight-medium">duplicados</span> o <span class="av3-weight-medium">entorno mixto</span>.
      </p>
      <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
        <div class="av3-type-body av3-text-slate-600">Mostrando <span class="av3-weight-medium"><?= (int)$conc_deviations['showing_count'] ?></span> filas</div>
        <div class="av3-flex av3-items-center av3-gap-2">
          <a class="btn btn-secondary btn-sm" href="<?= e((string)$conc_deviations['view_all_url']) ?>">Ver todos</a>
          <a class="btn btn-secondary btn-sm" href="<?= e((string)$conc_deviations['export_url']) ?>">Exportar CSV</a>
        </div>
      </div>
      <div class="max-h-[520px] av3-overflow-auto av3-radius-sm av3-border av3-border-slate-200">
        <table class="av3-min-w-full av3-type-body">
          <thead class="av3-sticky top-0 av3-surface-white">
            <tr class="av3-text-left av3-border-bottom">
              <th class="av3-pad-2">Pedido</th>
              <th class="av3-pad-2">Última emisión</th>
              <th class="av3-pad-2">Estado pedido</th>
              <th class="av3-pad-2">Total pedido</th>
              <th class="av3-pad-2">Facturas</th>
              <th class="av3-pad-2">NC</th>
              <th class="av3-pad-2">Neto ARCA</th>
              <th class="av3-pad-2">Resultado fiscal</th>
              <th class="av3-pad-2">Entorno</th>
              <th class="av3-pad-2">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php if (empty($conc_deviations['rows'])): ?>
              <tr><td class="av3-pad-3 av3-text-slate-600" colspan="10">Sin casos relevantes en el período.</td></tr>
            <?php else: ?>
              <?php foreach ($conc_deviations['rows'] as $row): ?>
                <tr class="av3-border-bottom">
                  <td class="av3-pad-2"><a class="underline" href="<?= e((string)$row['order_url']) ?>">#<?= (int)$row['order_id'] ?></a></td>
                  <td class="av3-pad-2"><?= e((string)$row['last_issued_at']) ?></td>
                  <td class="av3-pad-2"><?= e((string)$row['order_status_label']) ?></td>
                  <td class="av3-pad-2"><?= e((string)$row['order_total_label']) ?></td>
                  <td class="av3-pad-2"><div class="av3-text-slate-900 av3-weight-medium"><?= (int)$row['invoices_count'] ?></div><div class="av3-type-caption av3-text-slate-500"><?= e((string)$row['invoices_total_label']) ?></div></td>
                  <td class="av3-pad-2"><div class="av3-text-slate-900 av3-weight-medium"><?= (int)$row['credit_count'] ?></div><div class="av3-type-caption av3-text-slate-500"><?= e((string)$row['credit_total_label']) ?></div></td>
                  <td class="av3-pad-2"><?= e((string)$row['net_issued_label']) ?></td>
                  <td class="av3-pad-2"><span class="av3-inline-flex av3-pad-x2 av3-pad-yhalf av3-radius-full av3-type-caption av3-border <?= e((string)$row['result_class']) ?>"><?= e((string)$row['result_label']) ?></span></td>
                  <td class="av3-pad-2"><?= e((string)$row['env_text']) ?></td>
                  <td class="av3-pad-2"><a class="underline" href="<?= e((string)$row['comprobantes_url']) ?>">Ver comprobantes</a></td>
                </tr>
              <?php endforeach; ?>
            <?php endif; ?>
          </tbody>
        </table>
      </div>
      <div class="av3-type-caption av3-text-slate-500 av3-space-top-3">
        * “Neto ARCA” = <span class="av3-weight-medium">facturas emitidas - NC emitidas</span> del pedido en el período (por <span class="av3-weight-medium">issued_at</span>).
      </div>
    </div>

    <div class="av3-grid av3-grid-cols-1 av3-lg-grid-cols-2 av3-gap-4 av3-space-top-4">
      <div class="admin-surface av3-pad-4">
        <h2 class="av3-type-base av3-weight-strong av3-space-bottom-1">Duplicados exactos (emitidos)</h2>
        <p class="av3-type-body av3-text-slate-600 av3-space-bottom-3">Mismo <span class="av3-weight-medium">PV + tipo + nro</span>. Sirve para detectar errores de numeración/duplicación.</p>
        <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
          <div class="av3-type-body av3-text-slate-600">Mostrando <span class="av3-weight-medium"><?= (int)$conc_duplicates_exact['showing_count'] ?></span> filas</div>
          <div class="av3-flex av3-items-center av3-gap-2">
            <a class="btn btn-secondary btn-sm" href="<?= e((string)$conc_duplicates_exact['view_all_url']) ?>">Ver todos</a>
            <a class="btn btn-secondary btn-sm" href="<?= e((string)$conc_duplicates_exact['export_url']) ?>">Exportar CSV</a>
          </div>
        </div>
        <div class="max-h-[420px] av3-overflow-auto av3-radius-sm av3-border av3-border-slate-200">
          <table class="av3-min-w-full av3-type-body">
            <thead class="av3-sticky top-0 av3-surface-white">
              <tr class="av3-text-left av3-border-bottom">
                <th class="av3-pad-2">Entorno</th>
                <th class="av3-pad-2">Tipo</th>
                <th class="av3-pad-2">PV</th>
                <th class="av3-pad-2">Cbte</th>
                <th class="av3-pad-2">Cantidad</th>
                <th class="av3-pad-2">Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($conc_duplicates_exact['rows'])): ?>
                <tr><td class="av3-pad-2 av3-text-slate-500" colspan="6">Sin duplicados exactos en el período.</td></tr>
              <?php else: ?>
                <?php foreach ($conc_duplicates_exact['rows'] as $row): ?>
                  <tr class="av3-border-bottom align-top">
                    <td class="av3-pad-2"><?= e((string)$row['env_text']) ?></td>
                    <td class="av3-pad-2"><?= e((string)$row['kind_text']) ?></td>
                    <td class="av3-pad-2"><?= (int)$row['pto'] ?></td>
                    <td class="av3-pad-2"><?= e((string)$row['cbte']) ?></td>
                    <td class="av3-pad-2 av3-weight-medium"><?= (int)$row['count'] ?></td>
                    <td class="av3-pad-2"><a class="underline" href="<?= e((string)$row['comprobantes_url']) ?>">Ver en comprobantes</a></td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>

      <div class="admin-surface av3-pad-4">
        <h2 class="av3-type-base av3-weight-strong av3-space-bottom-1">Pedidos no facturables con factura</h2>
        <p class="av3-type-body av3-text-slate-600 av3-space-bottom-3">Pedidos en estados no facturables que igual tienen <span class="av3-weight-medium">Factura emitida</span> en el período.</p>
        <div class="av3-flex av3-items-center av3-justify-between av3-space-bottom-3">
          <div class="av3-type-body av3-text-slate-600">Mostrando <span class="av3-weight-medium"><?= (int)$conc_non_billable_with_invoice['showing_count'] ?></span> filas</div>
          <div class="av3-flex av3-items-center av3-gap-2">
            <a class="btn btn-secondary btn-sm" href="<?= e((string)$conc_non_billable_with_invoice['view_all_url']) ?>">Ver todos</a>
            <a class="btn btn-secondary btn-sm" href="<?= e((string)$conc_non_billable_with_invoice['export_url']) ?>">Exportar CSV</a>
          </div>
        </div>
        <div class="max-h-[420px] av3-overflow-auto av3-radius-sm av3-border av3-border-slate-200">
          <table class="av3-min-w-full av3-type-body">
            <thead class="av3-sticky top-0 av3-surface-white">
              <tr class="av3-text-left av3-border-bottom">
                <th class="av3-pad-2">Pedido</th>
                <th class="av3-pad-2">Estado</th>
                <th class="av3-pad-2">Fecha emisión</th>
                <th class="av3-pad-2">Cbte</th>
                <th class="av3-pad-2">Acción</th>
              </tr>
            </thead>
            <tbody>
              <?php if (empty($conc_non_billable_with_invoice['rows'])): ?>
                <tr><td class="av3-pad-2 av3-text-slate-500" colspan="5">Sin casos en el período.</td></tr>
              <?php else: ?>
                <?php foreach ($conc_non_billable_with_invoice['rows'] as $row): ?>
                  <tr class="av3-border-bottom align-top">
                    <td class="av3-pad-2"><a class="underline" href="<?= e((string)$row['order_url']) ?>">#<?= (int)$row['order_id'] ?></a></td>
                    <td class="av3-pad-2"><?= e((string)$row['order_status_label']) ?></td>
                    <td class="av3-pad-2"><?= e((string)$row['issued_at']) ?></td>
                    <td class="av3-pad-2"><?= e((string)$row['cbte']) ?></td>
                    <td class="av3-pad-2">
                      <a class="underline" href="<?= e((string)$row['comprobantes_url']) ?>">Ver comprobantes</a>
                      <?php if ((string)$row['doc_url'] !== ''): ?>
                        <span class="av3-text-slate-400">·</span>
                        <a class="underline" href="<?= e((string)$row['doc_url']) ?>">Abrir factura</a>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              <?php endif; ?>
            </tbody>
          </table>
        </div>
      </div>
    </div>
  <?php endif; ?>
</div>

