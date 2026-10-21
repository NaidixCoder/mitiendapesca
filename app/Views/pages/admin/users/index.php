<?php
$pageTitle = 'Usuarios';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
/** @var array $rows */
$rows = $rows ?? [];
$q = trim((string)($q ?? ''));
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/users/index/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/users/index/flashes.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/users/index/table.php'; ?>

  </div>
</section>
