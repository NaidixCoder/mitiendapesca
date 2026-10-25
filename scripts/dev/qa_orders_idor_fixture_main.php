<?php
declare(strict_types=1);
$p=new PDO('mysql:host=127.0.0.1;port=3306;dbname=mi_pesca_qa_local;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if($p->query('SELECT DATABASE()')->fetchColumn()!=='mi_pesca_qa_local')throw new RuntimeException('REFUSED');
$uid=(int)$p->query("SELECT id FROM users WHERE email='qa-client-main@example.invalid'")->fetchColumn();
$mode=$argv[1]??'seed';
if($mode==='seed'){
 $s=$p->prepare("INSERT INTO orders(user_id,status,payment_method,subtotal,shipping,discount,total,checkout_token) VALUES (?,'pending_payment','transferencia',2300,0,0,2300,?)");
 $s->execute([$uid,'qa-idor-'.bin2hex(random_bytes(8))]);
 echo $p->lastInsertId().PHP_EOL;
}elseif($mode==='delete'){
 $id=(int)($argv[2]??0);
 if($id<=0)throw new RuntimeException('bad id');
 $s=$p->prepare("DELETE FROM orders WHERE id=? AND user_id=? AND checkout_token LIKE 'qa-idor-%'");
 $s->execute([$id,$uid]);
 echo 'DELETED='.$s->rowCount().PHP_EOL;
}
