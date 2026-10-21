<?php
namespace App\Services\Catalog;

use App\Services\Admin\ProductWizardService;
use PDO;

final class ProductPublicationSyncService
{
    public function __construct(private PDO $db)
    {
    }

    public function syncProduct(int $productId): void
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return;
        }

        $st = $this->db->prepare('SELECT is_active FROM products WHERE id = :id LIMIT 1');
        $st->execute([':id' => $productId]);
        $current = $st->fetchColumn();
        if ($current === false) {
            return;
        }

        $isActive = (int)$current === 1;
        if (!$isActive) {
            return;
        }

        $status = (new ProductWizardService($this->db))->evaluate($productId);
        $publishable = (bool)($status['publishable'] ?? false);
        if ($publishable) {
            return;
        }

        $up = $this->db->prepare('UPDATE products SET is_active = 0 WHERE id = :id AND is_active = 1');
        $up->execute([':id' => $productId]);
    }

    /**
     * @return array{processed:int,activated:int,deactivated:int}
     */
    public function reconcileAll(bool $activatePublishable = false): array
    {
        $rows = $this->db->query('SELECT id, is_active FROM products')->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $processed = 0;
        $activated = 0;
        $deactivated = 0;
        $wizard = new ProductWizardService($this->db);

        foreach ($rows as $row) {
            $productId = (int)($row['id'] ?? 0);
            if ($productId <= 0) {
                continue;
            }

            $processed++;
            $isActive = (int)($row['is_active'] ?? 0) === 1;
            $publishable = (bool)($wizard->evaluate($productId)['publishable'] ?? false);

            if ($isActive && !$publishable) {
                $up = $this->db->prepare('UPDATE products SET is_active = 0 WHERE id = :id AND is_active = 1');
                $up->execute([':id' => $productId]);
                $deactivated += (int)$up->rowCount();
                continue;
            }

            if ($activatePublishable && !$isActive && $publishable) {
                $up = $this->db->prepare('UPDATE products SET is_active = 1 WHERE id = :id AND is_active = 0');
                $up->execute([':id' => $productId]);
                $activated += (int)$up->rowCount();
            }
        }

        return [
            'processed' => $processed,
            'activated' => $activated,
            'deactivated' => $deactivated,
        ];
    }

    public function syncByVariant(int $variantId): void
    {
        $variantId = (int)$variantId;
        if ($variantId <= 0) {
            return;
        }

        $st = $this->db->prepare('SELECT product_id FROM product_variants WHERE id = :id LIMIT 1');
        $st->execute([':id' => $variantId]);
        $productId = (int)($st->fetchColumn() ?: 0);
        if ($productId > 0) {
            $this->syncProduct($productId);
        }
    }
}
