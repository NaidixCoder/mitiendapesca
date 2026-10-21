<?php
namespace App\Repositories\Catalog;

use App\Support\Catalog\CategoryLabel;
use PDO;
use Throwable;

final class ProductRepository
{
    public function __construct(private PDO $db) {}

    /** Cache simple de columnas existentes (por proceso). */
    private static array $colCache = [];

    private function hasColumn(string $table, string $column): bool
    {
        $key = $table . '.' . $column;
        if (array_key_exists($key, self::$colCache)) {
            return (bool) self::$colCache[$key];
        }
        try {
            $st = $this->db->prepare("SHOW COLUMNS FROM {$table} LIKE ?");
            $st->execute([$column]);
            $ok = (bool) $st->fetch(PDO::FETCH_ASSOC);
            self::$colCache[$key] = $ok;
            return $ok;
        } catch (Throwable $e) {
            self::$colCache[$key] = false;
            return false;
        }
    }

    public function supportsRatingSort(): bool
    {
        return $this->hasColumn('products', 'rating');
    }

    private function effectivePriceSql(string $productAlias = 'p', string $variantAlias = 'v'): string
    {
        return "CASE
                    WHEN {$productAlias}.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF({$variantAlias}.price,0)) FROM product_variants {$variantAlias} WHERE {$variantAlias}.product_id = {$productAlias}.id AND {$variantAlias}.is_active = 1),
                        {$productAlias}.price
                    )
                    ELSE {$productAlias}.price
                END";
    }

    private function publicVisibilitySql(string $productAlias = 'p'): string
    {
        return "{$productAlias}.is_active = 1";
    }

    private function inStockVisibilitySql(string $productAlias = 'p'): string
    {
        return "(
            ({$productAlias}.sale_mode = 'variants' AND EXISTS (
                SELECT 1
                FROM product_variants pv_stock
                LEFT JOIN inventory_variants iv_stock ON iv_stock.variant_id = pv_stock.id
                WHERE pv_stock.product_id = {$productAlias}.id
                  AND pv_stock.is_active = 1
                  AND COALESCE(iv_stock.stock, 0) > 0
            ))
            OR
            ({$productAlias}.sale_mode <> 'variants' AND EXISTS (
                SELECT 1
                FROM inventory i_stock
                WHERE i_stock.product_id = {$productAlias}.id
                  AND i_stock.stock > 0
            ))
        )";
    }

    /** @return array{0:list<string>,1:array<string,mixed>} */
    private function buildCatalogWhere(array $filters, bool $includePrice = true): array
    {
        $where = [$this->publicVisibilitySql('p')];
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

        $effectivePriceSql = $this->effectivePriceSql('p', 'v');
        if ($includePrice) {
            $min = trim((string)($filters['min'] ?? ''));
            if ($min !== '' && is_numeric($min)) {
                $where[] = "{$effectivePriceSql} >= :min_price";
                $params[':min_price'] = (int)$min;
            }

            $max = trim((string)($filters['max'] ?? ''));
            if ($max !== '' && is_numeric($max)) {
                $where[] = "{$effectivePriceSql} <= :max_price";
                $params[':max_price'] = (int)$max;
            }
        }

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

        $brandSlugs = [];
        $brandSlug = trim((string)($filters['brand_slug'] ?? ''));
        if ($brandSlug !== '') {
            $brandSlugs[] = $brandSlug;
        } else {
            $rawBrands = $filters['marca'] ?? [];
            $brandSlugs = is_array($rawBrands) ? $rawBrands : [$rawBrands];
            $brandSlugs = array_values(array_filter(array_map(static fn($value): string => trim((string)$value), $brandSlugs), static fn(string $value): bool => $value !== ''));
        }
        if (!empty($brandSlugs)) {
            $placeholders = [];
            foreach (array_values($brandSlugs) as $index => $slugValue) {
                $key = ':brand_slug_' . $index;
                $placeholders[] = $key;
                $params[$key] = $slugValue;
            }
            $where[] = 'b.slug IN (' . implode(', ', $placeholders) . ')';
        }

        $enforceActiveCategory = (string)($filters['enforce_active_category'] ?? '') === '1';
        if ($enforceActiveCategory) {
            $where[] = 'c.is_active = 1';
        }

        $tagFilters = is_array($filters['tags'] ?? null) ? $filters['tags'] : [];
        foreach ($tagFilters as $groupSlug => $tagSlugs) {
            $groupSlug = trim((string)$groupSlug);
            $slugs = array_values(array_filter(array_map(static fn($value): string => trim((string)$value), is_array($tagSlugs) ? $tagSlugs : [$tagSlugs]), static fn(string $value): bool => $value !== ''));
            if ($groupSlug === '' || $slugs === []) {
                continue;
            }

            $placeholders = [];
            foreach ($slugs as $index => $tagSlug) {
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

    /** @return array{min:int,max:int} */
    public function priceBounds(array $filters): array
    {
        [$where, $params] = $this->buildCatalogWhere($filters, false);
        $whereSql = implode(' AND ', $where);
        $effectivePriceSql = $this->effectivePriceSql('p', 'v');

        $sql = "SELECT
                    MIN(NULLIF({$effectivePriceSql}, 0)) AS min_price,
                    MAX(NULLIF({$effectivePriceSql}, 0)) AS max_price
                FROM products p
                LEFT JOIN categories c ON c.id = p.category_id
                LEFT JOIN categories cp ON cp.id = c.parent_id
                LEFT JOIN brands b ON b.id = p.brand_id
                WHERE {$whereSql}";

        $st = $this->db->prepare($sql);
        foreach ($params as $key => $value) {
            $st->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['min_price' => 0, 'max_price' => 0];

        $min = max(0, (int)($row['min_price'] ?? 0));
        $max = max($min, (int)($row['max_price'] ?? 0));
        return ['min' => $min, 'max' => $max];
    }

    /** @param array<int,array<string,mixed>> $items */
    private function normalizeCategoryLabels(array $items): array
    {
        foreach ($items as &$item) {
            if (!is_array($item)) {
                continue;
            }

            $item['category_name'] = CategoryLabel::sanitize(
                (string)($item['category_name'] ?? ''),
                (string)($item['category_slug'] ?? '')
            );
        }
        unset($item);

        return $items;
    }

    /** @return string Lista de columnas para SELECT en products, con compatibilidad si falta supplier_id. */
    private function selectBaseColumns(): string
    {
        $cols = [
            'id','sku','internal_code','iva_rate','margin_pct','cost_currency_id',
            'ship_weight','ship_height','ship_width','ship_length','slug','name',
            'brand_id','category_id',
        ];
        if ($this->hasColumn('products', 'supplier_id')) {
            $cols[] = 'supplier_id';
        }
        $cols = array_merge($cols, ['sale_mode','price','list_price','cost','is_active']);
        return implode(', ', $cols);
    }

    public function findBySku(string $sku): ?array
    {
        $sku = trim($sku);
        if ($sku === '') return null;

        $st = $this->db->prepare("SELECT " . $this->selectBaseColumns() . " FROM products WHERE sku = ? LIMIT 1");
        $st->execute([$sku]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findByInternalCode(string $code): ?array
    {
        $code = trim($code);
        if ($code === '') return null;

        $st = $this->db->prepare("SELECT " . $this->selectBaseColumns() . " FROM products WHERE internal_code = ? LIMIT 1");
        $st->execute([$code]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findBySkuOrInternal(string $sku, string $internalCode = ''): ?array
    {
        $sku = trim($sku);
        $internalCode = trim($internalCode);
        if ($sku !== '') {
            $r = $this->findBySku($sku);
            if ($r) return $r;
        }
        if ($internalCode !== '') {
            return $this->findByInternalCode($internalCode);
        }
        return null;
    }

    /** @return array<string,mixed>|null */
    public function findQuickAdd(int $productId): ?array
    {
        $productId = (int)$productId;
        if ($productId <= 0) return null;

        $extraCols = '';
        if ($this->hasColumn('products', 'price_wh1_override')) {
            $extraCols .= "                    p.price_wh1_override,\n";
        }
        if ($this->hasColumn('products', 'price_wh2_override')) {
            $extraCols .= "                    p.price_wh2_override,\n";
        }

        $sql = "SELECT
                    p.id,
                    p.name,
                    p.slug,
                    p.sale_mode,
                    p.price,
                    p.list_price,
{$extraCols}
                    p.is_active,
                    COALESCE(NULLIF(p.short_desc, ''), NULLIF(p.long_desc, ''), '') AS short_desc,
                    p.long_desc,
                    b.name AS brand_name,
                    c.name AS category_name,
                    (
                        SELECT i.path
                        FROM product_images i
                        WHERE i.product_id = p.id
                        ORDER BY i.is_cover DESC, i.id ASC
                        LIMIT 1
                    ) AS cover_url
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE p.id = :id
                LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $productId, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function slugExists(string $slug): bool
    {
        $slug = trim($slug);
        if ($slug === '') return false;
        $st = $this->db->prepare("SELECT 1 FROM products WHERE slug=? LIMIT 1");
        $st->execute([$slug]);
        return (bool)$st->fetchColumn();
    }

    public function uniqueSlug(string $baseSlug, string $sku = ''): string
    {
        $baseSlug = trim($baseSlug);
        if ($baseSlug === '') $baseSlug = 'producto';

        if (!$this->slugExists($baseSlug)) return $baseSlug;

        $suffix = trim($sku);
        $suffix = ($suffix !== '') ? preg_replace('/[^a-zA-Z0-9]+/', '-', strtolower($suffix)) : bin2hex(random_bytes(3));
        $suffix = trim($suffix, '-');
        if ($suffix === '') $suffix = bin2hex(random_bytes(3));

        $candidate = $baseSlug . '-' . substr($suffix, 0, 20);
        if (!$this->slugExists($candidate)) return $candidate;

        // Último fallback: aleatorio
        return $baseSlug . '-' . bin2hex(random_bytes(4));
    }

    /** @param array<string,mixed> $fields */
    public function refreshVariantPriceCache(int $productId): void
    {
        $productId = (int)$productId;
        if ($productId <= 0) return;

        // Calcula "Desde" (min price) y "precio lista desde" (min list_price) de variantes activas.
        $st = $this->db->prepare("SELECT MIN(price) AS min_price, MIN(list_price) AS min_list
                                  FROM product_variants
                                  WHERE product_id = ? AND is_active = 1");
        $st->execute([$productId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['min_price'=>null,'min_list'=>null];

        $up = $this->db->prepare("UPDATE products
                                  SET price_from_cache = :p, list_price_from_cache = :l
                                  WHERE id = :id AND sale_mode = 'variants'");
        try {
            $up->execute([
            ':p' => $row['min_price'],
            ':l' => $row['min_list'],
            ':id'=> $productId,
        ]);
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'price_from_cache') === false) throw $e;
        }
    }



/**
 * Inserta un producto y devuelve el ID (para importaciones / rollback).
 * @param array<string,mixed> $d
 */
public function insertGetId(array $d): int
{
    $hasSupplier = $this->hasColumn('products', 'supplier_id');

    $cols = [
        'sku','internal_code','iva_rate','ship_weight','ship_height','ship_width','ship_length',
        'slug','name','brand_id','category_id',
    ];
    if ($hasSupplier) {
        $cols[] = 'supplier_id';
    }
    $cols = array_merge($cols, [
        'sale_mode','short_desc','long_desc','price','list_price','price_updated_at',
        'cost','cost_currency_id','margin_pct','barcode','is_active','meta_title','meta_description'
    ]);

    $placeholders = implode(',', array_fill(0, count($cols), '?'));

    $sql = "INSERT INTO products (" . implode(',', $cols) . ") VALUES (" . $placeholders . ")";
    $st = $this->db->prepare($sql);

    $sku = $d['sku'] ?? null;
    $slug = (string)($d['slug'] ?? '');
    $slug = $this->uniqueSlug($slug, (string)$sku);

    $price = array_key_exists('price', $d) ? $d['price'] : null;
    $listPrice = array_key_exists('list_price', $d) ? $d['list_price'] : null;
    $priceUpdatedAt = (($price !== null && $price !== '') || ($listPrice !== null && $listPrice !== '')) ? gmdate('Y-m-d H:i:s') : null;

    $params = [
        $sku,
        $d['internal_code'] ?? null,
        $d['iva_rate'] ?? null,
        $d['ship_weight'] ?? null,
        $d['ship_height'] ?? null,
        $d['ship_width'] ?? null,
        $d['ship_length'] ?? null,
        $slug,
        $d['name'],
        $d['brand_id'] ?? null,
        $d['category_id'],
    ];
    if ($hasSupplier) {
        $params[] = $d['supplier_id'] ?? null;
    }
    $params = array_merge($params, [
        $d['sale_mode'] ?? 'simple',
        $d['short_desc'] ?? null,
        $d['long_desc'] ?? null,
        ($price !== null && $price !== '' ? (int)$price : null),
        $d['list_price'] ?? null,
        $priceUpdatedAt,
        $d['cost'] ?? null,
        $d['cost_currency_id'] ?? null,
        $d['margin_pct'] ?? null,
        $d['barcode'] ?? null,
        isset($d['is_active']) ? (int)$d['is_active'] : 1,
        $d['meta_title'] ?? null,
        $d['meta_description'] ?? null,
    ]);

    $ok = (bool)$st->execute($params);
    return $ok ? (int)$this->db->lastInsertId() : 0;
}



function updateById(int $id, array $fields): bool
    {
        $id = (int)$id;
        if ($id <= 0) return false;

        $allowed = [
            'name','brand_id','category_id','supplier_id','short_desc','long_desc','price','list_price','cost','cost_currency_id','margin_pct','barcode','is_active','ship_weight','ship_height','ship_width','ship_length',
            'meta_title','meta_description','sale_mode','slug','sku','internal_code','iva_rate','cost_currency_id','margin_pct','price_wh1_override','price_wh2_override'
        ];

        if (!$this->hasColumn('products','supplier_id')) {
            $fields = $fields; // noop
            $allowed = array_values(array_filter($allowed, fn($c) => $c !== 'supplier_id'));
            if (array_key_exists('supplier_id', $fields)) unset($fields['supplier_id']);
        }



if (!$this->hasColumn('products','price_wh1_override')) {
    $allowed = array_values(array_filter($allowed, fn($c) => $c !== 'price_wh1_override'));
}
if (!$this->hasColumn('products','price_wh2_override')) {
    $allowed = array_values(array_filter($allowed, fn($c) => $c !== 'price_wh2_override'));
}

        $set = [];
        $params = [];

        // Registrar fecha de modificación de precio (solo si cambia price/list_price)
        $trackPrice = array_key_exists('price', $fields) || array_key_exists('list_price', $fields);
        $newPrice = array_key_exists('price', $fields) ? $fields['price'] : null;
        $newList  = array_key_exists('list_price', $fields) ? $fields['list_price'] : null;
        if ($trackPrice) {
            // Debe ir antes de asignar price/list_price en el SET para comparar con el valor actual en DB.
            $set[] = "price_updated_at = CASE WHEN (price_updated_at IS NULL AND ((? IS NOT NULL) OR (? IS NOT NULL))) THEN UTC_TIMESTAMP() WHEN ((? IS NOT NULL AND ? <> price) OR (? IS NOT NULL AND ? <> list_price)) THEN UTC_TIMESTAMP() ELSE price_updated_at END";
            $params[] = $newPrice; $params[] = $newList; $params[] = $newPrice; $params[] = $newPrice; $params[] = $newList; $params[] = $newList;
        }

        foreach ($fields as $k=>$v) {
            if (!in_array($k, $allowed, true)) continue;
            $set[] = "{$k} = ?";
            $params[] = $v;
        }

        if (!$set) return true;

        $params[] = $id;
        $sql = "UPDATE products SET " . implode(', ', $set) . " WHERE id = ?";
        $st = $this->db->prepare($sql);
        return (bool)$st->execute($params);
    }

    /** @param array{sku:string,name:string,slug:string,brand_id?:?int,category_id:int,short_desc?:?string,long_desc?:?string,price:int,list_price?:?int,cost?:?int,barcode?:?string,is_active?:int,sale_mode?:string,meta_title?:?string,meta_description?:?string} $d */
    public function insert(array $d): bool
    {
        $sql = "INSERT INTO products
            (sku, internal_code, iva_rate, slug, name, brand_id, category_id, supplier_id, sale_mode, short_desc, long_desc, price, list_price, price_updated_at, cost, barcode, is_active, meta_title, meta_description)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)";

        $st = $this->db->prepare($sql);

        $sku = $d['sku'] ?? null;
        $slug = (string)($d['slug'] ?? '');
        $slug = $this->uniqueSlug($slug, (string)$sku);

        $price = array_key_exists('price', $d) ? $d['price'] : null;
        $listPrice = array_key_exists('list_price', $d) ? $d['list_price'] : null;
        $priceUpdatedAt = (($price !== null && $price !== '') || ($listPrice !== null && $listPrice !== '')) ? gmdate('Y-m-d H:i:s') : null;

        return (bool)$st->execute([
            $sku,
            $d['internal_code'] ?? null,
            $d['iva_rate'] ?? null,
            $slug,
            $d['name'],
            $d['brand_id'] ?? null,
            $d['category_id'],
            $d['sale_mode'] ?? 'simple',
            $d['short_desc'] ?? null,
            $d['long_desc'] ?? null,
            (int)$d['price'],
            $d['list_price'] ?? null,
            $d['cost'] ?? null,
            $d['barcode'] ?? null,
            isset($d['is_active']) ? (int)$d['is_active'] : 1,
            $d['meta_title'] ?? null,
            $d['meta_description'] ?? null,
        ]);
    }

    /** @return array{items:array<int,array<string,mixed>>,total:int,page:int,per_page:int} */
    public function paginate(array $filters): array
    {
        $page = max(1, (int)($filters['page'] ?? 1));
        $perPage = (int)($filters['per_page'] ?? ($filters['perPage'] ?? 12));
        if ($perPage <= 0) $perPage = 12;
        $offset = ($page - 1) * $perPage;

        [$where, $params] = $this->buildCatalogWhere($filters, true);
        $whereSql = implode(' AND ', $where);
        $effectivePriceSql = $this->effectivePriceSql('p', 'v');

        $orderBy = strtolower((string)($filters['order_by'] ?? 'p.created_at'));
        $orderDir = strtoupper((string)($filters['order_dir'] ?? 'DESC'));
        if (!in_array($orderDir, ['ASC', 'DESC'], true)) {
            $orderDir = 'DESC';
        }

        $allowedOrderBy = ['p.created_at', 'sort_price', 'p.name'];
        if ($this->hasColumn('products', 'rating')) {
            $allowedOrderBy[] = 'sort_rating';
        }
        if (!in_array($orderBy, $allowedOrderBy, true)) {
            $orderBy = 'p.created_at';
        }

        $countSql = "SELECT COUNT(DISTINCT p.id)
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN categories cp ON cp.id = c.parent_id
            LEFT JOIN brands b ON b.id = p.brand_id
            WHERE {$whereSql}";

        $countStmt = $this->db->prepare($countSql);
        foreach ($params as $key => $value) {
            $countStmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $countStmt->execute();
        $total = (int)$countStmt->fetchColumn();

        $rankingSql = '';
        $orderSql = "ORDER BY {$orderBy} {$orderDir}";
        $selectParams = $params;
        $q = trim((string)($filters['q'] ?? ''));
        if ($q !== '') {
            $rankingSql = ",
                CASE
                    WHEN (p.name LIKE :rank_name OR p.sku LIKE :rank_sku) THEN 1
                    WHEN (
                        c.name LIKE :rank_category_name
                        OR c.slug LIKE :rank_category_slug
                        OR cp.name LIKE :rank_parent_category_name
                        OR cp.slug LIKE :rank_parent_category_slug
                    ) THEN 2
                    WHEN (b.name LIKE :rank_brand_name OR b.slug LIKE :rank_brand_slug) THEN 3
                    ELSE 4
                END AS search_rank";

            $like = '%' . $q . '%';
            $selectParams[':rank_name'] = $like;
            $selectParams[':rank_sku'] = $like;
            $selectParams[':rank_category_name'] = $like;
            $selectParams[':rank_category_slug'] = $like;
            $selectParams[':rank_parent_category_name'] = $like;
            $selectParams[':rank_parent_category_slug'] = $like;
            $selectParams[':rank_brand_name'] = $like;
            $selectParams[':rank_brand_slug'] = $like;

            $orderSql = "ORDER BY search_rank ASC, p.created_at DESC, p.id DESC";
        }

        $sql = "SELECT
                p.id,
                p.slug,
                p.name,
                p.sale_mode,
                {$effectivePriceSql} AS price,
                CASE
                    WHEN p.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF(v.list_price,0)) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1),
                        p.list_price
                    )
                    ELSE p.list_price
                END AS list_price,
                {$effectivePriceSql} AS sort_price,
                " . ($this->hasColumn('products', 'rating')
                    ? "COALESCE(p.rating, 0) AS sort_rating,\n"
                    : "0 AS sort_rating,\n") . "
                b.name AS brand_name,
                b.slug AS brand_slug,
                c.name AS category_name,
                c.slug AS category_slug,
                (SELECT i.path FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_cover DESC, i.id ASC LIMIT 1) AS cover_url
                {$rankingSql}
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN categories cp ON cp.id = c.parent_id
            LEFT JOIN brands b ON b.id = p.brand_id
            WHERE {$whereSql}
            {$orderSql}
            LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($selectParams as $key => $value) {
            $stmt->bindValue($key, $value, is_int($value) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->bindValue(':limit', $perPage, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        $items = $this->normalizeCategoryLabels($stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        return [
            'items' => $items,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
        ];
    }

    /** @return array<int,array<string,mixed>> */
    public function searchPreview(string $q, int $limit = 6): array
    {
        $q = trim($q);
        $limit = max(1, min(12, (int)$limit));
        if ($q === '' || mb_strlen($q, 'UTF-8') < 2) {
            return [];
        }

        $like = '%' . $q . '%';
        $sql = "SELECT
                p.id,
                p.slug,
                p.name,
                CASE
                    WHEN p.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF(v.price,0)) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1),
                        p.price
                    )
                    ELSE p.price
                END AS price,
                b.name AS brand_name,
                c.name AS category_name,
                (SELECT i.path FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_cover DESC, i.id ASC LIMIT 1) AS cover_url,
                CASE
                    WHEN (p.name LIKE :rank_name OR p.sku LIKE :rank_sku) THEN 1
                    WHEN (
                        c.name LIKE :rank_category_name
                        OR c.slug LIKE :rank_category_slug
                        OR cp.name LIKE :rank_parent_category_name
                        OR cp.slug LIKE :rank_parent_category_slug
                    ) THEN 2
                    WHEN (b.name LIKE :rank_brand_name OR b.slug LIKE :rank_brand_slug) THEN 3
                    ELSE 4
                END AS search_rank
            FROM products p
            LEFT JOIN categories c ON c.id = p.category_id
            LEFT JOIN categories cp ON cp.id = c.parent_id
            LEFT JOIN brands b ON b.id = p.brand_id
            WHERE " . $this->publicVisibilitySql('p') . "
              AND (
                p.name LIKE :q_name
                OR p.sku LIKE :q_sku
                OR c.name LIKE :q_category_name
                OR c.slug LIKE :q_category_slug
                OR cp.name LIKE :q_parent_category_name
                OR cp.slug LIKE :q_parent_category_slug
                OR b.name LIKE :q_brand_name
                OR b.slug LIKE :q_brand_slug
              )
            ORDER BY search_rank ASC, p.created_at DESC, p.id DESC
            LIMIT :lim";

        $st = $this->db->prepare($sql);
        $st->bindValue(':rank_name', $like, PDO::PARAM_STR);
        $st->bindValue(':rank_sku', $like, PDO::PARAM_STR);
        $st->bindValue(':rank_category_name', $like, PDO::PARAM_STR);
        $st->bindValue(':rank_category_slug', $like, PDO::PARAM_STR);
        $st->bindValue(':rank_parent_category_name', $like, PDO::PARAM_STR);
        $st->bindValue(':rank_parent_category_slug', $like, PDO::PARAM_STR);
        $st->bindValue(':rank_brand_name', $like, PDO::PARAM_STR);
        $st->bindValue(':rank_brand_slug', $like, PDO::PARAM_STR);
        $st->bindValue(':q_name', $like, PDO::PARAM_STR);
        $st->bindValue(':q_sku', $like, PDO::PARAM_STR);
        $st->bindValue(':q_category_name', $like, PDO::PARAM_STR);
        $st->bindValue(':q_category_slug', $like, PDO::PARAM_STR);
        $st->bindValue(':q_parent_category_name', $like, PDO::PARAM_STR);
        $st->bindValue(':q_parent_category_slug', $like, PDO::PARAM_STR);
        $st->bindValue(':q_brand_name', $like, PDO::PARAM_STR);
        $st->bindValue(':q_brand_slug', $like, PDO::PARAM_STR);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->execute();

        return $this->normalizeCategoryLabels($st->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }

    /** @return array<int,array<string,mixed>> */
    public function findRelatedFill(int $productId, int $categoryId, ?int $brandId, int $min = 5, int $max = 6): array
    {
        $productId = (int)$productId;
        $categoryId = (int)$categoryId;
        $brandId = $brandId !== null ? (int)$brandId : null;
        $min = max(1, (int)$min);
        $max = max($min, (int)$max);

        $baseSelect = "SELECT
                p.id,
                p.slug,
                p.name,
                p.sale_mode,
                CASE
                    WHEN p.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF(v.price,0)) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1),
                        p.price
                    )
                    ELSE p.price
                END AS price,
                CASE
                    WHEN p.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF(v.list_price,0)) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1),
                        p.list_price
                    )
                    ELSE p.list_price
                END AS list_price,
                b.name AS brand_name,
                b.slug AS brand_slug,
                c.name AS category_name,
                c.slug AS category_slug,
                (SELECT i.path FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_cover DESC, i.id ASC LIMIT 1) AS cover_url
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id";

        $ids = [$productId];
        $related = [];

        $fetch = function (string $whereSql, array $params, int $limit) use ($baseSelect): array {
            if ($limit <= 0) return [];
            $sql = $baseSelect . " WHERE {$whereSql} ORDER BY p.created_at DESC, p.id DESC LIMIT :limit";
            $st = $this->db->prepare($sql);
            foreach ($params as $k => $v) {
                $st->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
            }
            $st->bindValue(':limit', $limit, PDO::PARAM_INT);
            $st->execute();
            return $this->normalizeCategoryLabels($st->fetchAll(PDO::FETCH_ASSOC) ?: []);
        };

        // 1) Prioridad: misma categoria
        if ($categoryId > 0) {
            $remaining = $max - count($related);
            $rows = $fetch(
                $this->publicVisibilitySql('p') . " AND " . $this->inStockVisibilitySql('p') . " AND p.category_id = :category_id AND p.id <> :product_id",
                [':category_id' => $categoryId, ':product_id' => $productId],
                $remaining
            );
            foreach ($rows as $r) {
                $id = (int)($r['id'] ?? 0);
                if ($id <= 0 || in_array($id, $ids, true)) continue;
                $ids[] = $id;
                $related[] = $r;
            }
        }

        // 2) Fallback: misma marca
        if (count($related) < $min && $brandId !== null && $brandId > 0) {
            $remaining = $max - count($related);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = $baseSelect . " WHERE " . $this->publicVisibilitySql('p') . " AND " . $this->inStockVisibilitySql('p') . " AND p.brand_id = ? AND p.id NOT IN ({$placeholders}) ORDER BY p.created_at DESC, p.id DESC LIMIT ?";
            $st = $this->db->prepare($sql);
            $bind = [$brandId, ...$ids, $remaining];
            foreach ($bind as $i => $v) {
                $param = $i + 1;
                $type = is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $st->bindValue($param, $v, $type);
            }
            $st->execute();
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $r) {
                $id = (int)($r['id'] ?? 0);
                if ($id <= 0 || in_array($id, $ids, true)) continue;
                $ids[] = $id;
                $related[] = $r;
            }
        }

        // 3) Fallback global
        if (count($related) < $min) {
            $remaining = $max - count($related);
            $placeholders = implode(',', array_fill(0, count($ids), '?'));
            $sql = $baseSelect . " WHERE " . $this->publicVisibilitySql('p') . " AND " . $this->inStockVisibilitySql('p') . " AND p.id NOT IN ({$placeholders}) ORDER BY p.created_at DESC, p.id DESC LIMIT ?";
            $st = $this->db->prepare($sql);
            $bind = [...$ids, $remaining];
            foreach ($bind as $i => $v) {
                $param = $i + 1;
                $type = is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR;
                $st->bindValue($param, $v, $type);
            }
            $st->execute();
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $r) {
                $id = (int)($r['id'] ?? 0);
                if ($id <= 0 || in_array($id, $ids, true)) continue;
                $ids[] = $id;
                $related[] = $r;
            }
        }

        return array_slice($related, 0, $max);
    }

    /** @return array<int,array<string,mixed>> */
    public function findRelatedForCart(array $productIds, int $min = 5, int $max = 6): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
        $min = max(1, (int)$min);
        $max = max($min, (int)$max);

        $baseSelect = "SELECT
                p.id,
                p.slug,
                p.name,
                p.sale_mode,
                CASE
                    WHEN p.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF(v.price,0)) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1),
                        p.price
                    )
                    ELSE p.price
                END AS price,
                CASE
                    WHEN p.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF(v.list_price,0)) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1),
                        p.list_price
                    )
                    ELSE p.list_price
                END AS list_price,
                b.name AS brand_name,
                b.slug AS brand_slug,
                c.name AS category_name,
                c.slug AS category_slug,
                (SELECT i.path FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_cover DESC, i.id ASC LIMIT 1) AS cover_url
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id";

        $related = [];
        $excludedIds = $productIds;

        $appendRows = function (array $rows) use (&$related, &$excludedIds, $max): void {
            foreach ($rows as $row) {
                $id = (int)($row['id'] ?? 0);
                if ($id <= 0 || in_array($id, $excludedIds, true)) {
                    continue;
                }

                $excludedIds[] = $id;
                $related[] = $row;

                if (count($related) >= $max) {
                    return;
                }
            }
        };

        if ($productIds !== []) {
            $seedPlaceholders = implode(',', array_fill(0, count($productIds), '?'));
            $seedSql = "SELECT DISTINCT category_id, brand_id
                        FROM products
                        WHERE is_active = 1 AND id IN ({$seedPlaceholders})";
            $seedSt = $this->db->prepare($seedSql);
            foreach ($productIds as $index => $id) {
                $seedSt->bindValue($index + 1, $id, PDO::PARAM_INT);
            }
            $seedSt->execute();
            $seedRows = $seedSt->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $categoryIds = [];
            $brandIds = [];
            foreach ($seedRows as $seed) {
                $categoryId = (int)($seed['category_id'] ?? 0);
                $brandId = (int)($seed['brand_id'] ?? 0);
                if ($categoryId > 0) {
                    $categoryIds[] = $categoryId;
                }
                if ($brandId > 0) {
                    $brandIds[] = $brandId;
                }
            }
            $categoryIds = array_values(array_unique($categoryIds));
            $brandIds = array_values(array_unique($brandIds));

            if ($categoryIds !== []) {
                $remaining = $max - count($related);
                $categoryPlaceholders = implode(',', array_fill(0, count($categoryIds), '?'));
                $excludePlaceholders = implode(',', array_fill(0, count($excludedIds), '?'));
                $sql = $baseSelect . " WHERE " . $this->publicVisibilitySql('p') . " AND " . $this->inStockVisibilitySql('p') . "
                    AND p.category_id IN ({$categoryPlaceholders})
                    AND p.id NOT IN ({$excludePlaceholders})
                    ORDER BY p.created_at DESC, p.id DESC
                    LIMIT ?";
                $st = $this->db->prepare($sql);
                $bind = [...$categoryIds, ...$excludedIds, $remaining];
                foreach ($bind as $index => $value) {
                    $st->bindValue($index + 1, $value, PDO::PARAM_INT);
                }
                $st->execute();
                $appendRows($this->normalizeCategoryLabels($st->fetchAll(PDO::FETCH_ASSOC) ?: []));
            }

            if (count($related) < $min && $brandIds !== []) {
                $remaining = $max - count($related);
                $brandPlaceholders = implode(',', array_fill(0, count($brandIds), '?'));
                $excludePlaceholders = implode(',', array_fill(0, count($excludedIds), '?'));
                $sql = $baseSelect . " WHERE " . $this->publicVisibilitySql('p') . " AND " . $this->inStockVisibilitySql('p') . "
                    AND p.brand_id IN ({$brandPlaceholders})
                    AND p.id NOT IN ({$excludePlaceholders})
                    ORDER BY p.created_at DESC, p.id DESC
                    LIMIT ?";
                $st = $this->db->prepare($sql);
                $bind = [...$brandIds, ...$excludedIds, $remaining];
                foreach ($bind as $index => $value) {
                    $st->bindValue($index + 1, $value, PDO::PARAM_INT);
                }
                $st->execute();
                $appendRows($this->normalizeCategoryLabels($st->fetchAll(PDO::FETCH_ASSOC) ?: []));
            }
        }

        if (count($related) < $min) {
            $remaining = $max - count($related);
            if ($excludedIds !== []) {
                $excludePlaceholders = implode(',', array_fill(0, count($excludedIds), '?'));
                $sql = $baseSelect . " WHERE " . $this->publicVisibilitySql('p') . " AND " . $this->inStockVisibilitySql('p') . "
                    AND p.id NOT IN ({$excludePlaceholders})
                    ORDER BY p.created_at DESC, p.id DESC
                    LIMIT ?";
                $st = $this->db->prepare($sql);
                $bind = [...$excludedIds, $remaining];
                foreach ($bind as $index => $value) {
                    $st->bindValue($index + 1, $value, PDO::PARAM_INT);
                }
            } else {
                $sql = $baseSelect . " WHERE " . $this->publicVisibilitySql('p') . " AND " . $this->inStockVisibilitySql('p') . "
                    ORDER BY p.created_at DESC, p.id DESC
                    LIMIT ?";
                $st = $this->db->prepare($sql);
                $st->bindValue(1, $remaining, PDO::PARAM_INT);
            }
            $st->execute();
            $appendRows($this->normalizeCategoryLabels($st->fetchAll(PDO::FETCH_ASSOC) ?: []));
        }

        return array_slice($related, 0, $max);
    }

    /**
     * @param array<int,int> $productIds
     * @return array<int,array<string,mixed>>
     */
    public function findVisibleByIdsOrdered(array $productIds, int $limit = 6): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval', $productIds), static fn (int $id): bool => $id > 0)));
        $limit = max(1, (int)$limit);

        if ($productIds === []) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($productIds), '?'));
        $orderCaseParts = [];
        $bind = [];

        foreach ($productIds as $index => $id) {
            $orderCaseParts[] = 'WHEN ? THEN ' . $index;
            $bind[] = $id;
        }

        $baseSelect = "SELECT
                p.id,
                p.slug,
                p.name,
                p.sale_mode,
                CASE
                    WHEN p.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF(v.price,0)) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1),
                        p.price
                    )
                    ELSE p.price
                END AS price,
                CASE
                    WHEN p.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF(v.list_price,0)) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1),
                        p.list_price
                    )
                    ELSE p.list_price
                END AS list_price,
                b.name AS brand_name,
                b.slug AS brand_slug,
                c.name AS category_name,
                c.slug AS category_slug,
                (SELECT i.path FROM product_images i WHERE i.product_id = p.id ORDER BY i.is_cover DESC, i.id ASC LIMIT 1) AS cover_url
            FROM products p
            LEFT JOIN brands b ON b.id = p.brand_id
            LEFT JOIN categories c ON c.id = p.category_id";

        $sql = $baseSelect . " WHERE " . $this->publicVisibilitySql('p') . " AND " . $this->inStockVisibilitySql('p') . "
            AND p.id IN ({$placeholders})
            ORDER BY CASE p.id " . implode(' ', $orderCaseParts) . " ELSE " . count($productIds) . " END
            LIMIT ?";

        $st = $this->db->prepare($sql);
        $paramIndex = 1;
        foreach ($productIds as $id) {
            $st->bindValue($paramIndex++, $id, PDO::PARAM_INT);
        }
        foreach ($bind as $id) {
            $st->bindValue($paramIndex++, $id, PDO::PARAM_INT);
        }
        $st->bindValue($paramIndex, $limit, PDO::PARAM_INT);
        $st->execute();

        return $this->normalizeCategoryLabels($st->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }
}
