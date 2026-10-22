<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 2));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_URI'] = '/mi-pesca/public_html/admin/categories';
$_SERVER['REQUEST_METHOD'] = 'GET';
require BASE_PATH.'/vendor/autoload.php';
foreach (glob(BASE_PATH.'/config/boot/*.php') as $f) require_once $f;
require_once BASE_PATH.'/app/Views/helpers.php';

try {
    $db = db();
    $service = new App\Services\Admin\Categories\CategoriesIndexService($db);
    foreach ([[], ['per'=>50,'show_inactive'=>'1'], ['q'=>'unlikely-cat-xxxxx'], ['page'=>'9999999','per'=>25]] as $filters) {
        $data = $service->search($filters);
        if (count($data['rows']) > $data['per']) throw new RuntimeException('Paginación excedida');
        if ($data['page'] < 1 || $data['page'] > $data['pages']) throw new RuntimeException('Página inválida');
        if ($data['total'] < count($data['rows'])) throw new RuntimeException('Total de filas incorrecto');
        echo '[categories-db] '.json_encode($filters).' total='.$data['total'].' items='.count($data['rows'])."\n";
    }
    $base = $service->search(['show_inactive'=>'1','per'=>25]);
    $guard = new App\Services\Admin\Categories\CategoryHierarchyGuard($db);
    if ($guard->validateParent(1, 1) === null) throw new RuntimeException('Autoparentezco aceptado');
    if ($guard->validateParent(0, 999999999) === null) throw new RuntimeException('Padre inexistente aceptado');
    if ($base['rows']) {
        $id = (int)$base['rows'][0]['id'];
        if ($guard->validateParent($id, $id) === null) throw new RuntimeException('Autoparentezco real aceptado');
        foreach ($guard->descendantIds($id) as $childId) {
            if ($guard->validateParent($id, $childId) === null) throw new RuntimeException('Ciclo de jerarquía aceptado');
        }
    }
    ob_start();
    render_page('admin/categories/index', $base);
    $html = ob_get_clean();
    foreach (['av3-categories__summary','av3-categories__mobile','av3-categories__table','name="show_inactive"','name="per"'] as $needle)
        if (!str_contains($html, $needle)) throw new RuntimeException('HTML listado: '.$needle);

    $empty = $service->search(['q'=>'unlikely-cat-xxxxx']);
    ob_start();
    render_page('admin/categories/index', $empty);
    $emptyHtml = ob_get_clean();
    if (!str_contains($emptyHtml, 'No se encontraron categorías')) throw new RuntimeException('Estado vacío ausente');

    $vm = (new App\Services\Admin\Categories\ViewModels\CategoriesFormViewModel())->build(
        ['id'=>0,'name'=>'','slug'=>'','parent_id'=>null,'sort'=>0,'is_active'=>1,'cover_image'=>''],
        [], [], [], ['bullets'=>[],'specs'=>[],'faq'=>[]]
    );
    ob_start();
    render_page('admin/categories/form', array_merge($vm, ['pageId'=>'admin-category-form']));
    $formHtml = ob_get_clean();
    foreach (['name="parent_id"','name="cover_image_file"','name="is_active"','data-seo-score','name="slug"'] as $needle)
        if (!str_contains($formHtml, $needle)) throw new RuntimeException('HTML formulario: '.$needle);
    echo "[categories-smoke] Listado real, vacío y formulario SEO: OK; DB solo lectura\n";
} catch (Throwable $e) {
    while (ob_get_level()) ob_end_clean();
    fwrite(STDERR, '[categories-smoke] ERROR: '.$e->getMessage()."\n");
    exit(1);
}
