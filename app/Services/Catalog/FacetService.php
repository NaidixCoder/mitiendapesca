<?php
namespace App\Services\Catalog;

use App\Repositories\Catalog\CategoryRepository;
use App\Support\Catalog\CategoryLabel;
use PDO;
use Throwable;

final class FacetService
{
    private ?ProductPublicVisibilityService $productVisibility = null;

    public function __construct(
        private CategoryRepository $categories
    ) {}

    private function productVisibility(): ProductPublicVisibilityService
    {
        return $this->productVisibility ??= new ProductPublicVisibilityService($this->db());
    }

    /**
     * @return array{
     *   categories: array<int, array{slug:string,name:string,count:int,cover_image:string,children: array<int, array{slug:string,name:string,count:int}>}>,
     *   brands: array<int, array{slug:string,name:string,count:int}>,
     *   tags: array<int, array{slug:string,name:string,selected_count:int,options: array<int, array{slug:string,name:string,count:int,selected:bool}>}>
     * }
     */
    public function categoriesWithCounts(array $filters): array
    {
        $db = $this->db();

        $rows = [];
        try {
            $st = $db->query("SELECT id, parent_id, slug, name, COALESCE(cover_image, '') AS cover_image FROM categories WHERE is_active = 1 ORDER BY sort ASC, name ASC");
            $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
        } catch (Throwable $e) {
            $rows = [];
        }

        $eligible = $this->categories->visibleCategoryIds();
        $catsById = [];
        $parents = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            if (!isset($eligible[$id])) continue;
            $parentId = isset($row['parent_id']) ? (int)$row['parent_id'] : null;
            $catsById[$id] = [
                'id' => $id,
                'slug' => (string)($row['slug'] ?? ''),
                'name' => CategoryLabel::sanitize((string)($row['name'] ?? ''), (string)($row['slug'] ?? '')),
                'cover_image' => (string)($row['cover_image'] ?? ''),
                'parent_id' => $parentId,
                'children' => [],
            ];
        }

        foreach ($catsById as $id => $category) {
            $parentId = $category['parent_id'];
            if ($parentId !== null) {
                if (isset($catsById[$parentId])) {
                    $catsById[$parentId]['children'][] = $id;
                }
                // Nunca convertir huérfanos en raíces visibles.
                continue;
            }
            $parents[] = $id;
        }

        $qtyById = [];
        try {
            [$where, $params] = $this->buildProductFilters($filters, false, true);
            $sql = "SELECT p.category_id AS cat_id, COUNT(DISTINCT p.id) AS qty
                    FROM products p
                    LEFT JOIN categories c ON c.id = p.category_id
                    LEFT JOIN categories cp ON cp.id = c.parent_id
                    LEFT JOIN brands b ON b.id = p.brand_id
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY p.category_id";
            $st = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $st->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $st->execute();
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $qtyById[(int)($row['cat_id'] ?? 0)] = (int)($row['qty'] ?? 0);
            }
        } catch (Throwable $e) {
            $qtyById = [];
        }

        // OFF: productos activos de descendientes ocultos siguen disponibles.
        // Atribuir su cantidad al ancestro VISIBLE más próximo, sin promover
        // la categoría oculta en la faceta ni alterar sus propios flags.
        if (!$this->productVisibility()->hidesHiddenCategoryProducts()) {
            $pRows = $db->query('SELECT id,parent_id FROM categories')->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $parentsById = [];
            foreach ($pRows as $pRow) {
                $parentsById[(int)$pRow['id']] = $pRow['parent_id'] === null ? null : (int)$pRow['parent_id'];
            }
            foreach (array_keys($qtyById) as $sourceId) {
                if (isset($catsById[$sourceId])) continue;
                $at = $sourceId;
                $seenAncestors = [];
                while ($at > 0 && isset($parentsById[$at]) && !isset($seenAncestors[$at])) {
                    $seenAncestors[$at] = true;
                    $at = (int)($parentsById[$at] ?? 0);
                    if (isset($catsById[$at])) {
                        $qtyById[$at] = ($qtyById[$at] ?? 0) + $qtyById[$sourceId];
                        break;
                    }
                }
            }
        }

        // Acumulación iterativa de descendientes para evitar profundidad de pila
        // y visitas repetidas: O(categorías elegibles), sin consultas N+1.
        $order = [];
        $stack = array_reverse($parents);
        while ($stack !== []) {
            $nodeId = array_pop($stack);
            $order[] = $nodeId;
            foreach (array_reverse($catsById[$nodeId]['children']) as $childId) {
                $stack[] = $childId;
            }
        }
        $sums = [];
        foreach (array_reverse($order) as $nodeId) {
            $total = (int)($qtyById[$nodeId] ?? 0);
            foreach ($catsById[$nodeId]['children'] as $childId) {
                $total += $sums[$childId] ?? 0;
            }
            $sums[$nodeId] = $total;
        }

        $facetsCategories = [];
        foreach ($parents as $parentId) {
            $parent = $catsById[$parentId];
            $children = [];
            foreach ($parent['children'] as $childId) {
                $child = $catsById[$childId];
                $children[] = [
                    'slug' => $child['slug'],
                    'name' => $child['name'],
                    'count' => (int)($qtyById[$childId] ?? 0),
                ];
            }
            $facetsCategories[] = [
                'slug' => $parent['slug'],
                'name' => $parent['name'],
                'count' => $sums[$parentId] ?? 0,
                'cover_image' => (string)($parent['cover_image'] ?? ''),
                'children' => $children,
            ];
        }

        return [
            'categories' => $facetsCategories,
            'brands' => $this->brandsWithCounts($filters),
            'tags' => $this->tagsWithCounts($filters),
        ];
    }

    /**
     * @return array{by_slug: array<string,string>, by_id: array<int,string>, by_brand_slug: array<string,string>}
     */
    public function labelMap(): array
    {
        $db = $this->db();
        $bySlug = [];
        $byId = [];
        $byBrandSlug = [];

        try {
            $eligible = $this->categories->visibleCategoryIds();
            $st = $db->query("SELECT id, slug, name FROM categories WHERE is_active = 1");
            foreach ($st ? $st->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
                if (!isset($eligible[(int)$row['id']])) continue;
                $label = CategoryLabel::sanitize((string)($row['name'] ?? ''), (string)($row['slug'] ?? ''));
                $bySlug[(string)$row['slug']] = $label;
                $byId[(int)$row['id']] = $label;
            }
        } catch (Throwable $e) {
        }

        try {
            $st = $db->query("SELECT slug, name FROM brands ORDER BY name ASC");
            foreach ($st ? $st->fetchAll(PDO::FETCH_ASSOC) : [] as $row) {
                $slug = trim((string)($row['slug'] ?? ''));
                $name = trim((string)($row['name'] ?? ''));
                if ($slug === '' || $name === '') {
                    continue;
                }
                $byBrandSlug[$slug] = $name;
                $bySlug[$slug] = $bySlug[$slug] ?? $name;
            }
        } catch (Throwable $e) {
        }

        return ['by_slug' => $bySlug, 'by_id' => $byId, 'by_brand_slug' => $byBrandSlug];
    }

    /** @return array<int, array{slug:string,name:string,count:int}> */
    private function brandsWithCounts(array $filters): array
    {
        $db = $this->db();
        $brands = [];
        $selectedBrands = $this->brandSlugs($filters);

        try {
            [$where, $params] = $this->buildProductFilters($filters, true, false);
            $sql = "SELECT b.slug AS slug, b.name AS name, COUNT(DISTINCT p.id) AS qty
                    FROM brands b
                    INNER JOIN products p ON p.brand_id = b.id
                    LEFT JOIN categories c ON c.id = p.category_id
                    LEFT JOIN categories cp ON cp.id = c.parent_id
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY b.id, b.slug, b.name
                    ORDER BY b.name ASC";
            $st = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $st->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $st->execute();
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $slug = trim((string)($row['slug'] ?? ''));
                $name = trim((string)($row['name'] ?? ''));
                $count = (int)($row['qty'] ?? 0);
                if ($slug === '' || $name === '' || $count <= 0) {
                    continue;
                }
                $brands[] = ['slug' => $slug, 'name' => $name, 'count' => $count];
            }
        } catch (Throwable $e) {
            $brands = [];
        }

        foreach ($selectedBrands as $selectedBrand) {
            $exists = false;
            foreach ($brands as $brand) {
                if (($brand['slug'] ?? '') === $selectedBrand) {
                    $exists = true;
                    break;
                }
            }

            if ($exists) {
                continue;
            }

            try {
                $st = $db->prepare("SELECT slug, name FROM brands WHERE slug = ? LIMIT 1");
                $st->execute([$selectedBrand]);
                $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
                if ($row) {
                    $brands[] = [
                        'slug' => trim((string)($row['slug'] ?? '')),
                        'name' => trim((string)($row['name'] ?? '')),
                        'count' => 0,
                    ];
                }
            } catch (Throwable $e) {
            }
        }

        return $brands;
    }

    /** @return array{0:list<string>,1:array<string,mixed>} */
    private function buildProductFilters(array $filters, bool $includeCategory, bool $includeBrand): array
    {
        $where = [$this->productVisibility()->predicate('p')];
        $params = [];

        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $where[] = '(
                p.name LIKE :q_name
                OR p.sku LIKE :q_sku
                OR c.name LIKE :q_category_name
                OR c.slug LIKE :q_category_slug
                OR cp.name LIKE :q_parent_category_name
                OR cp.slug LIKE :q_parent_category_slug
                OR b.name LIKE :q_brand_name
                OR b.slug LIKE :q_brand_slug
            )';
            $like = '%' . $q . '%';
            $params[':q_name'] = $like;
            $params[':q_sku'] = $like;
            $params[':q_category_name'] = $like;
            $params[':q_category_slug'] = $like;
            $params[':q_parent_category_name'] = $like;
            $params[':q_parent_category_slug'] = $like;
            $params[':q_brand_name'] = $like;
            $params[':q_brand_slug'] = $like;
        }

        // Coincide con ProductRepository::effectivePriceSql(): variantes usan
        // el mínimo activo, no el precio cacheado del producto.
        $effectivePrice = "CASE WHEN p.sale_mode = 'variants' THEN COALESCE((SELECT MIN(NULLIF(v.price,0)) FROM product_variants v WHERE v.product_id=p.id AND v.is_active=1),p.price) ELSE p.price END";
        $min = trim((string)($filters['min'] ?? ''));
        if ($min !== '' && is_numeric($min)) {
            $where[] = $effectivePrice.' >= :min_price';
            $params[':min_price'] = (int)$min;
        }

        $max = trim((string)($filters['max'] ?? ''));
        if ($max !== '' && is_numeric($max)) {
            $where[] = $effectivePrice.' <= :max_price';
            $params[':max_price'] = (int)$max;
        }

        $scope = $filters['category_scope_ids'] ?? null;
        if (is_array($scope)) {
            $scope = array_values(array_unique(array_filter(array_map('intval',$scope),
                static fn(int $id): bool => $id > 0)));
            $where[] = $scope === [] ? '1=0' : 'p.category_id IN ('.implode(',', $scope).')';
        }
        if ($includeCategory && !is_array($scope)) {
            $categorySlug = trim((string)($filters['category_slug'] ?? ''));
            if ($categorySlug !== '') {
                $where[] = 'c.slug = :category_slug';
                $params[':category_slug'] = $categorySlug;
            } else {
                $subSlug = trim((string)($filters['sub'] ?? ''));
                if ($subSlug !== '') {
                    $where[] = 'c.slug = :sub_slug';
                    $params[':sub_slug'] = $subSlug;
                } else {
                    $categoryFilter = trim((string)($filters['categoria'] ?? ''));
                    if ($categoryFilter !== '') {
                        $where[] = '(c.slug = :categoria_slug OR cp.slug = :categoria_parent_slug)';
                        $params[':categoria_slug'] = $categoryFilter;
                        $params[':categoria_parent_slug'] = $categoryFilter;
                    }
                }
            }
        }

        if ($includeBrand) {
            $brandSlugs = $this->brandSlugs($filters);
            if (!empty($brandSlugs)) {
                $placeholders = [];
                foreach (array_values($brandSlugs) as $index => $brandSlug) {
                    $key = ':brand_slug_' . $index;
                    $placeholders[] = $key;
                    $params[$key] = $brandSlug;
                }
                $where[] = 'b.slug IN (' . implode(', ', $placeholders) . ')';
            }
        }

        // El filtro heredado es solo una pista de compatibilidad. Nunca
        // sustituye el setting B1 ni agrega otro predicado c.is_active.
        if ((string)($filters['enforce_active_category'] ?? '') === '1') {
            if (!$this->productVisibility()->hidesHiddenCategoryProducts() && function_exists('log_error')) {
                log_error('FacetService: petición legacy exige categoría activa, pero B1 está OFF; se respeta Settings.');
            }
        }
        $tagFilters = $this->tagSelections($filters);
        foreach ($tagFilters as $groupSlug => $tagSlugs) {
            if ($groupSlug === '' || $tagSlugs === []) {
                continue;
            }

            $placeholders = [];
            foreach (array_values($tagSlugs) as $index => $tagSlug) {
                $key = ':tag_' . preg_replace('/[^a-z0-9_]+/i', '_', $groupSlug) . '_' . $index;
                $placeholders[] = $key;
                $params[$key] = $tagSlug;
            }
            $groupKey = ':tag_group_' . preg_replace('/[^a-z0-9_]+/i', '_', $groupSlug);
            $params[$groupKey] = $groupSlug;

            $where[] = "EXISTS (
                SELECT 1
                FROM (
                    SELECT pct.product_id, ct.slug AS tag_slug, cg.slug AS group_slug
                    FROM product_catalog_tags pct
                    INNER JOIN catalog_tags ct ON ct.id = pct.tag_id
                    INNER JOIN catalog_tag_groups cg ON cg.id = ct.group_id
                    UNION ALL
                    SELECT pv.product_id, ct.slug AS tag_slug, cg.slug AS group_slug
                    FROM variant_catalog_tags vct
                    INNER JOIN product_variants pv ON pv.id = vct.variant_id
                    INNER JOIN catalog_tags ct ON ct.id = vct.tag_id
                    INNER JOIN catalog_tag_groups cg ON cg.id = ct.group_id
                ) tags_match
                WHERE tags_match.product_id = p.id
                  AND tags_match.group_slug = {$groupKey}
                  AND tags_match.tag_slug IN (" . implode(', ', $placeholders) . ')
            )';
        }

        return [$where, $params];
    }

    private function db(): PDO
    {
        $db = $this->categories->db();
        if (!$db instanceof PDO && function_exists('db')) {
            $db = db();
        }
        return $db;
    }

    /** @return array<int,string> */
    private function brandSlugs(array $filters): array
    {
        $brandSlug = trim((string)($filters['brand_slug'] ?? ''));
        if ($brandSlug !== '') {
            return [$brandSlug];
        }

        $rawBrands = $filters['marca'] ?? [];
        $brandSlugs = is_array($rawBrands) ? $rawBrands : [$rawBrands];

        return array_values(array_filter(array_map(static fn($value): string => trim((string)$value), $brandSlugs), static fn(string $value): bool => $value !== ''));
    }

    /**
     * @return array<int, array{slug:string,name:string,selected_count:int,options: array<int, array{slug:string,name:string,count:int,selected:bool}>}>
     */
    private function tagsWithCounts(array $filters): array
    {
        $db = $this->db();
        $selectedMap = $this->tagSelections($filters);
        $facets = [];

        try {
            [$where, $params] = $this->buildProductFilters($filters, true, true);
            $sql = "SELECT
                        tg.group_slug,
                        tg.group_name,
                        tg.group_sort,
                        tg.tag_slug,
                        tg.tag_name,
                        tg.tag_sort,
                        COUNT(DISTINCT p.id) AS qty
                    FROM products p
                    INNER JOIN (
                        SELECT pct.product_id,
                               cg.slug AS group_slug,
                               cg.name AS group_name,
                               cg.sort AS group_sort,
                               ct.slug AS tag_slug,
                               ct.name AS tag_name,
                               ct.sort AS tag_sort
                        FROM product_catalog_tags pct
                        INNER JOIN catalog_tags ct ON ct.id = pct.tag_id AND ct.is_active = 1
                        INNER JOIN catalog_tag_groups cg ON cg.id = ct.group_id AND cg.is_active = 1
                        UNION ALL
                        SELECT pv.product_id,
                               cg.slug AS group_slug,
                               cg.name AS group_name,
                               cg.sort AS group_sort,
                               ct.slug AS tag_slug,
                               ct.name AS tag_name,
                               ct.sort AS tag_sort
                        FROM variant_catalog_tags vct
                        INNER JOIN product_variants pv ON pv.id = vct.variant_id
                        INNER JOIN catalog_tags ct ON ct.id = vct.tag_id AND ct.is_active = 1
                        INNER JOIN catalog_tag_groups cg ON cg.id = ct.group_id AND cg.is_active = 1
                    ) tg ON tg.product_id = p.id
                    LEFT JOIN categories c ON c.id = p.category_id
                    LEFT JOIN categories cp ON cp.id = c.parent_id
                    LEFT JOIN brands b ON b.id = p.brand_id
                    WHERE " . implode(' AND ', $where) . "
                    GROUP BY tg.group_slug, tg.group_name, tg.group_sort, tg.tag_slug, tg.tag_name, tg.tag_sort
                    ORDER BY tg.group_sort ASC, tg.group_name ASC, tg.tag_sort ASC, tg.tag_name ASC";
            $st = $db->prepare($sql);
            foreach ($params as $key => $value) {
                $st->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $st->execute();
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                $groupSlug = trim((string)($row['group_slug'] ?? ''));
                $groupName = trim((string)($row['group_name'] ?? ''));
                $tagSlug = trim((string)($row['tag_slug'] ?? ''));
                $tagName = trim((string)($row['tag_name'] ?? ''));
                $count = (int)($row['qty'] ?? 0);
                if ($groupSlug === '' || $groupName === '' || $tagSlug === '' || $tagName === '' || $count <= 0) {
                    continue;
                }

                if (!isset($facets[$groupSlug])) {
                    $facets[$groupSlug] = [
                        'slug' => $groupSlug,
                        'name' => $groupName,
                        'sort' => (int)($row['group_sort'] ?? 0),
                        'selected_count' => 0,
                        'options' => [],
                    ];
                }

                $selected = in_array($tagSlug, $selectedMap[$groupSlug] ?? [], true);
                if ($selected) {
                    $facets[$groupSlug]['selected_count']++;
                }

                $facets[$groupSlug]['options'][] = [
                    'slug' => $tagSlug,
                    'name' => $tagName,
                    'count' => $count,
                    'selected' => $selected,
                ];
            }
        } catch (Throwable $e) {
            return [];
        }

        foreach ($selectedMap as $groupSlug => $selectedTagSlugs) {
            if (isset($facets[$groupSlug]) || $groupSlug === '' || $selectedTagSlugs === []) {
                continue;
            }

            try {
                $placeholders = implode(',', array_fill(0, count($selectedTagSlugs), '?'));
                $sql = "SELECT cg.slug AS group_slug,
                               cg.name AS group_name,
                               cg.sort AS group_sort,
                               ct.slug AS tag_slug,
                               ct.name AS tag_name
                        FROM catalog_tag_groups cg
                        INNER JOIN catalog_tags ct ON ct.group_id = cg.id
                        WHERE cg.slug = ?
                          AND ct.slug IN ({$placeholders})
                        ORDER BY ct.sort ASC, ct.name ASC";
                $st = $db->prepare($sql);
                $st->execute(array_merge([$groupSlug], $selectedTagSlugs));
                foreach ($st->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
                    $slug = trim((string)($row['group_slug'] ?? ''));
                    if ($slug === '') {
                        continue;
                    }

                    if (!isset($facets[$slug])) {
                        $facets[$slug] = [
                            'slug' => $slug,
                            'name' => trim((string)($row['group_name'] ?? $slug)),
                            'sort' => (int)($row['group_sort'] ?? 9999),
                            'selected_count' => 0,
                            'options' => [],
                        ];
                    }

                    $tagSlug = trim((string)($row['tag_slug'] ?? ''));
                    $selected = in_array($tagSlug, $selectedMap[$slug] ?? [], true);
                    if ($selected) {
                        $facets[$slug]['selected_count']++;
                    }
                    $facets[$slug]['options'][] = [
                        'slug' => $tagSlug,
                        'name' => trim((string)($row['tag_name'] ?? '')),
                        'count' => 0,
                        'selected' => $selected,
                    ];
                }
            } catch (Throwable $e) {
            }
        }

        uasort($facets, static function (array $a, array $b): int {
            $sortCmp = ((int)($a['sort'] ?? 0)) <=> ((int)($b['sort'] ?? 0));
            if ($sortCmp !== 0) {
                return $sortCmp;
            }
            return strcmp((string)($a['name'] ?? ''), (string)($b['name'] ?? ''));
        });

        return array_values(array_map(static function (array $facet): array {
            unset($facet['sort']);
            return $facet;
        }, $facets));
    }

    /**
     * @return array<string,array<int,string>>
     */
    private function tagSelections(array $filters): array
    {
        $raw = $filters['tags'] ?? [];
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $slug => $values) {
            $slug = trim((string)$slug);
            if ($slug === '') {
                continue;
            }
            $tagSlugs = array_values(array_unique(array_filter(array_map(static fn($value): string => trim((string)$value), is_array($values) ? $values : [$values]), static fn(string $value): bool => $value !== '')));
            if ($tagSlugs === []) {
                continue;
            }
            $out[$slug] = $tagSlugs;
        }

        ksort($out);
        return $out;
    }
}
