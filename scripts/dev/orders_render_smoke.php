<?php
declare(strict_types=1);
define('BASE_PATH',dirname(__DIR__,2));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_URI']='/mi-pesca/public_html/admin/orders';
$_SERVER['REQUEST_METHOD']='GET';
require BASE_PATH.'/vendor/autoload.php';
foreach(glob(BASE_PATH.'/config/boot/*.php') as $file)require_once $file;
require_once BASE_PATH.'/app/Views/helpers.php';
try {
    $filters=['view'=>'all','sort'=>'newest','per_page'=>25,'page'=>1,'q'=>'','status'=>'','payment_method'=>'','from'=>'','to'=>''];
    [$rows,$statuses,$paymentMethods,$meta]=(new App\Services\Admin\AdminOrderService(db()))->listForAdmin($filters);
    $vm=(new App\Services\Admin\Orders\ViewModels\OrdersIndexViewModel())->build([
       'orders'=>$rows,'filters'=>$filters,'statusOptions'=>$statuses,'paymentMethodOptions'=>$paymentMethods,'meta'=>$meta,
    ]);
    if(count($vm['orders_prepared'])!==count($rows))throw new RuntimeException('ViewModel omitió pedidos');
    if(count($vm['orders_quick_views'])!==5)throw new RuntimeException('No hay 5 vistas rápidas');
    ob_start();
    render_page('admin/orders/index',array_merge($vm,['pageId'=>'admin-orders-index']));
    $html=ob_get_clean();
    foreach(['class="av3-orders-index"','data-orders-index','id="orders-search"','aria-label="Vistas de pedidos"','class="av3-orders-mobile-list"','class="av3-orders-pagination"'] as $fragment){
      if(!str_contains($html,$fragment))throw new RuntimeException('Render ausente: '.$fragment);
    }
    if(substr_count($html,'class="av3-orders-mobile-item"')!==count($rows))
      throw new RuntimeException('El móvil no coincide con el listado');
    if(!str_contains($html,'name="sort"')||!str_contains($html,'name="per_page"'))
      throw new RuntimeException('Filtros de orden/paginación perdidos');
    echo "[ordenes-render] OK: HTML completo, ".$meta['total']." pedidos, filtros y version movil\n";
} catch(Throwable $e){
   while(ob_get_level()>0)ob_end_clean();
   fwrite(STDERR,"[ordenes-render] ERROR ".$e->getMessage()."\n");
   exit(1);
}
