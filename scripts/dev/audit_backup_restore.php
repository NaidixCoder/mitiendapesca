<?php
declare(strict_types=1);
require dirname(__DIR__, 2).'/bootstrap.php';
$source = (string)DB::pdo()->query('SELECT DATABASE()')->fetchColumn();
$sourceDb = DB::pdo();
$size=(string)$sourceDb->query("SELECT ROUND(COALESCE(SUM(data_length+index_length),0)/1024/1024,2) FROM information_schema.tables WHERE table_schema=DATABASE()")->fetchColumn();
echo "SOURCE_TABLES=".$sourceDb->query('SHOW TABLES')->rowCount()." SIZE_MB=$size\n";
$stamp=date('Ymd_His');
$clone='mi_pesca_audit_'.$stamp;
$backupDir='C:/xampp/mi-pesca-backups';
if (!is_dir($backupDir) && !mkdir($backupDir,0700,true)) { throw new RuntimeException('Backup directory failed'); }
$dumpFile=$backupDir.'/backup_'.$stamp.'.sql';
$mysqlBin='C:/xampp/mysql/bin/';
$host=(string)env('DB_HOST','127.0.0.1');
$port=(string)env('DB_PORT','3306');
$user=(string)env('DB_USER','root');
$pass=(string)env('DB_PASS','');
$exec=function(array $command, ?string $inputFile=null)use($pass):void {
 $cmd=implode(' ',array_map('escapeshellarg',$command));
 if($inputFile!==null){$cmd.=' < '.escapeshellarg($inputFile);}
 $envp=getenv();if(!is_array($envp))$envp=[];$envp['MYSQL_PWD']=$pass;
 $spec=[0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']];
 $proc=proc_open($cmd,$spec,$pipes,null,$envp);if(!is_resource($proc))throw new RuntimeException('Failed to run mysql tool');
 fclose($pipes[0]);$out=stream_get_contents($pipes[1]);fclose($pipes[1]);$err=stream_get_contents($pipes[2]);fclose($pipes[2]);
 $exit=proc_close($proc);if($exit!==0)throw new RuntimeException('MySQL tool failed code '.$exit.' '.substr($err,0,200));
};
if(!preg_match('/^[a-zA-Z0-9_]+$/',$source))throw new RuntimeException('Invalid source DB name');
$exec([$mysqlBin.'mysqldump.exe','--host='.$host,'--port='.$port,'--user='.$user,'--single-transaction','--quick','--routines','--events','--triggers','--default-character-set=utf8mb4','--result-file='.$dumpFile,$source]);
if(!is_file($dumpFile)||filesize($dumpFile)<100)throw new RuntimeException('Empty dump');
echo "BACKUP_OK bytes=".filesize($dumpFile)."\n";
$sourceDb->exec("CREATE DATABASE `$clone` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
try{
 $exec([$mysqlBin.'mysql.exe','--host='.$host,'--port='.$port,'--user='.$user,'--default-character-set=utf8mb4',$clone],$dumpFile);
 $countTables=function(string $db)use($sourceDb):int{
 $s=$sourceDb->prepare('SELECT COUNT(*) FROM information_schema.tables WHERE table_schema=? AND table_type=\'BASE TABLE\'');$s->execute([$db]);return (int)$s->fetchColumn();
 };
 $a=$countTables($source);$b=$countTables($clone);
 if($a!==$b||$a===0)throw new RuntimeException("TABLE_COUNT_MISMATCH source=$a restored=$b");
 $rowStmt=$sourceDb->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema=? AND table_type=\'BASE TABLE\'');$rowStmt->execute([$source]);
 $checked=0;foreach($rowStmt->fetchAll(PDO::FETCH_COLUMN) as $table){
   if(!preg_match('/^[a-zA-Z0-9_]+$/',$table))continue;
   $quoted='`'.$table.'`';$from=(int)$sourceDb->query("SELECT COUNT(*) FROM `$source`.$quoted")->fetchColumn();
   $to=(int)$sourceDb->query("SELECT COUNT(*) FROM `$clone`.$quoted")->fetchColumn();
   if($from!==$to)throw new RuntimeException("ROW_COUNT_MISMATCH table=$table original=$from restored=$to");
   $checked++;
 }
 // Verify the restored schema, indexes and transactional rollback independently.
 $schemaChecked=0;
 $schemaStmt=$sourceDb->prepare('SELECT table_name FROM information_schema.tables WHERE table_schema=? AND table_type=\'BASE TABLE\'');
 $schemaStmt->execute([$source]);
 foreach($schemaStmt->fetchAll(PDO::FETCH_COLUMN) as $table){
   if(!preg_match('/^[a-zA-Z0-9_]+$/',$table))continue;
   $srcCreate=$sourceDb->query("SHOW CREATE TABLE `$source`.`$table`")->fetch(PDO::FETCH_NUM)[1] ?? '';
   $dstCreate=$sourceDb->query("SHOW CREATE TABLE `$clone`.`$table`")->fetch(PDO::FETCH_NUM)[1] ?? '';
   if($srcCreate!==$dstCreate)throw new RuntimeException("SCHEMA_MISMATCH table=$table");
   $schemaChecked++;
 }
 $sourceDb->beginTransaction();
 try{
   $sourceDb->exec("CREATE TEMPORARY TABLE `$clone`.`_audit_transaction_test` (id INT PRIMARY KEY) ENGINE=InnoDB");
   $sourceDb->exec("INSERT INTO `$clone`.`_audit_transaction_test` (id) VALUES (1)");
   $sourceDb->rollBack();
   $remaining=(int)$sourceDb->query("SELECT COUNT(*) FROM `$clone`.`_audit_transaction_test`")->fetchColumn();
   if($remaining!==0)throw new RuntimeException('TRANSACTION_ROLLBACK_FAILED');
 } finally { if($sourceDb->inTransaction())$sourceDb->rollBack(); }
 // Repositorio real de pagos, exclusivamente en la copia y dentro de rollback.
 $testDsn=sprintf('mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',$host,$port,$clone);
 $testPdo=new PDO($testDsn,$user,$pass,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,PDO::ATTR_EMULATE_PREPARES=>false]);
 $testPdo->beginTransaction();
 try {
   $payRepo=new \App\Repositories\Payments\PaymentRepository($testPdo);
   if($payRepo->findByProviderPaymentId('audit_test','nonexistent_'. $stamp)!==null)throw new RuntimeException('PAYMENT_NOT_FOUND_FAILED');
   $existing=$testPdo->query("SELECT * FROM payments WHERE provider_payment_id IS NOT NULL AND provider_payment_id<>'' ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC);
   if($existing){
     $data=['provider'=>$existing['provider'],'provider_payment_id'=>$existing['provider_payment_id'],'provider_preference_id'=>$existing['provider_preference_id'] ?? '', 'status'=>$existing['status'], 'amount'=>$existing['amount'], 'currency'=>$existing['currency'], 'intent_id'=>$existing['intent_id'], 'order_id'=>$existing['order_id'], 'raw_json'=>$existing['raw_json'] ?? '{}'];
     $first=$payRepo->createOrUpdateByProviderPaymentId($data);
     $second=$payRepo->createOrUpdateByProviderPaymentId($data);
     if($first!==(int)$existing['id']||$first!==$second)throw new RuntimeException('PAYMENT_IDEMPOTENCE_FAILED');
     echo "PAYMENT_REPOSITORY_OK repeated_existing_payment=2 same_id\n";
   }else{echo "PAYMENT_REPOSITORY_SKIP no_existing_payments\n";}
 } finally {if($testPdo->inTransaction())$testPdo->rollBack();}
 echo "RESTORE_OK tables=$a verified_row_counts=$checked schema_checked=$schemaChecked rollback=OK\n";
} finally {
 $sourceDb->exec("DROP DATABASE `$clone`");
 echo "TEMP_CLONE_REMOVED\n";
}
