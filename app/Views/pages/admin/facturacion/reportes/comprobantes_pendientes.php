<?php
/** @var array $crumbs */
/** @var array $rows_prepared */
/** @var int $rows_count */
/** @var string $back_url */
/** @var string $export_url */
/** @var string $open_in_docs_url */
?>

<div class="av3-max-w-6xl av3-mx-auto">
  <?php
ob_start(); ?>Comprobantes en pendiente / error<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Comprobantes creados en el período con estado pendiente o error.<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
    
    <div class="av3-flex av3-items-center av3-gap-2">
      <a class="btn btn-secondary" href="<?= e($back_url) ?>">Volver a reportes</a>
      <a class="btn btn-secondary" href="<?= e($export_url) ?>">Exportar CSV</a>
      <a class="btn btn-secondary" href="<?= e($open_in_docs_url) ?>">Abrir en Comprobantes</a>
    </div>
  <?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Facturación','href'=>url('/admin/facturacion/control')],['label'=>'Reportes','href'=>url('/admin/facturacion/reportes')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
render_partial('admin/facturacion_nav');
?>

  <div class="admin-surface av3-pad-4">
    <div class="av3-flex av3-items-center av3-justify-between av3-gap-3 av3-space-bottom-3">
      <div class="av3-type-body av3-text-slate-600">Mostrando <span class="av3-weight-medium"><?= (int)$rows_count ?></span> filas</div>
    </div>

    <div class="max-h-[620px] av3-overflow-auto av3-radius-sm av3-border av3-border-slate-200">
      <table class="av3-min-w-full av3-type-body">
        <thead class="av3-sticky top-0 av3-surface-white">
          <tr class="av3-text-left av3-border-bottom">
            <th class="av3-pad-2">ID</th>
            <th class="av3-pad-2">Pedido</th>
            <th class="av3-pad-2">Tipo</th>
            <th class="av3-pad-2">Estado</th>
            <th class="av3-pad-2">Entorno</th>
            <th class="av3-pad-2">Total</th>
            <th class="av3-pad-2">Error</th>
            <th class="av3-pad-2">Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows_prepared)): ?>
            <tr><td class="av3-pad-3 av3-text-slate-600" colspan="8">No hay comprobantes pendientes/error en el período.</td></tr>
          <?php else: ?>
            <?php foreach ($rows_prepared as $row): ?>
              <tr class="av3-border-bottom">
                <td class="av3-pad-2 av3-weight-medium">#<?= (int)$row['id'] ?></td>
                <td class="av3-pad-2">#<?= (int)$row['order_id'] ?></td>
                <td class="av3-pad-2"><?= e((string)$row['kind_label']) ?></td>
                <td class="av3-pad-2"><?= e((string)$row['status_label']) ?></td>
                <td class="av3-pad-2"><?= e((string)$row['arca_env']) ?></td>
                <td class="av3-pad-2"><?= e((string)$row['total_label']) ?></td>
                <td class="av3-pad-2 av3-text-slate-600"><?= e((string)$row['error_text']) ?></td>
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

