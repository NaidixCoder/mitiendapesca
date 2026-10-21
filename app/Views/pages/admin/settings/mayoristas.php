<?php
$pageTitle = 'Ajustes · Mayoristas';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$crumbs = admin_crumbs('settings.section', ['name' => 'Mayoristas']);
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';

$cfg = $cfg ?? [];
?>
<div class="av3-wrap">
  <?php require BASE_PATH.'/app/Views/partials/admin/settings/mayoristas/header.php'; ?>
  <div class="card">
    <div class="card-body">

      <?php require BASE_PATH.'/app/Views/partials/admin/settings/mayoristas/form.php'; ?>

    </div>
  </div>
</div>
