<?php
declare(strict_types=1);
/** Microbench reproducible: MariaDB descartable, no datos comerciales. */
require dirname(__DIR__,2).'/vendor/autoload.php';
$dsn='mysql:host=127.0.0.1;port=18969;dbname=mi_import_integral_perf;charset=utf8mb4';
$db=new PDO($dsn,'root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if($db->query('SELECT DATABASE()')->fetchColumn()!=='mi_import_integral_perf')
    throw new RuntimeException('No usar fuera de BD sintética.');
if((int)$db->query('SELECT COUNT(*) FROM import_jobs')->fetchColumn()!==0)
    throw new RuntimeException('Se requieren fixtures nuevos; no sobrescribir DB existente');
$ins=$db->prepare("INSERT INTO import_jobs(job_type,status,revert_status,file_name)
                  VALUES('products','finished','reverted',?)");
$db->beginTransaction();
for($i=1;$i<=5000;$i++)$ins->execute(['qa_import_'.$i.'.csv']);
$db->commit();
$repo=new \App\Repositories\Imports\ImportRevertRepository($db);
$times=[];
foreach(range(1,20) as $i){
    $db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ');
    $db->beginTransaction();
    $start=hrtime(true);
    $ids=$repo->lockPendingReconciliationJobIds();
    $times[]=(hrtime(true)-$start)/1e6;
    $db->commit();
    if($ids)throw new RuntimeException('QA fixture no tiene reviews');
}
sort($times);
echo json_encode(['jobs'=>5000,'runs'=>count($times),
 'median_ms'=>round(($times[9]+$times[10])/2,2),
 'p95_ms'=>round($times[18],2),'max_ms'=>round(max($times),2)],
 JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT).PHP_EOL;
