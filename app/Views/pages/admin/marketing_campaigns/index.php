<?php
$pageTitle = 'Marketing';
require BASE_PATH.'/app/Views/partials/admin/subnav.php';
$crumbs = admin_crumbs('marketing.campaigns.index');
$q = (string)($q ?? '');
$days = (int)($days ?? 30);
$status = (string)($campaign_status ?? '');
$per = (int)($campaign_per ?? 25);
?>
<section class="admin-surface">
  <div class="av3-wrap av3-marketing-campaigns" data-marketing-list>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_campaigns/index/header.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_subnav.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_campaigns/index/filters.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_campaigns/index/attribution.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_campaigns/index/flashes.php'; ?>
    <?php require BASE_PATH.'/app/Views/partials/admin/marketing_campaigns/index/cards.php'; ?>
  </div>
</section>
