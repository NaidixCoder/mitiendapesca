<?php
ob_start(); ?>Importaciones CSV<?php $adminHeaderTitle=trim(strip_tags(ob_get_clean()));
ob_start(); ?>
      Flujo seguro en 2 pasos: <b>Vista previa</b> (sin tocar la base) ? <b>Aplicar importaci�n</b>.
      Si la vista previa detecta errores, <b>no se aplica nada</b>.
    <?php $adminHeaderDescription=trim(strip_tags(ob_get_clean()));
$adminHeaderActions='';
$adminHeaderBreadcrumbs=(isset($crumbs) && is_array($crumbs) && $crumbs) ? $crumbs : [['label'=>'Inicio','href'=>url('/admin')],['label'=>'Importaciones','href'=>url('/admin/imports')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
