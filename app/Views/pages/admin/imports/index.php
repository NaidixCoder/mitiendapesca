<?php
$pageTitle = 'Importaciones';
require BASE_PATH . '/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('imports.index');
$importsIndexView = is_array($imports_index_view ?? null) ? $imports_index_view : [];
extract($importsIndexView, EXTR_SKIP);
?>
<link rel="stylesheet" href="<?= e(asset('css/admin-v3/pages/imports.css')) ?>">
<section class="admin-surface">
  <div class="av3-wrap av3-imports av3-imports--index">
    <?php require BASE_PATH . '/app/Views/partials/admin/imports/index/header.php'; ?>
    <?php require BASE_PATH . '/app/Views/partials/admin/imports/index/flashes.php'; ?>
    <main class="av3-imports__layout" aria-label="Administración de importaciones">
      <?php if (!empty($showPreflight)): ?>
        <?php require BASE_PATH . '/app/Views/partials/admin/imports/index/preflight.php'; ?>
      <?php endif; ?>
      <?php if (!empty($showUploadAndHistory)): ?>
        <?php require BASE_PATH . '/app/Views/partials/admin/imports/index/upload_form.php'; ?>
        <?php require BASE_PATH . '/app/Views/partials/admin/imports/index/history.php'; ?>
      <?php endif; ?>
    </main>
  </div>
</section>
