<?php
declare(strict_types=1);
$root=dirname(__DIR__,2);
$paths=[
 'view'=>$root.'/app/Views/pages/admin/orders/index.php',
 'css'=>$root.'/public_html/assets/css/admin-v3/pages/orders.css',
 'js'=>$root.'/public_html/assets/js/admin/orders/index.js',
 'service'=>$root.'/app/Services/Admin/AdminOrderService.php',
 'vm'=>$root.'/app/Services/Admin/Orders/ViewModels/OrdersIndexViewModel.php',
 'controller'=>$root.'/app/Controllers/Admin/OrdersController.php',
];
$data=[];
foreach($paths as $key=>$path){
  $data[$key]=@file_get_contents($path);
  if($data[$key]===false)throw new RuntimeException('Archivo requerido no encontrado: '.$key);
}
$must=[
 'view'=>['class="av3-orders-index"','av3-orders-quick','av3-orders-advanced','data-orders-index',
          'av3-orders-mobile-item','av3-orders-pagination','name="status"','name="payment_method"',
          'name="sort"'],
 'css'=>['container-type:inline-size','@container (min-width:780px)','av3-orders-mobile-list','av3-orders-status--danger'],
 'service'=>['SELECT COUNT(*)','COUNT(*) AS total','payment_status','shipment_status'],
 'vm'=>['paymentState(','deliveryState(','quick_counts','orders_meta','payment_method_label'],
 'controller'=>['$listMeta','per_page','sort'],
 'js'=>['[data-orders-index]','[data-orders-auto-submit]'],
];
foreach($must as $key=>$terms)foreach($terms as $term){
 if(!str_contains($data[$key],$term))throw new RuntimeException('Contrato Pedidos: '.$key.' sin '.$term);
}
foreach(['av3-filters-form','av3-list-card','av3-md-grid-cols-5','hover:cursor-pointer'] as $legacy){
 if(str_contains($data['view'],$legacy)||str_contains($data['css'],$legacy))
  throw new RuntimeException('Estilo antiguo detectado: '.$legacy);
}
if(!str_contains($data['view'],"pagination['prev_url']")||
   !str_contains($data['view'],"pagination['next_url']")||
   !str_contains($data['vm'],"'prev_url'")||
   !str_contains($data['vm'],"'next_url'"))
 throw new RuntimeException('Falta navegación real de páginas');
if(str_contains($data['service'],'LIMIT 200'))
 throw new RuntimeException('La consulta conserva el límite anterior de 200 pedidos');
echo "[orders-index] OK: filtros, paginación, pago/entrega, tabla, móvil y CSS propio\n";
