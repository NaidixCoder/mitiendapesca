<?php
$pageTitle = 'Auditoria';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$loginsView = is_array($audit_logins_view ?? null) ? $audit_logins_view : [];
extract($loginsView, EXTR_SKIP);
?>
<section class="admin-surface">
  <div class="av3-wrap av3-flow-4">
    <?php require BASE_PATH.'/app/Views/partials/admin/audit/logins/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/audit/logins/filters.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/audit/logins/table.php'; ?>
  </div>
</section>

