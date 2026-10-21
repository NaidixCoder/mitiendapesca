<?php
$pageTitle = 'Ajustes · Pagos';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$crumbs = admin_crumbs('settings.section', ['name' => 'Pagos']);
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';

$cfg = $cfg ?? [];
?>

<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/settings/pagos/header.php'; ?>

    <form method="post" action="<?= e(url('/admin/settings?tab=pagos')) ?>" class="av3-card av3-flow-4">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">
      <input type="hidden" name="tab" value="pagos">

      <?php require BASE_PATH.'/app/Views/partials/admin/settings/pagos/commercial_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/pagos/actions.php'; ?>
    </form>

  </div>
</section>
