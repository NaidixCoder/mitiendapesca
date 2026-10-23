<?php
$u=is_array($show['user']??null)?$show['user']:[];
$uid=(int)($u['id']??0);
$adminHeaderTitle='Usuario #'.$uid;
$adminHeaderDescription='';
$adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Usuarios','href'=>url('/admin/users')],['label'=>'Usuario #'.$uid]];
$adminHeaderActions='<a class="av3-ui-btn" href="'.e(url('/admin/users')).'">Volver a usuarios</a>';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
