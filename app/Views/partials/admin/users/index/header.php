<?php
$adminHeaderTitle='Usuarios';
$adminHeaderDescription='';
$adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Usuarios']];
$adminHeaderActions='';
require BASE_PATH.'/app/Views/partials/admin/page_header.php';
?>
<form class="av3-ui-card av3-ui-card-body av3-users__toolbar" method="get" action="<?= e(url('/admin/users')) ?>">
  <label class="av3-ui-field" for="users-q"><span class="av3-ui-field-label">Nombre o email</span><input class="av3-ui-control" type="search" id="users-q" name="q" value="<?= e($q) ?>" maxlength="120" placeholder="Buscar usuarios"></label>
  <button type="submit" class="av3-ui-btn av3-ui-btn--primary">Buscar</button>
  <a class="av3-ui-btn" href="<?= e(url('/admin/users')) ?>">Limpiar</a>
</form>
