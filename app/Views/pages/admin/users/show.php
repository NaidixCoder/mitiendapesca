<?php
$pageTitle = $pageTitle ?? 'Usuario';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$show = is_array($show_payload ?? null) ? $show_payload : [];
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/users/show/header.php'; ?>

    <div class="av3-grid av3-gap-4 av3-md-grid-cols-3">
      <?php require BASE_PATH.'/app/Views/partials/admin/users/show/overview.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/users/show/profile.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/users/show/addresses.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/users/show/actions.php'; ?>
    </div>

    <?php require BASE_PATH.'/app/Views/partials/admin/users/show/orders.php'; ?>

  </div>
</section>
