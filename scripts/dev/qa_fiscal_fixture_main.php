<?php
declare(strict_types=1);
$db=new PDO('mysql:host=127.0.0.1;port=3306;dbname=mi_pesca_qa_local;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($db->query('SELECT DATABASE()')->fetchColumn()!=='mi_pesca_qa_local')throw new RuntimeException('QA only');
$mode=$argv[1]??'seed';
if($mode==='seed'){
 $db->beginTransaction();
 try{
  $uid=(int)$db->query("SELECT id FROM users WHERE email='qa-client-main@example.invalid'")->fetchColumn();
  $token='qa-fiscal-'.bin2hex(random_bytes(8));
  $q=$db->prepare("INSERT INTO orders(user_id,status,payment_method,subtotal,total,checkout_token) VALUES (?,'pending_payment','transferencia',2300,2300,?)");$q->execute([$uid,$token]);$order=(int)$db->lastInsertId();
  $q=$db->prepare("INSERT INTO comprobantes_fiscales(order_id,kind,related_comprobante_id,status,arca_env,pto_vta,cbte_tipo,doc_tipo,doc_nro,imp_total,imp_neto,imp_iva) VALUES (?,?,?,'pending','homologacion',5,?,99,0,2300,1900,400)");
  $q->execute([$order,'invoice',null,11]);$invoice=(int)$db->lastInsertId();
  $q->execute([$order,'credit_note',$invoice,13]);$note=(int)$db->lastInsertId();
  $duplicate=false;try{$q->execute([$order,'invoice',null,11]);}catch(PDOException $e){$duplicate=true;}
  if(!$duplicate)throw new RuntimeException('Missing invoice uniqueness');
  $db->commit();echo json_encode(['order'=>$order,'invoice'=>$invoice,'credit_note'=>$note,'unique_pass'=>true]).PHP_EOL;
 }catch(Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}elseif($mode==='cleanup'){
 $order=(int)($argv[2]??0);if($order<=0)throw new RuntimeException('order id invalid');
 $q=$db->prepare("DELETE FROM orders WHERE id=? AND checkout_token LIKE 'qa-fiscal-%'");$q->execute([$order]);
 echo 'QA_FISCAL_CLEANUP='.$q->rowCount().PHP_EOL;
}
