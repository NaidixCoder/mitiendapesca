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
// Verificar datadir real, no solo nombre de base/puerto; prohibir cualquier
// servidor distinto a la MariaDB efímera generada para esta suite.
$expectedData = realpath($privateDir.DIRECTORY_SEPARATOR.'data');
$serverData = realpath((string)$db->query('SELECT @@datadir')->fetchColumn());
if (!$expectedData || !$serverData
    || strcasecmp(rtrim($expectedData, '\\/'),rtrim($serverData,'\\/'))!==0) {
    throw new RuntimeException('REFUSED: MariaDB datadir is not private QA');
}
// Este ensayo es de una sola ejecución y no modifica estados persistentes
// de otras suites: exige tablas instaladas y vacías.
foreach (['categories','products','product_variants','attributes','import_jobs'] as $table) {
    $count=(int)$db->query('SELECT COUNT(*) FROM '.$table)->fetchColumn();
    if($count !== 0) throw new RuntimeException('REFUSED: private QA table not empty: '.$table);
}
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
// Diagnóstico privado MariaDB: misma ruta de inserción real, fuera de
// ImportService, revertida de inmediato para no interferir con el E2E.
$probeImporter = new \App\Services\Import\ProductImporter(
    new \App\Repositories\Catalog\ProductRepository($db),
    new \App\Repositories\Catalog\BrandRepository($db),
    new \App\Repositories\Catalog\CategoryRepository($db),
    new \App\Repositories\Catalog\SupplierRepository($db)
);
$probeInput = (new \App\Services\Import\RowNormalizer())->fromSpanish(
    $prod('QA-CROSS-PROBE','QA Cross Active'), 'products', ['update_publish'=>true]
);
$db->beginTransaction();
try {
    $probeResult = $probeImporter->upsert($probeInput, false, [
        'create_missing_products'=>true,'update_publish'=>true
    ]);
    $probeStmt = $db->prepare('SELECT sku,is_active,category_id FROM products WHERE id=?');
    $probeStmt->execute([(int)($probeResult['product_id'] ?? 0)]);
    echo '[qa-repository-probe] '.json_encode([
        'normalized_active'=>$probeInput['is_active'] ?? null,
        'result_ok'=>$probeResult['ok'] ?? false,
        'stored'=>$probeStmt->fetch(PDO::FETCH_ASSOC) ?: null,
    ], JSON_THROW_ON_ERROR).PHP_EOL;
} finally {
    $db->rollBack();
}
$flag(0);
[$job,$successful,$firstCsv]=$run('two_products',[
    $prod('QA-CROSS-VISIBLE','QA Cross Active'),
    $prod('QA-CROSS-HIDDEN','QA Cross Hidden')
]);
crossCheck($successful->applyOutcome==='committed' && $successful->errors===0,'two products imported via real ImportService');
crossCheck(is_file(substr($firstCsv,0,-4).'_rollback.json'),'successful import publishes reversible snapshot');
$visible=$lookup('QA-CROSS-VISIBLE');
$hidden=$lookup('QA-CROSS-HIDDEN');
crossCheck($visible!==null && $hidden!==null,'both imported SKUs persisted');
crossCheck((int)$visible['category_id']===$activeId && (int)$hidden['category_id']===$hiddenId,
    'imported category assignment preserves inactive category');
// Diagnóstico sintético: no ocultar un producto publicado incorrectamente.
// El E2E no consulta ni registra información comercial.
$csvPublishValues = [];
$csvReader = (new \App\Services\Import\CsvReader())->open($firstCsv,';');
$csvNormalizer = new \App\Services\Import\RowNormalizer();
foreach ($csvReader as $csvRow) {
    $normalizedRow = $csvNormalizer->fromSpanish($csvRow, 'products', $options);
    $csvPublishValues[(string)($normalizedRow['sku'] ?? '')] = [
        'raw_publicado'=>(string)($csvRow['publicado'] ?? ''),
        'normalized_active'=>$normalizedRow['is_active'] ?? null,
    ];
}
$publishedState = [
    'csv' => $csvPublishValues,
    'visible' => ['sku'=>(string)$visible['sku'], 'product_active'=>$visible['is_active'],
        'category_active'=>(int)$db->query("SELECT is_active FROM categories WHERE id=$activeId")->fetchColumn()],
    'hidden' => ['sku'=>(string)$hidden['sku'], 'product_active'=>$hidden['is_active'],
        'category_active'=>(int)$db->query("SELECT is_active FROM categories WHERE id=$hiddenId")->fetchColumn()],
];
echo '[qa-publication] '.json_encode($publishedState, JSON_THROW_ON_ERROR).PHP_EOL;
crossCheck((int)$visible['is_active']===1 && (int)$hidden['is_active']===1,
    'imported products must retain publicado=1 independently of category state');
crossCheck($stock((int)$visible['id'])===7 && $stock((int)$hidden['id'])===7,
    'import inventory persisted as configured');
crossCheck((int)$db->query("SELECT is_active FROM categories WHERE id=$hiddenId")->fetchColumn()===0,
    'import did not activate a hidden category');

$checkGuard((int)$visible['id'],true,'B1 OFF active');
$checkGuard((int)$hidden['id'],true,'B1 OFF hidden-category');
$flag(1);
$checkGuard((int)$visible['id'],true,'B1 ON active');
$checkGuard((int)$hidden['id'],false,'B1 ON hidden-category');

// Cruce REAL: importar variante + atributo y valor, chequear tres tablas de
// asociaciones, su stock y pertenencia al producto desde Checkout.
$variantFixture=[
  'tipo_fila'=>'variante',
  'sku'=>'QA-CROSS-VAR-AZUL','sku_padre'=>'QA-CROSS-VISIBLE',
  'nombre'=>'Variante azul de prueba',
  'precio'=>'2600','stock'=>'4','publicado'=>'1',
  'attr_1'=>'Color QA Transversal','valor_1'=>'Azul'
];
[$variantJob,$variantResult]=$run('variant_attributes',[$variantFixture]);
crossCheck($variantResult->applyOutcome==='committed' && $variantResult->errors===0,
    'ImportService creates variant with attribute and inventory');
$variantStmt=$db->prepare('SELECT id,product_id,is_active FROM product_variants WHERE sku=?');
$variantStmt->execute(['QA-CROSS-VAR-AZUL']);
$variant=$variantStmt->fetch();
crossCheck(is_array($variant) && (int)$variant['product_id']===(int)$visible['id'],
    'imported variant belongs to expected parent product');
$variantId=(int)$variant['id'];
$variantStock=$db->prepare('SELECT stock FROM inventory_variants WHERE variant_id=?');
$variantStock->execute([$variantId]);
crossCheck((int)$variantStock->fetchColumn()===4,'imported variant stock persisted');
$relations=$db->prepare(
  "SELECT av.value, a.name FROM product_variant_values pvv
   JOIN attribute_values av ON av.id=pvv.value_id
   JOIN attributes a ON a.id=pvv.attribute_id
   WHERE pvv.variant_id=?"
);
$relations->execute([$variantId]);
$attr=$relations->fetch();
crossCheck(is_array($attr) && $attr['name']==='Color QA Transversal'
  && $attr['value']==='Azul','imported variant retains attribute/value relationship');
$attrs=$db->prepare(
  "SELECT COUNT(*) FROM product_attributes pa
   JOIN attributes a ON a.id=pa.attribute_id
   WHERE pa.product_id=? AND a.name='Color QA Transversal'"
);
$attrs->execute([(int)$visible['id']]);
crossCheck((int)$attrs->fetchColumn()===1,'product-level attribute relation exists');
$values=$db->prepare(
  "SELECT COUNT(*) FROM product_attribute_values pav
   JOIN attribute_values av ON av.id=pav.value_id
   JOIN attributes a ON a.id=pav.attribute_id
   WHERE pav.product_id=? AND a.name='Color QA Transversal' AND av.value='Azul'"
);
$values->execute([(int)$visible['id']]);
crossCheck((int)$values->fetchColumn()===1,'product-level selected attribute value exists');
[$buyVariant]=$guard->checkItems([[
  'product_id'=>(int)$visible['id'],'variant_id'=>$variantId,'qty'=>1
]]);
crossCheck($buyVariant===true,'Checkout accepts imported variant of active category');
[$buyOther]=$guard->checkItems([[
  'product_id'=>(int)$hidden['id'],'variant_id'=>$variantId,'qty'=>1
]]);
crossCheck($buyOther===false,'Checkout rejects variant assigned to wrong product');

$public=new ProductPublicVisibilityService($db);
crossCheck($public->permitsProduct($activeId) && !$public->permitsProduct($hiddenId),
    'public visibility agrees with Checkout B1 ON');
$before=[
    (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM inventory')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM product_variants')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM inventory_variants')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM attributes')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM attribute_values')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM product_variant_values')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM product_attributes')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM product_attribute_values')->fetchColumn(),
];
$bad=['tipo_fila'=>'variante','sku'=>'','sku_padre'=>'QA-CROSS-VISIBLE','nombre'=>'Invalid row'];
$validButRejected=[
  'tipo_fila'=>'variante','sku'=>'QA-CROSS-VAR-ROLLBACK',
  'sku_padre'=>'QA-CROSS-VISIBLE','nombre'=>'Variante rechazada por I05',
  'precio'=>'3200','stock'=>'9','publicado'=>'1',
  'attr_1'=>'Material QA ReversiÃ³n','valor_1'=>'Titanio'
];
[$badJob,$rolledBack,$badCsv]=$run('i05_failed_mixed',[
    $prod('QA-CROSS-ROLLBACK','QA Cross Active'),$validButRejected,$bad
]);
crossCheck($rolledBack->applyOutcome==='rolled_back' && $rolledBack->errors>=1,
    'I05 mixed good and invalid rows force rollback');
crossCheck(!is_file(substr($badCsv,0,-4).'_rollback.json'),'rejected import never publishes snapshot');
crossCheck($lookup('QA-CROSS-ROLLBACK')===null,'valid row preceding bad row not committed');
$after=[
    (int)$db->query('SELECT COUNT(*) FROM products')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM inventory')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM product_variants')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM inventory_variants')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM attributes')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM attribute_values')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM product_variant_values')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM product_attributes')->fetchColumn(),
    (int)$db->query('SELECT COUNT(*) FROM product_attribute_values')->fetchColumn(),
];
crossCheck($after===$before,
    'I05 leaves no changes to products, variants, attributes, relationships or stock');
$lostVariant=$db->prepare('SELECT COUNT(*) FROM product_variants WHERE sku=?');
$lostVariant->execute(['QA-CROSS-VAR-ROLLBACK']);
crossCheck((int)$lostVariant->fetchColumn()===0,'valid variant from rejected CSV not persisted');
$lostAttr=$db->prepare('SELECT COUNT(*) FROM attributes WHERE name=?');
$lostAttr->execute(['Material QA ReversiÃ³n']);
crossCheck((int)$lostAttr->fetchColumn()===0,'new attribute from rejected CSV not persisted');
$checkGuard((int)$visible['id'],true,'B1 after failed import active');
$checkGuard((int)$hidden['id'],false,'B1 after failed import hidden');

$db->prepare('UPDATE categories SET is_active=0 WHERE id=?')->execute([$activeId]);
$checkGuard((int)$visible['id'],false,'B1 after category deactivation');
$flag(0);
$checkGuard((int)$visible['id'],true,'B1 switched OFF');
echo "[transversal-import-category-checkout] OK $n MariaDB cross-module assertions\n";
