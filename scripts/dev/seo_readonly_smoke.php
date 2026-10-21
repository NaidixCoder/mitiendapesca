<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 2));
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['SCRIPT_NAME'] = '/mi-pesca/public_html/index.php';
$_SERVER['REQUEST_URI'] = '/mi-pesca/public_html/admin/seo/auditoria';
$_SERVER['REQUEST_METHOD'] = 'GET';
require BASE_PATH . '/vendor/autoload.php';
foreach (glob(BASE_PATH.'/config/boot/*.php') as $file) require_once $file;
require_once BASE_PATH . '/app/Views/helpers.php';
try {
  $query = new App\Queries\Admin\SeoAuditQuery(db());
  foreach ([
    ['sort'=>'score', 'order'=>'asc', 'per'=>25, 'page'=>1],
    ['active'=>'1', 'sort'=>'score', 'order'=>'asc', 'per'=>10, 'page'=>1],
    ['issue'=>'meta_title', 'sort'=>'score', 'order'=>'asc', 'per'=>25, 'page'=>1],
    ['q'=>'no-existe-seo-7c29d0', 'per'=>25, 'page'=>1],
  ] as $filters) {
    $res = $query->search($filters);
    if (count($res['rows']) > $res['per']) throw new RuntimeException('Excede tamaño de página');
    if (count($res['rows']) > $res['total']) throw new RuntimeException('Total incoherente');
    if ($res['summary']['low'] + $res['summary']['medium'] + $res['summary']['good'] !== $res['summary']['active']) throw new RuntimeException('Totales de score no cierran');
    $vm = (new App\Services\Admin\Seo\ViewModels\SeoAuditIndexViewModel())->build([
      'filters'=>$filters, 'rows'=>$res['rows'], 'total'=>$res['total'],
      'pages'=>$res['pages'], 'page'=>$res['page'], 'per'=>$res['per'],
      'summary'=>$res['summary'],
    ]);
    if (count($vm['rows_prepared']) !== count($res['rows'])) throw new RuntimeException('ViewModel perdió filas');
    if (count($vm['quick_links']) !== 4) throw new RuntimeException('Faltan filtros rápidos');
    if (($filters['q'] ?? '') === 'no-existe-seo-7c29d0' && $res['total'] !== 0) throw new RuntimeException('Búsqueda sin resultados incorrecta');
    echo '[seo-db] OK: filtros '.json_encode($filters, JSON_UNESCAPED_UNICODE).' total='.$res['total']."\n";
  }
  $res = $query->search(['sort'=>'score','order'=>'asc','per'=>10,'page'=>1]);
  $vm = (new App\Services\Admin\Seo\ViewModels\SeoAuditIndexViewModel())->build([
    'filters'=>['sort'=>'score','order'=>'asc'], 'rows'=>$res['rows'], 'total'=>$res['total'],
    'pages'=>$res['pages'], 'page'=>$res['page'], 'per'=>$res['per'],
    'summary'=>$res['summary'],
  ]);
  ob_start();
  render_page('admin/seo/auditoria', array_merge(['pageId'=>'admin-seo-audit'], $vm));
  $html = ob_get_clean();
  foreach (['av3-wrap av3-seo-audit','av3-seo-overview','class="av3-seo-mobile-list"','class="av3-seo-table"','id="seo-audit-filters"'] as $needle)
    if (!str_contains($html, $needle)) throw new RuntimeException('HTML faltante: '.$needle);
  $count = count($res['rows']);
  if (substr_count($html, 'class="av3-seo-mobile-item"') !== $count)
    throw new RuntimeException('Lista móvil no coincide');
  if (substr_count($html, 'class="av3-seo-table__preview"') !== $count)
    throw new RuntimeException('Columna desktop de vista previa incompleta');
  if (substr_count($html, 'class="av3-seo-snippet"') !== ($count * 2))
    throw new RuntimeException('Vista previa escritorio/móvil no coincide');
  if (substr_count($html, '<details class="av3-seo-diagnostic">') !== $count)
    throw new RuntimeException('El diagnóstico desplegable debe existir solo en móvil');
  if (!str_contains($html, 'Vista previa en buscadores') || str_contains($html, 'av3-seo-rich-summary'))
    throw new RuntimeException('Diseño de tabla anterior detectado');
  echo "[seo-render] OK: tabla con vista previa permanente, móvil compacto y consultas de solo lectura\n";
} catch (Throwable $e) {
  while (ob_get_level() > 0) ob_end_clean();
  fwrite(STDERR, '[seo-db] ERROR: '.$e->getMessage()."\n");
  exit(1);
}
