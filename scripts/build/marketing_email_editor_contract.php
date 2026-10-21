<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$files=[
 'app/Views/partials/admin/marketing_email/campaign_form/pre_form.php'=>[
   'av3-email-composer','marketing_email_nav.php','admin/page_header.php'
 ],
 'app/Views/partials/admin/marketing_email/campaign_form/form_block.php'=>[
   'data-email-campaign-save','name="name"','name="subject"','name="preheader"','name="audience"',
   'name="aud_days"','name="aud_min_total"','name="cta_label"','name="cta_url"','name="status"','name="scheduled_at"',
   'data-email-audience-condition="vip"','data-email-audience-condition="inactive_buyers"',
   'av3-email-composer__workspace','campaign_form/templates.php','campaign_form/message.php',
   'campaign_form/tools.php','csrf_field()'
 ],
 'app/Views/partials/admin/marketing_email/campaign_form/templates.php'=>[
   'id="email-templates"','id="saved-template-id"','id="template-name"',
   'form="apply-template-form"','form="save-as-template-form"','av3-email-composer__templates'
 ],
 'app/Views/partials/admin/marketing_email/campaign_form/message.php'=>[
   'id="email-editor"','id="body_html"','id="body_html_raw"',
   'data-email-editor-tab="visual"','id="email-html-toolbar"','id="email-preview-open"'
 ],
 'app/Views/partials/admin/marketing_email/campaign_form/tools.php'=>[
   'id="email-tools"','data-tools-tab="blocks"','data-tools-panel="images"',
   'id="email-image-upload"','data-open-card-builder="grid2"','id="chk-body"'
 ],
 'app/Views/partials/admin/marketing_email/campaign_form/post_form.php'=>[
   'id="apply-template-form"','id="save-as-template-form"','csrf_field()',
   'modals/'
 ],
 'app/Views/partials/admin/marketing_email/campaign_form/modals/preview.php'=>[
   'id="email-preview-modal"','sandbox=""','id="email-preview"','is-hidden'
 ],
 'app/Views/partials/admin/marketing_email/campaign_form/modals/send_test.php'=>[
   'id="email-send-test-modal"','id="email-send-test-send"','is-hidden'
 ],
 'app/Views/partials/admin/marketing_email/campaign_form/modals/card_builder.php'=>[
   'id="email-card-builder-modal"','id="cb-apply"','is-hidden'
 ],
 'app/Views/partials/admin/marketing_email/campaign_form/modals/product_picker.php'=>[
   'id="email-product-picker-modal"','id="pp-results"','is-hidden'
 ],
 'app/Views/pages/admin/marketing_email/campaign_show.php'=>[
   'data-email-campaign-detail','data-confirm-schedule-email','data-confirm-cancel-email',
   'sandbox=""','srcdoc=','status_tone','marketing_email_nav.php','csrf_field()'
 ],
 'app/Services/Admin/MarketingEmail/ViewModels/MarketingEmailCampaignShowViewModel.php'=>[
   'status_tone','orderStatusLabel','can_schedule_now','can_cancel'
 ],
 'public_html/assets/js/admin/marketing/campaign_editor/index.js'=>[
   'applyAudienceUi','data-email-audience-condition','setEditingEnabled','setupCardBuilder'
 ],
 'public_html/assets/js/admin/marketing/campaign_show.js'=>[
   'data-confirm-schedule-email','data-confirm-cancel-email','window.confirm'
 ],
 'public_html/assets/js/app.js'=>['admin-email-campaign-show','campaign_show.js'],
 'public_html/assets/css/admin-v3/pages/marketing-email.css'=>[
   'av3-email-composer__fields','av3-email-composer__templates','av3-email-campaign-detail__metrics'
 ],
];
foreach($files as $file=>$tokens){
 $text=@file_get_contents($root.'/'.$file);
 if(!is_string($text))throw new RuntimeException('Archivo ausente: '.$file);
 foreach($tokens as $token)if(!str_contains($text,$token))throw new RuntimeException($file.': falta '.$token);
}
$part=file_get_contents($root.'/app/Views/partials/admin/marketing_email/campaign_form/form_block.php');
if(preg_match('/form="save-as-template-form"[^>]*form="apply-template-form"/',$part))
 throw new RuntimeException('Input tiene atributos form contradictorios');
if(str_contains($part,'<style>'))throw new RuntimeException('CSS inline heredado en formulario');
if(str_contains(file_get_contents($root.'/app/Views/pages/admin/marketing_email/campaign_show.php'),'class="prose'))
 throw new RuntimeException('HTML de email no puede ser inyectado en la vista administrativa');
$formPath=$root.'/app/Views/partials/admin/marketing_email/campaign_form/';
$post=file_get_contents($formPath.'post_form.php');
if (substr_count($part, '</form>')!==1 || !str_contains($part,'av3-email-composer__workspace'))
 throw new RuntimeException('El formulario principal debe cerrar su estructura HTML antes de terminar');
if (strpos($post, "</div>") > strpos($post, "foreach (['preview'"))
 throw new RuntimeException('Los modales deben quedar fuera del contenedor responsive');
foreach(['preview','send_test','product_picker'] as $modalName){
 $modal=file_get_contents($formPath.'modals/'.$modalName.'.php');
 if (!str_contains($modal,'class="av3-email-composer__modal') ||
     !str_contains($modal,'role="dialog"') ||
     !str_contains($modal,'aria-modal="true"') ||
     !str_contains($modal,'class="av3-email-composer__dialog'))
   throw new RuntimeException('Dialogo no migrado: '.$modalName);
 if(preg_match('/<div id="email-[^"]+-modal"[^>]+role="dialog"/',$modal))
   throw new RuntimeException('El rol dialog debe pertenecer al panel interior, no al overlay');
 if(preg_match('/class="[^"]*av3-(?:flex|fixed|pad-|bg-slate|type-|max-w-|radius-card)/',$modal))
   throw new RuntimeException('Modales pequeños contienen utilidades legacy: '.$modalName);
}
foreach(glob($root.'/public_html/assets/js/admin/marketing/campaign_editor/*.js') as $script){
 if(str_contains(file_get_contents($script),"'av3-hidden'"))
   throw new RuntimeException('JavaScript editor debe usar estado is-hidden: '.basename($script));
}
$style=file_get_contents($root.'/public_html/assets/css/admin-v3/pages/marketing-email.css');
if(strlen($style)<30000 || !str_contains($style,'.av3-email-composer__modal{position:fixed;inset:0;width:100vw'))
 throw new RuntimeException('CSS principal del editor incompleto o overlay sin ancho de viewport');
// Contrato de limpieza: los parciales nuevos ya no deben reintroducir utilidades
// heredadas ni depender de clases Tailwind para su comportamiento.
foreach(['form_block.php','templates.php','message.php','tools.php','modals/card_builder.php'] as $partial){
 $content=file_get_contents($formPath.$partial);
 preg_match_all('/class="([^"]*)"/',$content,$matches);
 foreach($matches[1] as $classList){
  foreach(preg_split('/\s+/',trim($classList)) as $class){
   if(str_starts_with($class,'av3-') &&
      !str_starts_with($class,'av3-email-') &&
      !str_starts_with($class,'av3-ui-'))
    throw new RuntimeException('Utilidad legacy regresó a '.$partial.': '.$class);
  }
 }
}
foreach(['index.js','preview_modal.js','card_modal.js','product_picker.js'] as $script){
 $js=file_get_contents($root.'/public_html/assets/js/admin/marketing/campaign_editor/'.$script);
 if(!str_contains($js,'setEmailDialogOpen(') || str_contains($js,"'av3-overflow-hidden'"))
  throw new RuntimeException('Control de scroll no unificado: '.$script);
}
$validation=file_get_contents($root.'/public_html/assets/js/admin/marketing/campaign_editor/form_validation.js');
if(!str_contains($validation,"classList.add('is-invalid')") || str_contains($validation,'style.borderColor'))
 throw new RuntimeException('La validación debe usar estados semánticos y CSS, no inline');
if(!str_contains($style,'body.av3-email-dialog-open') || !str_contains($style,'.is-invalid'))
 throw new RuntimeException('CSS de los estados locales ausente');
echo "[email-editor-contract] OK: editor V3, componentes limpios, estados y HTML aislado\n";
