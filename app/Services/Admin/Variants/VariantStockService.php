<?php
namespace App\Services\Admin\Variants;

use App\Repositories\Catalog\VariantInventoryRepository;
use PDO;

final class VariantStockService
{
    public function __construct(private PDO $db)
    {
    }

    /** @return array{stock:int,low_stock_threshold:int}|null */
    public function getSnapshot(int $variantId): ?array
    {
        $repo = new VariantInventoryRepository($this->db);
        return $repo->find($variantId);
    }

    public function saveStock(int $variantId, ?int $stock, ?int $lowStockThreshold): void
    {
        $repo = new VariantInventoryRepository($this->db);
        $repo->upsert(
            $variantId,
            (int)($stock ?? 0),
            (int)($lowStockThreshold ?? 3)
        );
    }
}

