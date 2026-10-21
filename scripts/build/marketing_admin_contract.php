<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$read = static fn(string $f): string => (string)file_get_contents($root.'/'.$f);
$checks = [
 'list_page' => ['app/Views/pages/admin/marketing_campaigns/index.php', ['av3-marketing-campaigns','index/filters.php','index/cards.php']],
 'table' => ['app/Views/partials/admin/marketing_campaigns/index/cards.php', ['av3-marketing-table','av3-marketing-mobile-list','campaigns_prepared','campaign_pagination','data-copy-text','/admin/marketing/campanas/toggle']],
 'filter' => ['app/Views/partials/admin/marketing_campaigns/index/filters.php', ['campaign_tabs','name="q"','name="per"','name="days"','av3-marketing-tabs']],
 'model' => ['app/Services/Admin/MarketingCampaigns/ViewModels/MarketingCampaignsIndexViewModel.php', ['campaigns_prepared','campaign_range','campaign_tabs','campaign_pagination']],
 'repo' => ['app/Repositories/Marketing/MarketingCampaignRepository.php', ['countForAdmin','LIMIT {$limit} OFFSET {$offset}',':q_name',':q_slug',':q_destination']],
 'queue' => ['app/Repositories/Marketing/EmailQueueRepository.php', ['cancelClaimed','cancelCampaignQueued',':q_email',':q_subject',':q_template']],
 'broadcast' => ['app/Services/Marketing/EmailBroadcastSchedulerService.php', ['NOT EXISTS','queued.subscriber_id=s.id','shouldFinalize($campaignId, $c)']],
 'worker' => ['app/Services/Marketing/EmailQueueWorkerService.php', ['isSubscribedById','maySend','cancelClaimed']],
 'iframe' => ['app/Views/partials/admin/marketing_email/templates_show/preview.php', ['sandbox=""']],
 'styles' => ['public_html/assets/css/admin-v3/pages/marketing-campaigns.css', ['@container (min-width:980px)','av3-marketing-mobile-item','av3-marketing-tabs','av3-marketing-table']],
 'head' => ['app/Views/partials/head.php', ['css/admin-v3/pages/marketing-campaigns.css']],
 'editor' => ['app/Views/pages/admin/marketing_campaigns/form.php', ['av3-marketing-editor','data-marketing-campaign-form','name="id"','name="name"','name="slug"','name="destination_path"','name="utm_source"','name="utm_medium"','name="utm_campaign"','name="utm_content"','name="utm_term"','name="notes"','name="is_active"','csrf_field()']],
 'show' => ['app/Views/pages/admin/marketing_campaigns/show.php', ['av3-marketing-detail', 'show/link_card.php','show/performance_card.php','show/diagnostics_card.php','show/recent_card.php']],
 'show_links' => ['app/Views/partials/admin/marketing_campaigns/show/link_card.php', ['data-copy-text','qr_open','qr_download','promo_url','destination_label']],
 'show_performance' => ['app/Views/partials/admin/marketing_campaigns/show/performance_card.php', ['av3-marketing-detail__metrics','av3-marketing-detail__series','ratio_purchases_fmt','revenue_fmt']],
 'show_diagnostics' => ['app/Views/partials/admin/marketing_campaigns/show/diagnostics_card.php', ['topPages','topProductsViewed','topProductsAdded']],
 'show_recent' => ['app/Views/partials/admin/marketing_campaigns/show/recent_card.php', ['recentHits','recentOrders']],
 'show_vm' => ['app/Services/Admin/MarketingCampaigns/ViewModels/MarketingCampaignsShowViewModel.php', ['referrerUi','top_products_viewed','recent_orders']],
 'js_preview' => ['public_html/assets/js/admin/marketing/campaigns.js', ['data-marketing-campaign-form','data-marketing-preview-url','bindCampaignPreview']],

];
foreach ($checks as $name => [$file,$required]) {
 $text=$read($file);
 if (!$text) throw new RuntimeException("Archivo ausente: $file");
 foreach ($required as $needle) if (!str_contains($text, $needle)) throw new RuntimeException("$name incompleto: $needle");
}
$listing=$read('app/Views/partials/admin/marketing_campaigns/index/cards.php');
if (str_contains($listing, 'api.qrserver.com') || str_contains($listing,'av3-marketing-more')) throw new RuntimeException('QR duplicado o desplegable obsoleto');
foreach (['app/Views/pages/admin/marketing_campaigns/form.php','app/Views/partials/admin/marketing_campaigns/show/header.php','app/Views/partials/admin/marketing_campaigns/show/link_card.php','app/Views/partials/admin/marketing_campaigns/show/performance_card.php','app/Views/partials/admin/marketing_campaigns/show/diagnostics_card.php','app/Views/partials/admin/marketing_campaigns/show/recent_card.php'] as $f) {
  $content = $read($f);
  if (preg_match('/class="(?:btn|input|label)(?: |")|hover:underline|max-h-\\[/', $content)) throw new RuntimeException('Clases legacy en: '.$f);
}
echo '[marketing-contract] OK: campañas responsive, filtros, paginación, envío y CSS único'."\n";
