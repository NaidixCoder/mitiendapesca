<?php
ob_start(); ?>Ajustes � Fiscal<?php $adminHeaderTitle=trim(strip_tags(ob_get_clean()));
ob_start(); ?>Configura el regimen del vendedor. Se usa para guardar <strong>snapshots contables</strong> en cada pedido (neto/IVA/total).<?php $adminHeaderDescription=trim(strip_tags(ob_get_clean()));
$adminHeaderActions='';
$adminHeaderBreadcrumbs=(isset($crumbs) && is_array($crumbs) && $crumbs) ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Ajustes','href'=>url('/admin/settings')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
