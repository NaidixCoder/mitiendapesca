<?php
declare(strict_types=1);
/**
 * V1D: solo crea BD descartable mi_import_v1d_* con DDL leído (NO datos).
 * Fallos de FS inyectados antes de commit; ambos resultados posibles de commit
 * para simular respuesta perdida. Limpieza en finally.
 */
$root = dirname(__DIR__, 2);
require $root.'/vendor/autoload.php';
use App\Services\Import\ImportService;
use App\Services\Import\ImportSnapshotStore;
use App\Services\Import\ImportRevertService;
use App\Repositories\Imports\ImportJobRepository;
function check(bool $ok,string $label):void { if(!$ok) throw new RuntimeException('FAIL '.$label); echo 'PASS '.$label.PHP_EOL; }
function envValues(string $file):array {
    $vars=[];
    foreach(file($file,FILE_IGNORE_NEW_LINES)?:[] as $l) {
        if(preg_match('/^\s*(DB_HOST|DB_PORT|DB_NAME|DB_USER|DB_PASS)\s*=(.*)$/',$l,$m)) {
            $v=trim($m[2]); if(strlen($v)>1 && in_array($v[0],['"',"'"],true) && substr($v,-1)===$v[0]) $v=substr($v,1,-1);
            $vars[$m[1]]=$v;
        }
    }
    foreach(['DB_HOST','DB_PORT','DB_NAME','DB_USER','DB_PASS'] as $key) if(!isset($vars[$key]))throw new RuntimeException('Missing config '.$key);
    return $vars;
}
class SnapshotCommitTestPDO extends PDO {
    public string $commitFailure = '';
    public function commit():bool {
        if($this->commitFailure==='before') {
            $this->commitFailure='';
            throw new RuntimeException('COMMIT simulated failure before server executes');
        }
        if($this->commitFailure==='after') {
            $result=parent::commit();
            $this->commitFailure='';
            if(!$result) throw new RuntimeException('COMMIT failed');
            throw new RuntimeException('COMMIT acknowledgement lost AFTER SQL committed');
        }
        return parent::commit();
    }
}
$e=envValues($root.'/.env');
// Worker únicamente para el fixture de concurrencia, con base y directorio
// descartables identificados por su prefijo, sin reutilizar credenciales web.
if (($argv[1] ?? '') === '--worker') {
    $dbName=(string)($argv[2]??'');
    $file=(string)($argv[3]??'');
    $job=(int)($argv[4]??0);
    $allowed=(string)($argv[5]??'');
    if (!preg_match('/^mi_import_v1d_[a-f0-9]{8}$/D',$dbName)
        || $dbName===$e['DB_NAME'] || $job<=0
        || !is_file($file) || realpath(dirname($file))!==realpath($allowed)
        || !str_starts_with(basename($file),'upload_')) throw new RuntimeException('Worker fixture guard');
    $conn=new PDO("mysql:host={$e['DB_HOST']};port={$e['DB_PORT']};dbname=$dbName;charset=utf8mb4",
        $e['DB_USER'],$e['DB_PASS'],[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
    $service=ImportService::fromDb($conn);
    (new ReflectionProperty($service,'snapshotStore'))->setValue($service,new ImportSnapshotStore(null,$allowed));
    $options=['job_id'=>$job,'delimiter'=>';','create_missing_products'=>true,'create_missing_brands'=>true,
        'create_missing_categories'=>true,'create_missing_suppliers'=>true,'update_price'=>true,'update_cost'=>true,
        'update_stock'=>true,'update_list_price'=>true,'default_stock_mode'=>'suma'];
    $out=$service->run('products',$file,false,$options);
    if($out->errors===0) (new ImportJobRepository($conn))->markFinished($job,(int)$out->total,(int)$out->ok,0);
    echo json_encode(['job'=>$job,'errors'=>$out->errors],JSON_UNESCAPED_UNICODE).PHP_EOL;
    exit($out->errors>0 ? 1 : 0);
}
$dsn="mysql:host={$e['DB_HOST']};port={$e['DB_PORT']};charset=utf8mb4";
$opts=[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false];
$admin=new PDO($dsn,$e['DB_USER'],$e['DB_PASS'],$opts);
$test='mi_import_v1d_'.bin2hex(random_bytes(4));
if(!preg_match('/^mi_import_v1d_[a-f0-9]{8}$/D',$test) || $test===$e['DB_NAME'])throw new RuntimeException('Unsafe DB name');
$tmp=sys_get_temp_dir().DIRECTORY_SEPARATOR.'imports_v1d_'.bin2hex(random_bytes(7));
mkdir($tmp,0700,true);
$created=false;
try {
    $admin->exec("CREATE DATABASE `$test` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
    $created=true;
    $source=new PDO($dsn.';dbname='.$e['DB_NAME'],$e['DB_USER'],$e['DB_PASS'],$opts);
    $db=new SnapshotCommitTestPDO($dsn.';dbname='.$test,$e['DB_USER'],$e['DB_PASS'],$opts);
    check((string)$db->query('SELECT DATABASE()')->fetchColumn()===$test,'Conexión verificada al MySQL descartable');
    $db->exec('SET FOREIGN_KEY_CHECKS=0');
    $tables=0;
    foreach($source->query('SHOW FULL TABLES')->fetchAll(PDO::FETCH_NUM) as $t) {
        if(($t[1]??'')!=='BASE TABLE')continue;
        if(!preg_match('/^[a-zA-Z0-9_]+$/',$t[0]))throw new RuntimeException('Unsafe DDL name');
        $ddl=$source->query("SHOW CREATE TABLE `{$t[0]}`")->fetch(PDO::FETCH_NUM)[1];
        $db->exec($ddl);$tables++;
    }
    $db->exec('SET FOREIGN_KEY_CHECKS=1');
    check($tables>=50,'Solo DDL comercial ('.$tables.' tablas), cero filas copiadas');
    $jobs=new ImportJobRepository($db);
    $headers=['tipo_fila','sku','sku_padre','internal_code','nombre','categoria','marca','proveedor','proveedor_cuit','iva_rate','moneda','costo','margen_pct','precio','precio_lista','precio_mayorista1','precio_mayorista2','stock','umbral','publicado','descripcion','descripcion_larga','attr_1','valor_1','attr_2','valor_2','attr_3','valor_3','peso_envio_g','alto_envio_cm','ancho_envio_cm','largo_envio_cm'];
    $normalOpt=['delimiter'=>';','create_missing_products'=>true,'create_missing_brands'=>true,'create_missing_categories'=>true,'create_missing_suppliers'=>true,'update_name'=>true,'update_price'=>true,'update_cost'=>true,'update_stock'=>true,'update_list_price'=>true,'default_stock_mode'=>'suma'];
    $run=function(string $label,?callable $fault=null,string $commitFail='',array $extraOpt=[]) use($tmp,$db,$jobs,$headers,$normalOpt):array {
        $idPart=bin2hex(random_bytes(4));
        $sku='V1D-'.$idPart;
        $file=$tmp.DIRECTORY_SEPARATOR.'upload_'.$idPart.'.csv';
        $fp=fopen($file,'wb');fwrite($fp,"\xEF\xBB\xBF");fputcsv($fp,$headers,';');
        $data=['tipo_fila'=>'producto','sku'=>$sku,'internal_code'=>$idPart,'nombre'=>'Anzuelo V1D','categoria'=>'Categoria V1D '.$idPart,'marca'=>'Marca V1D '.$idPart,'proveedor'=>'Proveedor V1D '.$idPart,'costo'=>'1100','precio'=>'2200','precio_lista'=>'2500','stock'=>'5'];
        $vals=[];foreach($headers as $h)$vals[]=$data[$h]??'';
        fputcsv($fp,$vals,';');fclose($fp);
        $jobId=$jobs->create('products',basename($file),$file);
        check($jobId>0,'Job creado: '.$label);
        $svc=ImportService::fromDb($db);
        $store=new ImportSnapshotStore($fault,$tmp);
        $rp=new ReflectionProperty($svc,'snapshotStore');
        $rp->setValue($svc,$store);
        $db->commitFailure=$commitFail;
        $result=$svc->run('products',$file,false,$normalOpt+['job_id'=>$jobId]+$extraOpt);
        $destination=substr($file,0,-4).'_rollback.json';
        $productCount=(int)$db->query("SELECT COUNT(*) FROM products WHERE sku=".$db->quote($sku))->fetchColumn();
        $inventoryCount=(int)$db->query("SELECT COUNT(*) FROM inventory i JOIN products p ON p.id=i.product_id WHERE p.sku=".$db->quote($sku))->fetchColumn();
        $brandCount=(int)$db->query('SELECT COUNT(*) FROM brands WHERE name='.$db->quote($data['marca']))->fetchColumn();
        $categoryCount=(int)$db->query('SELECT COUNT(*) FROM categories WHERE name='.$db->quote($data['categoria']))->fetchColumn();
        $supplierCount=(int)$db->query('SELECT COUNT(*) FROM suppliers WHERE nombre='.$db->quote($data['proveedor']))->fetchColumn();
        $temps=glob($tmp.'/.snapshot_'.$jobId.'_*.tmp')?:[];
        $json=is_file($destination)?json_decode((string)file_get_contents($destination),true):null;
        if($result->errors===0)$jobs->markFinished($jobId,(int)$result->total,(int)$result->ok,(int)$result->errors);
        else $jobs->markFailed($jobId,'snapshot');
        return compact('sku','file','destination','jobId','result','productCount','inventoryCount','brandCount','categoryCount','supplierCount','temps','json');
    };
    $normal=$run('success');
    check($normal['result']->errors===0 && $normal['productCount']===1 && $normal['inventoryCount']===1,'Apply normal confirma producto, precio y stock');
    check($normal['result']->applyOutcome===\App\Services\Import\ImportResult::APPLY_COMMITTED,'Resultado SQL committed explícito');
    check(is_array($normal['json']) && ImportRevertService::isSnapshotSafe($normal['json'])
        && (int)$normal['json']['job_id']===$normal['jobId'],'Snapshot v3 parseable antes de declarar éxito');
    check(!$normal['temps'],'No queda temporal después de commit exitoso');
    check($jobs->find($normal['jobId'])['status']==='finished','Job finalizado solamente tras commit');

    foreach(['open','write','flush','sync','verify','publish','published','before_commit'] as $phase) {
        $r=$run('error_'.$phase,static function(string $current)use($phase):void {
            if($current===$phase)throw new RuntimeException('Injected FS failure: '.$phase);
        });
        check($r['result']->errors>0 && $r['productCount']===0 && $r['inventoryCount']===0 && $r['brandCount']===0 && $r['categoryCount']===0 && $r['supplierCount']===0,'SQL íntegro ante '.$phase);
        check($r['result']->applyOutcome===\App\Services\Import\ImportResult::APPLY_ROLLED_BACK,'Resultado SQL rolled_back ante '.$phase);
        check(!is_file($r['destination']) && !$r['temps'],'Sin snapshot publicado/parcial ante '.$phase);
        check($jobs->find($r['jobId'])['status']==='failed','Sin estado finished ante '.$phase);
    }
    // Simula permiso denegado y disco lleno en el punto de escritura.
    foreach(['permissions','disk_full','rename_error','sync_error'] as $name) {
        $target=match($name){'permissions'=>'open','disk_full'=>'write','rename_error'=>'publish',default=>'sync'};
        $r=$run($name,static function(string $at)use($target,$name):void {
            if($at===$target)throw new RuntimeException('Injected '.$name);
        });
        check($r['productCount']===0 && $r['result']->errors>0 && !is_file($r['destination']),'Fallo simulado '.$name.' cancela DB');
    }
    // Simula bytes alterados DESPUÉS de verificar el temporal, antes del publish:
    // la comprobación del destino falla, SQL revierte y borra SOLO su propio inode.
    $r=$run('partial_file',static function(string $phase, ImportSnapshotStore $store):void {
        if($phase==='verify') {
            $rp=new ReflectionProperty($store,'staged');
            $stage=$rp->getValue($store);
            if(is_string($stage))file_put_contents($stage,'TRUNCATED');
        }
    });
    check($r['result']->errors>0 && $r['productCount']===0
        && !is_file($r['destination']) && !$r['temps'],'Archivo truncado tras verificación no se confirma');
    $r=$run('corrupted_published',static function(string $phase, ImportSnapshotStore $store):void {
        if($phase==='published') {
            $rp=new ReflectionProperty($store,'destination');
            $path=$rp->getValue($store);
            if(is_string($path))file_put_contents($path,'CORRUPTED_AFTER_PUBLISH');
        }
    });
    check($r['result']->errors>0 && $r['productCount']===0 && !is_file($r['destination']) && !$r['temps'],
        'Publicación corrupta abortada y compensada por inode propio');

    $failedCommit=$run('commit_before',null,'before');
    check($failedCommit['result']->applyOutcome===\App\Services\Import\ImportResult::APPLY_ROLLED_BACK,'COMMIT rechazado clasificado rolled_back');
    check($failedCommit['result']->errors>0 && $failedCommit['productCount']===0
        && !is_file($failedCommit['destination']) && !$failedCommit['temps'],'COMMIT rechazado antes de servidor: ROLLBACK + limpieza');

    // Simula ACK perdido: MySQL sí confirmó, PDO reporta excepción.
    // Se conserva snapshot válido en vez de borrar evidencia de SQL aplicado.
    $unknown=$run('commit_ack_lost',null,'after');
    check($unknown['result']->applyOutcome===\App\Services\Import\ImportResult::APPLY_COMMIT_UNCERTAIN,'ACK perdido clasificado commit_uncertain');
    check($unknown['result']->errors>0 && $unknown['productCount']===1
        && is_array($unknown['json']) && ImportRevertService::isSnapshotSafe($unknown['json']),
        'COMMIT de resultado incierto conserva snapshot publicable');
    check($jobs->find($unknown['jobId'])['status']==='failed','Estado conservador ante ACK perdido (requiere revisión manual)');
    check(!$unknown['temps'],'No quedan temporales ante ACK perdido');

    // Reintento: el mismo CSV original después de fallo de publicación debe
    // funcionar con otro job, sin reutilizar el snapshot fallido.
    $failedRetry=$run('retry_original_fail',static function(string $phase):void {
        if($phase==='publish')throw new RuntimeException('Retry fixture publication refused');
    });
    check($failedRetry['productCount']===0 && !is_file($failedRetry['destination']),'Primer intento falla limpiamente');
    $retryId=$jobs->create('products',basename($failedRetry['file']),$failedRetry['file']);
    $retryService=ImportService::fromDb($db);
    (new ReflectionProperty($retryService,'snapshotStore'))->setValue($retryService,new ImportSnapshotStore(null,$tmp));
    $retryResult=$retryService->run('products',$failedRetry['file'],false,$normalOpt+['job_id'=>$retryId]);
    $retrySnap=is_file($failedRetry['destination'])?json_decode((string)file_get_contents($failedRetry['destination']),true):null;
    check($retryResult->errors===0 && (int)($retrySnap['job_id']??0)===$retryId
        && ImportRevertService::isSnapshotSafe($retrySnap),'Reintento del mismo CSV obtiene snapshot de su nuevo job');
    check((int)$db->query('SELECT COUNT(*) FROM products WHERE sku='.$db->quote($failedRetry['sku']))->fetchColumn()===1,
        'Reintento conserva una sola alta de producto');

    $originalContent=file_get_contents($normal['destination']);
    $collisionStore=new ImportSnapshotStore(null,$tmp);
    $collisionPayload=$normal['json'];
    try {
        $collisionStore->prepare($normal['file'],$normal['jobId'],$collisionPayload);
        throw new RuntimeException('Collision not rejected');
    }catch(RuntimeException $ex) {
        check(str_contains($ex->getMessage(),'existe'),'Nunca sobreescribir snapshot previo');
        check(file_get_contents($normal['destination'])===$originalContent,'Snapshot ajeno intacto');
    }
    // Dos jobs distintos, cada uno en proceso/PDO propios, publican snapshots
    // simultáneamente sin colisionar ni heredar el objeto Store de otro job.
    $concurrent=[];
    for($n=1;$n<=2;$n++) {
        $suffix='race_'.bin2hex(random_bytes(4));
        $file=$tmp.'/upload_'.$suffix.'.csv';
        $handle=fopen($file,'wb');fwrite($handle,"\xEF\xBB\xBF");fputcsv($handle,$headers,';');
        $data=['tipo_fila'=>'producto','sku'=>'V1D-CONC-'.$n.'-'.substr($suffix,-4),
            'internal_code'=>'CONC-'.$n.'-'.substr($suffix,-4),'nombre'=>'Prueba concurrente '.$n,
            'categoria'=>'C '.$suffix,'marca'=>'M '.$suffix,'proveedor'=>'S '.$suffix,
            'precio'=>(string)(2000+$n),'costo'=>'1000','stock'=>'1'];
        $values=[];foreach($headers as $column)$values[]=$data[$column]??'';
        fputcsv($handle,$values,';');fclose($handle);
        $jid=$jobs->create('products',basename($file),$file);
        $concurrent[]=['file'=>$file,'job'=>$jid,'sku'=>$data['sku']];
    }
    $workers=[];
    foreach($concurrent as $item) {
        $cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' --worker '
            .escapeshellarg($test).' '.escapeshellarg($item['file']).' '
            .(int)$item['job'].' '.escapeshellarg($tmp);
        $out=$tmp.'/worker_'.$item['job'].'.out';
        $err=$tmp.'/worker_'.$item['job'].'.err';
        $process=proc_open($cmd,[0=>['pipe','r'],1=>['file',$out,'w'],2=>['file',$err,'w']],$pipes,$root);
        if(!is_resource($process))throw new RuntimeException('Concurrent worker unavailable');
        fclose($pipes[0]);$workers[]=[$process,$out,$err,$item];
    }
    foreach($workers as [$process,$out,$err,$item]) {
        $code=proc_close($process);
        $result=json_decode(trim((string)file_get_contents($out)),true);
        check($code===0 && is_array($result) && (int)$result['errors']===0,
            'Proceso independiente completó job '.$item['job']);
        $snapshotPath=substr($item['file'],0,-4).'_rollback.json';
        $json=json_decode((string)file_get_contents($snapshotPath),true);
        check(is_array($json) && ImportRevertService::isSnapshotSafe($json)
            && (int)$json['job_id']===$item['job']
            && (int)$db->query('SELECT COUNT(*) FROM products WHERE sku='.$db->quote($item['sku']))->fetchColumn()===1,
            'Publicación concurrente aislada y ligada a job '.$item['job']);
    }
    // Dry-run jamás inicia FS snapshot.
    $dryFile=$tmp.'/only_dry.csv';copy($normal['file'],$dryFile);
    $dry=ImportService::fromDb($db)->run('products',$dryFile,true,$normalOpt);
    check(!is_file($tmp.'/only_dry_rollback.json'),'Dry-run sin snapshot');
    check($dry->applyOutcome===\App\Services\Import\ImportResult::APPLY_NOT_STARTED,'Dry-run sin outcome SQL aplicado');
    // Caso sin ops reversibles: no hay prueba suficiente de que los importadores
    // no escribieron otros efectos; se rechaza el commit por diseño fail-closed.
    $noop=$tmp.'/upload_noop.csv';copy($normal['file'],$noop);
    $noopJob=$jobs->create('products','upload_noop.csv',$noop);
    $noopService=ImportService::fromDb($db);
    (new ReflectionProperty($noopService,'snapshotStore'))->setValue($noopService,new ImportSnapshotStore(null,$tmp));
    $noChange=$noopService->run('products',$noop,false,['delimiter'=>';','job_id'=>$noopJob]);
    check($noChange->applyOutcome===\App\Services\Import\ImportResult::APPLY_ROLLED_BACK,'Sin operaciones verificables revertido');
    check($noChange->errors>0 && !is_file($tmp.'/upload_noop_rollback.json'),
        'Ejecución sin operaciones verificables queda bloqueada sin snapshot');
    check((int)$db->query('SELECT COUNT(*) FROM products WHERE sku='.$db->quote($normal['sku']))->fetchColumn()===1,
        'No-op rechazado no modifica el catálogo');

    echo 'V1D_MYSQL_DURABILITY_ISOLATED_PASS'.PHP_EOL;
} finally {
    if($created) {
        $admin->exec("DROP DATABASE `$test`");
        echo 'ISOLATED_DATABASE_DESTROYED '. $test .PHP_EOL;
    }
    foreach(glob($tmp.'/*')?:[] as $f)if(is_file($f))@unlink($f);
    foreach(glob($tmp.'/.snapshot_*.tmp')?:[] as $f)if(is_file($f))@unlink($f);
    @rmdir($tmp);
    echo 'ISOLATED_STORAGE_CLEANED'.PHP_EOL;
}
