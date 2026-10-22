<?php
declare(strict_types=1);
/** Solo SQL sintético descartable. No usar sobre la BD comercial. */
require dirname(__DIR__,2).'/vendor/autoload.php';
$dsn=(string)getenv('MI_IMPORTS_INTEGRAL_DSN');
if(!preg_match('/^mysql:host=127\.0\.0\.1;port=18969;dbname=mi_import_integral_test;charset=utf8mb4$/D',$dsn)){
    throw new RuntimeException('No es una BD de QA autorizada');
}
$db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if($db->query('SELECT DATABASE()')->fetchColumn()!=='mi_import_integral_test')throw new RuntimeException('DB no descartable');
$GLOBALS['qaDb']=$db;
function db():PDO{return $GLOBALS['qaDb'];}
function ok(bool $pass,string $m):void {
    if(!$pass)throw new RuntimeException('FAIL '.$m);
    echo 'PASS '.$m.PHP_EOL;
}
$dir=dirname(__DIR__,2).'/storage/imports';if(!is_dir($dir))mkdir($dir,0700,true);
$jobs=new \App\Repositories\Imports\ImportJobRepository($db);
$rev=\App\Services\Import\ImportRevertService::fromDb($db);
$columns=['tipo_fila','sku','sku_padre','internal_code','nombre','categoria','marca','proveedor',
 'proveedor_cuit','iva_rate','moneda','costo','margen_pct','precio','precio_lista','precio_mayorista1',
 'precio_mayorista2','stock','umbral','publicado','descripcion','descripcion_larga',
 'attr_1','valor_1','attr_2','valor_2','attr_3','valor_3',
 'peso_envio_g','alto_envio_cm','ancho_envio_cm','largo_envio_cm'];
function makeRow(string $sku,string $color):array {
 return ['tipo_fila'=>'variante','sku'=>$sku,'sku_padre'=>'QA-INTEGRAL-P',
    'nombre'=>'Anzuelo QA '.$sku,'precio'=>'2500','costo'=>'950',
    'attr_1'=>'Color','valor_1'=>$color];
}
function importRows(string $name,array $rows,bool $dry=false):array {
 global $columns,$dir,$jobs,$db;
 $csv=$dir.'/qa_integral_'.$name.'.csv';$h=fopen($csv,'wb');fwrite($h,"\xef\xbb\xbf");
 fputcsv($h,$columns,';');
 foreach($rows as $row){$cols=[];foreach($columns as $k)$cols[]=$row[$k]??'';fputcsv($h,$cols,';');}
 fclose($h);
 $id=$dry?0:$jobs->create('products',basename($csv),$csv);
 $service=\App\Services\Import\ImportService::fromDb($db);
 (new ReflectionProperty($service,'snapshotStore'))->setValue($service,
    new \App\Services\Import\ImportSnapshotStore(null,$dir));
 $options=['job_id'=>$id,'delimiter'=>';','update_attributes'=>true,
   'create_missing_products'=>true,'allow_parent_from_variants'=>false,
   'update_price'=>true,'update_stock'=>false,'update_cost'=>true,
   'update_name'=>true,'update_ship_dims'=>false];
 $res=$service->run('products',$csv,$dry,$options);
 if(!$dry){
   if($res->applyOutcome===\App\Services\Import\ImportResult::APPLY_COMMITTED){
      $jobs->markFinished($id,$res->total,$res->ok,$res->errors);
   }else{$jobs->markFailed($id);}
 }
 $file=substr($csv,0,-4).'_rollback.json';
 return ['result'=>$res,'job'=>$id,'snapshot'=>is_file($file)?json_decode(file_get_contents($file),true):null];
}
function v(string $sku):?array {
 global $db;$s=$db->prepare('SELECT * FROM product_variants WHERE sku=?');$s->execute([$sku]);
 return $s->fetch()?:null;
}
function rows(string $table,string $where='1=1'):array {
 global $db;
 if(!in_array($table,['attributes','attribute_values','product_variant_values','product_attributes','product_attribute_values'],true))
 throw new RuntimeException('Invalid table');
 return $db->query("SELECT * FROM $table WHERE $where")->fetchAll();
}

$db->exec("INSERT INTO categories(name,slug,is_active) VALUES('QA Integral','qa-integral',1)");
$cat=(int)$db->lastInsertId();
$db->prepare("INSERT INTO products(category_id,sku,slug,name,sale_mode,price) VALUES(?,?,?,'Parent QA','variants',3000)")
  ->execute([$cat,'QA-INTEGRAL-P','qa-integral-p']);
$parentId=(int)$db->lastInsertId();
$create=importRows('new',[makeRow('QA-INTEGRAL-NEW','Azul')]);
ok($create['result']->applyOutcome==='committed','CSV oficial attr_1: COMMITTED');
$variant=v('QA-INTEGRAL-NEW');
ok(is_array($variant),'Variante nueva persistida');
$vid=(int)$variant['id'];
ok(count(rows('attributes'))===1 && count(rows('attribute_values'))===1,'Atributo y valor maestros creados');
ok(count(rows('product_variant_values',"variant_id=$vid"))===1,'product_variant_values creado');
ok(count(rows('product_attributes',"product_id=$parentId"))===1,'product_attributes creado');
ok(count(rows('product_attribute_values',"product_id=$parentId"))===1,'product_attribute_values creado');
$ss=$create['snapshot'];
ok(\App\Services\Import\ImportRevertService::isSnapshotSafe($ss),'Snapshot v3 seguro para asociaciones');
$ops=array_map(static fn($op)=>$op['entity'].':'.$op['action'],$ss['ops']);
echo 'ATTRIBUTE_OPS '.implode(' > ',$ops).PHP_EOL;
foreach(['attribute:create','attribute_value:create','relation_variant_values:update',
         'relation_product_attrs:update','relation_product_attr_values:update'] as $part){
 ok(in_array($part,$ops,true),'Snapshot documenta '.$part);
}
$back=$rev->revertJob($create['job'],0);
echo 'REVERT_NEW '.json_encode($back,JSON_UNESCAPED_UNICODE).PHP_EOL;
ok(!empty($back['ok']),'Rollback íntegro de asociaciones nuevas');
ok(!v('QA-INTEGRAL-NEW') && !rows('attributes') && !rows('attribute_values')
 && !rows('product_attributes') && !rows('product_attribute_values')
 && !rows('product_variant_values'),'No quedan maestros ni asociaciones huérfanas');
ok((int)$db->query('SELECT count(*) FROM products WHERE id='.$parentId)->fetchColumn()===1,
 'Producto padre preexistente preservado');

$db->prepare('INSERT INTO attributes(name,slug,type) VALUES(?,?,?)')->execute(['Color','color','select']);
$attr=(int)$db->lastInsertId();
$db->prepare('INSERT INTO attribute_values(attribute_id,value,sort) VALUES(?,?,0)')->execute([$attr,'Rojo']);
$red=(int)$db->lastInsertId();
$db->prepare('INSERT INTO attribute_values(attribute_id,value,sort) VALUES(?,?,0)')->execute([$attr,'Azul']);
$blue=(int)$db->lastInsertId();
$db->prepare("INSERT INTO product_variants(product_id,sku,price,title) VALUES(?,?,?,?)")
 ->execute([$parentId,'QA-INTEGRAL-EXIST',2000,'Existing']);
$existing=(int)$db->lastInsertId();
$db->prepare('INSERT INTO product_variant_values(variant_id,attribute_id,value_id) VALUES(?,?,?)')
 ->execute([$existing,$attr,$red]);
$db->prepare('INSERT INTO product_attributes(product_id,attribute_id,sort,is_required) VALUES(?,?,7,1)')
 ->execute([$parentId,$attr]);
$db->prepare('INSERT INTO product_attribute_values(product_id,attribute_id,value_id) VALUES(?,?,?)')
 ->execute([$parentId,$attr,$red]);

$change=importRows('replace',[makeRow('QA-INTEGRAL-EXIST','Azul')]);
ok($change['result']->applyOutcome==='committed','Actualización de atributo existente COMMITTED');
ok((int)rows('product_variant_values',"variant_id=$existing")[0]['value_id']===$blue,
 'Reemplazo rojo->azul persistido');
ok(count(rows('product_attribute_values',"product_id=$parentId"))===2,
 'Producto conserva rojo y asegura azul');
ok(\App\Services\Import\ImportRevertService::isSnapshotSafe($change['snapshot']),
 'Snapshot actualización verificable');
$beforeOps=array_values(array_filter($change['snapshot']['ops'],
 static fn($op)=>$op['entity']==='relation_variant_values'));
ok(isset($beforeOps[0]['before']['rows'][0]) &&
 (int)$beforeOps[0]['before']['rows'][0]['value_id']===$red,'BEFORE conserva asociación roja');
$db->prepare('UPDATE product_variant_values SET value_id=? WHERE variant_id=?')
 ->execute([$red,$existing]);
$blocked=$rev->revertJob($change['job'],0);
ok(empty($blocked['ok']) && count(rows('product_attribute_values',"product_id=$parentId"))===2,
 'Edición manual bloquea reversión sin cambios parciales');
$db->prepare('UPDATE product_variant_values SET value_id=? WHERE variant_id=?')
 ->execute([$blue,$existing]);
$restore=$rev->revertJob($change['job'],0);
echo 'REVERT_REPLACE '.json_encode($restore,JSON_UNESCAPED_UNICODE).PHP_EOL;
ok(!empty($restore['ok']) && (int)rows('product_variant_values',"variant_id=$existing")[0]['value_id']===$red,
 'Rollback restaura vínculo antiguo');
ok(count(rows('product_attribute_values',"product_id=$parentId"))===1 &&
 (int)rows('product_attribute_values',"product_id=$parentId")[0]['value_id']===$red,
 'Rollback restaura valores asociados producto');
ok((int)rows('product_attributes',"product_id=$parentId")[0]['is_required']===1,
 'Rollback preserva metadatos is_required y sort');

$again=importRows('repeat',[makeRow('QA-INTEGRAL-EXIST','Rojo')]);
ok($again['result']->applyOutcome==='committed' &&
 count(rows('product_variant_values',"variant_id=$existing"))===1,
 'Reimportar mismo SKU y valor no duplica');
$bad=makeRow('QA-INTEGRAL-BAD','');$bad['attr_1']='Color';
$dry=importRows('invalid',[$bad],true);
ok($dry['result']->errors>0 && !v('QA-INTEGRAL-BAD'),'CSV atributo incompleto rechazado antes de escribir');
$pairA=makeRow('QA-INTEGRAL-PAIR-A','Azul');
$pairA['attr_2']='Material';$pairA['valor_2']='Acero';
$pairB=makeRow('QA-INTEGRAL-PAIR-B','Rojo');
$pairB['attr_2']='Material';$pairB['valor_2']='Acero';
$two=importRows('two_variants_two_attrs',[$pairA,$pairB]);
ok($two['result']->applyOutcome==='committed' &&
 \App\Services\Import\ImportRevertService::isSnapshotSafe($two['snapshot']),
 'Dos variantes/dos atributos de CSV generan v3 completo');
$entityOps=array_map(static fn($x)=>$x['entity'].':'.$x['action'],$two['snapshot']['ops']);
ok(count(array_filter($entityOps,static fn($x)=>$x==='relation_product_attrs:update'))===1 &&
 count(array_filter($entityOps,static fn($x)=>$x==='relation_product_attr_values:update'))===1,
 'Scope producto deduplicado en dos filas mismo padre');
$a=v('QA-INTEGRAL-PAIR-A');$b=v('QA-INTEGRAL-PAIR-B');
ok($a!==null && $b!==null &&
 count(rows('product_variant_values',"variant_id=".(int)$a['id']))===2 &&
 count(rows('product_variant_values',"variant_id=".(int)$b['id']))===2,
 'Dos variantes con pares Color/Material en SQL');
$rbTwo=$rev->revertJob($two['job'],0);
ok(!empty($rbTwo['ok']) && !v('QA-INTEGRAL-PAIR-A') && !v('QA-INTEGRAL-PAIR-B') &&
 count(rows('product_attributes',"product_id=$parentId"))===1 &&
 count(rows('product_attribute_values',"product_id=$parentId"))===1,
 'Rollback multi-variant restaura scope producto original');
echo 'IMPORTS_ATTRIBUTES_INTEGRAL_SQL_PASS'.PHP_EOL;
