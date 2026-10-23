<?php
$vm = is_array($control_vm ?? null) ? $control_vm : [];
extract($vm, EXTR_SKIP);
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
<main class="av3-fiscal-page av3-fiscal-dashboard" aria-label="Panel de control fiscal">
  <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/header.php'; ?>
  <section class="av3-fiscal-overview" aria-label="Indicadores del período">
    <header class="av3-fiscal-section-heading">
      <h2>Resumen del período</h2>
      <span><?= e($currentPeriod) ?></span>
    </header>
    <dl class="av3-fiscal-metrics">
      <div class="av3-fiscal-metric <?= $totalAlerts > 0 ? 'is-warning' : 'is-ok' ?>">
        <dt>Alertas por revisar</dt>
        <dd><?= (int)$totalAlerts ?></dd>
        <small><?= $totalAlerts > 0 ? 'Requieren atención' : 'Sin pendientes detectados' ?></small>
      </div>
      <div class="av3-fiscal-metric">
        <dt>Compras sin validar</dt>
        <dd><?= (int)$cLoaded ?></dd>
        <small>Registros cargados</small>
      </div>
      <div class="av3-fiscal-metric">
        <dt>Sin comprobante adjunto</dt>
        <dd><?= (int)$cMissing ?></dd>
        <small>Documentación pendiente</small>
      </div>
      <div class="av3-fiscal-metric">
        <dt>Períodos sin cierre</dt>
        <dd><?= (int)$cUnclosed ?></dd>
        <small>Con movimientos registrados</small>
      </div>
    </dl>
  </section>
  <div class="av3-fiscal-workspace">
    <section class="av3-fiscal-main-column" aria-label="Actividad y cierres">
      <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/recommended_card.php'; ?>
      <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/closures_card.php'; ?>
      <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/operation_card.php'; ?>
    </section>
    <aside class="av3-fiscal-side-column" aria-label="Estado y accesos rápidos">
      <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/state_card.php'; ?>
      <?php require BASE_PATH . '/app/Views/partials/admin/facturacion/control/quick_links_card.php'; ?>
    </aside>
  </div>
</main>
