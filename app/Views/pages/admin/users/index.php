<?php
$pageTitle = 'Usuarios';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
/** @var array $rows */
$rows = $rows ?? [];
$q = trim((string)($q ?? ''));
?>
<style><?= file_get_contents(BASE_PATH.'/public_html/assets/css/admin-v3/pages/users.css') ?></style>
<section class="admin-surface av3-users">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/users/index/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/users/index/flashes.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/users/index/table.php'; ?>

  </div>
</section>
