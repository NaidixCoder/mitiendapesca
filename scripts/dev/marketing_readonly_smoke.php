<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 2));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_URI'] = '/mi-pesca/public_html/admin/marketing/campanas';
$_SERVER['REQUEST_METHOD'] = 'GET';
require BASE_PATH . '/vendor/autoload.php';
foreach (glob(BASE_PATH.'/config/boot/*.php') as $file) require_once $file;
require_once BASE_PATH.'/app/Views/helpers.php';
try {
 $repo=new App\Repositories\Marketing\MarketingCampaignRepository(db());
 foreach (['', 'active', 'inactive'] as $status) {
   $total=$repo->countForAdmin('sin-coincidencias-c84b7', $status);
   $rows=$repo->listForAdmin('sin-coincidencias-c84b7',30,$status,25,0);
   if ($total!==0 || $rows!==[]) throw new RuntimeException('Filtro de búsqueda incorrecto');
 }
 $mock=[
   ['id'=>1,'name'=>'Campaña de muestra','slug'=>'instagram-test','destination_path'=>'/productos',
    'is_active'=>1,'hits_30d'=>45,'add_to_cart_30d'=>8,'purchases_30d'=>3,'revenue_30d'=>250000],
   ['id'=>2,'name'=>'Campaña inactiva','slug'=>'whatsapp-test','destination_path'=>'/productos',
    'is_active'=>0,'hits_30d'=>0,'add_to_cart_30d'=>0,'purchases_30d'=>0,'revenue_30d'=>0],
 ];
 $model=new App\Services\Admin\MarketingCampaigns\ViewModels\MarketingCampaignsIndexViewModel();
 $vm=$model->build($mock,'',30,'',1,25,2,1);
 if (count($vm['campaigns_prepared'])!==2 || count($vm['campaign_tabs'])!==3) throw new RuntimeException('VM perdió campañas');
 ob_start();
 render_page('admin/marketing_campaigns/index', array_merge($vm, [
   'pageId'=>'admin-marketing-campaigns','rows'=>$mock,'days'=>30,'q'=>'','attributionMode'=>'first_touch',
 ]));
 $html=ob_get_clean();
 foreach(['data-marketing-list','av3-marketing-tabs','av3-marketing-table','av3-marketing-mobile-list','av3-marketing-status','av3-marketing-attribution'] as $needle)
   if(!str_contains($html,$needle))throw new RuntimeException('Render incompleto: '.$needle);
 if(substr_count($html,'data-copy-text=')!==4)throw new RuntimeException('Copiar link desktop/mobile incompleto');
 if(substr_count($html,'action="'.url('/admin/marketing/campanas/toggle').'"')!==4) throw new RuntimeException('Acción de estado debe funcionar en desktop y mobile');
 echo "[marketing-readonly] OK: filtros contra DB, VM y HTML desktop/mobile con campañas sintéticas\n";
 if(in_array('--snapshot', $argv ?? [], true)) {
   $path=BASE_PATH.'/storage/marketing_visual_snapshot.html';
   file_put_contents($path, $html);
   echo "SNAPSHOT $path\n";
 }
 if(in_array('--snapshot-empty', $argv ?? [], true)) {
   ob_start();
   render_page('admin/marketing_campaigns/index', array_merge($model->build([], '', 30, '', 1, 25, 0, 1), [
     'pageId'=>'admin-marketing-campaigns','rows'=>[],'days'=>30,'q'=>'','attributionMode'=>'first_touch',
   ]));
   $emptyHtml=ob_get_clean();
   if(!str_contains($emptyHtml,'Crear primera campaña')) throw new RuntimeException('Estado vacío sin acción');
   $path=BASE_PATH.'/storage/marketing_empty_snapshot.html';
   file_put_contents($path,$emptyHtml);
   echo "EMPTY SNAPSHOT $path\n";
 }
}catch(Throwable $e) {
 while(ob_get_level()>0)ob_end_clean();
 fwrite(STDERR,'[marketing-readonly] ERROR: '.$e->getMessage()."\n");exit(1);
}
