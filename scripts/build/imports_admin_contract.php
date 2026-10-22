<?php
declare(strict_types=1);
/**
 * Contrato focal de Importaciones Admin V3. Solo lectura de código;
 * render sintético sin DB ni acciones POST.
 */
$root = dirname(__DIR__, 2);
$failed = [];
$checks = 0;
$assert = static function (bool $pass, string $name) use (&$checks, &$failed): void {
    $checks++;
    if (!$pass) $failed[] = $name;
};
$read = static fn(string $relative): string => (string)file_get_contents($root . '/' . $relative);
$views = glob($root . '/app/Views/{pages/admin/imports,partials/admin/imports/index,partials/admin/imports/view}/*.php', GLOB_BRACE);
if (!$views) {
    $views = array_merge(
        glob($root . '/app/Views/pages/admin/imports/*.php') ?: [],
        glob($root . '/app/Views/partials/admin/imports/index/*.php') ?: [],
        glob($root . '/app/Views/partials/admin/imports/view/*.php') ?: []
    );
}
$assert(count($views) >= 14, 'Vistas/parciales localizados');
foreach ($views as $file) {
    $assert(strpos((string)file_get_contents($file), "\xEF\xBF\xBD") === false, 'Sin U+FFFD: ' . basename($file));
}
$index = $read('app/Views/pages/admin/imports/index.php');
$show = $read('app/Views/pages/admin/imports/view.php');
$css = $read('public_html/assets/css/admin-v3/pages/imports.css');
$js = $read('public_html/assets/js/admin/imports/preview.js');
$assert(substr_count($index, "css/admin-v3/pages/imports.css") === 1, 'CSS index una vez');
$assert(substr_count($show, "css/admin-v3/pages/imports.css") === 1, 'CSS show una vez');
$assert(strpos($css, '!important') === false, 'Sin !important');
$assert(strpos($css, '@container') !== false, 'Responsive container query');
$assert(strpos($js, "level === 'warn' ? 'warning'") !== false, 'Alias warn JS');
$assert(strpos($js, 'aria-pressed') !== false, 'Filtros teclado/estado');
$assert(strpos($read('app/Views/partials/admin/imports/index/preflight.php'), 'data-label="SKU"') !== false, 'Filas móviles');
$assert(strpos($read('app/Views/partials/admin/imports/view/rows.php'), 'av3-imports__diff-from') !== false, 'Antes/después');

require_once $root . '/app/Services/Admin/Imports/ViewModels/ImportsIndexViewModel.php';
require_once $root . '/app/Services/Admin/Imports/ImportsViewPresenterService.php';
if (!function_exists('url')) {
    function url(string $route): string { return $route; }
}
if (!function_exists('e')) {
    function e($value): string { return htmlspecialchars((string)$value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
if (!function_exists('csrf_field')) {
    function csrf_field(): string { return '<input type="hidden" name="_csrf" value="sintetico">'; }
}
$rows = [
    ['row' => 2, 'level' => 'warning', 'sku' => 'AV-1', 'sku_padre' => '', 'tipo_fila' => 'producto', 'entidad' => 'Producto', 'accion' => 'Actualizar', 'notas' => ['Costo: 100 -> 120']],
    ['row' => 3, 'level' => 'warn', 'sku' => 'AV-2', 'sku_padre' => '', 'tipo_fila' => 'producto', 'entidad' => 'Producto', 'accion' => 'Actualizar', 'notas' => ['Precio: 200 -> 240']],
    ['row' => 4, 'level' => 'error', 'sku' => 'ER-1', 'sku_padre' => '', 'tipo_fila' => 'producto', 'entidad' => 'Producto', 'accion' => 'Bloquea', 'notas' => ['Revisar']],
    ['row' => 5, 'level' => 'ok', 'sku' => 'OK-1', 'sku_padre' => '', 'tipo_fila' => 'producto', 'entidad' => 'Producto', 'accion' => 'Actualizar', 'notas' => ['Stock: 1 -> 5']],
];
$vm = (new \App\Services\Admin\Imports\ViewModels\ImportsIndexViewModel())->build([
    'preview' => ['original_name' => 'sintetico.csv', 'result' => ['rows' => $rows, 'total' => 4, 'ok' => 1, 'err' => 1, 'warnings' => 2, 'plan' => []]],
    'preflight' => ['reason' => 'Hay validaciones pendientes'],
    'confirm' => ['existing_file' => 'synthetic.csv', 'opt_b64' => 'fake'],
]);
$data = $vm['imports_index_view'];
$assert((int)$data['cntWarn'] === 2, 'Contador warning y warn');
$assert((int)$data['cntErr'] === 1, 'Contador de errores');
$assert(!$data['canApply'], 'Aplicar bloqueado en error');
$assert(($data['detailRowsPrepared'][0]['level'] ?? null) === 'warning', 'Warning normalizado');
$assert(($data['detailRowsPrepared'][1]['level'] ?? null) === 'warning', 'Warn legacy normalizado');

$present = (new \App\Services\Admin\Imports\ImportsViewPresenterService())->presentShow(
    ['id'=>1, 'status'=>'dry-failed', 'total_rows'=>2, 'ok_rows'=>1, 'err_rows'=>1],
    ['rows'=>[
        ['row'=>2, 'level'=>'warning', 'sku'=>'AV-1', 'accion'=>'Actualizar', 'notas'=>['Costo: 100 -> 120']],
        ['row'=>3, 'level'=>'warn', 'sku'=>'AV-2', 'accion'=>'Actualizar', 'notas'=>['Stock: 3 -> 4']],
        ['row'=>4, 'level'=>'ok', 'sku'=>'AV-3', 'accion'=>'Crear', 'notas'=>['costo_sin_iva: 100 ARS · iva: 21% · precio_ars: 220']],
    ]],
    0
);
$assert(($present['rowsPrepared'][0]['level'] ?? null) === 'warning', 'Presenter warning real');
$assert(($present['rowsPrepared'][1]['level'] ?? null) === 'warning', 'Presenter warn legacy');
$assert(($present['rowsPrepared'][0]['cambios'][0]['from'] ?? null) === '100', 'Diff conserva costo anterior');
$assert(($present['rowsPrepared'][0]['cambios'][0]['to'] ?? null) === '120', 'Diff conserva costo nuevo');
$assert(($present['rowsPrepared'][2]['cambios'][0]['type'] ?? null) === 'metadata', 'Metadatos de precio identificados');
$assert(($present['rowsPrepared'][2]['cambios'][0]['items'][0]['value'] ?? null) === '100 ARS', 'Costo neto informativo intacto');
$assert(strpos($read('app/Views/partials/admin/imports/view/rows.php'), 'av3-imports__details-values') !== false, 'Metadatos en diferencias visuales');

$upload = $root . '/app/Views/partials/admin/imports/index/upload_form.php';
ob_start();
require $upload;
$html = (string)ob_get_clean();
$names = ['file','job_type','delimiter','create_missing_products_present','create_missing_products','create_missing_brands','create_missing_categories','create_missing_suppliers','allow_parent_from_variants',
 'u_cost','u_price','u_wholesale_overrides','u_list_price','u_stock','stock_mode_sum','u_ship_dims','u_iva','u_internal_code','u_name','u_brand','u_supplier','u_category','u_desc','u_publish','u_attrs',
 'derive_prices','default_margin_pct','default_iva_rate','list_price_equals_price'];
foreach ($names as $name) {
    $assert(strpos($html, 'name="' . $name . '"') !== false, 'Campo conservado: ' . $name);
}
$assert(strpos($html, 'action="/admin/imports/preview"') !== false, 'Primera acción preview');
$assert(strpos($html, 'Analizar archivo') !== false, 'CTA analizar');
$assert(strpos($html, 'CSV o Excel') === false, 'No promete XLS/XLSX');
$assert(strpos($html, 'name="_csrf"') !== false, 'CSRF upload');
$assert(strpos($read('app/Views/partials/admin/imports/index/history.php'), "url('/admin/imports/rollback')") !== false, 'Revert historial');
$assert(strpos($read('app/Views/partials/admin/imports/view/summary.php'), "url('/admin/imports/rollback')") !== false, 'Revert detalle');
$assert(strpos($read('app/Views/partials/admin/imports/index/preflight.php'), "url('/admin/imports/apply')") !== false, 'Apply solo en preview');

if ($failed) {
    fwrite(STDERR, "FAIL " . count($failed) . "/$checks\n - " . implode("\n - ", $failed) . "\n");
    exit(1);
}
echo "OK: $checks verificaciones (contrato HTML/CSS/JS, warning y diffs sintéticos).\n";
