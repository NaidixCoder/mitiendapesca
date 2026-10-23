<?php
$pageTitle = $pageTitle ?? 'Usuario';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$show = is_array($show_payload ?? null) ? $show_payload : [];
?>
<style><?= file_get_contents(BASE_PATH.'/public_html/assets/css/admin-v3/pages/users.css') ?></style>
<section class="admin-surface av3-users">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/users/show/header.php'; ?>

    <div class="av3-users__details">
      <?php require BASE_PATH.'/app/Views/partials/admin/users/show/overview.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/users/show/profile.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/users/show/addresses.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/users/show/actions.php'; ?>
    </div>

    <?php require BASE_PATH.'/app/Views/partials/admin/users/index/flashes.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/users/show/orders.php'; ?>

  </div>
</section>
