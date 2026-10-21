<?php
$job = is_array($show['job'] ?? null) ? $show['job'] : [];
$canRollback = !empty($show['can_rollback']);
?>
<?php
ob_start(); ?>Detalle de importacion<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Resultados y errores por fila (si los hay).<?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
  
  <div class="av3-actions">
    <a class="btn-secondary" href="<?= url('/admin/imports') ?>">Volver</a>
    <a class="btn-primary" href="<?= url('/admin/imports') ?>">Nueva importacion</a>
    <?php if ($canRollback): ?>
      <form method="post" action="<?= url('/admin/imports/rollback') ?>" class="av3-inline">
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= (int)($job['id'] ?? 0) ?>">
        <button type="submit" class="btn-danger" data-confirm="Revertir esta importacion? Se restauraran los valores anteriores guardados en el punto de restauracion.">Revertir importacion</button>
      </form>
    <?php endif; ?>
  </div>
<?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Catálogo','href'=>url('/admin/products')],['label'=>'Importaciones','href'=>url('/admin/imports')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
