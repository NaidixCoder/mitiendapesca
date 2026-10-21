<?php
$vm = is_array($control_vm ?? null) ? $control_vm : [];
extract($vm, EXTR_SKIP);

// Compat bridge for existing partials (camelCase vars expected there).
$cLoaded = (int)($loaded ?? 0);
$cMissing = (int)($missing ?? 0);
$cUnclosed = (int)($unclosed ?? 0);
$totalAlerts = (int)($total_alerts ?? 0);
$currentPeriod = (string)($current_period ?? date('Y-m'));
$lastClosure = (string)($last_closure ?? '');
$periodState = (string)($period_state ?? ($totalAlerts > 0 ? 'Con alertas' : 'Operativo'));
$periodPillLabel = (string)($period_pill_label ?? ($totalAlerts > 0 ? 'Periodo con alertas' : 'Periodo estable'));
$periodPillDotClass = (string)($period_pill_dot_class ?? ($totalAlerts > 0 ? 'is-warning' : 'is-ok'));
$quickLinks = is_array($quick_links ?? null) ? $quick_links : [];
$latestClosures = is_array($latest_closures ?? null) ? $latest_closures : [];
$crumbs = is_array($crumbs ?? null) ? $crumbs : admin_crumbs('facturacion.control');
?>

<?php render_partial('admin/subnav'); ?>
<section class="fisc-wrap fisc-control">
  <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/header.php'; ?>

  <div class="fisc-grid-main">
    <div class="fisc-col">
      <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/recommended_card.php'; ?>
      <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/closures_card.php'; ?>
      <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/operation_card.php'; ?>
    </div>

    <aside class="fisc-col">
      <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/state_card.php'; ?>
      <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/quick_links_card.php'; ?>
    </aside>
  </div>
</section>
