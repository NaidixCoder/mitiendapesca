<?php
namespace App\Services\Catalog;

use App\Repositories\Catalog\ProductRepository;

/**
 * Orquesta la paginación y respeta per_page/orden.
 */
final class ProductService
{
    public function __construct(private ProductRepository $products) {}

    /** @return array<int,array{value:string,label:string}> */
    public function sortOptions(): array
    {
        return [
            ['value' => 'relevancia', 'label' => 'Relevancia'],
            ['value' => 'precio_asc', 'label' => 'Menor precio'],
            ['value' => 'precio_desc', 'label' => 'Mayor precio'],
            ['value' => 'novedades', 'label' => 'Novedades'],
        ];
    }

    public function normalizeOrden(string $orden): string
    {
        $allowed = array_column($this->sortOptions(), 'value');
        return in_array($orden, $allowed, true) ? $orden : 'relevancia';
    }

    public function currentSortLabel(string $orden): string
    {
        $normalized = $this->normalizeOrden($orden);
        foreach ($this->sortOptions() as $option) {
            if ($normalized === (string)$option['value']) {
                return (string)$option['label'];
            }
        }
        return 'Relevancia';
    }

    /** @return array{min:int,max:int} */
    public function priceBounds(array $filters): array
    {
        unset($filters['min'], $filters['max'], $filters['page'], $filters['per_page'], $filters['perPage']);
        return $this->products->priceBounds($filters);
    }

    /** @return array{items:array,total:int,page:int,per_page:int} */
    public function paginate(array $filters): array
    {
        $filters['orden'] = $this->normalizeOrden((string)($filters['orden'] ?? 'relevancia'));

        // asegurar per_page consistente
        if (isset($filters['perPage']) && !isset($filters['per_page'])) {
            $filters['per_page'] = (int)$filters['perPage'];
        }

        // traducir 'orden' a order_by/order_dir si el repo lo soporta
        if (!isset($filters['order_by']) || !isset($filters['order_dir'])) {
            [$by,$dir] = $this->mapOrdenToSql($filters['orden'] ?? 'relevancia');
            $filters['order_by']  = $filters['order_by']  ?? $by;
            $filters['order_dir'] = $filters['order_dir'] ?? $dir;
        }

        $pager = $this->products->paginate($filters);

        $items    = $pager['items']      ?? [];
        $total    = (int)($pager['total'] ?? 0);
        $page     = (int)($pager['page']  ?? ($filters['page'] ?? 1));
        $per_page = (int)($pager['per_page'] ?? ($filters['per_page'] ?? 12));

        return compact('items','total','page','per_page');
    }

    /**
     * @param array<int,array<string,mixed>> $items
     * @return array<int,array<string,mixed>>
     */
    public function buildCardModels(array $items): array
    {
        return (new ProductCardViewModelService())->buildMany($items);
    }

    private function mapOrdenToSql(string $orden): array
    {
        return match ($orden) {
            'precio_asc'     => ['sort_price', 'ASC'],
            'precio_desc'    => ['sort_price', 'DESC'],
            'novedades'      => ['p.created_at', 'DESC'],
            'mejor_valorado' => ['sort_rating', 'DESC'],
            default          => ['p.created_at', 'DESC'],
        };
    }
}
