<?php
declare(strict_types=1);
/** Private-only sabotage test: injected into disposable VariantImporter clone. */
$root=dirname(__DIR__,2);
require $root.'/vendor/autoload.php';
$dsn=(string)getenv('MI_IMPORTS_1FA_DSN');
if(!preg_match('/^mysql:host=127\.0\.0\.1;port=18929;dbname=mi_import_v1fa_test;charset=utf8mb4$/D',$dsn))
    throw new RuntimeException('This test requires the private V1F-A database');
$db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if((string)$db->query('SELECT DATABASE()')->fetchColumn()!=='mi_import_v1fa_test')throw new RuntimeException('Wrong DB');
$GLOBALS['qaDb']=$db;
function db():PDO{return $GLOBALS['qaDb'];}
$check=static function(bool $v,string $name):void {
    if(!$v)throw new RuntimeException('FAIL '.$name);
    echo 'PASS '.$name.PHP_EOL;
};
$before=[];
foreach(['products','product_variants','brands','categories','suppliers','inventory_variants'] as $table)
  $before[$table]=(int)$db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
$directory=$root.'/storage/imports';@mkdir($directory,0700,true);
$file=$directory.'/qa_1fa_fault_'.bin2hex(random_bytes(3)).'.csv';
$header=['tipo_fila','sku','sku_padre','internal_code','nombre','categoria','marca','proveedor',
    'precio','costo','stock','modo_stock'];
$row=['variante','QA-1FA-FAULT-V','QA-1FA-FAULT-P','QA-FAULT-V','Anzuelo prueba',
 'QA FAULT CATEGORY','QA FAULT BRAND','QA FAULT SUPPLIER','2100','900','3','fija'];
$h=fopen($file,'wb');fputcsv($h,$header,';');fputcsv($h,$row,';');fclose($h);
$jobs=new \App\Repositories\Imports\ImportJobRepository($db);
$job=$jobs->create('products',basename($file),$file);
$service=\App\Services\Import\ImportService::fromDb($db);
(new ReflectionProperty($service,'snapshotStore'))->setValue($service,
  new \App\Services\Import\ImportSnapshotStore(null,$directory));
$options=['job_id'=>$job,'delimiter'=>';','create_missing_products'=>true,
    'allow_parent_from_variants'=>true,'create_missing_brands'=>true,
    'create_missing_categories'=>true,'create_missing_suppliers'=>true,
    'update_price'=>true,'update_stock'=>true,'default_stock_mode'=>'fija'];
$result=$service->run('products',$file,false,$options);
$check($result->applyOutcome===\App\Services\Import\ImportResult::APPLY_ROLLED_BACK,
    'Missing parent snapshot trace cancels entire SQL transaction');
$check($result->errors>0,'Missing trace produces an explicit error');
foreach($before as $table=>$count) {
 $check((int)$db->query("SELECT COUNT(*) FROM $table")->fetchColumn()===$count,
       'No partial changes after injected trace fault '.$table);
}
$snap=preg_replace('/\.csv$/','_rollback.json',$file);
$check(!is_file($snap),'No published recovery file after rollback');
@unlink($file);
echo 'V1FA_PRIVATE_INCOMPLETE_SNAPSHOT_FAIL_CLOSED_PASS'.PHP_EOL;
