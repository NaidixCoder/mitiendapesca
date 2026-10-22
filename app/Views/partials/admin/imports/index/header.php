<?php
$adminHeaderTitle = 'Importaciones';
$adminHeaderDescription = 'Cargá un CSV, revisá los cambios y confirmá antes de aplicarlos.';
$adminHeaderActions = '';
$adminHeaderBreadcrumbs = (isset($crumbs) && is_array($crumbs) && $crumbs)
    ? $crumbs
    : [['label' => 'Inicio', 'href' => url('/admin')], ['label' => 'Importaciones']];
require BASE_PATH . '/app/Views/partials/admin/page_header.php';
?>
