<?php
namespace App\Repositories\Catalog;

use App\Services\Catalog\ProductPublicationSyncService;
use PDO;

final class VariantInventoryRepository
{
    public function __construct(private PDO $db) {}

    /** @return array{stock:int,low_stock_threshold:int}|null */
    public function find(int $variantId): ?array
    {
        if ($variantId <= 0) return null;

        $st = $this->db->prepare("SELECT stock, low_stock_threshold
                                  FROM inventory_variants
                                  WHERE variant_id = ?
                                  LIMIT 1");
        $st->execute([$variantId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        if (!$row) return null;

        return [
            'stock' => (int)($row['stock'] ?? 0),
            'low_stock_threshold' => (int)($row['low_stock_threshold'] ?? 0),
        ];
    }

    public function upsert(int $variantId, int $stock, int $lowStockThreshold): void
    {
        if ($variantId <= 0) {
            throw new \RuntimeException('Variante invalida para inventario.');
        }

        $stock = max(0, (int)$stock);
        $lowStockThreshold = max(0, (int)$lowStockThreshold);

        $st = $this->db->prepare(
            "INSERT INTO inventory_variants (variant_id, stock, low_stock_threshold)
             VALUES (:variant_id, :stock, :low_stock_threshold)
             ON DUPLICATE KEY UPDATE stock = VALUES(stock), low_stock_threshold = VALUES(low_stock_threshold)"
        );
        $st->execute([
            ':variant_id' => $variantId,
            ':stock' => $stock,
            ':low_stock_threshold' => $lowStockThreshold,
        ]);

        (new ProductPublicationSyncService($this->db))->syncByVariant($variantId);
    }
}

