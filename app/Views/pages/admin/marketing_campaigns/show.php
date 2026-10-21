<?php
$showView = is_array($show_view ?? null) ? $show_view : [];
$campaign = is_array($showView['campaign'] ?? null) ? $showView['campaign'] : [];
$report = is_array($showView['report'] ?? null) ? $showView['report'] : [];
$series = is_array($showView['series'] ?? null) ? $showView['series'] : [];
$recentHits = is_array($showView['recentHits'] ?? null) ? $showView['recentHits'] : [];
$recentOrders = is_array($showView['recentOrders'] ?? null) ? $showView['recentOrders'] : [];
$topPages = is_array($showView['topPages'] ?? null) ? $showView['topPages'] : [];
$topProductsViewed = is_array($showView['topProductsViewed'] ?? null) ? $showView['topProductsViewed'] : [];
$topProductsAdded = is_array($showView['topProductsAdded'] ?? null) ? $showView['topProductsAdded'] : [];
$links = is_array($showView['links'] ?? null) ? $showView['links'] : [];
$days = (int)($showView['days'] ?? 30);
$pageTitle = 'Detalle de campaña';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
?>
<section class="admin-surface">
  <div class="av3-wrap av3-marketing-campaigns av3-marketing-detail" data-marketing-detail>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_campaigns/show/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_campaigns/show/link_card.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_campaigns/show/performance_card.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_campaigns/show/diagnostics_card.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_campaigns/show/recent_card.php'; ?>
  </div>
</section>
