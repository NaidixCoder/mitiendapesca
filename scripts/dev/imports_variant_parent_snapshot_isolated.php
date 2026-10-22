<?php
declare(strict_types=1);
/**
 * 1F-A end-to-end real SQL fixture. Requires a disposable DB and a PRIVATE site
 * clone with DB params in MI_IMPORTS_1FA_DSN.
 * Does not create or modify the commercial database.
 */
$root=dirname(__DIR__,2);
require $root.'/vendor/autoload.php';
$dsn=(string)getenv('MI_IMPORTS_1FA_DSN');
if(!preg_match('/^mysql:host=127\.0\.0\.1;port=\d{4,5};dbname=mi_import_v1fa_[a-z0-9_]+;charset=utf8mb4$/D',$dsn))
    throw new RuntimeException('Private database DSN required');
$db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
if((string)$db->query('SELECT DATABASE()')->fetchColumn()!=='mi_import_v1fa_test')throw new RuntimeException('Unsafe DB');
$GLOBALS['qaDb']=$db;
function db():PDO{return $GLOBALS['qaDb'];}
function ck(bool $value,string $label):void {if(!$value)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
$dir=$root.'/storage/imports';
@mkdir($dir,0700,true);
$headers=['tipo_fila','sku','sku_padre','internal_code','nombre','categoria','marca','proveedor','proveedor_cuit','iva_rate','moneda','costo','margen_pct','precio','precio_lista','precio_mayorista1','precio_mayorista2','stock','umbral','publicado','descripcion','descripcion_larga','attr_1','valor_1','attr_2','valor_2','attr_3','valor_3','peso_envio_g','alto_envio_cm','ancho_envio_cm','largo_envio_cm'];
function vrow(string $sku,string $parent,string $name,string $cat,string $brand='',string $supplier=''):array {
 return ['tipo_fila'=>'variante','sku'=>$sku,'sku_padre'=>$parent,
 'internal_code'=>'CODE-'.$sku,'nombre'=>$name,'categoria'=>$cat,
 'marca'=>$brand,'proveedor'=>$supplier,'moneda'=>'ARS','costo'=>'900',
 'margen_pct'=>'20','precio'=>'2100','precio_lista'=>'2400','stock'=>'4','umbral'=>'2','publicado'=>'1'];
}
function getone(string $table,string $field,string $value):?array{
 global $db;
 $allowed=['products'=>['sku'],'product_variants'=>['sku'],'brands'=>['name'],'categories'=>['name'],'suppliers'=>['nombre']];
 if(!isset($allowed[$table])||!in_array($field,$allowed[$table],true))throw new RuntimeException('unsafe fixture selector');
 $st=$db->prepare("SELECT * FROM $table WHERE $field=?");$st->execute([$value]);return $st->fetch()?:null;
}
function countRows(string $table):int {
 global $db;
 if(!in_array($table,['products','product_variants','brands','categories','suppliers','inventory_variants'],true))throw new RuntimeException('unsafe fixture count');
 return (int)$db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
}
function applyCase(string $name,array $rows,bool $dry=false,array $settings=[]):array{
 global $root,$dir,$headers,$db;
 $csv=$dir.'/qa_1fa_'.$name.'.csv';$fp=fopen($csv,'wb');fwrite($fp,"\xef\xbb\xbf");
 fputcsv($fp,$headers,';');
 foreach($rows as $row) {
   $values=[];foreach($headers as $col)$values[]=$row[$col]??'';
   fputcsv($fp,$values,';');
 }
 fclose($fp);
 $options=['delimiter'=>';','create_missing_products'=>true,'allow_parent_from_variants'=>true,
   'create_missing_brands'=>true,'create_missing_categories'=>true,'create_missing_suppliers'=>true,
   'update_internal_code'=>true,'update_name'=>true,'update_cost'=>true,'update_price'=>true,
   'update_list_price'=>true,'update_stock'=>true,'default_stock_mode'=>'fija','update_publish'=>true,
   'update_ship_dims'=>false,'update_attributes'=>false];
 $jobs=new \App\Repositories\Imports\ImportJobRepository($db);
 $job=$dry?0:$jobs->create('products',basename($csv),$csv);
 $svc=\App\Services\Import\ImportService::fromDb($db);
 (new ReflectionProperty($svc,'snapshotStore'))->setValue($svc,new \App\Services\Import\ImportSnapshotStore(null,$dir));
 $res=$svc->run('products',$csv,$dry,array_replace($options,['job_id'=>$job],$settings));
 if(!$dry){
   if($res->applyOutcome===\App\Services\Import\ImportResult::APPLY_COMMITTED)
     $jobs->markFinished($job,(int)$res->total,(int)$res->ok,(int)$res->errors);
   else $jobs->markFailed($job);
 }
 $f=substr($csv,0,-4).'_rollback.json';
 return ['job'=>$job,'result'=>$res,'snapshot'=>is_file($f)?json_decode((string)file_get_contents($f),true):null,'file'=>$f];
}
function ops(array $entry):array{
 return array_map(static fn(array $op)=>$op['entity'].':'.$op['action'],(array)($entry['snapshot']['ops']??[]));
}
function rollbackCase(int $id):array {global $db;return \App\Services\Import\ImportRevertService::fromDb($db)->revertJob($id,1);}
function verifyV3(array $c,array $expected):void{
 ck($c['result']->applyOutcome===\App\Services\Import\ImportResult::APPLY_COMMITTED
  && is_array($c['snapshot']) && \App\Services\Import\ImportRevertService::isSnapshotSafe($c['snapshot']),
  'SQL committed with verified complete v3');
 $actual=ops($c);
 ck($actual===$expected,'All ops in FK-safe sequence: '.implode(',',$actual));
 foreach($c['snapshot']['ops'] as $op){
    ck(isset($op['after_sha256']) && strlen($op['after_sha256'])===64
       && ($op['action']!=='create' || isset($op['after_rel_sha256'])),
       'Verifiable poststate and created-entity dependencies for '.$op['entity']);
 }
}
$db->exec("INSERT INTO categories(name,slug,is_active) VALUES('QA Preexisting Category','qa-preexisting',1)");
$db->exec("INSERT INTO brands(name,slug) VALUES('QA Preexisting Brand','qa-preexisting-brand')");
$db->exec("INSERT INTO currencies(code,name,symbol,is_base,is_active) VALUES('ARS','Pesos','$',1,1)");
$preexistingProduct=['category'=>getone('categories','name','QA Preexisting Category'),'brand'=>getone('brands','name','QA Preexisting Brand')];
$baseline=[];
foreach(['products','product_variants','brands','categories','suppliers','inventory_variants'] as $table)$baseline[$table]=countRows($table);

$first=vrow('QA-1FA-V1','QA-1FA-IMPLICIT','Anzuelo verde','QA New Category','QA New Brand','QA New Supplier');
$second=vrow('QA-1FA-V2','QA-1FA-IMPLICIT','Anzuelo rojo','QA New Category','QA New Brand','QA New Supplier');
$dry=applyCase('preview_implicit',[$first,$second],true);
ck($dry['result']->errors===0,'Dry-run plans implicit parent');
foreach($baseline as $table=>$count)ck(countRows($table)===$count,'Dry-run no write '.$table);
$job=applyCase('two_siblings',[$first,$second]);
echo 'IMPLICIT_TRACE '.json_encode(['outcome'=>$job['result']->applyOutcome,'errors'=>$job['result']->errors,'ops'=>ops($job)],JSON_UNESCAPED_UNICODE).PHP_EOL;
verifyV3($job,['brand:create','category:create','supplier:create','product:create','variant:create','inventory_variant:create','variant:create','inventory_variant:create']);
$parent=getone('products','sku','QA-1FA-IMPLICIT');
ck($parent!==null && (int)$parent['category_id']>0 && (int)$parent['brand_id']>0
 && getone('suppliers','nombre','QA New Supplier')!==null,
 'Created parent references category/brand; new supplier is independently tracked');
ck($parent['sale_mode']==='variants' && getone('product_variants','sku','QA-1FA-V1')!==null
 && getone('product_variants','sku','QA-1FA-V2')!==null,'Two variants share one new parent');
ck(substr_count(implode('|',ops($job)),'product:create')===1,'Parent creation recorded exactly once');
$undo=rollbackCase($job['job']);echo 'ROLLBACK_IMPLICIT '.json_encode($undo,JSON_UNESCAPED_UNICODE).PHP_EOL;
ck(!empty($undo['ok']) && ($undo['summary']['deleted']??0)===8,'Revert removes eight inserts in reverse order');
foreach($baseline as $table=>$count)ck(countRows($table)===$count,'Exact baseline restored '.$table);
ck(getone('categories','name','QA Preexisting Category')!==null &&
 getone('brands','name','QA Preexisting Brand')!==null,'Preexisting catalogs untouched');
ck(!empty(rollbackCase($job['job'])['summary']['already_reverted']),'Idempotent double rollback');

// Parent already exists: new variant must never create a second parent.
$db->prepare("INSERT INTO products(category_id,brand_id,sku,slug,name,sale_mode,price) VALUES(?,?,?,?,?,'variants',3000)")
 ->execute([(int)$preexistingProduct['category']['id'],(int)$preexistingProduct['brand']['id'],
 'QA-1FA-EXIST','qa-1fa-exist','Existing parent']);
$existingId=(int)$db->lastInsertId();
$existing=vrow('QA-1FA-EX-V','QA-1FA-EXIST','Existing child','QA Preexisting Category','QA Preexisting Brand');
$parentJob=applyCase('existing_parent',[$existing]);
verifyV3($parentJob,['product:update','variant:create','inventory_variant:create']);
ck((int)getone('product_variants','sku','QA-1FA-EX-V')['product_id']===$existingId,
 'Existing parent reused by ID');
ck(!empty(rollbackCase($parentJob['job'])['ok']) && getone('products','sku','QA-1FA-EXIST')!==null,
 'Existing parent never removed by rollback');

// Consecutive jobs with same parent: new job must not duplicate parent catalog ops.
$base=vrow('QA-1FA-CON-A','QA-1FA-CON-P','First variant','QA Consecutive Cat','QA Consecutive Brand','QA Consecutive Supplier');
$j1=applyCase('consecutive_first',[$base]);
verifyV3($j1,['brand:create','category:create','supplier:create','product:create','variant:create','inventory_variant:create']);
$other=vrow('QA-1FA-CON-B','QA-1FA-CON-P','Second variant','QA Consecutive Cat','QA Consecutive Brand','QA Consecutive Supplier');
$j2=applyCase('consecutive_second',[$other]);
verifyV3($j2,['product:update','variant:create','inventory_variant:create']);
ck((int)getone('product_variants','sku','QA-1FA-CON-A')['product_id']
 ===(int)getone('product_variants','sku','QA-1FA-CON-B')['product_id'],
 'Consecutive jobs do not duplicate parent');
$manual=getone('product_variants','sku','QA-1FA-CON-B');
$db->prepare('UPDATE product_variants SET price=2949 WHERE id=?')->execute([(int)$manual['id']]);
$denied=rollbackCase($j2['job']);
ck(empty($denied['ok']) && (int)getone('product_variants','sku','QA-1FA-CON-B')['price']===2949,
 'Manual edit blocks rollback without partial deletion');
ck(getone('products','sku','QA-1FA-CON-P')!==null
 && getone('product_variants','sku','QA-1FA-CON-A')!==null
 && getone('product_variants','sku','QA-1FA-CON-B')!==null,
 'Conflict leaves parent and both variants intact');
ck(empty(rollbackCase($j1['job'])['ok']),'LIFO protects earlier parent after latest conflict');
echo 'V1FA_PRIVATE_SQL_SUITE_PASS'.PHP_EOL;
