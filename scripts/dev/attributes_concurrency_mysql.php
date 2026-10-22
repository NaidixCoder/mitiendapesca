<?php
declare(strict_types=1);
/** Ejecución aislada de carreras reales MariaDB. Nunca conecta al puerto comercial. */
define('BASE_PATH',dirname(__DIR__,2));require BASE_PATH.'/vendor/autoload.php';
function privateDb():PDO {
 $p=new PDO('mysql:host=127.0.0.1;port=18991;dbname=mi_attrs_v2_20261022;charset=utf8mb4',
 (string)(getenv('ATTRIBUTES_QA_USER') ?: 'root'), (string)(getenv('ATTRIBUTES_QA_PASSWORD') ?: ''),[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 if ((int)$p->query('SELECT @@port')->fetchColumn()!==18991 || (string)$p->query('SELECT DATABASE()')->fetchColumn()!=='mi_attrs_v2_20261022') throw new RuntimeException('REFUSED');
 $p->exec('SET SESSION innodb_lock_wait_timeout=8');
 return $p;
}
$mode=$argv[1]??'parent';
if ($mode==='delete-worker') {
 $r=(new App\Services\Admin\Variants\VariantAttributeValueDeleteService(privateDb()))->deleteAttributeValue(706,701);
 echo json_encode($r);exit(!empty($r['ok'])?2:0);
}
if ($mode==='create-worker') {
 $r=(new App\Services\Admin\Variants\VariantValueSetsService(privateDb()))->createValueSetFromProduct(701,'QA carrera simultánea');
 echo json_encode($r);exit(!empty($r['ok'])?2:0);
}
function worker(string $mode, PDO $db, callable $inside):void{
 $exec='"C:\\xampp\\php\\php.exe" "'.__FILE__.'" '.$mode;
 $pipes=[];$proc=proc_open($exec,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes);
 if (!is_resource($proc)) throw new RuntimeException('No se lanzó worker');
 fclose($pipes[0]);
 try {
   usleep(500000);
   $st=proc_get_status($proc);
   if (!$st['running']) throw new RuntimeException('Worker no quedó bloqueado: '.$mode);
   $inside();
   $out=stream_get_contents($pipes[1]);
   $err=stream_get_contents($pipes[2]);
   fclose($pipes[1]);fclose($pipes[2]);
   $result=json_decode($out,true);
   if (!is_array($result) || !empty($result['ok'])) throw new RuntimeException('Carrera inválida '.$mode.' '.$out.' '.$err);
   echo '[concurrencia] PASS '.$mode.' blocked then '.($result['code']??$result['message']??'?').PHP_EOL;
 } finally {
   if ($db->inTransaction())$db->rollBack();
   proc_terminate($proc);
   proc_close($proc);
 }
}
$db=privateDb();
$db->beginTransaction();
$db->prepare('SELECT id FROM attribute_values WHERE id=? FOR UPDATE')->execute([706]);
worker('delete-worker',$db,function()use($db){
 $db->prepare('INSERT IGNORE INTO product_attribute_values (product_id,attribute_id,value_id) VALUES (702,701,706)')->execute();
 $db->commit();
});
$present=(int)$db->query('SELECT COUNT(*) FROM product_attribute_values WHERE value_id=706')->fetchColumn();
$master=(int)$db->query('SELECT COUNT(*) FROM attribute_values WHERE id=706')->fetchColumn();
if($present!==1||$master!==1)throw new RuntimeException('Se perdió selección concurrente');
$db->beginTransaction();
$db->prepare('SELECT id FROM categories WHERE id=? FOR UPDATE')->execute([701]);
worker('create-worker',$db,function()use($db){
 $db->prepare("INSERT IGNORE INTO variant_value_sets (name,category_id,brand_id) VALUES ('QA carrera simultánea',701,701)")->execute();
 $db->commit();
});
$count=(int)$db->query("SELECT COUNT(*) FROM variant_value_sets WHERE name='QA carrera simultánea' AND category_id=701 AND brand_id=701")->fetchColumn();
if($count!==1)throw new RuntimeException('Creación concurrente duplicada');
echo "[concurrencia] 2 escenarios InnoDB PASS puerto 18991\n";
