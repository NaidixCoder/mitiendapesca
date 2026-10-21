<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$requirements=[
 'app/Controllers/Admin/EmailCampaignsController.php'=>[
     "'pageId' => 'admin-email-campaign-show'",'canEdit($id)','scheduleNowIfDraft($id)',
     'cancelIfActive($id)','validateCampaignReady($data)','$repo->update($id, $data)'
 ],
 'app/Controllers/Admin/EmailTemplatesController.php'=>[
     'canEdit($campaignId)',"if (!\$campRepo->update(\$campaignId, \$data))"
 ],
 'app/Repositories/Marketing/EmailCampaignRepository.php'=>[
     'function canEdit(', 'function update(', 'function scheduleNowIfDraft(', 'function cancelIfActive(',
     "AND status IN ('draft','scheduled')",'NOT EXISTS ('
 ],
 'app/Services/Admin/MarketingEmail/ViewModels/MarketingEmailCampaignShowViewModel.php'=>[
     "'can_edit'", "'can_schedule_now' => \$status === 'draft'"
 ],
 'app/Views/pages/admin/marketing_email/campaign_show.php'=>[
     "if (!empty(\$campaign['can_edit']))",'data-confirm-schedule-email','data-confirm-cancel-email'
 ],
 'app/Services/Admin/MarketingEmail/ViewModels/MarketingEmailCampaignFormViewModel.php'=>[
     "'draft'", "'scheduled'"
 ],
 'public_html/assets/js/admin/marketing/campaign_show.js'=>[
     "window.confirm(message)",'data-confirm-schedule-email','data-confirm-cancel-email'
 ],
 'scripts/dev/marketing_email_campaign_lifecycle_smoke.php'=>[
     'beginTransaction()','rollBack()','scheduleNowIfDraft','cancelIfActive'
 ]
];
foreach($requirements as $file=>$tokens){
 $text=@file_get_contents($root.'/'.$file);
 if(!is_string($text))throw new RuntimeException('Falta: '.$file);
 foreach($tokens as $token){
    if(!str_contains($text,str_replace('\\$','$',$token)))throw new RuntimeException('Contrato '. $file . ': falta '. $token);
 }
}
$opts=(string)file_get_contents($root.'/app/Services/Admin/MarketingEmail/ViewModels/MarketingEmailCampaignFormViewModel.php');
if(str_contains($opts,"['value' => 'cancelled', 'label' => 'Cancelada']"))
 throw new RuntimeException('No permitir cancelar desde guardar formulario');
$ctrl=(string)file_get_contents($root.'/app/Controllers/Admin/EmailCampaignsController.php');
if(str_contains($ctrl,"->setStatus(\$id, 'scheduled'") || str_contains($ctrl,"->setStatus(\$id, 'cancelled'"))
 throw new RuntimeException('Las transiciones deben usar mutaciones condicionales');
echo "[campaign-lifecycle-contract] OK: rutas reales, confirmaciones activas, estados protegidos y pruebas revertibles\n";
