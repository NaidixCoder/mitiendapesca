<?php
/** @var array $cfg */
$pageTitle = 'Ajustes · General';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('settings.section', ['name' => 'General']);
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';
?>

<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/settings/general/header.php'; ?>

    <form method="post" action="<?= e(url('/admin/settings?tab=general')) ?>" class="av3-card av3-flow-4">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

      <?php require BASE_PATH.'/app/Views/partials/admin/settings/general/catalog_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/general/social_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/general/actions.php'; ?>
    </form>

  </div>
</section>
