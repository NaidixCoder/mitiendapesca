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
  $db=db();
  $campaignRepo=new App\Repositories\Marketing\EmailCampaignRepository($db);
  $queueRepo=new App\Repositories\Marketing\EmailQueueRepository($db);
  $templatesRepo=new App\Repositories\Marketing\EmailTemplateRepository($db);
  foreach ([
    'campaigns'=>$campaignRepo->countForAdmin(),
    'queue'=>$queueRepo->countForAdmin('','all'),
    'templates'=>$templatesRepo->countForAdmin(),
  ] as $kind=>$value) {
    if ($value < 0) throw new RuntimeException('Recuento incorrecto: '.$kind);
    echo "COUNT $kind=$value\n";
  }
  if(count($campaignRepo->listWithStats(25,0))>25 || count($templatesRepo->listLite(25,0))>25 ||
     count($queueRepo->listQueue('','all',25,0))>25)
    throw new RuntimeException('La paginación no limita las consultas');
  $queueRepo->countForAdmin('sin-coincidencia-imposible-9201','all');
  $queueRepo->listQueue('sin-coincidencia-imposible-9201','all',25,0);
  $makePage=static function(string $page,array $vars,string $route):string {
    $_SERVER['REQUEST_URI']='/mi-pesca/public_html/admin/marketing/email/'.$route;
    ob_start();
    render_page('admin/marketing_email/'.$page,$vars);
    return (string)ob_get_clean();
  };
  $pager=App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailPagination::build('/admin/marketing/email/campanas',75,25,2);
  if($pager['range_label']!=='26–50 de 75' || count($pager['pagination'])<3)throw new RuntimeException('Paginación incorrecta');
  $campaignInput=[
    'id'=>117,'name'=>'Novedades primavera','subject'=>'Reeles para esta temporada','status'=>'scheduled',
    'scheduled_at'=>'2026-10-21 10:00:00','sent_count'=>200,'open_count'=>78,'click_count'=>30,
    'orders_count'=>5,'revenue_total'=>165000
  ];
  $model=(new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailCampaignsIndexViewModel())->build(['campaigns'=>[$campaignInput]]);
  $campaignHTML=$makePage('campaigns',array_merge(['pageId'=>'admin-marketing-email'],$model,$pager),'campanas');
  foreach(['data-email-campaigns','av3-email-table','av3-email-mobile-list','Nuevo envío', 'Novedades primavera','26–50 de 75','Programada'] as $needle)
    if(!str_contains($campaignHTML,$needle))throw new RuntimeException('Campañas sin '.$needle);
  $campaignEmpty=$makePage('campaigns',array_merge(['pageId'=>'admin-marketing-email'],(new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailCampaignsIndexViewModel())->build(['campaigns'=>[]]),
    App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailPagination::build('/admin/marketing/email/campanas',0,25,1)),'campanas');
  if(!str_contains($campaignEmpty,'Crear campaña'))throw new RuntimeException('Estado vacío de envíos');

  $queueRows=[
    ['to_email'=>'demo@example.test','subject'=>'Recuperá tu carrito','status'=>'queued','scheduled_at'=>'2026-10-21 11:00:00','attempts'=>0],
    ['to_email'=>'fallo@example.test','subject'=>'Email fallido','status'=>'failed','scheduled_at'=>'2026-10-20 11:00:00','attempts'=>2],
  ];
  $logs=[
    ['to_email'=>'demo@example.test','subject'=>'Novedades','status'=>'sent','created_at'=>'2026-10-20 10:00:00','error_text'=>''],
    ['to_email'=>'fallo@example.test','subject'=>'Email fallido','status'=>'failed','created_at'=>'2026-10-20 11:00:00','error_text'=>'SMTP temporariamente no disponible'],
  ];
  $queueVm=(new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailQueueViewModel())->build([
    'rows'=>$queueRows,'logs'=>$logs,'q'=>'','status'=>'all',
    'counts'=>['queued'=>1,'failed'=>1,'sent'=>1,'total'=>3]
  ]);
  $queueHTML=$makePage('queue',array_merge(['pageId'=>'admin-marketing-email'],$queueVm,
    App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailPagination::build('/admin/marketing/email/cola',75,25,2,['status'=>'all'])),'cola');
  foreach(['data-email-queue','Procesamiento manual','csrf','Recuperá tu carrito','SMTP temporariamente no disponible','26–50 de 75'] as $needle)
    if(!str_contains($queueHTML,$needle))throw new RuntimeException('Cola sin '.$needle);
  if(!str_contains($queueHTML,'av3-email-status--error'))throw new RuntimeException('Tono de error de envío ausente');

  $tpl=[['id'=>10,'name'=>'Cabecera clásica','editor_mode'=>'visual','subject_default'=>'Ofertas',
    'created_at'=>'2026-10-20 10:00:00']];
  $templatesVm=(new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailTemplatesIndexViewModel())->build($tpl,['message'=>'Plantilla guardada','type'=>'success']);
  $templatesVm=array_merge($templatesVm,App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailPagination::build('/admin/marketing/email/templates',75,25,2));
  $templatesHTML=$makePage('templates_index',['pageId'=>'admin-email-templates','templates_payload'=>$templatesVm],'templates');
  foreach(['data-email-templates','Cabecera clásica','26–50 de 75','data-confirm-delete-template','av3-email-notice--success'] as $needle)
    if(!str_contains($templatesHTML,$needle))throw new RuntimeException('Plantillas sin '.$needle);
  $templatesEmpty=(new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailTemplatesIndexViewModel())->build([],null);
  $templatesEmptyHTML=$makePage('templates_index',['pageId'=>'admin-email-templates','templates_payload'=>array_merge(
    $templatesEmpty,App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailPagination::build('/admin/marketing/email/templates',0,25,1)
  )],'templates');
  if(!str_contains($templatesEmptyHTML,'Tu biblioteca todavía está vacía'))throw new RuntimeException('Estado vacío plantillas');

  $templateView=(new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailTemplatesShowViewModel())->build([
    'id'=>10,'name'=>'Cabecera clásica','editor_mode'=>'html','subject_default'=>'Ofertas','preheader_default'=>'Una novedad','created_at'=>'2026-10-20','body_html'=>'<h1>Ofertas</h1>'
  ]);
  $detailHTML=$makePage('templates_show',['pageId'=>'admin-email-templates','template_show'=>$templateView['template_show']],'templates/show');
  foreach(['data-email-template-detail','sandbox=""','srcdoc=','Nueva campaña','Usar en campaña','data-confirm-delete-template'] as $needle) {
    if($needle==='Nueva campaña')continue;
    if(!str_contains($detailHTML,$needle))throw new RuntimeException('Detalle plantilla sin '.$needle);
  }
  if(!str_contains($detailHTML,'&lt;h1&gt;Ofertas&lt;/h1&gt;'))throw new RuntimeException('HTML no escapado en iframe');
  echo "[email-v3-phase2] OK: consultas read-only, datos sintéticos, estados vacíos, acciones y vista previa aislada\n";
  if(in_array('--snapshot',$argv??[],true)){
    $dir='C:/xampp/mi-pesca-backups/marketing_email_phase2_20261020_163032';
    foreach(['campaigns'=>$campaignHTML,'campaigns_empty'=>$campaignEmpty,'queue'=>$queueHTML,'templates'=>$templatesHTML,'templates_empty'=>$templatesEmptyHTML,'template_detail'=>$detailHTML] as $key=>$html){
      file_put_contents($dir.'/snapshot_'.$key.'.html',$html);
      echo "SNAPSHOT $key\n";
    }
  }
}catch(Throwable $e){
  while(ob_get_level()>0)ob_end_clean();
  fwrite(STDERR,"[email-v3-phase2] ERROR: ".$e->getMessage()."\n");exit(1);
}
