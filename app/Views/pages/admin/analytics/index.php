<?php
$pageTitle = 'Analíticas';
require BASE_PATH . '/app/Views/partials/admin/subnav.php';
?>
<section class="admin-surface">
  <main class="av3-analytics av3-ui-page av3-layout--operational" aria-labelledby="analytics-title">
    <?php
    $adminHeaderTitle='Analíticas';
    $adminHeaderDescription='Tráfico, comportamiento y conversión de tu tienda.';
    $adminHeaderBreadcrumbs=[['label'=>'Inicio','href'=>url('/admin')],['label'=>'Analíticas']];
    $adminHeaderActions='<a class="av3-analytics__back av3-ui-btn" href="'.url('/admin').'">Volver al dashboard</a>';
    require BASE_PATH.'/app/Views/partials/admin/page_header.php';
    ?>
    <?php require BASE_PATH . '/app/Views/partials/admin/analytics/overview.php'; ?>
  </main>
</section>
