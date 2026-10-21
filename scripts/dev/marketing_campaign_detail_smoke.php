<?php
declare(strict_types=1);
define('BASE_PATH',dirname(__DIR__,2));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_METHOD']='GET';
$_SERVER['REQUEST_URI']='/mi-pesca/public_html/admin/marketing/campanas/show?id=9';
require BASE_PATH.'/vendor/autoload.php';
foreach(glob(BASE_PATH.'/config/boot/*.php') as $file) require_once $file;
require_once BASE_PATH.'/app/Views/helpers.php';

$warnings=[];
set_error_handler(static function(int $severity,string $message)use(&$warnings):bool{
    if($severity & (E_WARNING | E_NOTICE)){$warnings[]=$message;return true;}
    return false;
});
try {
    $campaign=['id'=>9,'name'=>'Campaña de contrato','slug'=>'contrato-demo',
        'destination_path'=>'/productos','utm_campaign'=>'demo','is_active'=>1,'notes'=>''];
    $report=['hits'=>10,'new_users'=>0,'add_to_cart'=>1,'purchases'=>1,
        'revenue'=>12500,'ratio_new_users'=>0,'ratio_add_to_cart'=>10,'ratio_purchases'=>10];
    $series=[['date'=>'2026-10-20','hits'=>10,'new_users'=>0,'add_to_cart'=>1,
        'purchases'=>1,'revenue'=>12500]];
    $recentOrders=[['id'=>11,'status'=>'paid','total'=>12500,'shipping_full_name'=>'',
        'created_at'=>'2026-10-20 12:00:00']];
    $topPages=[['path'=>'/productos','views'=>3]];
    $vm=(new App\Services\Admin\MarketingCampaigns\ViewModels\MarketingCampaignsShowViewModel())->build([
        'campaign'=>$campaign,'report'=>$report,'series'=>$series,'recentOrders'=>$recentOrders,
        'topPages'=>$topPages,'days'=>30,
    ]);
    // Reproducir la combinación del controlador: datos crudos Y show_view preparado.
    $input=array_merge(['pageId'=>'admin-marketing-campaigns',
        'campaign'=>$campaign,'report'=>$report,'series'=>$series,
        'recentOrders'=>$recentOrders,'topPages'=>$topPages,'days'=>30],$vm);
    ob_start();
    render_page('admin/marketing_campaigns/show',$input);
    $html=(string)ob_get_clean();
    foreach(['Campaña de contrato','12.500','Compras atribuidas','Agregados al carrito','/productos'] as $needle){
        if(!str_contains($html,$needle))throw new RuntimeException('Render perdió '.$needle);
    }
    if($warnings)throw new RuntimeException('Avisos PHP inesperados: '.implode(' | ',array_slice($warnings,0,4)));
    if(str_contains($html,'Undefined array key'))throw new RuntimeException('Claves de VM mezcladas con datos crudos');
    echo "[marketing-campaign-detail] OK: VM preparado, ingresos 12.500, serie y tablas; sin avisos ni escrituras\n";
}catch(Throwable $e){
    if(ob_get_level()>0)ob_end_clean();
    fwrite(STDERR,'[marketing-campaign-detail] ERROR: '.$e->getMessage()."\n");exit(1);
}finally{restore_error_handler();}
