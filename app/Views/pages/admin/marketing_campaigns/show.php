<?php
$showView = is_array($show_view ?? null) ? $show_view : [];
extract($showView, EXTR_SKIP);
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
