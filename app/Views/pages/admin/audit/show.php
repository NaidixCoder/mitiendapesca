<?php
$pageTitle = 'Auditoria · Detalles';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';

$auditShowView = is_array($audit_show_view ?? null) ? $audit_show_view : [];
extract($auditShowView, EXTR_SKIP);
?>

<section class="admin-surface">
  <div class="av3-wrap">
    <div class="audit-show" style="max-width: 980px; margin: 0 auto;">
      <?php
      $adminHeaderTitle='Detalle de auditoría';
      $adminHeaderDescription='';
      $adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Auditoría','href'=>url('/admin/audit')],['label'=>'Detalle']];
      $adminHeaderActions='<a class="btn btn--ghost" href="'.e((string)($backUrl ?? base_url('/admin/audit'))).'">Volver a auditoría</a>';
      require BASE_PATH.'/app/Views/partials/admin/page_header.php';
      ?>

      <?php require BASE_PATH.'/app/Views/partials/admin/audit/show/header_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/audit/show/snapshot_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/audit/show/receipt_card.php'; ?>
      <?php require BASE_PATH.'/app/Views/partials/admin/audit/show/details_card.php'; ?>
    </div>
  </div>
</section>
