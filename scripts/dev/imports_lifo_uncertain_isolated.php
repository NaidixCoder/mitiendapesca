<?php
declare(strict_types=1);
/** V1D-C disposable MariaDB test. Requires MI_IMPORTS_LIFO_QA_DSN to private schema. */
require dirname(__DIR__,2).'/vendor/autoload.php';

function connectQa():PDO {
    $dsn=(string)getenv('MI_IMPORTS_LIFO_QA_DSN');
    if(!preg_match('/^mysql:host=127\.0\.0\.1;port=\d{4,5};dbname=mi_import_v1dc_[a-z0-9_]+;charset=utf8mb4$/D',$dsn)){
        throw new RuntimeException('Non-private database denied');
    }
    $db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
    if(!str_starts_with((string)$db->query('SELECT DATABASE()')->fetchColumn(),'mi_import_v1dc_'))throw new RuntimeException('Wrong database');
    $db->exec('SET innodb_lock_wait_timeout=7');
    return $db;
}
function db():PDO{return $GLOBALS['qaDb'];}
function check(bool $ok,string $message):void {
    if(!$ok)throw new RuntimeException('FAIL '.$message);
    echo 'PASS '.$message.PHP_EOL;
}
function job(PDO $db,string $name,string $status,string $dir):array {
    static $num=0; $num++;
    $sku='QA-V1DC-'.$name.'-'.$num;
    $cat=(int)$db->query("SELECT id FROM categories WHERE slug='qa-v1dc-category'")->fetchColumn();
    $db->prepare('INSERT INTO products(category_id,sku,slug,name,price,cost) VALUES(?,?,?,?,1000,500)')
        ->execute([$cat,$sku,strtolower($sku),'Fixture '.$sku]);
    $product=(int)$db->lastInsertId();
    $csv=$dir.'/qa_'.$num.'.csv';file_put_contents($csv,"tipo_fila;sku\nproducto;$sku\n");
    $jobs=new \App\Repositories\Imports\ImportJobRepository($db);
    $id=$jobs->create('products',basename($csv),$csv);
    $jobs->markFinished($id,1,1,0);
    if($status==='review_required')$jobs->markReviewRequired($id,1,1,0);
    elseif($status==='failed')$jobs->markFailed($id);
    $repo=new \App\Repositories\Imports\ImportRevertRepository($db);
    $snap=['version'=>3,'job_id'=>$id,'complete'=>true,'ops'=>[[
       'entity'=>'product','action'=>'update','pk'=>['id'=>$product],
       'before'=>['price'=>900],'after_sha256'=>$repo->fingerprint('product',$product),
    ]]];
    $file=substr($csv,0,-4).'_rollback.json';
    file_put_contents($file,json_encode($snap,JSON_THROW_ON_ERROR|JSON_PRETTY_PRINT));
    return compact('id','product','file');
}
function status(PDO $db,int $id):array{
    $st=$db->prepare('SELECT status,revert_status FROM import_jobs WHERE id=?');
    $st->execute([$id]);return $st->fetch()?:[];
}
function price(PDO $db,int $id):int{
    $st=$db->prepare('SELECT price FROM products WHERE id=?');
    $st->execute([$id]);return (int)$st->fetchColumn();
}
function startWorker(string $mode,array $args):array {
    $cmd=escapeshellarg(PHP_BINARY).' '.escapeshellarg(__FILE__).' --'.$mode;
    foreach($args as $arg)$cmd.=' '.escapeshellarg((string)$arg);
    $proc=proc_open($cmd,[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']],$pipes,dirname(__DIR__,2));
    if(!is_resource($proc))throw new RuntimeException('Cannot start concurrent worker');
    fclose($pipes[0]);return [$proc,$pipes[1],$pipes[2]];
}
function finishWorker(array $p):array {
    [$proc,$stdout,$stderr]=$p;
    $out=trim((string)stream_get_contents($stdout));
    $err=trim((string)stream_get_contents($stderr));
    fclose($stdout);fclose($stderr);
    $exit=proc_close($proc);
    if($exit!==0)throw new RuntimeException('Worker failed: '.$out.' '.$err);
    return json_decode($out,true,512,JSON_THROW_ON_ERROR);
}
$db=connectQa();$GLOBALS['qaDb']=$db;
$mode=$argv[1]??'--verify';
if($mode==='--revert'){
    echo json_encode(\App\Services\Import\ImportRevertService::fromDb($db)->revertJob((int)($argv[2]??0),0),JSON_THROW_ON_ERROR).PHP_EOL;
    exit;
}
if($mode==='--insert-review'){
    $dir=(string)($argv[2]??'');
    if(!is_dir($dir)||!str_starts_with((string)realpath($dir),(string)sys_get_temp_dir()))throw new RuntimeException('Invalid fixture dir');
    $new=job($db,'newreview','review_required',$dir);
    echo json_encode(['inserted'=>$new['id']],JSON_THROW_ON_ERROR).PHP_EOL;exit;
}
if($mode!=='--verify')throw new RuntimeException('Invalid mode');
$dir=sys_get_temp_dir().'/imports_v1dc_'.bin2hex(random_bytes(6));
mkdir($dir,0700,true);
try {
    check((string)$db->query('SELECT DATABASE()')->fetchColumn()==='mi_import_v1dc_test','Private DB identity');
    $db->exec("INSERT INTO categories(name,slug,is_active) VALUES('QA V1DC','qa-v1dc-category',1)");
    $a=job($db,'first','finished',$dir);
    $b=job($db,'second','finished',$dir);
    $c=job($db,'uncertain','review_required',$dir);
    $d=job($db,'afterreview','finished',$dir);
    $svc=\App\Services\Import\ImportRevertService::fromDb($db);
    foreach([$a,$b,$c,$d] as $item) {
        $hash=hash_file('sha256',$item['file']);
        $result=$svc->revertJob($item['id'],0);
        check(empty($result['ok']) && str_contains((string)($result['error']??''),'conciliación'),
            'Global review blocks #'.$item['id']);
        check(price($db,$item['product'])===1000 &&
            status($db,$item['id'])['revert_status']==='not_reverted' &&
            hash_file('sha256',$item['file'])===$hash,'No SQL or snapshot modifications #'.$item['id']);
    }
    check((int)$db->query("SELECT COUNT(*) FROM admin_audit_log WHERE action='import_revert_blocked'")->fetchColumn()>=4
        && (int)$db->query("SELECT COUNT(*) FROM admin_audit_log WHERE action='import_reverted'")->fetchColumn()===0,
        'Only blocked audit, no false successful events');
    $db->prepare("UPDATE import_jobs SET status='failed' WHERE id=?")->execute([$c['id']]);
    $older=$svc->revertJob($b['id'],0);
    check(empty($older['ok']) && str_contains((string)$older['error'],'LIFO'),
        'No review: ordinary LIFO rejects older');
    $normal=$svc->revertJob($d['id'],0);
    check(!empty($normal['ok']) && price($db,$d['product'])===900,'Latest snapshot v3 rollback success');
    $again=$svc->revertJob($d['id'],0);
    check(!empty($again['summary']['already_reverted']),'Reversal idempotency unchanged');
    check(!empty($svc->revertJob($b['id'],0)['ok']),'After latest rollback previous can proceed');
    $legacy=job($db,'legacy','finished',$dir);
    $snap=json_decode((string)file_get_contents($legacy['file']),true);
    $snap['version']=2;unset($snap['complete'],$snap['ops'][0]['after_sha256']);
    file_put_contents($legacy['file'],json_encode($snap));
    check(empty($svc->revertJob($legacy['id'],0)['ok'])&&price($db,$legacy['product'])===1000,
        'Legacy v2 blocked');
    $db->prepare("UPDATE import_jobs SET status='failed' WHERE id=?")->execute([$legacy['id']]);
    $conflict=job($db,'changed','finished',$dir);
    $db->prepare('UPDATE products SET price=1299 WHERE id=?')->execute([$conflict['product']]);
    check(empty($svc->revertJob($conflict['id'],0)['ok'])&&price($db,$conflict['product'])===1299,
        'Modified record conflict guarded');
    check(empty($svc->revertJob($a['id'],0)['ok']),'Conflicted latest blocks earlier LIFO');
    $db->prepare("UPDATE import_jobs SET status='failed' WHERE id=?")->execute([$conflict['id']]);
    // Race 1: concurrent status update starts BEFORE rollback.
    $candidate=job($db,'race','finished',$dir);
    $writer=connectQa();$writer->beginTransaction();
    $writer->prepare("UPDATE import_jobs SET status='review_required' WHERE id=?")->execute([$c['id']]);
    $worker=startWorker('revert',[$candidate['id']]);
    usleep(450000);
    check((bool)proc_get_status($worker[0])['running'],'Rollback waits for in-flight review update');
    $writer->commit();
    $race=finishWorker($worker);
    check(empty($race['ok']) && str_contains((string)$race['error'],'conciliación')
        && price($db,$candidate['product'])===1000,'Revalidation sees committed review');
    $db->prepare("UPDATE import_jobs SET status='failed' WHERE id=?")->execute([$c['id']]);
    // Race 2: rollback already has table scan locks, waits on product row;
    // new insert of review_required must be serialized AFTER rollback.
    $latest=job($db,'raceinsert','finished',$dir);
    $locker=connectQa();$locker->beginTransaction();
    $locker->prepare('UPDATE products SET price=price WHERE id=?')->execute([$latest['product']]);
    $reversing=startWorker('revert',[$latest['id']]);
    $waiting=false;
    for($n=0;$n<24;$n++){
        usleep(100000);
        if((int)$db->query('SELECT COUNT(*) FROM information_schema.INNODB_LOCK_WAITS')->fetchColumn()>0){
            $waiting=true;break;
        }
    }
    check($waiting,'Rollback locked import_jobs and waits for product');
    $inserter=startWorker('insert-review',[$dir]);
    usleep(400000);
    check((bool)proc_get_status($inserter[0])['running'],'Concurrent review insert waits on next-key locks');
    $locker->commit();
    $reversed=finishWorker($reversing);
    check(!empty($reversed['ok'])&&price($db,$latest['product'])===900,
        'Rollback serialized before uncertainty insert');
    $inserted=finishWorker($inserter);
    check(status($db,(int)$inserted['inserted'])['status']==='review_required',
        'Review insert commits only after release');
    check(empty($svc->revertJob($a['id'],0)['ok']),'Inserted review blocks further reversals');
    echo 'V1DC_PRIVATE_MYSQL_FULL_SUITE_PASS'.PHP_EOL;
} finally {
    foreach(glob($dir.'/*')?:[] as $f)if(is_file($f))@unlink($f);
    @rmdir($dir);
}
