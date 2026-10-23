<?php
$pageTitle = 'Ajustes · Mayoristas';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$crumbs = admin_crumbs('settings.section', ['name' => 'Mayoristas']);
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';

$cfg = $cfg ?? [];
?>
<link rel="stylesheet" href="<?= e(url('/assets/css/admin-v3/pages/settings.css')) ?>">
<section class="admin-surface av3-settings-detail av3-settings-detail--mayoristas"><div class="av3-wrap av3-flow-4">
  <?php require BASE_PATH.'/app/Views/partials/admin/settings/mayoristas/header.php'; ?>

    <nav class="av3-settings-detail__toc" aria-label="Ir a una sección">
      <a href="#ajustes-mayoristas">Escalas y redondeo</a>
    </nav>
  <div class="av3-ui-card av3-settings-detail__panel">
    <div class="av3-ui-card-body">

      <?php require BASE_PATH.'/app/Views/partials/admin/settings/mayoristas/form.php'; ?>

    </div>
  </div>
</div>

</section>
