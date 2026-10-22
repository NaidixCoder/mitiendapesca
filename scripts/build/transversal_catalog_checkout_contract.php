<?php
declare(strict_types=1);
/**
 * Ejecuta lógica REAL de visibilidad B1 + guard Checkout sobre SQLite privado
 * y lógica real CSV de normalización/validación Importaciones.
 * No simula ImportService ni locks InnoDB: quedan en el E2E MariaDB pendiente.
 */
require dirname(__DIR__,2).'/vendor/autoload.php';

use App\Services\Catalog\CategoryPublicVisibilityService;
use App\Services\Catalog\ProductPublicVisibilityService;
use App\Services\Checkout\CheckoutProductEligibilityGuard;
use App\Services\Import\RowNormalizer;
use App\Services\Import\RowValidator;

$db=new PDO('sqlite::memory:',null,null,[PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION]);
$db->exec(<<<'SQL'
CREATE TABLE app_settings(setting_key TEXT PRIMARY KEY,setting_value TEXT NOT NULL);
CREATE TABLE categories(id INTEGER PRIMARY KEY,parent_id INTEGER NULL,is_active INTEGER NOT NULL);
CREATE TABLE products(id INTEGER PRIMARY KEY,category_id INTEGER NOT NULL,is_active INTEGER NOT NULL);
CREATE TABLE product_variants(id INTEGER PRIMARY KEY,product_id INTEGER NOT NULL,is_active INTEGER NOT NULL);
INSERT INTO app_settings(setting_key,setting_value)
  VALUES('catalog_hide_inactive_category_products','0');
INSERT INTO categories(id,parent_id,is_active)
  VALUES(1,NULL,1),(2,1,1),(3,1,0),(4,3,1),(5,NULL,1),
        (6,7,1),(7,6,1),(8,999,1);
INSERT INTO products(id,category_id,is_active)
  VALUES(11,2,1),(12,3,1),(13,4,1),(14,5,0),
        (15,6,1),(16,8,1),(17,5,1);
INSERT INTO product_variants(id,product_id,is_active)
  VALUES(101,11,1),(102,11,0),(103,12,1),(104,17,1);
SQL);
$n=0;
$check=static function(bool $ok,string $label) use (&$n):void {
    ++$n;
    if(!$ok){fwrite(STDERR,"[cross-contract] FAIL $n: $label\n");exit(1);}
    echo "PASS $n: $label\n";
};
$guard=new CheckoutProductEligibilityGuard($db);
$batch=static fn(int $id,?int $variant=null):array=>
    [['product_id'=>$id,'qty'=>1]+($variant!==null?['variant_id'=>$variant]:[])];
$allowed=static function(array $items)use($guard):bool{
    [$ok]=$guard->checkItems($items,false);
    return $ok;
};

$check($allowed($batch(11)),'B1 OFF permits product in active descendant');
$check($allowed($batch(12)),'B1 OFF preserves published product of inactive category');
$check($allowed($batch(13)),'B1 OFF preserves published product below inactive parent');
$check(!$allowed($batch(14)),'product inactive blocks checkout even B1 OFF');
$check($allowed($batch(11,101)),'active variant of same parent allowed');
$check(!$allowed($batch(11,102)),'inactive variant denied');
$check(!$allowed($batch(17,101)),'wrong variant/product association denied');
$check(!$allowed($batch(999)),'missing product denied');
$check(!$allowed([['product_id'=>11,'qty'=>0]]),'zero quantity denied');
$check(!$allowed([]),'empty cart denied');
$check($allowed($batch(15)),'B1 OFF can sell product in cyclic category (flag controls category policy)');

$db->exec("UPDATE app_settings SET setting_value='1' WHERE setting_key='catalog_hide_inactive_category_products'");
$check($allowed($batch(11)),'B1 ON permits active descendant');
$check(!$allowed($batch(12)),'B1 ON blocks inactive category');
$check(!$allowed($batch(13)),'B1 ON blocks active child of inactive ancestor');
$check(!$allowed($batch(15)),'B1 ON blocks category cycle');
$check(!$allowed($batch(16)),'B1 ON blocks missing ancestor');
$check($allowed($batch(17,104)),'B1 ON allows active product and own variant');
$check(!$allowed([$batch(11)[0],$batch(12)[0]]),'mixed cart all-or-nothing for checkout');
$catalog=new ProductPublicVisibilityService($db);
$check($catalog->eligibleIdsAmong([11,12,13,14,15,16,17])===[11=>true,17=>true],
    'catalog public selection matches checkout B1 policy');
$category=new CategoryPublicVisibilityService($db);
$ids=$category->visibleIdSet();
$check(isset($ids[1],$ids[2],$ids[5])&&!isset($ids[3],$ids[4],$ids[6],$ids[7],$ids[8]),
    'visibility excludes inactive descendants, cycles and orphans');

$db->exec('UPDATE categories SET is_active=0 WHERE id=1');
$check(!$allowed($batch(11)),'category deactivation invalidates stale cart at checkout');
$db->exec("UPDATE app_settings SET setting_value='0' WHERE setting_key='catalog_hide_inactive_category_products'");
$check($allowed($batch(11)),'switching B1 OFF does not silently unpublish product');

$normalizer=new RowNormalizer();
$validator=new RowValidator();
$row=['tipo_fila'=>'producto','sku'=>'CROSS-QA-1','nombre'=>'Producto QA',
    'categoria'=>'Categoría activa','precio'=>'2300','publicado'=>'1',
    'attr_1'=>'Color','valor_1'=>'Azul'];
$normalized=$normalizer->fromSpanish($row,'products',['update_attributes'=>true]);
$check(($normalized['sku']??'')==='CROSS-QA-1' && ($normalized['price']??null)===2300,
    'Importaciones normaliza SKU y precio en formato oficial');
$check(($normalized['attr_pairs'][0]['name']??'')==='Color'
    && ($normalized['attr_pairs'][0]['value']??'')==='Azul',
    'Importaciones conserva par de atributo oficial');
$check($validator->validate($normalized,'products')===null,'fila CSV con pares completos válida');
$invalid=$row;$invalid['valor_1']='';
$normalizedInvalid=$normalizer->fromSpanish($invalid,'products',['update_attributes'=>true]);
$check($validator->validate($normalizedInvalid,'products')!==null,
    'fila CSV con atributo incompleto rechazada por RowValidator');
$check(!$allowed([['product_id'=>11,'qty'=>1,'variant_id'=>103]]),
    'checkout no mezcla variante de otro producto incluso tras B1 OFF');

echo "[cross-contract] OK $n executable cross-module checks (SQLite + normalizer)\n";
