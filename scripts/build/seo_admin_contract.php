<?php
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$files = [
  'view' => 'app/Views/pages/admin/seo/auditoria.php',
  'filters' => 'app/Views/partials/admin/seo/auditoria/filters.php',
  'results' => 'app/Views/partials/admin/seo/auditoria/results.php',
  'diagnostic' => 'app/Views/partials/admin/seo/auditoria/diagnostic.php',
  'snippet' => 'app/Views/partials/admin/seo/auditoria/serp_preview.php',
  'query' => 'app/Queries/Admin/SeoAuditQuery.php',
  'vm' => 'app/Services/Admin/Seo/ViewModels/SeoAuditIndexViewModel.php',
  'css' => 'public_html/assets/css/admin-v3/pages/seo.css',
  'head' => 'app/Views/partials/head.php',
  'product' => 'app/Views/partials/admin/products/tab_seo.php',
  'entity' => 'app/Views/partials/admin/seo/entity_panel.php',
  'js' => 'public_html/assets/js/admin/seo/product_blocks.js',
];
$data = [];
foreach ($files as $key => $file) {
  $data[$key] = file_get_contents($root . '/' . $file);
  if ($data[$key] === false) throw new RuntimeException('SEO: archivo faltante ' . $file);
}
$requires = [
  'view' => ['av3-seo-audit', 'av3-seo-overview', 'page_header.php'],
  'filters' => ['id="seo-audit-filters"', 'data-seo-audit-q', 'av3-seo-filterbar', 'av3-seo-advanced', 'av3-seo-advanced__grid', 'name="issue"', 'name="active"', 'name="score_min"', 'name="per"'],
  'results' => ['data-seo-audit-table', 'av3-seo-table', 'av3-seo-col-preview', 'Vista previa en buscadores', 'av3-seo-mobile-list', 'av3-seo-mobile-item', 'auditoria/diagnostic.php', 'auditoria/serp_preview.php', 'av3-seo-pagination'],
  'diagnostic' => ['av3-seo-diagnostic', 'issues_remaining', 'auditoria/serp_preview.php'],
  'snippet' => ['av3-seo-snippet', 'serp_title', 'serp_desc', 'public_url'],
  'css' => ['container-type:inline-size', '@container (min-width:1060px)', '.av3-seo-col-preview', '.av3-seo-snippet', '.av3-seo-editor__panel', '.av3-seo-mobile-moves'],
  'query' => ['AS seo_score', '$summarySql', 't.is_active = 1', 'COUNT(*) AS active'],
  'vm' => ['issues_primary', 'issues_remaining', 'score_tone', 'quick_links', 'advanced_count', 'pagination_links'],
  'product' => ['av3-seo-editor', 'av3-seo-editor__settings', 'data-seo-score', 'data-seo-blocks'],
  'entity' => ['av3-seo-editor', 'av3-seo-editor__settings', 'data-seo-score', 'data-seo-blocks'],
  'js' => ['data-seo-mobile-moves', 'moveRow(', 'data-seo-move'],
  'head' => ['css/admin-v3/pages/seo.css'],
];
foreach ($requires as $key => $list) foreach ($list as $term) {
  if (!str_contains($data[$key], $term)) throw new RuntimeException("SEO: $key sin $term");
}
if (substr_count($data['results'], 'auditoria/diagnostic.php') !== 1) throw new RuntimeException('SEO: diagnóstico debe existir solo en móvil');
if (substr_count($data['results'], 'auditoria/serp_preview.php') !== 1) throw new RuntimeException('SEO: vista previa desktop no es permanente');
if (substr_count($data['results'], '<details') !== 0) throw new RuntimeException('SEO: colapsable desktop obsoleto');
if (str_contains($data['diagnostic'], 'Contenido complementario') || str_contains($data['diagnostic'], 'Problemas evaluados')) throw new RuntimeException('SEO: contenido repetido en diagnóstico');
foreach (['card av3-pad-', 'bg-rose-100 text-rose-800', 'av3-md-grid-cols-5', 'btn-primary'] as $obsolete) {
  if (str_contains($data['results'], $obsolete) || str_contains($data['filters'], $obsolete))
    throw new RuntimeException('SEO: presentación obsoleta ' . $obsolete);
}
foreach (['product','entity'] as $key) {
  if (str_contains($data[$key], 'class="card av3-pad-'))
    throw new RuntimeException('SEO editor antiguo en ' . $key);
}
echo "[seo-admin] OK: listado mobile-first, CSS V3, auditoría y editores SEO\n";
