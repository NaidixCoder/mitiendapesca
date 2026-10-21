<?php
$pageTitle = 'Detalle importacion';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('imports.view');
$show = is_array($show_payload ?? null) ? $show_payload : [];
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">

    <?php require BASE_PATH.'/app/Views/partials/admin/imports/view/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/imports/view/notices.php'; ?>

    <?php if (empty($show['job']) || !is_array($show['job'])): ?>
      <div class="av3-card">
        <p class="av3-type-body av3-text-var-muted">No se encontro la importacion.</p>
      </div>
    <?php else: ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/imports/view/summary.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/imports/view/applied_changes.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/imports/view/warnings.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/imports/view/errors.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/imports/view/rows.php'; ?>
    <?php endif; ?>

  </div>
</section>
