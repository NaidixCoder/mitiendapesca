<?php
declare(strict_types=1);
/**
 * Tests the REAL ProductPublicationSyncService invoked by InventoryRepository,
 * not a mock. SQLite :memory: only; no HTTP, .env, MariaDB or real products.
 */
require dirname(__DIR__,2).'/vendor/autoload.php';

use App\Repositories\Catalog\InventoryRepository;
use App\Services\Admin\ProductWizardService;
use App\Services\Catalog\ProductPublicationSyncService;
use App\Services\Checkout\CheckoutProductEligibilityGuard;

$db=new PDO('sqlite::memory:',null,null,[
    PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC,
]);
$db->exec(<<<'SQL'
CREATE TABLE app_settings(setting_key TEXT PRIMARY KEY,setting_value TEXT);
CREATE TABLE categories(id INTEGER PRIMARY KEY,parent_id INTEGER NULL,is_active INTEGER);
CREATE TABLE products(
 id INTEGER PRIMARY KEY,sku TEXT,slug TEXT,name TEXT,category_id INTEGER,
 sale_mode TEXT,price INTEGER,is_active INTEGER
);
CREATE TABLE product_images(id INTEGER PRIMARY KEY,product_id INTEGER,variant_id INTEGER NULL);
CREATE TABLE product_variants(
 id INTEGER PRIMARY KEY,product_id INTEGER,sku TEXT,is_active INTEGER,
 price INTEGER
);
CREATE TABLE inventory(product_id INTEGER PRIMARY KEY,stock INTEGER,low_stock_threshold INTEGER);
CREATE TABLE inventory_variants(variant_id INTEGER PRIMARY KEY,stock INTEGER,low_stock_threshold INTEGER);
INSERT INTO categories(id,parent_id,is_active) VALUES(1,NULL,1),(2,NULL,0);
INSERT INTO products(id,sku,slug,name,category_id,sale_mode,price,is_active) VALUES
 (11,'QA-SIMPLE-IMAGE','qa-simple-image','Producto con imagen pendiente',1,'simple',2300,1),
 (12,'QA-WAIVER','qa-waiver','Producto para excepción',1,'simple',2300,1),
 (13,'QA-HIDDEN','qa-hidden','Producto en categoría oculta',2,'simple',2300,1),
 (14,'QA-NO-PRICE','qa-no-price','Sin precio',1,'simple',0,1);
SQL);
$count=0;
$check=static function(bool $yes,string $label)use(&$count):void{
    ++$count;
    if(!$yes){fwrite(STDERR,"FAIL $count: $label\n");exit(1);}
    echo "PASS $count: $label\n";
};
$active=static function(int $id)use($db):int {
    $st=$db->prepare('SELECT is_active FROM products WHERE id=?');
    $st->execute([$id]);return (int)$st->fetchColumn();
};
$setting=static function(string $key,string $value)use($db):void {
    $st=$db->prepare('INSERT INTO app_settings(setting_key,setting_value) VALUES(?,?)
       ON CONFLICT(setting_key) DO UPDATE SET setting_value=excluded.setting_value');
    $st->execute([$key,$value]);
};
$inv=new InventoryRepository($db);
$sync=new ProductPublicationSyncService($db);
$wizard=new ProductWizardService($db);
$guard=new CheckoutProductEligibilityGuard($db);

$check($inv->insert(11,7,2),'inventory insert invokes real publication sync');
$check($active(11)===0,'without image and with default policy product is unpublished');
$state=$wizard->evaluate(11);
$check(($state['publishable']??true)===false &&
    in_array('Agregá al menos 1 imagen.', $state['tabs']['imagenes']['blockers']??[],true),
    'wizard reports missing image as reason');

$setting('catalog_allow_publish_without_images','1');
$db->exec('UPDATE products SET is_active=1 WHERE id=12');
$check($inv->insert(12,7,2),'stock insert succeeds under private image waiver');
$check($active(12)===1,'private waiver allows synthetic product without image');
$check(($wizard->evaluate(12)['publishable']??false)===true,
    'wizard recognizes product as publishable with explicit waiver');

$check($inv->insert(13,7,2) && $active(13)===1,
    'inactive category does not silently change product publication when B1 is off');
[$allowed]=$guard->checkItems([['product_id'=>13,'qty'=>1]]);
$check($allowed===true,'B1 OFF permits active product from inactive category');
$setting('catalog_hide_inactive_category_products','1');
[$allowed]=$guard->checkItems([['product_id'=>13,'qty'=>1]]);
$check($allowed===false,'B1 ON blocks inactive category without editing product');
$check($active(13)===1,'B1 never mutates publication field');

$check($inv->insert(14,7,2),'no-price product inventory insert completes');
$check($active(14)===0,'image waiver does not override missing price requirement');

$setting('catalog_allow_publish_without_images','0');
$db->exec('UPDATE products SET is_active=1 WHERE id=12');
$check($inv->updateByProductId(12,8,2),'stock update invokes publication sync');
$check($active(12)===0,'turning waiver off restores image requirement');

$db->exec('INSERT INTO product_images(id,product_id,variant_id) VALUES(101,12,NULL)');
$db->exec('UPDATE products SET is_active=1 WHERE id=12');
$check($inv->updateByProductId(12,9,2),'inventory sync works with real image record');
$check($active(12)===1,'product with image retains publication under default policy');
echo "[transversal-publication-stock] OK $count checks (real repository/wizard, sqlite memory)\n";
