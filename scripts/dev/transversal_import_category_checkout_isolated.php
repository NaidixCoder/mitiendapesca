<?php
declare(strict_types=1);
/**
 * MiTiendaPesca cross-module SQL QA: genuine ImportService -> B1 catalog -> Checkout guard.
 * DENY by default. Synthetic MariaDB 127.0.0.1:18989 only.
 */
require dirname(__DIR__, 2).'/vendor/autoload.php';

use App\Repositories\Imports\ImportJobRepository;
use App\Services\Import\ImportService;
use App\Services\Import\ImportSnapshotStore;
use App\Services\Catalog\ProductPublicVisibilityService;
use App\Services\Checkout\CheckoutProductEligibilityGuard;

$dsn = (string)(getenv('MI_TRANSVERSAL_QA_DSN') ?: '');
$required = 'mysql:host=127.0.0.1;port=18989;dbname=mi_transversal_qa_20261022;charset=utf8mb4';
if ($dsn !== $required) { fwrite(STDERR,"REFUSED: private DSN required\n"); exit(2); }
$db = new PDO($dsn,(string)(getenv('MI_TRANSVERSAL_QA_USER') ?: ''),
    (string)(getenv('MI_TRANSVERSAL_QA_PASSWORD') ?: ''),
    [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES=>false,
        PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
if ($db->query('SELECT DATABASE()')->fetchColumn()!=='mi_transversal_qa_20261022'
    || (int)$db->query('SELECT @@port')->fetchColumn()!==18989) {
    throw new RuntimeException('REFUSED: wrong DB identity/port');
}
$privateDir = realpath(sys_get_temp_dir().DIRECTORY_SEPARATOR.'mi_transversal_qa_20261022');
if (!$privateDir || !is_dir($privateDir)) throw new RuntimeException('Private storage directory missing');
$storage = $privateDir.DIRECTORY_SEPARATOR.'imports';
if (!is_dir($storage) && !mkdir($storage,0700)) throw new RuntimeException('Unable to create private storage');
$n=0;
function crossCheck(bool $value,string $label): void {
    global $n;
    ++$n;
    if(!$value) throw new RuntimeException("FAIL ($n): $label");
    echo "PASS ($n): $label\n";
}
$headers=['tipo_fila','sku','sku_padre','internal_code','nombre','categoria','marca','proveedor',
 'proveedor_cuit','iva_rate','moneda','costo','margen_pct','precio','precio_lista',
 'precio_mayorista1','precio_mayorista2','stock','umbral','publicado','descripcion',
 'descripcion_larga','attr_1','valor_1','attr_2','valor_2','attr_3','valor_3',
 'peso_envio_g','alto_envio_cm','ancho_envio_cm','largo_envio_cm'];
$options=[
 'delimiter'=>';','update_name'=>true,'update_category'=>true,'update_price'=>true,
 'update_cost'=>true,'update_list_price'=>true,'update_stock'=>true,
 'update_publish'=>true,'default_stock_mode'=>'fija','update_attributes'=>true,
 'create_missing_products'=>true,'create_missing_brands'=>false,
 'create_missing_categories'=>false,'create_missing_suppliers'=>false
];
$jobs=new ImportJobRepository($db);
$makeCsv=static function(string $tag,array $rows) use($storage,$headers):string {
    $path=$storage.DIRECTORY_SEPARATOR.'cross_'.$tag.'.csv';
    $f=fopen($path,'wb');
    if (!$f) throw new RuntimeException('Unable to open fixture');
    fwrite($f,"\xef\xbb\xbf");
    fputcsv($f,$headers,';');
    foreach($rows as $row) {
        fputcsv($f,array_map(static fn(string $h):string=>(string)($row[$h]??''),$headers),';');
    }
    fclose($f);
    return $path;
};
$run=static function(string $tag,array $rows,bool $dry=false) use($db,$jobs,$options,$storage,$makeCsv): array {
    $file=$makeCsv($tag,$rows);
    $job=$dry?0:$jobs->create('products',basename($file),$file);
    $service=ImportService::fromDb($db);
    (new ReflectionProperty($service,'snapshotStore'))->setValue(
        $service,new ImportSnapshotStore(null,$storage));
    $result=$service->run('products',$file,$dry,$options+['job_id'=>$job]);
    if (!$dry && $result->applyOutcome==='committed') $jobs->markFinished(
        $job,$result->total,$result->ok,$result->errors);
    return [$job,$result,$file];
};
$prod=static fn(string $sku,string $category):array=>[
    'tipo_fila'=>'producto','sku'=>$sku,'nombre'=>'QA '.str_replace('-',' ',$sku),
    'categoria'=>$category,'precio'=>'2300','precio_lista'=>'2500','costo'=>'1000',
    'stock'=>'7','umbral'=>'2','publicado'=>'1'
];
$lookup=static function(string $sku) use($db):?array {
    $s=$db->prepare('SELECT * FROM products WHERE sku=?');
    $s->execute([$sku]);return $s->fetch()?:null;
};
$stock=static function(int $productId)use($db):int{
    $s=$db->prepare('SELECT stock FROM inventory WHERE product_id=?');
    $s->execute([$productId]);return (int)$s->fetchColumn();
};
$flag=static function(int $value)use($db):void{
    $s=$db->prepare("INSERT INTO app_settings(setting_key,setting_value) VALUES
        ('catalog_hide_inactive_category_products',?)
        ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value)");
    $s->execute([(string)$value]);
};
$guard=new CheckoutProductEligibilityGuard($db);
$checkGuard=static function(int $id,bool $expected,string $tag) use($guard,$db):void {
    [$ok]=$guard->checkItems([['product_id'=>$id,'qty'=>1]]);
    crossCheck($ok===$expected, $tag.' unlocked checkout guard');
    $db->beginTransaction();
    try {
        [$locked]=$guard->checkItems([['product_id'=>$id,'qty'=>1]],true);
        crossCheck($locked===$expected,$tag.' locked checkout guard');
    }finally { if($db->inTransaction()) $db->rollBack(); }
};
$db->exec("INSERT INTO categories(name,slug,is_active) VALUES
    ('QA Cross Active','qa-cross-active',1),
    ('QA Cross Hidden','qa-cross-hidden',0)");
$activeId=(int)$db->query("SELECT id FROM categories WHERE slug='qa-cross-active'")->fetchColumn();
$hiddenId=(int)$db->query("SELECT id FROM categories WHERE slug='qa-cross-hidden'")->fetchColumn();
crossCheck($activeId>0 && $hiddenId>0,'synthetic active/inactive categories created');
$flag(0);
[$job,$successful]=$run('two_products',[
    $prod('QA-CROSS-VISIBLE','QA Cross Active'),
    $prod('QA-CROSS-HIDDEN','QA Cross Hidden')
]);
crossCheck($successful->applyOutcome==='committed' && $successful->errors===0,'two products imported via real ImportService');
$visible=$lookup('QA-CROSS-VISIBLE');
$hidden=$lookup('QA-CROSS-HIDDEN');
crossCheck($visible!==null && $hidden!==null,'both imported SKUs persisted');
crossCheck((int)$visible['category_id']===$activeId && (int)$hidden['category_id']===$hiddenId,
    'imported category assignment preserves inactive category');
crossCheck((int)$visible['is_active']===1 && (int)$hidden['is_active']===1,
    'import publication did not override category state');
crossCheck($stock((int)$visible['id'])===7 && $stock((int)$hidden['id'])===7,
    'import inventory persisted as configured');
crossCheck((int)$db->query("SELECT is_active FROM categories WHERE id=$hiddenId")->fetchColumn()===0,
    'import did not activate a hidden category');

$checkGuard((int)$visible['id'],true,'B1 OFF active');
$checkGuard((int)$hidden['id'],true,'B1 OFF hidden-category');
$flag(1);
$checkGuard((int)$visible['id'],true,'B1 ON active');
$checkGuard((int)$hidden['id'],false,'B1 ON hidden-category');
$public=new ProductPublicVisibilityService($db);
crossCheck($public->permitsProduct($activeId) && !$public->permitsProduct($hiddenId),
    'public visibility agrees with Checkout B1 ON');
$before=[(int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM inventory')->fetchColumn()];
$bad=['tipo_fila'=>'variante','sku'=>'','sku_padre'=>'QA-CROSS-VISIBLE','nombre'=>'Invalid row'];
[$badJob,$rolledBack]=$run('i05_failed_mixed',[
    $prod('QA-CROSS-ROLLBACK','QA Cross Active'),$bad
]);
crossCheck($rolledBack->applyOutcome==='rolled_back' && $rolledBack->errors>=1,
    'I05 mixed good and invalid rows force rollback');
crossCheck($lookup('QA-CROSS-ROLLBACK')===null,'valid row preceding bad row not committed');
$after=[(int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM inventory')->fetchColumn()];
crossCheck($after===$before,'no products/stock retained from rejected CSV');
$checkGuard((int)$visible['id'],true,'B1 after failed import active');
$checkGuard((int)$hidden['id'],false,'B1 after failed import hidden');

$db->prepare('UPDATE categories SET is_active=0 WHERE id=?')->execute([$activeId]);
$checkGuard((int)$visible['id'],false,'B1 after category deactivation');
$flag(0);
$checkGuard((int)$visible['id'],true,'B1 switched OFF');
echo "[transversal-import-category-checkout] OK $n MariaDB cross-module assertions\n";
