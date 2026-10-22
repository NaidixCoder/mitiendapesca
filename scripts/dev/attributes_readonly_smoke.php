<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 2));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_URI'] = '/mi-pesca/public_html/admin/attributes';
$_SERVER['REQUEST_METHOD'] = 'GET';
require BASE_PATH . '/vendor/autoload.php';
foreach (glob(BASE_PATH . '/config/boot/*.php') as $file) require_once $file;
require_once BASE_PATH . '/app/Views/helpers.php';
try {
    $db = db();
    $columns = implode(', ', App\Support\Admin\AttributesSchema::editableColumns($db));
    $rows = $db->query("SELECT {$columns} FROM attributes ORDER BY name ASC")->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $rows = array_map([App\Support\Admin\AttributesSchema::class, 'normalizeRow'], $rows);
    $keyword = trim((string)($rows[0]['name'] ?? 'z____attribute_nonexistent____'));
    $sql = 'SELECT id FROM attributes WHERE name LIKE :q_name OR slug LIKE :q_slug';
    $params = [':q_name'=>'%'.$keyword.'%', ':q_slug'=>'%'.$keyword.'%'];
    if (App\Support\Admin\AttributesSchema::hasColumn($db, 'attributes', 'code')) {
        $sql .= ' OR code LIKE :q_code';
        $params[':q_code'] = '%'.$keyword.'%';
    }
    $query = $db->prepare($sql);
    $query->execute($params);
    $found = $query->fetchAll(PDO::FETCH_ASSOC);
    if ($rows && !$found) throw new RuntimeException('Búsqueda SQL no encontró un atributo existente');

    $svc = new App\Services\Admin\Variants\VariantValueSetsService($db);
    $exists = $svc->valueSetsTablesExist();
    $sets = $exists ? $svc->listValueSetsAdmin([], 120) : [];
    $stats = [
        'attributes' => (int)$db->query('SELECT COUNT(*) FROM attributes')->fetchColumn(),
        'values' => (int)$db->query('SELECT COUNT(*) FROM attribute_values')->fetchColumn(),
    ];
    $categories = $db->query('SELECT id,name FROM categories ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $brands = $db->query('SELECT id,name FROM brands ORDER BY name')->fetchAll(PDO::FETCH_ASSOC) ?: [];
    $payload = compact('rows', 'stats', 'sets', 'categories', 'brands')
        + ['q'=>'', 'setsTablesOk'=>$exists, 'sets_q'=>'', 'sets_category_id'=>0, 'sets_brand_id'=>0];
    $vm = (new App\Services\Admin\Attributes\ViewModels\AttributesIndexViewModel())->build($payload);
    $_GET = ['tab' => 'attributes'];
    ob_start();
    render_page('admin/attributes/index', array_merge($payload, $vm, [
        'pageId'=>'admin-attributes',
        'extraHead'=>'<link rel="stylesheet" href="http://localhost/mi-pesca/public_html/assets/css/admin-v3/pages/attributes.css">',
    ]));
    $html = ob_get_clean();
    $visualDir = (string)(getenv('ATTRIBUTES_VISUAL_DIR') ?: '');
    if ($visualDir !== '') {
        if (!is_dir($visualDir)) mkdir($visualDir, 0770, true);
        file_put_contents($visualDir . '/index.html', str_replace('="/mi-pesca/public_html/', '="http://localhost/mi-pesca/public_html/', $html));
    }
    foreach (['av3-attributes--index', 'id="atributos-listado"', 'id="plantillas-listado"', 'av3-attributes__list-table', 'attributes.css'] as $term) {
        if (!str_contains($html, $term)) throw new RuntimeException('Listado incompleto: '.$term);
    }
    if (substr_count($html, 'class="av3-attributes__mobile-item">') !== count($rows)) throw new RuntimeException('Listado móvil omitió atributos');
    if (substr_count($html, 'data-set-row="') !== count($sets)*2) throw new RuntimeException('Listados desktop/móvil omitieron plantillas');
    if (!str_contains($html, 'role="tablist"') || !preg_match('/data-attr-panel="templates"[^>]*\\shidden(?=\\s|>)/', $html)) throw new RuntimeException('Pestañas Atributos no delimitadas');
    $_GET = ['tab' => 'templates', 'sets_q'=>''];
    ob_start();
    render_page('admin/attributes/index', array_merge($payload, $vm, [
        'pageId'=>'admin-attributes',
        'extraHead'=>'<link rel="stylesheet" href="http://localhost/mi-pesca/public_html/assets/css/admin-v3/pages/attributes.css">',
    ]));
    $templateHtml = ob_get_clean();
    if (!preg_match('/data-attr-panel="attributes"[^>]*\\shidden(?=\\s|>)/', $templateHtml) || preg_match('/data-attr-panel="templates"[^>]*\\shidden(?=\\s|>)/', $templateHtml)) {
        throw new RuntimeException('Selección SSR de Plantillas incorrecta');
    }
    if ($visualDir !== '') file_put_contents($visualDir.'/templates.html', $templateHtml);
    $_GET = [];
    echo '[atributos-db] OK: atributos='.$stats['attributes'].' valores='.$stats['values'].' plantillas_mostradas='.count($sets)."\n";

    $attribute = $rows[0] ?? App\Support\Admin\AttributesSchema::normalizeRow(['id'=>0,'name'=>'','slug'=>'','type'=>'select']);
    $sourceValues = [];
    if ((int)$attribute['id'] > 0) {
        $countSets = $exists ? '(SELECT COUNT(*) FROM variant_value_set_items vsi WHERE vsi.value_id = av.id)' : '0';
        $stmtValues = $db->prepare("SELECT av.id, av.value, av.sort,
            (SELECT COUNT(*) FROM product_attribute_values pav WHERE pav.value_id = av.id) AS selected_in_products,
            (SELECT COUNT(*) FROM product_variant_values pvv WHERE pvv.value_id = av.id) AS used_in_variants,
            {$countSets} AS used_in_sets
            FROM attribute_values av WHERE av.attribute_id = ? ORDER BY av.sort ASC, av.value ASC");
        $stmtValues->execute([(int)$attribute['id']]);
        $sourceValues = $stmtValues->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    $formVm = (new App\Services\Admin\Attributes\ViewModels\AttributesFormViewModel())->build($attribute, $sourceValues);
    ob_start();
    render_page('admin/attributes/form', array_merge(['pageId'=>'admin-attributes', 'row'=>$attribute, 'extraHead'=>'<link rel="stylesheet" href="http://localhost/mi-pesca/public_html/assets/css/admin-v3/pages/attributes.css">'], $formVm));
    $formHtml = ob_get_clean();
    if ($visualDir !== '') {
        file_put_contents($visualDir . '/form.html', str_replace('="/mi-pesca/public_html/', '="http://localhost/mi-pesca/public_html/', $formHtml));
    }
    foreach (['av3-attributes--form', 'name="code"', 'name="slug"', 'name="type"', 'name="unit"'] as $term) {
        if (!str_contains($formHtml, $term)) throw new RuntimeException('Formulario incompleto: '.$term);
    }
    if (str_contains($formHtml, 'name="is_active"')) throw new RuntimeException('Campo Activo sin persistencia reapareció');
    echo "[atributos-form] OK: render sintético y nombres de campos conservados\n";

    if ($sets) {
        $setId = (int)$sets[0]['id'];
        $set = $svc->getValueSet($setId);
        $groups = $svc->getValueSetEditGroups($setId); // Solo lectura: sin parámetros de alta.
        $editVars = (new App\Services\Admin\Attributes\ViewModels\AttributeValueSetEditViewModel())->build([
            'set'=>$set, 'categories'=>$svc->listCategories(), 'brands'=>$svc->listBrands(),
            'groups'=>$groups, 'all_attributes'=>$svc->listAttributes(),
        ]);
        extract($editVars, EXTR_SKIP);
        ob_start();
        require BASE_PATH.'/app/Views/pages/admin/attributes/value_set_edit.php';
        $fragment = ob_get_clean();
        if ($visualDir !== '') file_put_contents($visualDir . '/fragment.html', $fragment);
        if (!str_contains($fragment, 'data-role="set-edit-form"') || str_contains($fragment, '<html')) {
            throw new RuntimeException('Fragmento de edición inválido');
        }
        echo "[atributos-plantillas] OK: fragmento sin layout completo; grupos=".count($groups)."\n";
    } else {
        echo "[atributos-plantillas] SIN DATOS: falta plantilla real para render de edición; contrato estático cubierto\n";
    }
    // Regresión de lectura cruzada: el editor de variantes sigue interpretando atributos y valores.
    $productId = (int)($db->query('SELECT product_id FROM product_variants ORDER BY product_id ASC LIMIT 1')->fetchColumn() ?: 0);
    if ($productId <= 0) $productId = (int)($db->query('SELECT id FROM products ORDER BY id ASC LIMIT 1')->fetchColumn() ?: 0);
    if ($productId > 0) {
        $variantData = (new App\Services\Admin\Variants\VariantReadService($db))->getData($productId);
        if (!is_array($variantData) || !isset($variantData['variants']) || !isset($variantData['variant_attributes'])) {
            throw new RuntimeException('Modelo de lectura de variantes incompleto');
        }
        echo '[atributos-variantes] OK: lectura de producto='.$productId.' variantes='.count($variantData['variants']).' ejes='.count($variantData['variant_attributes'])."\n";
    } else {
        echo "[atributos-variantes] SIN DATOS: no hay productos para smoke cruzado\n";
    }
} catch (Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    fwrite(STDERR, '[atributos] ERROR: '.$e->getMessage()."\n");
    exit(1);
}
