<?php
declare(strict_types=1);
define('BASE_PATH',dirname(__DIR__,2));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_METHOD']='GET';
require BASE_PATH.'/vendor/autoload.php';
foreach(glob(BASE_PATH.'/config/boot/*.php') as $f) require_once $f;
require_once BASE_PATH.'/app/Views/helpers.php';

try {
 $render = static function(string $view,array $data,string $route):string {
  $_SERVER['REQUEST_URI']='/mi-pesca/public_html/admin/marketing/email/campanas/'.$route;
  ob_start();
  render_page('admin/marketing_email/'.$view,$data);
  return (string)ob_get_clean();
 };
 $formModel = new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailCampaignFormViewModel();
 $base=[
   'id'=>0,'name'=>'','subject'=>'','status'=>'draft','audience'=>'all',
   'preheader'=>'','cta_label'=>'','cta_url'=>'','body_html'=>'',
   'scheduled_at'=>null,'audience_params_json'=>null,
 ];
 $newVm=$formModel->build(['campaign'=>$base,'templatesLite'=>[
   ['id'=>15,'name'=>'Oferta visual','editor_mode'=>'visual','subject_default'=>'Oferta'],
 ]]);
 $htmlNew=$render('campaign_form',array_merge($newVm,['pageId'=>'admin-email-campaign-form']),'new');
 $edit=array_replace($base,['id'=>17,'name'=>'Campaña de ejemplo','subject'=>'Un lanzamiento',
   'body_html'=>'<p>Hola <strong>cliente</strong></p>','status'=>'scheduled',
   'scheduled_at'=>'2026-10-22 17:00:00',
   'audience'=>'vip','audience_params_json'=>'{"min_total":150000}']);
 $editVm=$formModel->build(['campaign'=>$edit,'templatesLite'=>[
   ['id'=>15,'name'=>'Oferta visual','editor_mode'=>'visual','subject_default'=>'Oferta'],
 ]]);
 $htmlEdit=$render('campaign_form',array_merge($editVm,['pageId'=>'admin-email-campaign-form']),'edit?id=17');
 foreach(['data-email-composer','data-email-campaign-save','email-campaign-name','campaign-status','campaign-audience',
   'data-email-audience-condition="vip"','email-editor','body_html','body_html_raw','email-preview-modal','email-send-test-modal',
   'email-card-builder-modal','email-product-picker-modal','save-as-template-form','apply-template-form',
   'csrf','data-tools-tab="images"','email-editor-toolbar'] as $test)
   if(!str_contains($htmlNew,$test)) throw new RuntimeException('Nuevo: falta '.$test);
 if(!str_contains($htmlEdit,'2026-10-22T17:00') || !str_contains($htmlEdit,'Un lanzamiento') ||
    !str_contains($htmlEdit,'Campaña de ejemplo'))throw new RuntimeException('Edición con datos incompleta');
 if(substr_count($htmlNew,'id="save-as-template-form"')!==1 || substr_count($htmlNew,'id="apply-template-form"')!==1)
   throw new RuntimeException('Formularios de plantillas duplicados');
 if(preg_match('/form="save-as-template-form"[^>]*form="apply-template-form"/',$htmlNew))
   throw new RuntimeException('Atributos form duplicados');
 if(!str_contains($htmlNew,'sandbox=""'))throw new RuntimeException('Preview del modal sin sandbox');

 $showVM=(new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailCampaignShowViewModel())->build([
   'campaign'=>$edit,'stats'=>['sent_count'=>210,'open_count'=>71,'click_count'=>27,'orders_count'=>1,'revenue_total'=>32000],
   'orders'=>[['id'=>51,'user_id'=>7,'status'=>'paid','total'=>32000,'created_at'=>'2026-10-20 11:00:00']],
 ]);
 $htmlShow=$render('campaign_show',array_merge($showVM,['pageId'=>'admin-email-campaign-show']),'show?id=17');
 foreach(['data-email-campaign-detail','Cancelar envío','data-confirm-cancel-email',
   'av3-email-campaign-detail__metrics','srcdoc=','sandbox=""','Pedido #51',
   'Pagado'] as $term)
   if(!str_contains($htmlShow,$term))throw new RuntimeException('Detalle: falta '.$term);
 if(str_contains($htmlShow,'Programar ahora')) throw new RuntimeException('Una campaña ya programada no puede programarse de nuevo');
 if(!str_contains($htmlShow,'>Editar</a>')) throw new RuntimeException('Una campaña programada sin cola debería poder editarse');
 if(!str_contains($htmlShow,'&lt;p&gt;Hola &lt;strong&gt;cliente&lt;/strong&gt;&lt;/p&gt;'))
   throw new RuntimeException('HTML crudo escapó del sandbox');
 if(str_contains($htmlShow,'class="prose'))throw new RuntimeException('Vista previa sin aislar');
 $emptyShow=$render('campaign_show',array_merge((new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailCampaignShowViewModel())->build([
   'campaign'=>array_replace($base,['id'=>22,'name'=>'Campaña nueva','status'=>'draft']),
 ]),['pageId'=>'admin-email-campaign-show']),'show?id=22');
 if(!str_contains($emptyShow,'Todavía no hay compras'))throw new RuntimeException('Detalle vacío sin estado');
 if(!str_contains($emptyShow,'Programar ahora') || str_contains($emptyShow,'Cancelar envío'))
    throw new RuntimeException('Acciones incorrectas para un borrador');
 $sentShow=$render('campaign_show',array_merge((new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailCampaignShowViewModel())->build([
   'campaign'=>array_replace($base,['id'=>23,'name'=>'Terminada','status'=>'sent']),
   'can_edit'=>false,
 ]),['pageId'=>'admin-email-campaign-show']),'show?id=23');
 if(str_contains($sentShow,'>Editar</a>') || str_contains($sentShow,'Programar ahora') || str_contains($sentShow,'Cancelar envío'))
    throw new RuntimeException('Campaña enviada admite acciones prohibidas');
 echo "[email-editor-smoke] OK: nuevo, edición, detalle con compras y vacío, controles de plantillas y sandbox; sin escrituras ni envíos\n";
 if(in_array('--snapshot',$argv??[],true)){
   $directory='C:/xampp/mi-pesca-backups/marketing_email_editor_20261020_165541';
   foreach(['new'=>$htmlNew,'edit'=>$htmlEdit,'show'=>$htmlShow,'show_empty'=>$emptyShow,'show_sent'=>$sentShow] as $key=>$value){
     file_put_contents($directory.'/snapshot_'.$key.'.html',$value);
     echo "SNAPSHOT $key\n";
   }
 }
} catch(Throwable $e) {
 while(ob_get_level()>0)ob_end_clean();
 fwrite(STDERR,"[email-editor-smoke] ERROR: ".$e->getMessage()."\n");exit(1);
}
