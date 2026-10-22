<?php
$job = is_array($show['job'] ?? null) ? $show['job'] : [];
$canRollback = !empty($show['can_rollback']);
$adminHeaderTitle = 'Detalle de importación';
$adminHeaderDescription = 'Resultado de la ejecución, diferencias y estado de reversión.';
ob_start();
?>
<div class="av3-ui-actions av3-imports__header-actions">
  <a class="av3-ui-btn" href="<?= e(url('/admin/imports')) ?>">Volver a importaciones</a>
</div>
<?php
$adminHeaderActions = trim(ob_get_clean());
$adminHeaderBreadcrumbs = (isset($crumbs) && is_array($crumbs) && $crumbs)
    ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Importaciones','href'=>url('/admin/imports')],['label'=>'Detalle']];
require BASE_PATH . '/app/Views/partials/admin/page_header.php';
?>
