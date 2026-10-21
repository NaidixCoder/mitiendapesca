<?php
declare(strict_types=1);
define('BASE_PATH',dirname(__DIR__,2));
$_SERVER['HTTP_HOST']='localhost';
$_SERVER['SCRIPT_NAME']='/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_URI']='/mi-pesca/public_html/admin/orders';
$_SERVER['REQUEST_METHOD']='GET';
require BASE_PATH.'/vendor/autoload.php';
foreach(glob(BASE_PATH.'/config/boot/*.php') as $file)require_once $file;
try {
  $db=db();
  $tables=[];
  foreach(['orders','payments','order_shipments'] as $table){
    $stmt=$db->query("SHOW COLUMNS FROM ".$table);
    $tables[$table]=array_column($stmt->fetchAll(PDO::FETCH_ASSOC),'Field');
  }
  foreach(['orders'=>['id','created_at','status','payment_method','total'],
    'payments'=>['id','order_id','updated_at','status'],
    'order_shipments'=>['order_id','status','updated_at']] as $table=>$fields){
    foreach($fields as $field)if(!in_array($field,$tables[$table],true))throw new RuntimeException("Falta $table.$field");
  }
  $svc=new App\Services\Admin\AdminOrderService($db);
  foreach([
      ['view'=>'all','per_page'=>25],
      ['view'=>'collect','per_page'=>25,'page'=>1],
      ['view'=>'prepare','sort'=>'oldest','per_page'=>50],
      ['view'=>'all','q'=>'#14','sort'=>'total_desc','per_page'=>25],
      ['view'=>'all','q'=>'prueba_inexistente_c5f247f9','per_page'=>25],
  ] as $f){
    [$rows,$states,$methods,$meta]=$svc->listForAdmin($f);
    if(count($rows)>$meta['per_page'])throw new RuntimeException('Pagina excede limite');
    if($meta['total']<count($rows))throw new RuntimeException('Total inconsistente');
    if(!$meta['pages']||!$meta['page'])throw new RuntimeException('Paginacion invalida');
    echo "[ordenes-db] OK: vista=".($f['view']??'all').", filas=".count($rows).", total=".$meta['total']."\n";
  }
  echo "[ordenes-db] OK: esquema y consultas de solo lectura\n";
} catch(Throwable $e){
  fwrite(STDERR,"[ordenes-db] ERROR: ".$e->getMessage()."\n");
  exit(1);
}
