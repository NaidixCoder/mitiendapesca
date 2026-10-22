<?php
declare(strict_types=1);
/**
 * I05 - POLÍTICA VINCULANTE: atomicidad total. MySQL descartable únicamente.
 * Ejecutar dentro del clon con MI_IMPORTS_I05_DSN y storage propios.
 */
require dirname(__DIR__,2).'/vendor/autoload.php';
$dsn=(string)getenv('MI_IMPORTS_I05_DSN');
if(!preg_match('/^mysql:host=127\.0\.0\.1;port=18979;dbname=mi_import_i05_test;charset=utf8mb4$/D',$dsn)) {
    throw new RuntimeException('El test I05 requiere el DSN privado y descartable.');
}
$db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if((string)$db->query('SELECT DATABASE()')->fetchColumn()!=='mi_import_i05_test')throw new RuntimeException('DB no permitida');
$GLOBALS['qaDb']=$db;
function db():PDO{return $GLOBALS['qaDb'];}
function ck(bool $v,string $label):void{if(!$v)throw new RuntimeException('FAIL '.$label);echo 'PASS '.$label.PHP_EOL;}
$storage=dirname(__DIR__,2).'/storage/imports';
@mkdir($storage,0700,true);
$headers=['tipo_fila','sku','sku_padre','internal_code','nombre','categoria','marca','proveedor',
 'proveedor_cuit','iva_rate','moneda','costo','margen_pct','precio','precio_lista',
 'precio_mayorista1','precio_mayorista2','stock','umbral','publicado','descripcion',
 'descripcion_larga','attr_1','valor_1','attr_2','valor_2','attr_3','valor_3',
 'peso_envio_g','alto_envio_cm','ancho_envio_cm','largo_envio_cm'];
$jobs=new \App\Repositories\Imports\ImportJobRepository($db);
$baseOptions=[
    'delimiter'=>';','update_name'=>true,'update_cost'=>true,'update_price'=>true,
    'update_list_price'=>true,'update_stock'=>true,'update_publish'=>true,
    'default_stock_mode'=>'fija','update_attributes'=>true,'create_missing_products'=>true,
    'create_missing_brands'=>true,'create_missing_categories'=>true,
    'create_missing_suppliers'=>true,'allow_parent_from_variants'=>false
];
function rowProduct(string $sku,string $name,string $category='QA I05 Category'):array {
    return ['tipo_fila'=>'producto','sku'=>$sku,'nombre'=>$name,'categoria'=>$category,
      'precio'=>'2900','precio_lista'=>'3100','costo'=>'1400','stock'=>'7','umbral'=>'3','publicado'=>'1'];
}
function rowVariant(string $sku,string $parent):array {
    return ['tipo_fila'=>'variante','sku'=>$sku,'sku_padre'=>$parent,'nombre'=>'Variante I05',
      'precio'=>'2300','precio_lista'=>'2600','costo'=>'900','stock'=>'5',
      'umbral'=>'1','attr_1'=>'Color I05','valor_1'=>'Azul'];
}
function runScenario(string $tag,array $rows,bool $dry=false,array $more=[]):array {
    global $storage,$headers,$jobs,$db,$baseOptions;
    $file=$storage.'/qa_i05_final_'.$tag.'.csv';
    $fp=fopen($file,'wb');fwrite($fp,"\xef\xbb\xbf");fputcsv($fp,$headers,';');
    foreach($rows as $row) {
        $ordered=[];
        foreach($headers as $key)$ordered[]=$row[$key]??'';
        fputcsv($fp,$ordered,';');
    }
    fclose($fp);
    $id=$dry?0:$jobs->create('products',basename($file),$file);
    $svc=\App\Services\Import\ImportService::fromDb($db);
    (new ReflectionProperty($svc,'snapshotStore'))->setValue($svc,
        new \App\Services\Import\ImportSnapshotStore(null,$storage));
    $result=$svc->run('products',$file,$dry,array_merge($baseOptions,$more,['job_id'=>$id]));
    if(!$dry) {
        if($result->applyOutcome==='committed')$jobs->markFinished($id,$result->total,$result->ok,$result->errors);
        elseif($result->applyOutcome==='rolled_back')$jobs->markFailed($id,'I05 transacción cancelada');
    }
    return ['job'=>$id,'result'=>$result,'snapshot'=>substr($file,0,-4).'_rollback.json'];
}
function counts():array {
    global $db;
    $tables=['products','product_variants','inventory','inventory_variants','brands','categories',
        'suppliers','attributes','attribute_values','product_variant_values',
        'product_attributes','product_attribute_values'];
    $result=[];
    foreach($tables as $t)$result[$t]=(int)$db->query('SELECT COUNT(*) FROM '.$t)->fetchColumn();
    return $result;
}
function productRow(string $sku):?array {
    global $db;
    $st=$db->prepare('SELECT * FROM products WHERE sku=?');$st->execute([$sku]);
    return $st->fetch()?:null;
}
$db->exec("INSERT INTO categories(name,slug,is_active) VALUES('QA I05 Category','qa-i05-category',1)");
$cat=(int)$db->lastInsertId();
$db->prepare("INSERT INTO products(category_id,sku,slug,name,sale_mode,price,cost)
 VALUES(?,?,?,'Producto existente','simple',1800,800)")
 ->execute([$cat,'QA-I05-EXIST','qa-i05-exist']);
$parentBefore=productRow('QA-I05-EXIST');
$invalid=rowProduct('','Producto inválido');
foreach(['valid_then_invalid','invalid_then_valid'] as $order) {
    $good=rowProduct('QA-I05-NEW-'.$order,'Nuevo primero');
    $rows=$order==='valid_then_invalid'?[$good,$invalid]:[$invalid,$good];
    $before=counts();
    $test=runScenario($order,$rows,false);
    ck($test['result']->applyOutcome==='rolled_back','I05 '.$order.' transaction ROLLED_BACK');
    ck($test['result']->errors===1 && count($test['result']->list)===1,
        'I05 '.$order.' maintains exact invalid-row diagnostics');
    ck($test['result']->ok>=1,'I05 '.$order.' preserves valid row diagnostics');
    ck(counts()===$before && productRow($good['sku'])===null,
        'I05 '.$order.' zero persisted commercial changes');
    ck(!is_file($test['snapshot']),'I05 '.$order.' never publishes snapshot');
    ck((string)$db->query('SELECT status FROM import_jobs WHERE id='.$test['job'])->fetchColumn()==='failed',
        'I05 '.$order.' job never finished');
}
$before=counts();
$old=rowProduct('QA-I05-EXIST','Producto cambiado');
$novel=rowProduct('QA-I05-NEW2','Producto nuevo y catálogo','QA I05 Created Category');
$novel['marca']='QA I05 Created Brand';
$novel['proveedor']='QA I05 Created Supplier';
$val=rowVariant('QA-I05-V-ATTR','QA-I05-EXIST');
$batch=runScenario('full_mutations_cancelled',[$old,$novel,$val,$invalid]);
ck($batch['result']->applyOutcome==='rolled_back'&&$batch['result']->errors===1,
    'I05 validates full CSV after existing/new product, variant, stock and attributes');
ck(counts()===$before && productRow('QA-I05-EXIST')===$parentBefore,
    'I05 restores existing price/cost/stock/attrs, drops new parent/variant/catalogue');
ck(!is_file($batch['snapshot']),'I05 no rollback file after complex transaction rolled back');
ck(count($batch['result']->rows)===4,'I05 all four row explanations preserved');

$warn=rowVariant('QA-I05-WARN','QA-I05-EXIST');
$warn['iva_rate']='21';
$warnResult=runScenario('informational_warning',[$warn]);
ck($warnResult['result']->applyOutcome==='committed' &&
   $warnResult['result']->errors===0 &&
   $warnResult['result']->warnings>=1,'I05 informational warning is NON-blocking');
ck(is_file($warnResult['snapshot']) &&
  \App\Services\Import\ImportRevertService::isSnapshotSafe(
      json_decode((string)file_get_contents($warnResult['snapshot']),true)),
  'I05 warning commit has valid v3 snapshot');
$undo=\App\Services\Import\ImportRevertService::fromDb($db)->revertJob($warnResult['job'],1);
ck(!empty($undo['ok']),'I05 successful warning job remains revertible');

$db->exec("CREATE TRIGGER qa_i05_force_variant_fail BEFORE INSERT ON product_variants
 FOR EACH ROW BEGIN IF NEW.sku='QA-I05-SQL-FAULT'
 THEN SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT='QA simulated variant write failure'; END IF; END");
$clean=counts();
$fault=runScenario('apply_sql_fault',[rowProduct('QA-I05-FAULT-P','Padre antes del error'),rowVariant('QA-I05-SQL-FAULT','QA-I05-FAULT-P')]);
ck($fault['result']->applyOutcome==='rolled_back' &&
    $fault['result']->errors>=1,'I05 SQL fault after parent mutation forces rollback');
ck(counts()===$clean && productRow('QA-I05-FAULT-P')===null,
   'I05 triggered mid-Apply failure leaves zero SQL commercial state');
ck(!is_file($fault['snapshot']),'I05 SQL failure never leaves v3 snapshot');
$db->exec('DROP TRIGGER qa_i05_force_variant_fail');
echo 'I05_ATOMIC_ALL_OR_NOTHING_ISOLATED_PASS'.PHP_EOL;
