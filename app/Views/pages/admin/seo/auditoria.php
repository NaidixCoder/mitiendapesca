<?php
$pageTitle = 'Auditoría SEO';
require BASE_PATH . '/app/Views/partials/admin/subnav.php';
?>
<section class="admin-surface">
  <div class="av3-wrap av3-seo-audit">
    <?php
      $adminHeaderTitle = 'Auditoría SEO';
      $adminHeaderBreadcrumbs = [['label'=>'Inicio','href'=>url('/admin')],['label'=>'SEO'],['label'=>'Auditoría']];
      $adminHeaderActions = '<a class="av3-ui-btn" href="'.url('/admin/products').'">Ir a productos</a>';
      require BASE_PATH . '/app/Views/partials/admin/page_header.php';
    ?>
    <?php $seoSummary = is_array($seo_summary ?? null) ? $seo_summary : []; ?>
    <section class="av3-seo-overview" aria-label="Estado SEO de productos activos">
      <div class="av3-seo-overview__heading">
        <h2>Estado del catálogo</h2>
        <span>Productos activos · Score interno</span>
      </div>
      <div class="av3-seo-overview__grid">
        <div class="av3-seo-metric av3-seo-metric--danger">
          <span>Prioridad alta</span>
          <strong><?= (int)($seoSummary['low'] ?? 0) ?></strong>
          <small>Score 0–64</small>
        </div>
        <div class="av3-seo-metric av3-seo-metric--warning">
          <span>Mejorables</span>
          <strong><?= (int)($seoSummary['medium'] ?? 0) ?></strong>
          <small>Score 65–84</small>
        </div>
        <div class="av3-seo-metric av3-seo-metric--success">
          <span>Buen score</span>
          <strong><?= (int)($seoSummary['good'] ?? 0) ?></strong>
          <small>Score 85–100</small>
        </div>
        <div class="av3-seo-metric">
          <span>Activos analizados</span>
          <strong><?= (int)($seoSummary['active'] ?? 0) ?></strong>
          <small>Catálogo publicado</small>
        </div>
      </div>
    </section>
    <?php require BASE_PATH . '/app/Views/partials/admin/seo/auditoria/filters.php'; ?>
    <?php require BASE_PATH . '/app/Views/partials/admin/seo/auditoria/results.php'; ?>
  </div>
</section>
