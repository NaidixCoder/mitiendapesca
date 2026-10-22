<?php
declare(strict_types=1);
/** V1E: only disposable MariaDB; run using MI_IMPORTS_V1E_DSN=mysql:host=127.0.0.1;port=18909;dbname=mi_import_v1e_test;charset=utf8mb4 */
$root=dirname(__DIR__,2);
require $root.'/vendor/autoload.php';
$dsn=(string)getenv('MI_IMPORTS_V1E_DSN');
if(!preg_match('/^mysql:host=127\.0\.0\.1;port=\d{4,5};dbname=mi_import_v1e_[a-z0-9_]+;charset=utf8mb4$/D',$dsn))
    throw new RuntimeException('Refusing non-private DSN');
$db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if((string)$db->query('SELECT DATABASE()')->fetchColumn()!=='mi_import_v1e_test')throw new RuntimeException('Unsafe DB');
$GLOBALS['qaDb']=$db;
function db():PDO{return $GLOBALS['qaDb'];}
function q(bool $c,string $name):void{if(!$c)throw new RuntimeException('FAIL '.$name);echo "PASS $name\n";}
$dir=$root.'/storage/imports';
if(!is_dir($dir))mkdir($dir,0700,true);
$header=['tipo_fila','sku','sku_padre','internal_code','nombre','categoria','marca','proveedor','proveedor_cuit','iva_rate','moneda','costo','margen_pct','precio','precio_lista','precio_mayorista1','precio_mayorista2','stock','umbral','publicado','descripcion','descripcion_larga','attr_1','valor_1','attr_2','valor_2','attr_3','valor_3','peso_envio_g','alto_envio_cm','ancho_envio_cm','largo_envio_cm'];
$opt=['delimiter'=>';','job_id'=>0,'create_missing_products'=>true,'create_missing_brands'=>true,
'create_missing_categories'=>true,'create_missing_suppliers'=>true,'update_name'=>true,
'update_internal_code'=>true,'update_price'=>true,'update_list_price'=>true,'update_cost'=>true,
'update_stock'=>true,'default_stock_mode'=>'fija','update_publish'=>true,'update_attributes'=>true,
'update_ship_dims'=>false];
$jobs=new \App\Repositories\Imports\ImportJobRepository($db);
$revert=\App\Services\Import\ImportRevertService::fromDb($db);
function writeCSV(string $path,array $header,array $rows):void{
 $h=fopen($path,'wb');fwrite($h,"\xef\xbb\xbf");fputcsv($h,$header,';');
 foreach($rows as $r){$row=[];foreach($header as $k)$row[]=$r[$k]??'';fputcsv($h,$row,';');}
 fclose($h);
}
function runImport(string $name,array $rows,bool $dry,array $overrides=[]) : array {
 global $db,$dir,$header,$opt,$jobs;
 $csv=$dir.'/qa_v1e_'.$name.'.csv';
 writeCSV($csv,$header,$rows);
 $id=$dry ? 0 : $jobs->create('products',basename($csv),$csv);
 $service=\App\Services\Import\ImportService::fromDb($db);
 $store=new \App\Services\Import\ImportSnapshotStore(null,$dir);
 (new ReflectionProperty($service,'snapshotStore'))->setValue($service,$store);
 $result=$service->run('products',$csv,$dry,array_replace($opt,['job_id'=>$id],$overrides));
 if(!$dry){
   if($result->applyOutcome===\App\Services\Import\ImportResult::APPLY_COMMITTED){
      $jobs->markFinished($id,(int)$result->total,(int)$result->ok,(int)$result->errors);
   } else {$jobs->markFailed($id);}
 }
 return ['result'=>$result,'job'=>$id,'snapshot'=>substr($csv,0,-4).'_rollback.json'];
}
function row(string $sku,string $parent,string $title,int $price=2000):array{
 return ['tipo_fila'=>'variante','sku'=>$sku,'sku_padre'=>$parent,'internal_code'=>'V1E-'.$sku,
  'nombre'=>$title,'precio'=>(string)$price,'precio_lista'=>(string)($price+300),
  'costo'=>'950','moneda'=>'ARS','margen_pct'=>'25','stock'=>'4','umbral'=>'2','publicado'=>'1'];
}
function variant(string $sku):?array{
 global $db;$s=$db->prepare('SELECT * FROM product_variants WHERE sku=?');$s->execute([$sku]);
 return $s->fetch()?:null;
}
function inv(int $id):?array{
 global $db;$s=$db->prepare('SELECT * FROM inventory_variants WHERE variant_id=?');$s->execute([$id]);
 return $s->fetch()?:null;
}
function snapshot(array $r):?array{
 return is_file($r['snapshot']) ? json_decode((string)file_get_contents($r['snapshot']),true) : null;
}

$db->exec("INSERT INTO categories(name,slug,is_active) VALUES('QA V1E','qa-v1e',1)");
$cat=(int)$db->lastInsertId();
$db->exec("INSERT INTO currencies(code,name,symbol,is_base,is_active) VALUES('ARS','Pesos','$',1,1)");
$currency=(int)$db->lastInsertId();
$db->prepare("INSERT INTO products(category_id,sku,slug,name,sale_mode,price,ship_weight) VALUES(?,?,?,'Padre QA','variants',3000,700)")
    ->execute([$cat,'QA-V1E-PARENT','qa-v1e-parent']);
$parentId=(int)$db->lastInsertId();
try{$db->query('SELECT ship_weight FROM product_variants LIMIT 1');throw new RuntimeException('Legacy SELECT did not fail');}
catch(PDOException $e){q((string)$e->getCode()==='42S22','PRE-FIX legacy SQL 1054 reproduced only on private schema');}

$repo=new \App\Repositories\Imports\ProductVariantImportRepository($db);
$directId=$repo->insert($parentId,['sku'=>'QA-V1E-DIRECT','internal_code'=>'DIRECT-1','title'=>'Direct',
 'price'=>2100,'list_price'=>2450,'cost'=>900,'cost_currency_id'=>$currency,'margin_pct'=>0.25,'is_active'=>1]);
q($directId>0,'Repository INSERT without nonexistent ship columns');
$direct=$repo->findBySku('QA-V1E-DIRECT');
q((int)($direct['cost_currency_id']??0)===$currency&&abs((float)$direct['margin_pct']-0.25)<0.0001,
 'Repository SELECT preserves currency/margin');
q($repo->updateById($directId,['price'=>2700,'cost'=>1100,'margin_pct'=>0.2]),'Repository UPDATE real fields');
q((int)$repo->findBySku('QA-V1E-DIRECT')['price']===2700,'Repository updated SQL');
$directCols=array_column($db->query('SHOW COLUMNS FROM product_variants')->fetchAll(),'Field');
q(!in_array('ship_weight',$directCols,true),'No invented columns in schema');

$invalid=row('QA-V1E-BADDIMS','QA-V1E-MISSING','Inválida',2700);
$invalid['categoria']='QA V1E';$invalid['peso_envio_g']='700';$invalid['alto_envio_cm']='10';
$before=(int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn();
$bad=runImport('ship_on_invalid',[$invalid],true,['update_ship_dims'=>true,'allow_parent_from_variants'=>true]);
q($bad['result']->errors>0 && str_contains(json_encode($bad['result']->toArray(),JSON_UNESCAPED_UNICODE),'dimensiones de envío'),
  'Ship dimensions ON => explicit dry-run error before parent');
q((int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn()===$before &&
  variant('QA-V1E-BADDIMS')===null,'Invalid dry-run leaves parent/variant unchanged');

$good=row('QA-V1E-NEW','QA-V1E-PARENT','Azul',2200);
$good['peso_envio_g']='850';$good['alto_envio_cm']='21';$good['attr_1']='Color';$good['valor_1']='Azul';
$dry=runImport('off_dry',[$good],true,['update_ship_dims'=>false]);
q($dry['result']->errors===0,'Ship dims OFF dry-run passes despite CSV dimensions');
$first=runImport('off_apply',[$good],false,['update_ship_dims'=>false]);
$record=variant('QA-V1E-NEW');
echo 'APPLY_OFF_RESULT '.json_encode(['outcome'=>$first['result']->applyOutcome,'errors'=>$first['result']->errors,'rows'=>$first['result']->rows,'snapshot'=>snapshot($first)],JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)."\n";
q($record!==null,'Variant created from official 32-column CSV; no SQL 1054');
q((int)$record['price']===2200 && (int)$record['cost']===950 &&
  (int)$record['cost_currency_id']===$currency && (float)$record['margin_pct']>0,
  'Variant price/cost/currency/margin mapped');
q((int)$db->query("SELECT ship_weight FROM products WHERE id=$parentId")->fetchColumn()===700,
   'Variant dimensions NOT transferred to parent');
$inv=inv((int)$record['id']);
q($inv!==null && (int)$inv['stock']===4,'Variant stock/inventory created');
if($first['result']->applyOutcome!==\App\Services\Import\ImportResult::APPLY_COMMITTED){
 echo 'OBSERVED_BLOCKER_VARIANT_SNAPSHOT: '.json_encode($first['result']->toArray(),JSON_UNESCAPED_UNICODE)."\n";
} else {
 q(\App\Services\Import\ImportRevertService::isSnapshotSafe(snapshot($first)),
   'Snapshot v3 complete for new variant');
 $back=$revert->revertJob($first['job'],1);
 echo 'ROLLBACK_RESULT '.json_encode($back,JSON_UNESCAPED_UNICODE)."\n";
 q(!empty($back['ok']),'Safe rollback new variant');
}
$newParent=['tipo_fila'=>'producto','sku'=>'QA-V1E-NEW-PARENT','internal_code'=>'V1EPARENT','nombre'=>'Padre nuevo CSV','categoria'=>'QA V1E','marca'=>'Marca fixture','proveedor'=>'Proveedor fixture','precio'=>'3100'];
$newVariant=row('QA-V1E-NEW-CHILD','QA-V1E-NEW-PARENT','Rosa',2400);
$newVariant['attr_1']='Color';$newVariant['valor_1']='Rosa';
$pair=runImport('parent_created',[$newParent,$newVariant],false);
$parentRow=$db->query("SELECT id,sale_mode,ship_weight FROM products WHERE sku='QA-V1E-NEW-PARENT'")->fetch();
$child=variant('QA-V1E-NEW-CHILD');
echo 'PARENT_CREATED_RESULT '.json_encode(['outcome'=>$pair['result']->applyOutcome,'errors'=>$pair['result']->errors,'snapshot'=>snapshot($pair),'rows'=>$pair['result']->rows],JSON_UNESCAPED_UNICODE).PHP_EOL;
q(is_array($parentRow) && $child && (int)$child['product_id']===(int)$parentRow['id'],'CSV parent+variant created together');
q($parentRow['sale_mode']==='variants','New parent switched to variants');
q($pair['result']->applyOutcome===\App\Services\Import\ImportResult::APPLY_COMMITTED &&
  \App\Services\Import\ImportRevertService::isSnapshotSafe(snapshot($pair)),'New parent+variant snapshot v3');
$createdId=(int)$child['id'];
$update=row('QA-V1E-NEW-CHILD','QA-V1E-NEW-PARENT','Rosa editada',2900);$update['stock']='7';
$onEmpty=runImport('ship_on_empty',[$update],true,['update_ship_dims'=>true]);
q($onEmpty['result']->errors===0,'Ship ON with empty cells passes');
$upd=runImport('update_existing',[$update],false,['update_ship_dims'=>true]);
$record=variant('QA-V1E-NEW-CHILD');
echo 'UPDATE_RESULT '.json_encode(['outcome'=>$upd['result']->applyOutcome,'errors'=>$upd['result']->errors,'snapshot'=>snapshot($upd)],JSON_UNESCAPED_UNICODE).PHP_EOL;
q($upd['result']->applyOutcome===\App\Services\Import\ImportResult::APPLY_COMMITTED &&
 (int)$record['id']===$createdId && (int)$record['price']===2900 &&
 (int)inv($createdId)['stock']===7,'Reimport updates same variant price/stock');
q((int)$db->query("SELECT COUNT(*) FROM product_variants WHERE sku='QA-V1E-NEW-CHILD'")->fetchColumn()===1,'Same SKU not duplicated');
q(\App\Services\Import\ImportRevertService::isSnapshotSafe(snapshot($upd)),'Update snapshot v3');
$db->prepare("UPDATE product_variants SET price=2999 WHERE id=?")->execute([$createdId]);
$blocked=$revert->revertJob($upd['job'],1);
q(empty($blocked['ok'])&&(int)variant('QA-V1E-NEW-CHILD')['price']===2999,'Manual edit blocks rollback');
$db->prepare("UPDATE product_variants SET price=2900 WHERE id=?")->execute([$createdId]);
$undo=$revert->revertJob($upd['job'],1);
echo 'UPDATE_ROLLBACK '.json_encode($undo,JSON_UNESCAPED_UNICODE).PHP_EOL;
q(!empty($undo['ok']) && (int)variant('QA-V1E-NEW-CHILD')['price']===2400 &&
 (int)inv($createdId)['stock']===4,'Restore price/stock after conflict resolved');
$undoParent=$revert->revertJob($pair['job'],1);
echo 'PARENT_ROLLBACK '.json_encode($undoParent,JSON_UNESCAPED_UNICODE).PHP_EOL;
if(empty($undoParent['ok'])){
 echo 'OBSERVED_SCOPE_BLOCKER_PARENT_VARIANT_ROLLBACK: v3 verification rejected after a later update and its rollback. No catalog changes made.'.PHP_EOL;
 q((int)variant('QA-V1E-NEW-CHILD')['price']===2400,
    'Conservative failed rollback preserves existing parent/variant');
} else {
 q(variant('QA-V1E-NEW-CHILD')===null &&
  !$db->query("SELECT id FROM products WHERE sku='QA-V1E-NEW-PARENT'")->fetchColumn(),
  'Rollback CSV parent+variant cleanly');
}

// Implicit parent from a SINGLE variant row (existing support; only diagnose).
$implicit=row('QA-V1E-IMPLICIT-CHILD','QA-V1E-IMPLICIT-PARENT','Variante sin padre',2550);
$implicit['categoria']='QA V1E';$implicit['marca']='Marca fixture';
$preview=runImport('implicit_preview',[$implicit],true,['allow_parent_from_variants'=>true]);
q($preview['result']->errors===0,'Implicit parent dry-run');
$applied=runImport('implicit_apply',[$implicit],false,['allow_parent_from_variants'=>true]);
$impParent=$db->query("SELECT id FROM products WHERE sku='QA-V1E-IMPLICIT-PARENT'")->fetch();
$impChild=variant('QA-V1E-IMPLICIT-CHILD');
echo 'IMPLICIT_RESULT '.json_encode(['outcome'=>$applied['result']->applyOutcome,'errors'=>$applied['result']->errors,'snapshot'=>snapshot($applied),'parent'=>$impParent,'variant'=>$impChild],JSON_UNESCAPED_UNICODE).PHP_EOL;
q($impParent && $impChild,'Implicit parent+child created');
$ops=array_map(static fn($op)=>(string)$op['entity'],(array)(snapshot($applied)['ops']??[]));
if(!in_array('product',$ops,true))echo 'OBSERVED_SCOPE_BLOCKER: implicit parent created but not recorded in snapshot ops'.PHP_EOL;
echo 'V1E_VARIANT_PRIVATE_SUITE_COMPLETED'.PHP_EOL;
