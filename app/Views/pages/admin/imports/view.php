<?php
$pageTitle = 'Detalle de importación';
require BASE_PATH . '/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('imports.view');
$show = is_array($show_payload ?? null) ? $show_payload : [];
?>
<link rel="stylesheet" href="<?= e(asset('css/admin-v3/pages/imports.css')) ?>">
<section class="admin-surface">
  <div class="av3-wrap av3-imports av3-imports--show">
    <?php require BASE_PATH . '/app/Views/partials/admin/imports/view/header.php'; ?>
    <main class="av3-imports__layout" aria-label="Detalle de importación">
      <?php require BASE_PATH . '/app/Views/partials/admin/imports/view/notices.php'; ?>
      <?php if (empty($show['job']) || !is_array($show['job'])): ?>
        <div class="av3-ui-card av3-ui-empty">No se encontró la importación solicitada.</div>
      <?php else: ?>
        <?php require BASE_PATH . '/app/Views/partials/admin/imports/view/summary.php'; ?>
        <?php require BASE_PATH . '/app/Views/partials/admin/imports/view/applied_changes.php'; ?>
        <?php require BASE_PATH . '/app/Views/partials/admin/imports/view/warnings.php'; ?>
        <?php require BASE_PATH . '/app/Views/partials/admin/imports/view/errors.php'; ?>
        <?php require BASE_PATH . '/app/Views/partials/admin/imports/view/rows.php'; ?>
      <?php endif; ?>
    </main>
  </div>
</section>
