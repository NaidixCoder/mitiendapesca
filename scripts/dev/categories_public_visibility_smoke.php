<?php
declare(strict_types=1);
define('BASE_PATH', dirname(__DIR__, 2));
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? '127.0.0.1';
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['REQUEST_URI'] = '/categorias';
$_SERVER['REQUEST_METHOD'] = 'GET';
require BASE_PATH.'/vendor/autoload.php';
foreach (glob(BASE_PATH.'/config/boot/*.php') as $file) require_once $file;

$db = db();
$current = (string)$db->query('SELECT DATABASE()')->fetchColumn();
$expected = trim((string)getenv('CATS_EXPECT_DB'));
if ($expected !== '' && $current !== $expected) {
    throw new RuntimeException('Refuse unexpected database: '.$current);
}
$repo = new App\Repositories\Catalog\CategoryRepository($db);
$visible = $repo->visibleCategoryIds();
$nodes = $db->query('SELECT id,parent_id,is_active,slug FROM categories')->fetchAll(PDO::FETCH_ASSOC) ?: [];
$byId = [];
foreach ($nodes as $row) $byId[(int)$row['id']] = $row;
foreach (array_keys($visible) as $id) {
    $seen = [];
    $at = $id;
    while (true) {
        if (isset($seen[$at]) || !isset($byId[$at]) || (int)$byId[$at]['is_active'] !== 1) {
            throw new RuntimeException('Invalid public visibility ancestry at '.$id);
        }
        $seen[$at] = true;
        if ($byId[$at]['parent_id'] === null) break;
        $at = (int)$byId[$at]['parent_id'];
    }
}
$facets = (new App\Services\Catalog\FacetService($repo))->categoriesWithCounts([]);
$home = $repo->listHomeFeatured(24);
foreach ($home as $row) {
    if (!isset($visible[$row['id']])) throw new RuntimeException('Featured invisible '.$row['id']);
}
if ($expected === 'mi_pesca_cat_w6a_20261021') {
    foreach ([1,6,7,9,10,11,13,18] as $id)
        if (!isset($visible[$id])) throw new RuntimeException('Expected visible '.$id);
    foreach ([2,3,4,5,8,12,14,15,16,17,19] as $id)
        if (isset($visible[$id])) throw new RuntimeException('Hidden node leaked '.$id);
    foreach (['child-parent-off','grandchild-grandparent-off','cycle-a','cycle-b','orphan-a','orphan-child','deep-hidden-child','direct-off'] as $slug)
        if ($repo->findActiveBySlug($slug) !== null) throw new RuntimeException('Hidden slug accepted '.$slug);
    if (!$repo->findActiveBySlug('good-grandchild')) throw new RuntimeException('Visible deep category missing');
    $rootSlugs = array_column($facets['categories'], 'slug');
    foreach (['root-ok','parent-on','good-root','parent-reactivated','root-empty-6a'] as $slug)
        if (!in_array($slug, $rootSlugs, true)) throw new RuntimeException('Valid facet root missing '.$slug);
    foreach (['child-parent-off','orphan-a','cycle-a','grandchild-grandparent-off','deep-hidden-child'] as $slug)
        if (in_array($slug, $rootSlugs, true)) throw new RuntimeException('Invalid node promoted to root '.$slug);
    $map = (new App\Services\Catalog\FacetService($repo))->labelMap()['by_slug'];
    if (isset($map['child-parent-off']) || isset($map['orphan-a'])) throw new RuntimeException('Hidden label leaked');
    // Importaciones necesitan resolver categorías aun cuando sean invisibles.
    if ($repo->idBySlug('child-parent-off') !== 3) throw new RuntimeException('idBySlug contract changed');
    if ($repo->idByName('Child Parent Off') !== 3) throw new RuntimeException('idByName contract changed');
    if (($repo->resolveIdPlanned('Root OK', false, true)['id'] ?? null) !== 1) throw new RuntimeException('Import planned lookup changed');
    $planned = $repo->resolveIdPlanned('new-w6a-import', true, true);
    if (($planned['would_create'] ?? false) !== true || ($planned['id'] ?? -1) !== 0)
        throw new RuntimeException('Import dry-run semantics changed');
}
echo '[categories-public-smoke] DB='.$current.' nodes='.count($nodes).' visible='.count($visible).' roots='.count($facets['categories']).' home='.count($home).' OK, read-only'.PHP_EOL;
