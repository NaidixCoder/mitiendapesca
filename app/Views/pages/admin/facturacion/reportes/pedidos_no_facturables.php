<?php
/** @var array $crumbs */
/** @var array $rows_prepared */
/** @var int $rows_count */
/** @var string $back_url */
/** @var string $export_url */
?>

<div class="av3-max-w-6xl av3-mx-auto">
  <?php
ob_start(); ?>Pedidos no facturables con factura<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Pedidos en estados no facturables con Factura emitida en el período (por issued_at).<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
    
    <div class="av3-flex av3-items-center av3-gap-2">
      <a class="btn btn-secondary" href="<?= e($back_url) ?>">Volver</a>
      <a class="btn btn-secondary" href="<?= e($export_url) ?>">Exportar CSV</a>
    </div>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>'Reportes','href'=>url('/admin/facturacion/reportes')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

  <div class="admin-surface av3-pad-4">
    <div class="av3-type-body av3-text-slate-600 av3-space-bottom-3">Mostrando <span class="av3-weight-medium"><?= (int)$rows_count ?></span> filas (máx 200).</div>

    <div class="max-h-[640px] av3-overflow-auto av3-radius-sm av3-border av3-border-slate-200">
      <table class="av3-min-w-full av3-type-body">
        <thead class="av3-sticky top-0 av3-surface-white">
          <tr class="av3-text-left av3-border-bottom">
            <th class="av3-pad-2">Pedido</th>
            <th class="av3-pad-2">Estado</th>
            <th class="av3-pad-2">Creado</th>
            <th class="av3-pad-2">Emisión</th>
            <th class="av3-pad-2">Cbte</th>
            <th class="av3-pad-2">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows_prepared)): ?>
            <tr><td class="av3-pad-2 av3-text-slate-500" colspan="6">Sin casos en el período.</td></tr>
          <?php else: ?>
            <?php foreach ($rows_prepared as $row): ?>
              <tr class="av3-border-bottom align-top">
                <td class="av3-pad-2"><a class="underline" href="<?= e((string)$row['order_url']) ?>">#<?= (int)$row['order_id'] ?></a></td>
                <td class="av3-pad-2"><?= e((string)$row['order_status_label']) ?></td>
                <td class="av3-pad-2"><?= e((string)$row['created_at']) ?></td>
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

