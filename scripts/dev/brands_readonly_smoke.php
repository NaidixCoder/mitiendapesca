<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 2));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_URI'] = '/mi-pesca/public_html/admin/brands';
$_SERVER['REQUEST_METHOD'] = 'GET';
require BASE_PATH . '/vendor/autoload.php';
foreach (glob(BASE_PATH . '/config/boot/*.php') as $file) require_once $file;
require_once BASE_PATH . '/app/Views/helpers.php';

try {
    $db = db();
    $repo = new App\Repositories\Admin\BrandAdminRepository($db);
    $existingSlugs = $db->query('SELECT slug FROM brands')->fetchAll(PDO::FETCH_COLUMN);
    $legacySlugs = array_values(array_filter($existingSlugs, static fn ($s) => !preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/D', (string)$s)));
    echo '[marcas-db] Slugs existentes con formato distinto: ' . count($legacySlugs) . "\n";
    $vmFactory = new App\Services\Admin\Brands\ViewModels\BrandsIndexViewModel();
    foreach ([
        ['', 1, 25], ['', 2, 50],
        ['marca_inexistente_984qv9', 1, 25],
        ['%', 1, 25], ['_', 1, 25], ['=', 1, 25],
    ] as [$q, $page, $per]) {
        $result = $repo->listPage($q, $page, $per);
        $vm = $vmFactory->build($result['rows'], $result['total'], $q, $page, $per);
        if (count($vm['rows']) > $per || $vm['total'] < count($vm['rows']) ||
            $vm['page'] < 1 || $vm['page'] > $vm['pages']) {
            throw new RuntimeException('Conteo o paginación inconsistente.');
        }
        if (in_array($q, ['%', '_', '='], true)) {
            foreach ($vm['rows'] as $matched) {
                if (!str_contains((string)$matched['name'], $q) && !str_contains((string)$matched['slug'], $q)) {
                    throw new RuntimeException('LIKE no buscó literalmente '.json_encode($q));
                }
            }
        }
        echo "[marcas-db] OK: q=" . json_encode($q) . ", filas=" . count($vm['rows']) . ", total=" . $vm['total'] . "\n";
    }

    $syntheticRows = [[
        'id' => 101, 'name' => 'Marca de prueba', 'slug' => 'marca-de-prueba', 'sort' => 1,
        'cover_image' => '', 'created_at' => '2026-10-20 11:00:00',
        'product_count' => 5, 'active_product_count' => 3,
    ]];
    $vm = $vmFactory->build($syntheticRows, 1, '', 1, 25);
    ob_start();
    render_page('admin/brands/index', array_merge(['pageId'=>'admin-brands-index'], $vm));
    $html = ob_get_clean();
    foreach (['av3-brands__table','av3-brands__mobile-item','marca-de-prueba',
              'name="per"','admin-v3/pages/brands.css','5</span>','3 activos'] as $fragment) {
        if (!str_contains($html, $fragment)) throw new RuntimeException('Falta listado: ' . $fragment);
    }
    if (substr_count($html, 'class="av3-brands__mobile-item"') !== 1) {
        throw new RuntimeException('Vista móvil con registros duplicados.');
    }

    ob_start();
    render_page('admin/brands/index', array_merge(['pageId'=>'admin-brands-index'], $vmFactory->build([], 0, 'no-resultados', 1, 25)));
    $emptyHtml = ob_get_clean();
    if (!str_contains($emptyHtml, 'No encontramos marcas')) throw new RuntimeException('Estado vacío ausente.');

    $brand = ['id'=>0,'name'=>'','slug'=>'','sort'=>0,'cover_image'=>'','meta_title'=>null,'meta_description'=>null,'seo_text'=>null];
    ob_start();
    render_page('admin/brands/form', [
        'pageId'=>'admin-brand-form', 'brand'=>$brand, 'seoAudit'=>[],
        'seoSettings'=>[], 'seoBlocks'=>['bullets'=>[], 'specs'=>[], 'faq'=>[]],
    ]);
    $form = ob_get_clean();
    foreach (['name="name"','name="slug"','name="cover_image_file"','name="seo_index"',
              'data-seo-score','data-seo-blocks','data-brands-form','name="id"'] as $fragment) {
        if (!str_contains($form, $fragment)) throw new RuntimeException('Falta formulario: ' . $fragment);
    }

    $draft = App\Services\Admin\Brands\BrandFormDraft::class;
    $draft::remember(0, ['name'=>'Prueba recuperada','slug'=>'texto-prueba','seo_index'=>'noindex',
        'seo_bullets'=>['Uno','Dos'], '_csrf'=>'no-se-debe-guardar']);
    $old = $draft::consume(0);
    if ($draft::consume(0) !== [] || isset($old['_csrf'])) throw new RuntimeException('Draft no es de un solo uso o filtró CSRF');
    $brandCopy = $brand;
    $settingsCopy = [];
    $blocksCopy = ['bullets'=>[], 'specs'=>[], 'faq'=>[]];
    $draft::overlay($brandCopy, $settingsCopy, $blocksCopy, $old);
    if (($brandCopy['name'] ?? null) !== 'Prueba recuperada' || ($settingsCopy['robots'] ?? null) !== 'noindex,follow'
        || ($blocksCopy['bullets'] ?? null) !== ['Uno','Dos']) throw new RuntimeException('Recuperación SEO incompleta');
    $controller = (new ReflectionClass(App\Controllers\Admin\BrandsController::class))->newInstanceWithoutConstructor();
    $uploadCheck = (new ReflectionMethod(App\Controllers\Admin\BrandsController::class, 'storeCoverImage'))
        ->invoke($controller, ['error'=>UPLOAD_ERR_OK, 'size'=>6*1024*1024, 'tmp_name'=>''], 'brands');
    if (($uploadCheck['ok'] ?? true) !== false) throw new RuntimeException('Se aceptó una subida demasiado grande.');
    echo "[marcas-render] OK: listado desktop/móvil, vacío, formulario SEO y límite de subida. Sin POST.\n";
} catch (Throwable $e) {
    while (ob_get_level() > 0) ob_end_clean();
    fwrite(STDERR, "[marcas] ERROR: " . $e->getMessage() . "\n");
    exit(1);
}
