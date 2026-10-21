<?php
$pageTitle = 'Ajustes';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('settings.index');
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/settings/index/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/settings/index/summary.php'; ?>

  </div>
</section>
