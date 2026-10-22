<?php
declare(strict_types=1);
/** V1D filesystem/JSON contract without MySQL, unique disposable directory. */
$root=dirname(__DIR__,2);
require $root.'/vendor/autoload.php';
use App\Services\Import\ImportSnapshotStore;
$checks=0;
function ok(bool $condition,string $message):void {
    global $checks; $checks++;
    if(!$condition)throw new RuntimeException('FAILED '.$message);
}
$dir=sys_get_temp_dir().DIRECTORY_SEPARATOR.'imports_1d_contract_'.bin2hex(random_bytes(7));
mkdir($dir,0700,true);
$payload=static function(int $id):array {
    return ['version'=>3,'job_id'=>$id,'complete'=>true,'created_at_utc'=>gmdate('Y-m-d H:i:s'),'ops'=>[
        ['entity'=>'product','action'=>'create','pk'=>['id'=>1],
         'after_sha256'=>str_repeat('a',64),'after_rel_sha256'=>str_repeat('b',64)]
    ]];
};
$csv=static function(string $name)use($dir):string {
    $file=$dir.DIRECTORY_SEPARATOR.'upload_'.$name.'.csv';
    file_put_contents($file,'tipo_fila;sku'.PHP_EOL.'producto;QA');
    return $file;
};
$dest=static fn(string $file):string=>substr($file,0,-4).'_rollback.json';
try {
    $f=$csv('success');
    $store=new ImportSnapshotStore(null,$dir);
    $store->prepare($f,11,$payload(11));
    ok(!file_exists($dest($f)),'Sin publicación antes de prepare');
    $store->publish();
    ok(is_file($dest($f)),'Destino visible antes del SQL COMMIT');
    $store->beforeCommit();
    $onDisk=json_decode(file_get_contents($dest($f)),true);
    ok(($onDisk['version']??0)===3 && ($onDisk['job_id']??0)===11,'Payload v3 correcto');
    $store->committed();
    ok(is_file($dest($f)) && !glob($dir.'/.snapshot_11_*.tmp'),'Commit retiene únicamente destino');
    $hash=hash_file('sha256',$dest($f));

    $collision=new ImportSnapshotStore(null,$dir);
    try {$collision->prepare($f,11,$payload(11));throw new RuntimeException('Missing collision guard');}
    catch(RuntimeException $ex) {ok(str_contains($ex->getMessage(),'existe'),'Protección de destino previo');}
    ok(hash_equals($hash,hash_file('sha256',$dest($f))),'Snapshot original no se alteró');
    $r=$csv('race');
    $a=new ImportSnapshotStore(null,$dir);
    $b=new ImportSnapshotStore(null,$dir);
    $a->prepare($r,21,$payload(21));
    $b->prepare($r,21,$payload(21));
    $a->publish();
    try {$b->publish();throw new RuntimeException('No collision');}
    catch(RuntimeException $ex) {ok(str_contains($ex->getMessage(),'publicar') || str_contains($ex->getMessage(),'Colisión'),'Publicaciones concurrentes no sobrescriben');}
    $b->aborted();
    ok(is_file($dest($r)),'Compensación del perdedor no borra snapshot ganador');
    $a->committed();
    ok(!glob($dir.'/.snapshot_21_*.tmp'),'Sin temporales tras carrera');

    $f3=$csv('abort');
    $failed=new ImportSnapshotStore(null,$dir);
    $failed->prepare($f3,31,$payload(31));
    $failed->publish();
    $failed->aborted();
    ok(!is_file($dest($f3)) && !glob($dir.'/.snapshot_31_*.tmp'),'Rollback SQL confirmado limpia destino propio');

    $f4=$csv('uncertain');
    $uncertain=new ImportSnapshotStore(null,$dir);
    $uncertain->prepare($f4,41,$payload(41));
    $uncertain->publish();
    $uncertain->uncertainCommit();
    ok(is_file($dest($f4)) && !glob($dir.'/.snapshot_41_*.tmp'),'Commit incierto conserva evidencia');

    $f5=$csv('badjson');
    $invalid=$payload(51);unset($invalid['ops'][0]['after_sha256']);
    try {(new ImportSnapshotStore(null,$dir))->prepare($f5,51,$invalid);throw new RuntimeException('Invalid accepted');}
    catch(RuntimeException $ex) {ok(str_contains($ex->getMessage(),'incompleto'),'V3 incompleto rechazado');}
    try {(new ImportSnapshotStore(null,$dir))->prepare($f5,52,$payload(51));throw new RuntimeException('Mismatch accepted');}
    catch(RuntimeException $ex) {ok(str_contains($ex->getMessage(),'incompleto'),'Job ID no coincide');}
    ok(!is_file($dest($f5)),'Snapshot inválido nunca publicado');

    $f6=$csv('failure');
    $failure=new ImportSnapshotStore(static function(string $phase):void {
        if($phase==='sync')throw new RuntimeException('Injected fsync failed');
    },$dir);
    try {$failure->prepare($f6,61,$payload(61));throw new RuntimeException('Sync fault not blocked');}
    catch(RuntimeException $ex) {ok(str_contains($ex->getMessage(),'fsync'),'Fallo fsync se propaga');}
    $failure->aborted();
    ok(!is_file($dest($f6)) && !glob($dir.'/.snapshot_61_*.tmp'),'Sin temporal persistente ante fsync');

    $f7=$csv('published_corrupted');
    $failure=new ImportSnapshotStore(static function(string $phase,ImportSnapshotStore $self):void {
        if($phase==='published') {
            $rp=new ReflectionProperty($self,'destination');
            $file=$rp->getValue($self);
            file_put_contents($file,'BROKEN');
        }
    },$dir);
    $failure->prepare($f7,71,$payload(71));
    try {$failure->publish();throw new RuntimeException('Corrupted destination accepted');}
    catch(RuntimeException $ex) {ok(str_contains($ex->getMessage(),'coincide'),'Publicación corrupta detectada');}
    $failure->aborted();
    ok(!is_file($dest($f7)) && !glob($dir.'/.snapshot_71_*.tmp'),'Corrupta propia compensada por inode');
    ok(!glob($dir.'/.snapshot_*.tmp'),'Directorio sin temporales pendientes');
    echo "OK: $checks verificaciones de snapshot filesystem, sin BD.".PHP_EOL;
}finally {
    foreach(glob($dir.'/*')?:[] as $item)if(is_file($item))@unlink($item);
    foreach(glob($dir.'/.snapshot_*.tmp')?:[] as $item)if(is_file($item))@unlink($item);
    @rmdir($dir);
}
