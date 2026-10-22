<?php
declare(strict_types=1);
/**
 * Smoke de compra B1 para clon privado: SOLO lecturas (y BEGIN/ROLLBACK).
 * Requiere CATS_B1_EXPECT_DB con nombre de BD sintética. Nunca ejecutar
 * con DB habitual ni crear productos, pedidos, cobros o inventario.
 */
$expected=(string)getenv('CATS_B1_EXPECT_DB');
if (!preg_match('/^mi_pesca_cat_w6b3_[a-z0-9_]*20261021$|^mi_pesca_cat_w6b3_20261021$/',$expected)) {
    fwrite(STDERR,"[checkout-b1] REFUSED: synthetic DB required\n");
    exit(2);
}
define('BASE_PATH',dirname(__DIR__,2));
$_SERVER['HTTP_HOST']='127.0.0.1';
$_SERVER['SCRIPT_NAME']='/index.php';
$_SERVER['REQUEST_URI']='/checkout';
$_SERVER['REQUEST_METHOD']='GET';
require BASE_PATH.'/vendor/autoload.php';
foreach(glob(BASE_PATH.'/config/boot/*.php') as $boot)require_once $boot;
$db=db();
$actual=(string)$db->query('SELECT DATABASE()')->fetchColumn();
if ($actual !== $expected) {
    fwrite(STDERR,"[checkout-b1] REFUSED: unexpected database\n");
    exit(2);
}
$guard=new \App\Services\Checkout\CheckoutProductEligibilityGuard($db);
$setting=(string)$db->query(
    "SELECT setting_value FROM app_settings WHERE setting_key='catalog_hide_inactive_category_products'"
)->fetchColumn();
$hide=$setting==='1';
$items=[
 ['product_id'=>105,'qty'=>1,'expected'=>true],
 ['product_id'=>101,'qty'=>1,'expected'=>!$hide],
 ['product_id'=>112,'qty'=>1,'expected'=>!$hide],
 ['product_id'=>113,'qty'=>1,'expected'=>!$hide],
];
foreach($items as $item) {
 $expectedEligible=$item['expected'];
 unset($item['expected']);
 [$ok]=$guard->checkItems([$item]);
 if($ok!==$expectedEligible)throw new \RuntimeException('Wrong eligibility product='.$item['product_id']);
 $db->beginTransaction();
 try {
  [$lockedOk]=$guard->checkItems([$item],true);
  if($lockedOk!==$expectedEligible)throw new \RuntimeException('Locked eligibility differs for '.$item['product_id']);
  $db->rollBack();
 }catch(\Throwable $e){if($db->inTransaction())$db->rollBack();throw $e;}
}
echo '[checkout-b1] PASS synthetic='.$actual.' B1='.($hide?'ON':'OFF').' 4 normal + 4 locked checks'.PHP_EOL;
