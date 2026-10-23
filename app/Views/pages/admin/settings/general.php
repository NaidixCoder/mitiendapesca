<?php
/** @var array $cfg */
$pageTitle = 'Ajustes · General';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('settings.section', ['name' => 'General']);
require BASE_PATH.'/app/Views/partials/admin/settings_subnav.php';
?>

<link rel="stylesheet" href="<?= e(url('/assets/css/admin-v3/pages/settings.css')) ?>">
<section class="admin-surface av3-settings-detail av3-settings-detail--general">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/settings/general/header.php'; ?>

    <nav class="av3-settings-detail__toc" aria-label="Ir a una sección">
      <a href="#ajustes-catalogo">Catálogo</a>
      <a href="#ajustes-contacto">Contacto y redes</a>
    </nav>
<form method="post" action="<?= e(url('/admin/settings?tab=general')) ?>" class="av3-settings-detail__form av3-flow-4">
      <input type="hidden" name="_csrf" value="<?= e(csrf_token()) ?>">

      <?php require BASE_PATH.'/app/Views/partials/admin/settings/general/catalog_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/general/social_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/settings/general/actions.php'; ?>
    </form>

  </div>
</section>
