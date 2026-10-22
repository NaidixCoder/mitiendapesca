<?php
declare(strict_types=1);
namespace App\Services\Catalog;

use App\Repositories\Settings\AppSettingsRepository;
use PDO;

/**
 * Política comercial B1 de lectura PÚBLICA exclusivamente.
 * OFF: p.is_active. ON: p.is_active y su categoría/ancestros elegibles.
 * Una sola lectura de settings y una jerarquía memoizada por instancia;
 * nunca filtrar tras LIMIT/OFFSET ni modificar estados comerciales.
 */
final class ProductPublicVisibilityService
{
    private ?bool $hide = null;
    /** @var array<int,bool>|null */
    private ?array $categoryIds = null;

    public function __construct(private PDO $db) {}

    public function hidesHiddenCategoryProducts(): bool
    {
        return $this->hide ??= (new AppSettingsRepository($this->db))
            ->get('catalog_hide_inactive_category_products', '0') === '1';
    }

    /** @return array<int,bool> */
    public function visibleCategoryIds(): array
    {
        return $this->categoryIds ??= (new CategoryPublicVisibilityService($this->db))->visibleIdSet();
    }

    /**
     * Conjunto de subárbol para landings PÚBLICAS, aun con hijos inactivos.
     * B1 ON los filtra luego por predicate(); OFF los conserva vendibles.
     * Una consulta de categorías, recorrido BFS sin recursión ni N+1.
     * @return list<int>
     */
    public function descendantCategoryIds(int $rootId): array
    {
        if ($rootId <= 0) return [];
        $children = [];
        foreach ($this->db->query('SELECT id,parent_id FROM categories')->fetchAll(PDO::FETCH_ASSOC) ?: [] as $row) {
            $parent = $row['parent_id'] === null ? 0 : (int)$row['parent_id'];
            $children[$parent][] = (int)$row['id'];
        }
        $queue = [$rootId];
        $seen = [];
        for ($i = 0; $i < count($queue); $i++) {
            $id = $queue[$i];
            if ($id <= 0 || isset($seen[$id])) continue;
            $seen[$id] = true;
            foreach ($children[$id] ?? [] as $childId) {
                if (!isset($seen[$childId])) $queue[] = $childId;
            }
        }
        return array_keys($seen);
    }

    public function isCategoryPubliclyVisible(int $categoryId): bool
    {
        return $categoryId > 0 && isset($this->visibleCategoryIds()[$categoryId]);
    }

    /** p must expose category_id; creates no joins nor per-product queries. */
    public function predicate(string $alias = 'p'): string
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/i', $alias)) {
            throw new \InvalidArgumentException('Alias SQL público inválido.');
        }
        $where = "{$alias}.is_active = 1";
        if (!$this->hidesHiddenCategoryProducts()) return $where;
        $ids = array_keys($this->visibleCategoryIds());
        return $ids === []
            ? "({$where} AND 1=0)"
            : "({$where} AND {$alias}.category_id IN (".implode(',',array_map('intval',$ids))."))";
    }

    public function permitsProduct(int $categoryId, bool $active = true): bool
    {
        return $active && (!$this->hidesHiddenCategoryProducts() || $this->isCategoryPubliclyVisible($categoryId));
    }

    /** Query única en batch. No sustituye los selectores del Carrito. */
    public function eligibleIdsAmong(array $productIds): array
    {
        $productIds = array_values(array_unique(array_filter(array_map('intval',$productIds),
            static fn(int $v): bool => $v > 0)));
        if ($productIds === []) return [];
        $sql = 'SELECT p.id FROM products p WHERE '.$this->predicate('p')
            .' AND p.id IN ('.implode(',',array_fill(0,count($productIds),'?')).')';
        $st = $this->db->prepare($sql);
        $st->execute($productIds);
        return array_fill_keys(array_map('intval',$st->fetchAll(PDO::FETCH_COLUMN) ?: []),true);
    }

    /** Evita que un producto público apunte a categoría landing 404. */
    public function sanitizeCategoryLink(array $product): array
    {
        if (!$this->isCategoryPubliclyVisible((int)($product['category_id'] ?? 0))) {
            $product['category_slug'] = '';
            $product['category_name'] = '';
        }
        return $product;
    }
}
