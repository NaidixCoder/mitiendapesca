<?php
$adminHeaderTitle='Precios mayoristas';
$adminHeaderDescription='Configuración de condiciones y precios mayoristas.';
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Ajustes','href'=>url('/admin/settings')],['label'=>'Mayoristas']];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
