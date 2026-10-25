<?php
declare(strict_types=1);
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$src='mi_pesca_qa_local';$dst='mi_pesca_qa_restore_20261025';
$rows=$pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='$src' AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
if(count($rows)<60)throw new RuntimeException('QA source schema incomplete');
$dest=$pdo->query("SELECT TABLE_NAME FROM information_schema.TABLES WHERE TABLE_SCHEMA='$dst' AND TABLE_TYPE='BASE TABLE' ORDER BY TABLE_NAME")->fetchAll(PDO::FETCH_COLUMN);
if($rows!==$dest)throw new RuntimeException('Table name mismatch');
foreach($rows as $table){
 if(!preg_match('/^[A-Za-z0-9_]+$/',$table))throw new RuntimeException('Unsafe table');
 $a=(int)$pdo->query("SELECT COUNT(*) FROM `$src`.`$table`")->fetchColumn();
 $b=(int)$pdo->query("SELECT COUNT(*) FROM `$dst`.`$table`")->fetchColumn();
 if($a!==$b)throw new RuntimeException("Row-count mismatch $table $a != $b");
 $a=(string)$pdo->query("SHOW CREATE TABLE `$src`.`$table`")->fetch(PDO::FETCH_NUM)[1];
 $b=(string)$pdo->query("SHOW CREATE TABLE `$dst`.`$table`")->fetch(PDO::FETCH_NUM)[1];
 if($a!==$b)throw new RuntimeException("Schema mismatch $table");
}
$pdo->exec("CREATE TEMPORARY TABLE `$dst`._qa_tx_verify (id INT PRIMARY KEY) ENGINE=InnoDB");
$pdo->beginTransaction();
try{
 $pdo->exec("INSERT INTO `$dst`._qa_tx_verify (id) VALUES (1)");
 if((int)$pdo->query("SELECT COUNT(*) FROM `$dst`._qa_tx_verify")->fetchColumn()!==1)throw new RuntimeException('QA tx insert failed');
 $pdo->rollBack();
 if((int)$pdo->query("SELECT COUNT(*) FROM `$dst`._qa_tx_verify")->fetchColumn()!==0)throw new RuntimeException('QA tx rollback failed');
}finally{if($pdo->inTransaction())$pdo->rollBack();}
echo 'QA_BACKUP_RESTORE_VERIFIED tables='.count($rows).' row_counts='.count($rows).' schema='.count($rows).' transactional_rollback=PASS'.PHP_EOL;
