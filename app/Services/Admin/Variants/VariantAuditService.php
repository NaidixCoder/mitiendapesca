<?php
namespace App\Services\Admin\Variants;

use PDO;
use Throwable;

final class VariantAuditService
{
    public function __construct(private PDO $db) {}

    public function snapshotVariant(int $productId, int $variantId): array
    {
        $variant = [];
        try {
            $pricing = (new VariantPricingService($this->db))->getSnapshot($variantId);
            if (is_array($pricing) && (int)($pricing['product_id'] ?? 0) === $productId) {
                $variant['price'] = $pricing['price'] ?? null;
                $variant['list_price'] = $pricing['list_price'] ?? null;
                $variant['cost'] = $pricing['cost'] ?? null;
                $variant['margin_pct'] = $pricing['margin_pct'] ?? null;
            }

            $st = $this->db->prepare('SELECT sku, is_active FROM product_variants WHERE id=:id AND product_id=:pid LIMIT 1');
            $st->execute([':id' => $variantId, ':pid' => $productId]);
            $meta = $st->fetch(PDO::FETCH_ASSOC) ?: [];
            if (!empty($meta)) {
                $variant['sku'] = (string)($meta['sku'] ?? '');
                $variant['is_active'] = (int)($meta['is_active'] ?? 0);
            }
        } catch (Throwable $_) {
            $variant = [];
        }

        $inventory = ['stock' => null, 'low_stock_threshold' => null];
        try {
            $stock = (new VariantStockService($this->db))->getSnapshot($variantId);
            if (is_array($stock)) {
                $inventory = [
                    'stock' => (int)($stock['stock'] ?? 0),
                    'low_stock_threshold' => (int)($stock['low_stock_threshold'] ?? 0),
                ];
            }
        } catch (Throwable $_) {
        }

        return ['variant' => $variant, 'inventory' => $inventory];
    }

    public function logVariantSave(int $productId, int $variantId, ?array $before, ?array $after): void
    {
        if (!$this->isAuditAvailable() || !is_array($before) || !is_array($after)) {
            return;
        }

        $changes = [];
        foreach (['sku', 'price', 'list_price', 'cost', 'margin_pct', 'is_active'] as $field) {
            $b = $before['variant'][$field] ?? null;
            $a = $after['variant'][$field] ?? null;
            if ((string)$b !== (string)$a) {
                $changes[$field] = ['before' => $b, 'after' => $a];
            }
        }

        if (!empty($changes)) {
            $priceChanges = [];
            $otherChanges = [];
            foreach ($changes as $field => $change) {
                $f = strtolower((string)$field);
                $isPrice = (strpos($f, 'price') !== false)
                    || (strpos($f, 'precio') !== false)
                    || (strpos($f, 'cost') !== false)
                    || (strpos($f, 'margin') !== false)
                    || (strpos($f, 'costo') !== false);
                if ($isPrice) {
                    $priceChanges[$field] = $change;
                } else {
                    $otherChanges[$field] = $change;
                }
            }

            if (!empty($priceChanges)) {
                \App\Services\Audit\Audit::log('price_updated', 'product', $productId, [
                    'tab' => 'variantes',
                    'variant_id' => $variantId,
                    'changes' => $priceChanges,
                ]);
            }

            if (!empty($otherChanges)) {
                \App\Services\Audit\Audit::log('product_updated', 'product', $productId, [
                    'tab' => 'variantes',
                    'variant_id' => $variantId,
                    'changes' => $otherChanges,
                ]);
            }
        }

        $bStock = $before['inventory']['stock'] ?? null;
        $aStock = $after['inventory']['stock'] ?? null;
        $bLow = $before['inventory']['low_stock_threshold'] ?? null;
        $aLow = $after['inventory']['low_stock_threshold'] ?? null;
        $invChanges = [];
        if ((string)$bStock !== (string)$aStock) {
            $invChanges['stock'] = ['before' => $bStock, 'after' => $aStock];
        }
        if ((string)$bLow !== (string)$aLow) {
            $invChanges['low_stock_threshold'] = ['before' => $bLow, 'after' => $aLow];
        }

        if (!empty($invChanges)) {
            \App\Services\Audit\Audit::log('stock_updated', 'product', $productId, [
                'tab' => 'variantes',
                'variant_id' => $variantId,
                'changes' => $invChanges,
            ]);
        }
    }

    public function logVariantToggle(int $productId, int $variantId, ?array $before, ?array $after): void
    {
        if (!$this->isAuditAvailable()) {
            return;
        }

        $b = is_array($before) ? ($before['variant']['is_active'] ?? null) : null;
        $a = is_array($after) ? ($after['variant']['is_active'] ?? null) : null;
        if ((string)$b === (string)$a) {
            return;
        }

        \App\Services\Audit\Audit::log('product_updated', 'product', $productId, [
            'tab' => 'variantes',
            'variant_id' => $variantId,
            'changes' => ['is_active' => ['before' => $b, 'after' => $a]],
        ]);
    }

    public function logVariantDeletePaused(int $productId, int $variantId, ?array $before, ?array $after): void
    {
        if (!$this->isAuditAvailable()) {
            return;
        }

        \App\Services\Audit\Audit::log('product_updated', 'product', $productId, [
            'tab' => 'variantes',
            'variant_id' => $variantId,
            'result' => 'paused',
            'before' => $before,
            'after' => $after,
        ]);
    }

    public function logVariantDeleteDeleted(int $productId, int $variantId, ?array $before): void
    {
        if (!$this->isAuditAvailable()) {
            return;
        }

        \App\Services\Audit\Audit::log('product_updated', 'product', $productId, [
            'tab' => 'variantes',
            'variant_id' => $variantId,
            'result' => 'deleted',
            'before' => $before,
        ]);
    }

    public function logCleanupObsolete(int $productId, string $mode, int $total, int $deleted, int $paused): void
    {
        if (!$this->isAuditAvailable()) {
            return;
        }

        \App\Services\Audit\Audit::log('product_updated', 'product', $productId, [
            'tab' => 'variantes',
            'result' => 'cleanup_obsolete',
            'mode' => $mode,
            'total' => $total,
            'deleted' => $deleted,
            'paused' => $paused,
        ]);
    }

    public function logDeleteIncomplete(int $productId, int $deleted, int $paused): void
    {
        if (!$this->isAuditAvailable()) {
            return;
        }

        \App\Services\Audit\Audit::log('product_updated', 'product', $productId, [
            'tab' => 'variantes',
            'result' => 'delete_incomplete',
            'deleted' => $deleted,
            'paused' => $paused,
        ]);
    }

    private function isAuditAvailable(): bool
    {
        return class_exists('\\App\\Services\\Audit\\Audit\\Audit');
    }
}



