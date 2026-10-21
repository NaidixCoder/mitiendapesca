<?php
$pageTitle = 'Importaciones';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('imports.index');
$importsIndexView = is_array($imports_index_view ?? null) ? $imports_index_view : [];
extract($importsIndexView, EXTR_SKIP);
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/imports/index/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/imports/index/flashes.php'; ?>

    <?php if (!empty($showPreflight)): ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/imports/index/preflight.php'; ?>
    <?php endif; ?>

    <?php if (!empty($showUploadAndHistory)): ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/imports/index/upload_form.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/imports/index/history.php'; ?>
    <?php endif; ?>

  </div>
</section>
