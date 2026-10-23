<?php
/** @var array $crumbs */
/** @var array $rows_prepared */
/** @var int $rows_count */
/** @var string $back_url */
/** @var string $export_url */
?>

<div class="av3-fiscal-page av3-max-w-6xl av3-mx-auto">
  <?php
ob_start(); ?>Duplicados exactos (emitidos)<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Mismo PV + tipo + nro dentro del período (por fecha de emisión).<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
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
            <th class="av3-pad-2">Entorno</th>
            <th class="av3-pad-2">Tipo</th>
            <th class="av3-pad-2">PV</th>
            <th class="av3-pad-2">Cbte</th>
            <th class="av3-pad-2">Cantidad</th>
            <th class="av3-pad-2">Primera emisión</th>
            <th class="av3-pad-2">Última emisión</th>
            <th class="av3-pad-2">Acción</th>
          </tr>
        </thead>
        <tbody>
          <?php if (empty($rows_prepared)): ?>
            <tr><td class="av3-pad-2 av3-text-slate-500" colspan="8">Sin duplicados exactos en el período.</td></tr>
          <?php else: ?>
            <?php foreach ($rows_prepared as $row): ?>
              <tr class="av3-border-bottom align-top">
                <td class="av3-pad-2"><?= e((string)$row['env_text']) ?></td>
                <td class="av3-pad-2"><?= e((string)$row['kind_text']) ?></td>
                <td class="av3-pad-2"><?= (int)$row['pto'] ?></td>
                <td class="av3-pad-2"><?= e((string)$row['cbte']) ?></td>
                <td class="av3-pad-2 av3-weight-medium"><?= (int)$row['count'] ?></td>
                <td class="av3-pad-2"><?= e((string)$row['first_issued_at']) ?></td>
                <td class="av3-pad-2"><?= e((string)$row['last_issued_at']) ?></td>
                <td class="av3-pad-2"><a class="underline" href="<?= e((string)$row['comprobantes_url']) ?>">Ver en comprobantes</a></td>
              </tr>
            <?php endforeach; ?>
          <?php endif; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

