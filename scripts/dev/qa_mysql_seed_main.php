<?php
declare(strict_types=1);
$pdo=new PDO('mysql:host=127.0.0.1;port=3306;dbname=mi_pesca_qa_local;charset=utf8mb4','root','',[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
if ((string)$pdo->query('SELECT DATABASE()')->fetchColumn()!=='mi_pesca_qa_local') throw new RuntimeException('REFUSED');
$pdo->beginTransaction();
try {
 $pass=password_hash('QA-ONLY-2026-Test!',PASSWORD_BCRYPT);
 foreach ([['QA Admin','qa-admin-main@example.invalid','admin'],['QA Client','qa-client-main@example.invalid','customer'],['QA Wholesale','qa-wholesale-main@example.invalid','mayorista1']] as [$name,$email,$role]) {
  $s=$pdo->prepare("INSERT INTO users (name,email,password_hash,email_verified,email_verified_at,role) VALUES (?,?,?,1,NOW(),?) ON DUPLICATE KEY UPDATE name=VALUES(name)");
  $s->execute([$name,$email,$pass,$role]);
 }
 $pdo->exec("INSERT INTO categories (name,slug,is_active) VALUES ('QA Pesca','qa-local-main-categoria',1) ON DUPLICATE KEY UPDATE name=VALUES(name)");
 $cat=(int)$pdo->query("SELECT id FROM categories WHERE slug='qa-local-main-categoria'")->fetchColumn();
 foreach ([['qa-local-main-producto','QA Producto disponible',2300,1],['qa-local-main-oculto','QA Producto oculto',3100,0],['qa-local-main-sin-stock','QA Producto sin stock',1800,1]] as [$slug,$name,$price,$active]) {
  $q=$pdo->prepare("INSERT INTO products (slug,name,sku,category_id,price,iva_rate,is_active,sale_mode) VALUES (?,?,?,?,?,?,?,'simple') ON DUPLICATE KEY UPDATE name=VALUES(name),is_active=VALUES(is_active)");
  $q->execute([$slug,$name,strtoupper(str_replace('-','_',$slug)),$cat,$price,21,$active]);
 }
 $stockStmt=$pdo->prepare('INSERT INTO inventory (product_id,stock) SELECT id, ? FROM products WHERE slug=? ON DUPLICATE KEY UPDATE stock=VALUES(stock)');
 foreach ([['qa-local-main-producto',5],['qa-local-main-oculto',0],['qa-local-main-sin-stock',0]] as [$slug,$stock]) $stockStmt->execute([$stock,$slug]);
 $uid=(int)$pdo->query("SELECT id FROM users WHERE email='qa-client-main@example.invalid'")->fetchColumn();
 $q=$pdo->prepare("INSERT INTO carts (user_id,token,status) SELECT ?,?,'active' WHERE NOT EXISTS (SELECT 1 FROM carts WHERE user_id=? AND status='active')");
 $q->execute([$uid,'qa-main-cart-token-20261025',$uid]);
 $pdo->commit();
 echo "QA_SYNTHETIC_SEED_OK users=3 products=3 category=1 client_cart=1\n";
} catch(Throwable $e){$pdo->rollBack();throw $e;}
