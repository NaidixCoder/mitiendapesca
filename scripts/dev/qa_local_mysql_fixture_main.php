<?php
declare(strict_types=1);
// LOCAL QA ONLY: fixed, explicit test database; never bootstrap app .env.
$pdo = new PDO('mysql:host=127.0.0.1;port=3306;dbname=mi_pesca_qa_local;charset=utf8mb4', 'root', '', [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
$db = (string)$pdo->query('SELECT DATABASE()')->fetchColumn();
$host = (string)$pdo->query('SELECT @@hostname')->fetchColumn();
if ($db !== 'mi_pesca_qa_local') throw new RuntimeException('REFUSED non-QA database');
$pdo->beginTransaction();
try {
    $mark='e2e-main-qa-20261025';
    $pdo->prepare('INSERT INTO users (name,email,password_hash,email_verified,role) VALUES (?,?,?,?,?)')->execute(['QA CLIENT','qa-main-'.bin2hex(random_bytes(5)).'@example.invalid',password_hash('SyntheticQAPass_123',PASSWORD_BCRYPT),1,'customer']);
    $uid=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO categories (name,slug,is_active) VALUES (?,?,1)')->execute(['QA Category',$mark.'-'.bin2hex(random_bytes(3))]);
    $cid=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO products (slug,name,sku,category_id,price,iva_rate,is_active) VALUES (?,?,?,?,?,?,1)')->execute([$mark.'-product-'.bin2hex(random_bytes(4)),'Producto sintetico MAIN QA','QA-MAIN-'.bin2hex(random_bytes(4)),$cid,1800,21.0]);
    $pid=(int)$pdo->lastInsertId();
    $pdo->prepare('INSERT INTO carts (user_id,token,status) VALUES (?,?,?)')->execute([$uid,bin2hex(random_bytes(20)),'active']);
    $cart=(int)$pdo->lastInsertId();
    foreach (['users'=>$uid,'products'=>$pid,'categories'=>$cid,'carts'=>$cart] as $t=>$id) {
      $st=$pdo->prepare("SELECT COUNT(*) FROM `$t` WHERE id=?"); $st->execute([$id]);
      if ((int)$st->fetchColumn()!==1) throw new RuntimeException('FAIL '.$t);
      echo 'PASS SQL insert/select '.$t.PHP_EOL;
    }
    $duplicateRejected=false;
    try {$pdo->prepare('INSERT INTO users (name,email,role) SELECT ?,email,? FROM users WHERE id=?')->execute(['Duplicate','customer',$uid]);}
    catch(PDOException $ex){$duplicateRejected=true;}
    if (!$duplicateRejected) throw new RuntimeException('FAIL user UNIQUE');
    echo 'PASS SQL unique users.email'.PHP_EOL;
    $pdo->rollBack();
    foreach (['users'=>$uid,'products'=>$pid,'categories'=>$cid,'carts'=>$cart] as $t=>$id) {
      $st=$pdo->prepare("SELECT COUNT(*) FROM `$t` WHERE id=?");$st->execute([$id]);if ((int)$st->fetchColumn()!==0) throw new RuntimeException('FAIL ROLLBACK '.$t);
      echo 'PASS SQL rollback '.$t.PHP_EOL;
    }
    echo 'QA_SQL_FIXTURE_OK database='.$db.' host='.$host.PHP_EOL;
} finally { if ($pdo->inTransaction()) $pdo->rollBack(); }
