<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$get = static fn(string $path): string => (string)file_get_contents($root.'/'.$path);
$service = $get('app/Services/Catalog/CategoryPublicVisibilityService.php');
$repo = $get('app/Repositories/Catalog/CategoryRepository.php');
$facets = $get('app/Services/Catalog/FacetService.php');
$detail = $get('app/Controllers/Catalog/CategoryController.php');
$directory = $get('app/Controllers/Catalog/CategoriesController.php');
$meta = $get('app/Controllers/System/MetaController.php');
$mobile = $get('app/Views/partials/header/_mobile_search_sheet.php');

$checks = [
    'service category public visibility exists' => str_contains($service, 'class CategoryPublicVisibilityService'),
    'loads hierarchy once' => str_contains($service, "SELECT id,parent_id,is_active FROM categories") && str_contains($service, '$this->visible !== null'),
    'no recursive queries or recursion in service' => substr_count($service, '->query(') === 1 && !str_contains($service, 'WITH RECURSIVE'),
    'rejects inactive ancestors' => str_contains($service, "if (!\$node['is_active'])"),
    'rejects orphan parents and cycles' => str_contains($service, 'isset($seen[$current])') && str_contains($service, '!isset($this->nodes[$current])'),
    'uses memoized iterative traversal' => str_contains($service, '$path[] = $current') && str_contains($service, '$resolved[$nodeId] = $valid'),
    'repo caches visibility object' => str_contains($repo, 'private ?CategoryPublicVisibilityService') && str_contains($repo, 'new CategoryPublicVisibilityService($this->db)'),
    'repository landing checks full ancestry' => str_contains($repo, "isVisibleId((int)\$row['id'])"),
    'repository homepage featured filters before limit' => str_contains($repo, 'array_slice($rows, 0, $limit)') && str_contains($repo, 'visibleCategoryIds()'),
    'repository import creation preserved' => str_contains($repo, 'function resolveId(') && str_contains($repo, 'function resolveIdPlanned(') && str_contains($repo, 'function create('),
    'facet filters eligible categories' => str_contains($facets, '$eligible = $this->categories->visibleCategoryIds()') && str_contains($facets, 'if (!isset($eligible[$id])) continue;'),
    'facet does not promote hidden orphan' => str_contains($facets, 'Nunca convertir huérfanos en raíces visibles'),
    'facet category count aggregation iterative' => str_contains($facets, 'array_reverse($order)') && str_contains($facets, '$sums[$nodeId]'),
    'facet category label map filtered' => str_contains($facets, 'if (!isset($eligible[(int)$row[\'id\']])) continue;'),
    'facet product policy unchanged' => str_contains($facets, "if ((string)(\$filters['enforce_active_category'] ?? '') === '1')"),
    'category controller explicit 404' => str_contains($detail, 'http_response_code(404);') && str_contains($detail, "findActiveBySlug(\$slug)"),
    'directory retains show-empty option' => str_contains($directory, 'catalog_show_empty_categories'),
    'directory delegates category filtering to facets' => str_contains($directory, 'categoriesWithCounts([])'),
    'repo public ordered API introduced' => str_contains($repo, 'function listPublicVisibleOrdered(?int $limit = null)'),
    'repo filters visible before limit' => str_contains($repo, 'if (!isset($eligible[$id])) continue;') && str_contains($repo, 'count($rows) >= max(1, $limit)'),
    'sitemap uses only shared public ordered API' => str_contains($meta, '->listPublicVisibleOrdered();') && str_contains($meta, 'function sitemapCategories(): void'),
    'sitemap preserves XML escaping and created at' => str_contains($meta, 'htmlspecialchars($locUrl, ENT_QUOTES') && str_contains($meta, 'created_at'),
    'mobile requests 8 AFTER visibility' => str_contains($mobile, '->listPublicVisibleOrdered(8)') && !str_contains($mobile, 'LIMIT 8'),
    'mobile HTML markup retained' => str_contains($mobile, 'ph-search-chip ph-search-chip-link') && str_contains($mobile, 'data-ph-search-results'),

];
foreach ($checks as $name=>$passed) {
    if (!$passed) {
        fwrite(STDERR, "[categories-public-contract] FAIL: {$name}\n");
        exit(1);
    }
}
echo "[categories-public-contract] OK: ".count($checks)." checks\n";
