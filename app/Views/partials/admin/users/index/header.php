<?php
ob_start(); ?>Usuarios<?php $adminHeaderTitle=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>Total: <?= (int)count($rows) ?><?php $adminHeaderDescription=trim(html_entity_decode(strip_tags(ob_get_clean()),ENT_QUOTES|ENT_HTML5,'UTF-8'));
ob_start(); ?>
  
  <form class="av3-flex av3-items-center av3-gap-2" method="get" action="<?= url('/admin/users') ?>">
    <input class="input av3-w-72" type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nombre o email">
    <button class="btn-secondary">Buscar</button>
    <a class="btn-secondary" href="<?= url('/admin/users') ?>">Limpiar</a>
  </form>
<?php $adminHeaderActions=trim(ob_get_clean());
$adminHeaderBreadcrumbs=(isset($crumbs)&&is_array($crumbs)&&$crumbs)?$crumbs:[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Usuarios','href'=>url('/admin/users')],['label'=>$adminHeaderTitle]];
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
