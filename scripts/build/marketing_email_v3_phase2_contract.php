<?php
declare(strict_types=1);
$base=dirname(__DIR__,2);
$checks=[
 'app/Views/pages/admin/marketing_email/campaigns.php'=>['data-email-campaigns','av3-email-table','av3-email-mobile-list','marketing_email_pagination.php','status_tone'],
 'app/Views/pages/admin/marketing_email/queue.php'=>['data-email-queue','Procesamiento manual','csrf_field()','av3-email-queue-overview','marketing_email_pagination.php','result_tone'],
 'app/Views/pages/admin/marketing_email/templates_index.php'=>['data-email-templates','marketing_email_template_actions.php','marketing_email_pagination.php'],
 'app/Views/partials/admin/marketing_email_template_actions.php'=>['data-confirm-delete-template','csrf_field()','name="id"'],
 'app/Views/pages/admin/marketing_email/templates_show.php'=>['data-email-template-detail','templates_show/'],
 'app/Views/partials/admin/marketing_email/templates_show/preview.php'=>['sandbox=""','srcdoc','referrerpolicy="no-referrer"'],
 'app/Views/partials/admin/marketing_email_pagination.php'=>['aria-current="page"','pagination'],
 'app/Services/Admin/MarketingEmail/ViewModels/MarketingEmailPagination.php'=>['range_label','pagination'],
 'app/Repositories/Marketing/EmailCampaignRepository.php'=>['countForAdmin','OFFSET'],
 'app/Repositories/Marketing/EmailQueueRepository.php'=>['countForAdmin','OFFSET'],
 'app/Repositories/Marketing/EmailTemplateRepository.php'=>['countForAdmin','OFFSET'],
 'app/Controllers/Admin/EmailCampaignsController.php'=>['MarketingEmailPagination::build','listWithStats($per,'],
 'app/Controllers/Admin/EmailMarketingController.php'=>['listQueue($q, $status, $per,'],
 'app/Controllers/Admin/EmailTemplatesController.php'=>["$".'vm'."['templates_total']", 'MarketingEmailPagination::build'],
 'public_html/assets/css/admin-v3/pages/marketing-email.css'=>['av3-email-template-detail','av3-email-queue-overview','av3-email-page-size'],
];
foreach($checks as $path=>$needles){
 $text=@file_get_contents($base.'/'.$path);
 if(!is_string($text))throw new RuntimeException('Falta archivo: '.$path);
 foreach($needles as $needle)if(!str_contains($text,$needle))throw new RuntimeException($path.': falta '.$needle);
}
foreach(['campaigns','queue','templates_index','templates_show'] as $view){
 $src=file_get_contents($base.'/app/Views/pages/admin/marketing_email/'.$view.'.php');
 if(preg_match('/max-h-\[|av3-max-w-6xl|class="btn-primary/', $src))
  throw new RuntimeException('Legacy en '.$view);
}
if(str_contains(file_get_contents($base.'/app/Views/partials/admin/marketing_email/templates_show/preview.php'),'allow-scripts'))
 throw new RuntimeException('Vista previa de plantilla no debe ejecutar scripts');
echo "[email-v3-phase2-contract] OK: envíos, cola, plantillas, paginación y sandbox\n";
