<?php
declare(strict_types=1);
require dirname(__DIR__,2).'/vendor/autoload.php';
$dsn='mysql:host=127.0.0.1;port=18969;dbname=mi_import_integral_cache;charset=utf8mb4';
$db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if($db->query('SELECT DATABASE()')->fetchColumn()!=='mi_import_integral_cache')throw new RuntimeException('No commercial DB');
$GLOBALS['qaDb']=$db;
function db():PDO{return $GLOBALS['qaDb'];}
function ck(bool $ok,string $label):void{if(!$ok)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
$db->exec("INSERT INTO categories(name,slug,is_active) VALUES('QA Cache','qa-cache',1)");
$cat=(int)$db->lastInsertId();
$db->prepare("INSERT INTO products(category_id,sku,slug,name,sale_mode,price,price_from_cache,list_price_from_cache)
VALUES(?,?,?,'Test cache padre','simple',2500,5555,7777)")
 ->execute([$cat,'QA-CACHE-P','qa-cache-p']);
$pid=(int)$db->lastInsertId();
$before=$db->query("SELECT sale_mode,price_from_cache,list_price_from_cache,price FROM products WHERE id=$pid")->fetch();
$dir=dirname(__DIR__,2).'/storage/imports';@mkdir($dir,0700,true);
$csv=$dir.'/qa_integral_parent_cache.csv';
$cols=['tipo_fila','sku','sku_padre','nombre','precio','precio_lista','costo'];
$h=fopen($csv,'wb');fputcsv($h,$cols,';');fputcsv($h,['variante','QA-CACHE-V','QA-CACHE-P','Variante nueva',2100,2350,900],';');fclose($h);
$jobs=new \App\Repositories\Imports\ImportJobRepository($db);
$id=$jobs->create('products','qa_integral_parent_cache.csv',$csv);
$service=\App\Services\Import\ImportService::fromDb($db);
(new ReflectionProperty($service,'snapshotStore'))->setValue($service,new \App\Services\Import\ImportSnapshotStore(null,$dir));
$out=$service->run('products',$csv,false,['delimiter'=>';','job_id'=>$id,'update_price'=>true,'update_list_price'=>true,
    'update_cost'=>true,'update_stock'=>false,'create_missing_products'=>false]);
echo 'CACHE_RESULT '.json_encode($out->toArray(),JSON_UNESCAPED_UNICODE).PHP_EOL;
ck($out->applyOutcome==='committed','Variant changes existing parent committed');
$jobs->markFinished($id,$out->total,$out->ok,$out->errors);
$payload=json_decode(file_get_contents(substr($csv,0,-4).'_rollback.json'),true);
ck(\App\Services\Import\ImportRevertService::isSnapshotSafe($payload),'Cache-bearing snapshot v3 valid');
$ops=$payload['ops'];
ck(count($ops)===2 && $ops[0]['entity']==='product' && $ops[0]['action']==='update'
 && $ops[1]['entity']==='variant' && $ops[1]['action']==='create',
 'Captures existing parent UPDATE before variant CREATE');
ck(array_key_exists('price_from_cache',$ops[0]['before'])
    && array_key_exists('list_price_from_cache',$ops[0]['before'])
    && array_key_exists('sale_mode',$ops[0]['before']), 'BEFORE cache/mode complete');
$changed=$db->query("SELECT sale_mode,price_from_cache,list_price_from_cache,price FROM products WHERE id=$pid")->fetch();
ck($changed['sale_mode']==='variants' && (int)$changed['price_from_cache']===2100,
   'Parent mode/cache modified by import');
$undo=\App\Services\Import\ImportRevertService::fromDb($db)->revertJob($id,0);
ck(!empty($undo['ok']),'Rollback restores parent mode and caches');
$after=$db->query("SELECT sale_mode,price_from_cache,list_price_from_cache,price FROM products WHERE id=$pid")->fetch();
ck($after===$before,'Complete parent BEFORE restored, including caches');
ck((int)$db->query("SELECT COUNT(*) FROM product_variants WHERE sku='QA-CACHE-V'")->fetchColumn()===0,
 'New variant deleted');
echo 'IMPORTS_PARENT_CACHE_SQL_PASS'.PHP_EOL;
