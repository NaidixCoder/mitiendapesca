<?php
declare(strict_types=1);
/**
 * V1C, MySQL descartable. Ejecutar --baseline antes del cambio, o --verify después.
 * Nunca escribe en la BD habitual. Requiere XAMPP MySQL disponible.
 */
$root=dirname(__DIR__,2);
require $root.'/vendor/autoload.php';
use App\Repositories\Imports\ImportJobRepository;
use App\Services\Import\ImportService;
use App\Services\Import\ImportRevertService;
// Auditoría en el fixture, nunca en el singleton de la base comercial.
function db(): PDO { return $GLOBALS['v1c_test_db']; }

function check(bool $ok,string $message): void { if(!$ok)throw new RuntimeException($message); echo "PASS $message\n"; }
function readEnv(string $file): array {
 $wanted=['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS'];$out=[];
 foreach(file($file,FILE_IGNORE_NEW_LINES)?:[] as $line) {
  if(!preg_match('/^\s*(DB_HOST|DB_PORT|DB_NAME|DB_USER|DB_PASS)\s*=(.*)$/',$line,$m))continue;
  $v=trim($m[2]);if(strlen($v)>1&&in_array($v[0],['"',"'"],true)&&substr($v,-1)===$v[0])$v=substr($v,1,-1);
  $out[$m[1]]=$v;
 }
 foreach($wanted as $key)if(!array_key_exists($key,$out))throw new RuntimeException('Falta '.$key);
 return $out;
}
$e=readEnv($root.'/.env');
$e=readEnv($root.'/.env');
// Worker solo admite un identificador de BD temporal con el prefijo reservado.
if (($argv[1] ?? '') === '--worker') {
 $target=(string)($argv[2]??'');
 $jid=(int)($argv[3]??0);
 if (!preg_match('/^mi_import_v1c_[0-9a-f]{8}$/',$target)
     || $target===$e['DB_NAME'] || $jid<=0) throw new RuntimeException('Worker guard failed');
 $conn=new PDO("mysql:host={$e['DB_HOST']};port={$e['DB_PORT']};dbname={$target};charset=utf8mb4",
     $e['DB_USER'],$e['DB_PASS'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
 $GLOBALS['v1c_test_db']=$conn;
 $r=\App\Services\Import\ImportRevertService::fromDb($conn)->revertJob($jid,0);
 echo json_encode($r,JSON_UNESCAPED_UNICODE).PHP_EOL;
 exit(empty($r['ok'])?1:0);
}
$dsn="mysql:host={$e['DB_HOST']};port={$e['DB_PORT']};charset=utf8mb4";
$opts=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false];
$admin=new PDO($dsn,$e['DB_USER'],$e['DB_PASS'],$opts);
$source=$e['DB_NAME'];
$test='mi_import_v1c_'.bin2hex(random_bytes(4));
check((bool)preg_match('/^mi_import_v1c_[0-9a-f]{8}$/',$test)&&$source!==$test,'Nombre DB aislado y guard');
$tmp=sys_get_temp_dir().DIRECTORY_SEPARATOR.'imports_v1c_'.bin2hex(random_bytes(6));
mkdir($tmp,0700,true);
$created=false;
$mode=$argv[1]??'--verify';
$log=[];
try {
 $admin->exec("CREATE DATABASE `$test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
 $created=true;
 $src=new PDO($dsn.';dbname='.$source,$e['DB_USER'],$e['DB_PASS'],$opts);
 $db=new PDO($dsn.';dbname='.$test,$e['DB_USER'],$e['DB_PASS'],$opts);
 check((string)$db->query('SELECT DATABASE()')->fetchColumn()===$test,'Conexión protegida a fixture');
 $db->exec('SET FOREIGN_KEY_CHECKS=0');
 $tables=$src->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM);$num=0;
 foreach($tables as $t) {
  if(($t[1]??'')!=='BASE TABLE')continue;
  $name=(string)$t[0];
  if(!preg_match('/^[a-zA-Z0-9_]+$/',$name))throw new RuntimeException('Bad schema name');
  $ddl=$src->query("SHOW CREATE TABLE `$name`")->fetch(PDO::FETCH_NUM)[1];
  $db->exec($ddl);$num++;
 }
 $db->exec('SET FOREIGN_KEY_CHECKS=1');
 check($num>=40,'Estructura aislada sin filas comerciales: '.$num.' tablas');
 // Auditoría auxiliar debe operar en la BD aislada, nunca sobre DB singleton habitual.
 $GLOBALS['v1c_test_db']=$db;
 $jobs=new ImportJobRepository($db);
 $service=ImportService::fromDb($db);
 $revert=ImportRevertService::fromDb($db);
 $headers=['tipo_fila','sku','sku_padre','internal_code','nombre','categoria','marca','proveedor','proveedor_cuit','iva_rate','moneda','costo','margen_pct','precio','precio_lista','precio_mayorista1','precio_mayorista2','stock','umbral','publicado','descripcion','descripcion_larga','attr_1','valor_1','attr_2','valor_2','attr_3','valor_3','peso_envio_g','alto_envio_cm','ancho_envio_cm','largo_envio_cm'];
 $opt=['delimiter'=>';','create_missing_products'=>true,'create_missing_brands'=>true,'create_missing_categories'=>true,'create_missing_suppliers'=>true,'update_price'=>true,'update_cost'=>true,'update_list_price'=>true,'update_iva'=>true,'update_stock'=>true,'default_stock_mode'=>'suma','update_name'=>true,'update_internal_code'=>true];
 $apply=function(string $name,array $items,array $extra=[]) use($tmp,$headers,$jobs,$service,$opt): int {
  $file=$tmp.DIRECTORY_SEPARATOR.$name.'.csv';
  $h=fopen($file,'wb');fwrite($h,"\xEF\xBB\xBF");fputcsv($h,$headers,';');
  foreach($items as $row){$record=[];foreach($headers as $key)$record[]=(string)($row[$key]??'');fputcsv($h,$record,';');}
  fclose($h);
  $id=$jobs->create('products',$name.'.csv',$file);
  check($id>0,'Job sintético creado: '.$name);
  $rp=new ReflectionProperty($service, 'snapshotStore');
  $rp->setValue($service, new \App\Services\Import\ImportSnapshotStore(null, $tmp));
  $result=$service->run('products',$file,false,$opt+['job_id'=>$id]+$extra);
  $jobs->markFinished($id,(int)$result->total,(int)$result->ok,(int)$result->errors);
  if ($result->errors !== 0) echo 'IMPORT_ERRORS '.json_encode($result->toArray(), JSON_UNESCAPED_UNICODE).PHP_EOL;
  check($result->errors===0,'Apply sintético: '.$name.' errores='.$result->errors);
  return $id;
 };
 $row=['tipo_fila'=>'producto','sku'=>'V1C-001','internal_code'=>'V1C001','nombre'=>'Caña de prueba','categoria'=>'Pruebas 1C','marca'=>'Prueba 1C','proveedor'=>'Proveedor 1C','costo'=>'1000','precio'=>'2000','precio_lista'=>'2300','stock'=>'4'];
 $job1=$apply('create',[$row]);
 $row['precio']='2480';$row['costo']='1250';$row['stock']='2';
 $job2=$apply('update',[$row]);
 $read=function()use($db):array{$r=$db->query("SELECT p.id,p.price,p.cost,i.stock FROM products p JOIN inventory i ON i.product_id=p.id WHERE p.sku='V1C-001'")->fetch();if(!$r)throw new RuntimeException('Product missing');return $r;};
 $initial=$read();
 check((int)$initial['price']===2480,'Precio preconflicto=2480');
 $db->exec("UPDATE products SET price=2499 WHERE sku='V1C-001'");
 check((int)$read()['price']===2499,'Edición manual posterior=2499');
 $result=$revert->revertJob($job2,1);
 $final=$read();
 echo 'OBSERVED mode='.$mode.' reverted='.(!empty($result['ok'])?'yes':'no').' current_price='.$final['price'].' revert_status='.$jobs->find($job2)['revert_status']."\n";
 if($mode==='--baseline'){
  check(!empty($result['ok'])&&(int)$final['price']===2000,'P0 reproducido: sobrescribe 2499 y vuelve a 2000');
 }else{
  check(empty($result['ok'])&&(int)$final['price']===2499,'P0 corregido: precio manual intacto');
  $snap2=json_decode(file_get_contents($tmp.'/update_rollback.json'),true);
  check(($snap2['version']??0)===3 && !empty($snap2['complete'])
      && strlen((string)($snap2['ops'][0]['after_sha256']??''))===64,'Snapshot v3 con huella posestado');
  // Job posterior bloqueado no habilita reversión de un import más viejo.
  $older=$revert->revertJob($job1,1);
  check(empty($older['ok']) && $jobs->find($job1)['revert_status']==='blocked_not_last',
      'LIFO impide salto sobre job bloqueado por conflicto');
  // Alta y rollback normal sin cambios posteriores.
  $rowB=$row;
  $rowB['sku']='V1C-002';$rowB['internal_code']='V1C002';$rowB['precio']='3000';$rowB['stock']='3';
  $jobB=$apply('newclean',[$rowB]);
  $result=$revert->revertJob($jobB,1);
  if (empty($result['ok'])) echo 'NORMAL_DIAG '.json_encode(['snapshot'=>json_decode((string)file_get_contents($tmp.'/newclean_rollback.json'),true),'result'=>$result],JSON_UNESCAPED_UNICODE).PHP_EOL;
  check(!empty($result['ok']) && $jobs->find($jobB)['revert_status']==='reverted',
      'Reversión de alta sin cambios posteriores');
  check((int)$db->query("SELECT count(*) FROM products WHERE sku='V1C-002'")->fetchColumn()===0,
      'Alta limpia eliminada (no desactivada)');
  // Costo: modificación posterior del registro entero.
  $row['costo']='1450';$row['precio']='2600';$row['stock']='2';
  $costJob=$apply('cost_update',[$row]);
  $db->exec("UPDATE products SET cost=9999 WHERE sku='V1C-001'");
  $res=$revert->revertJob($costJob,1);
  check(empty($res['ok']) && (int)$read()['cost']===9999 && $jobs->find($costJob)['revert_status']==='blocked_conflict',
      'Costo manual preservado');
  // Stock independiente: inventory (PK product_id, sin updated_at).
  $row['cost']='';$row['costo']='';$row['precio']='';$row['stock']='3';
  $stockJob=$apply('stock_update',[$row]);
  $db->exec("UPDATE inventory SET stock=777 WHERE product_id=(SELECT id FROM products WHERE sku='V1C-001')");
  $res=$revert->revertJob($stockJob,1);
  check(empty($res['ok']) && (int)$read()['stock']===777,
      'Stock manual preservado ante conflicto');
  // Crear una variante (también deberá detectar edición posterior).
  $variant=['tipo_fila'=>'variante','sku'=>'V1C-VAR-1','sku_padre'=>'V1C-001',
      'nombre'=>'Variante azul','precio'=>'1234','precio_lista'=>'1444','costo'=>'500'];
  // El importador de variantes vigente referencia ship_weight ausente del esquema
  // real. Ese fallo AJENO no se corrige aquí; comprobamos el guard con fixture
  // de variante y snapshot v3 formado con la misma API.
  $parent=(int)$db->query("SELECT id FROM products WHERE sku='V1C-001'")->fetchColumn();
  $db->prepare("INSERT INTO product_variants(product_id,sku,title,price,cost,is_active) VALUES(?,?,?,?,?,1)")
      ->execute([$parent,'V1C-VAR-1','Variante azul',1234,500]);
  $variantId=(int)$db->lastInsertId();
  check($variantId>0,'Variante de fixture insertada');
  $varCsv=$tmp.'/variant_guard.csv';
  file_put_contents($varCsv,'fixture');
  $varJob=$jobs->create('products','variant_guard.csv',$varCsv);
  $jobs->markFinished($varJob,1,1,0);
  $stateRepo=new \App\Repositories\Imports\ImportRevertRepository($db);
  $varSnap=['version'=>3,'complete'=>true,'job_id'=>$varJob,'ops'=>[[
      'entity'=>'variant','action'=>'update','pk'=>['id'=>$variantId],
      'before'=>['price'=>1000],
      'after_sha256'=>$stateRepo->fingerprint('variant',$variantId),
      'after_rel_sha256'=>$stateRepo->dependentFingerprint('variant',$variantId),
  ]]];
  file_put_contents($tmp.'/variant_guard_rollback.json',json_encode($varSnap));
  check(\App\Services\Import\ImportRevertService::isSnapshotSafe($varSnap),'Snapshot variante verificable');
  $db->prepare("UPDATE product_variants SET price=1987 WHERE id=?")->execute([$variantId]);
  $varResult=$revert->revertJob($varJob,1);
  check(empty($varResult['ok']) && (int)$db->query("SELECT price FROM product_variants WHERE id=$variantId")->fetchColumn()===1987,
      'Variante creada/editada queda intacta');
  // Registro creado luego editado.
  $rowC=$rowB;$rowC['sku']='V1C-003';$rowC['internal_code']='V1C003';
  $createdJob=$apply('new_edited',[$rowC]);
  $db->exec("UPDATE products SET name='Nombre editado manualmente' WHERE sku='V1C-003'");
  $res=$revert->revertJob($createdJob,1);
  check(empty($res['ok']) && (int)$db->query("SELECT count(*) FROM products WHERE sku='V1C-003' AND name='Nombre editado manualmente'")->fetchColumn()===1,
      'Entidad recién creada y modificada no se elimina');
  // v2 legacy: no aportar artificialmente AFTER ni convertir snapshots viejos.
  $legacy=$apply('old_snap',[$row]);
  $oldPath=$tmp.'/old_snap_rollback.json';
  $oldRaw=json_decode(file_get_contents($oldPath),true);
  $oldRaw['version']=2;unset($oldRaw['complete']);foreach($oldRaw['ops'] as &$op)unset($op['after_sha256']);unset($op);
  file_put_contents($oldPath,json_encode($oldRaw));
  $res=$revert->revertJob($legacy,1);
  check(empty($res['ok']) && $jobs->find($legacy)['revert_status']==='blocked_conflict',
      'Snapshot v2 anterior bloqueado sin conversión');
  // Snapshot v3 incompleto.
  $incomplete=$apply('missing_after',[$row]);
  $badPath=$tmp.'/missing_after_rollback.json';
  $bad=json_decode(file_get_contents($badPath),true);
  unset($bad['ops'][0]['after_sha256']);file_put_contents($badPath,json_encode($bad));
  $res=$revert->revertJob($incomplete,1);
  check(empty($res['ok']), 'Snapshot v3 con AFTER omitido bloqueado');
  // Snapshot ausente.
  $missing=$apply('missing_file',[$row]);
  unlink($tmp.'/missing_file_rollback.json');
  $res=$revert->revertJob($missing,1);
  check(empty($res['ok']), 'Snapshot inexistente bloqueado');
  check($jobs->find($missing)['revert_status']==='blocked_conflict',
      'Bloqueo persistido para auditoría');
  // Una dependencia creada después debe impedir borrar el producto padre.
  $rowD=$rowB;$rowD['sku']='V1C-004';$rowD['internal_code']='V1C004';
  $dependentJob=$apply('dependent_new',[$rowD]);
  $parentD=(int)$db->query("SELECT id FROM products WHERE sku='V1C-004'")->fetchColumn();
  $db->prepare("INSERT INTO product_variants(product_id,sku,title,price,is_active) VALUES(?,?,?,990,1)")
      ->execute([$parentD,'V1C-004-A','Variante manual posterior']);
  $blockedDepend=$revert->revertJob($dependentJob,1);
  check(empty($blockedDepend['ok']) && (int)$db->query("SELECT COUNT(*) FROM product_variants WHERE sku='V1C-004-A'")->fetchColumn()===1,
      'Dependencia/variante agregada después preservada antes de CASCADE');
  // No basta con v3: una alta sin hash del árbol FK es legacy no verificable.
  $rowE=$rowB;$rowE['sku']='V1C-005';$rowE['internal_code']='V1C005';
  $relatedJob=$apply('relation_missing',[$rowE]);
  $relPath=$tmp.'/relation_missing_rollback.json';
  $relSnap=json_decode(file_get_contents($relPath),true);
  foreach($relSnap['ops'] as &$entry) {
      if($entry['action']==='create')unset($entry['after_rel_sha256']);
  }
  unset($entry);
  file_put_contents($relPath,json_encode($relSnap));
  check(empty($revert->revertJob($relatedJob,1)['ok']),'Snapshot sin postestado de dependencias bloqueado');
  // Todas las escrituras de test apuntaron al fixture.
  // Competencia real de DOS conexiones/procesos independientes por el mismo job.
  $rowConcurrent=$row;
  $rowConcurrent['precio']='3333';$rowConcurrent['costo']='1500';$rowConcurrent['stock']='';
  $raceJob=$apply('race_job',[$rowConcurrent]);
  $workerBase=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' --worker '
      .escapeshellarg($test).' '.(int)$raceJob;
  $jobsRunning=[];
  for($i=0;$i<2;$i++) {
      $outFile=$tmp.'/worker'.$i.'.out';
      $errFile=$tmp.'/worker'.$i.'.err';
      $descriptors=[0=>['pipe','r'],1=>['file',$outFile,'w'],2=>['file',$errFile,'w']];
      $process=proc_open($workerBase,$descriptors,$pipes,$root);
      if(!is_resource($process))throw new RuntimeException('No se inició worker concurrente');
      fclose($pipes[0]);
      $jobsRunning[]=[$process,$outFile,$errFile];
  }
  $answers=[];
  foreach($jobsRunning as [$process,$outFile,$errFile]) {
      $exit=proc_close($process);
      $output=trim((string)file_get_contents($outFile));
      if($exit!==0)throw new RuntimeException('Worker failed: '.$output.' '.file_get_contents($errFile));
      $arr=json_decode($output,true);
      if(!is_array($arr))throw new RuntimeException('Worker output invalid: '.$output);
      $answers[]=$arr;
  }
  $already=count(array_filter($answers,static fn($r)=>!empty($r['summary']['already_reverted'])));
  check($already===1 && $jobs->find($raceJob)['revert_status']==='reverted',
      'Dos reversiones concurrentes: una sola aplica, segunda idempotente');
  $audited=(int)$db->query("SELECT count(*) FROM admin_audit_log WHERE action IN ('import_revert_blocked','import_reverted')")->fetchColumn();
  check($audited>0,'Eventos de auditoría registrados exclusivamente en fixture');
  check((string)$db->query('SELECT DATABASE()')->fetchColumn()===$test,'Sin contacto de escritura con DB comercial');
 }
} finally {
 if($created){$admin->exec("DROP DATABASE `$test`");echo "CLEANUP DB $test OK\n";}
 foreach(glob($tmp.'/*')?:[] as $f)@unlink($f);
 @rmdir($tmp);
}
