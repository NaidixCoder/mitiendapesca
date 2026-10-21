<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__,2));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REQUEST_URI']='/mi-pesca/public_html/admin/marketing/email';
require BASE_PATH.'/vendor/autoload.php';
foreach(glob(BASE_PATH.'/config/boot/*.php') as $file) require_once $file;
require_once BASE_PATH.'/app/Views/helpers.php';
try {
  $repo = new App\Repositories\Marketing\EmailSubscriberRepository(db());
  $overall = (int)$repo->stats()['total'];
  if ($overall !== $repo->countForAdmin('', '')) throw new RuntimeException('Total de suscriptores inconsistente');
  if (count($repo->listForAdmin('', '', 25, 0)) > 25) throw new RuntimeException('Límite de página incorrecto');
  foreach(['','pending','subscribed','unsubscribed'] as $status) {
    $total = $repo->countForAdmin('zz-nonexistent-992911', $status);
    $rows = $repo->listForAdmin('zz-nonexistent-992911',$status,25,0);
    if($total !== 0 || $rows !== []) throw new RuntimeException('Filtro DB incorrecto '.$status);
  }
  $render = static function(string $view,array $vars):string {
    $_SERVER['REQUEST_URI'] = '/mi-pesca/public_html/admin/marketing/email'
      .($view === 'subscribers' ? '/suscriptores' : ($view === 'automations' ? '/automatizaciones' : ''));
    ob_start();
    render_page('admin/marketing_email/'.$view,$vars);
    return (string)ob_get_clean();
  };
  $summary=$render('index',[
    'pageId'=>'admin-marketing-email',
    'stats'=>['total'=>75,'pending'=>8,'subscribed'=>64,'unsubscribed'=>3],
    'queueCounts'=>['queued'=>2,'sent'=>31,'failed'=>1,'total'=>34]
  ]);
  foreach(['data-email-summary','Suscriptores','Cola de envío','Nuevo envío'] as $term)
    if(!str_contains($summary,$term)) throw new RuntimeException('Resumen sin '.$term);

  $subscriberRows=[];
  for($i=1;$i<=25;$i++) $subscriberRows[]=[
    'id'=>$i,'email'=>"cliente{$i}@example.test", 'status'=>$i%3===0?'unsubscribed':'subscribed',
    'created_at'=>'2026-10-20 10:00:00','confirmed_at'=>'2026-10-20 10:30:00',
  ];
  $subsVM = (new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailSubscribersViewModel())->build([
    'q'=>'','status'=>'','rows'=>$subscriberRows,'total'=>75,'per'=>25,'page'=>2,'pages'=>3
  ]);
  $subscribers=$render('subscribers',array_merge(['pageId'=>'admin-marketing-email'],$subsVM));
  if(substr_count($subscribers,'class="av3-email-mobile-item"')!==25) throw new RuntimeException('Filas móviles incompletas');
  if(substr_count($subscribers,'class="av3-email-action"')!==50) throw new RuntimeException('Acción de estado ausente');
  foreach(['data-email-subscribers','av3-email-table','26–50 de 75','aria-current="page"'] as $term)
    if(!str_contains($subscribers,$term)) throw new RuntimeException('Suscriptores sin '.$term);

  $autoRows = [
    ['automation_id'=>1,'automation_code'=>'welcome','automation_name'=>'Bienvenida','automation_enabled'=>1,
      'step_id'=>9,'step_code'=>'welcome_1','step_name'=>'Primer correo','delay_minutes'=>60,
      'subject'=>'Bienvenido','template'=>'marketing/welcome','step_enabled'=>1],
    ['automation_id'=>1,'automation_code'=>'welcome','automation_name'=>'Bienvenida','automation_enabled'=>1,
      'step_id'=>10,'step_code'=>'welcome_2','step_name'=>'Recordatorio','delay_minutes'=>1440,
      'subject'=>'Seguimos en contacto','template'=>'marketing/welcome_2','step_enabled'=>0],
    ['automation_id'=>2,'automation_code'=>'cart','automation_name'=>'Carrito abandonado','automation_enabled'=>0,
      'step_id'=>null,'step_code'=>null,'step_name'=>null,'delay_minutes'=>null,
      'subject'=>null,'template'=>null,'step_enabled'=>null]
  ];
  $autoVM=(new App\Services\Admin\MarketingEmail\ViewModels\MarketingEmailAutomationsViewModel())->build(['rows'=>$autoRows]);
  $automations=$render('automations',array_merge(['pageId'=>'admin-marketing-email'],$autoVM));
  foreach(['data-email-automations','Bienvenida','Recordatorio','Carrito abandonado','Pausar serie','Activar serie','Pausar','Activar','csrf'] as $term)
    if(!str_contains($automations,$term)) throw new RuntimeException('Automatizaciones sin '.$term);
  if(substr_count($automations,'class="av3-email-step"')!==2) throw new RuntimeException('Pasos incompletos');
  if(substr_count($automations,'class="av3-email-nav"')!==1) throw new RuntimeException('Navegación duplicada');
  echo "[email-v3-smoke] OK: consultas read-only, resumen, paginación y automatizaciones, sin envíos/escrituras\n";
  if(in_array('--snapshot',$argv??[],true)) {
    $dest='C:/xampp/mi-pesca-backups/marketing_email_phase1_20261020_160038';
    foreach(['summary'=>$summary,'subscribers'=>$subscribers,'automations'=>$automations] as $name=>$html) {
      $file=$dest.'/snapshot_'.$name.'.html';
      file_put_contents($file,$html);
      echo "SNAPSHOT $file\n";
    }
  }
} catch(Throwable $e) {
 while(ob_get_level()>0)ob_end_clean();
 fwrite(STDERR,"[email-v3-smoke] ERROR: ".$e->getMessage()."\n");
 exit(1);
}
