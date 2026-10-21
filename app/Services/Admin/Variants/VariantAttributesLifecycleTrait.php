<?php
namespace App\Services\Admin\Variants;

use App\Services\Catalog\ProductPublicationSyncService;
use PDO;
use Throwable;

trait VariantAttributesLifecycleTrait
{
    private function toggle(int $productId, int $variantId, int $active): void
    {
        $st = $this->db->prepare("UPDATE product_variants SET is_active=:a WHERE id=:id AND product_id=:pid");
        $st->execute([':a' => $active, ':id' => $variantId, ':pid' => $productId]);

        $this->refreshProductPriceCache($productId);
        (new ProductPublicationSyncService($this->db))->syncProduct($productId);
    }

    private function deleteVariant(int $productId, int $variantId): array
    {
        $st = $this->db->prepare("SELECT id FROM product_variants WHERE id=? AND product_id=? LIMIT 1");
        $st->execute([$variantId, $productId]);
        if (!$st->fetchColumn()) {
            throw new \RuntimeException('Variante no encontrada.');
        }

        if ($this->variantHasOrders($variantId)) {
            $this->toggle($productId, $variantId, 0);
            return ['action' => 'paused'];
        }

        $alreadyInTx = $this->db->inTransaction();
        if (!$alreadyInTx) {
            $this->db->beginTransaction();
        }
        $clearedConfig = false;
        try {
            $this->db->prepare("DELETE FROM cart_items WHERE variant_id = ?")->execute([$variantId]);
            $this->db->prepare("DELETE FROM product_variants WHERE id = ? AND product_id = ?")->execute([$variantId, $productId]);

            $this->syncSelectionToExistingVariants($productId);
            $this->clearSelectionIfNoVariants($productId);
            $clearedConfig = $this->clearAttributesIfNoVariants($productId);

            if (!$alreadyInTx) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if (!$alreadyInTx) {
                $this->db->rollBack();
            }
            throw $e;
        }

        (new ProductPublicationSyncService($this->db))->syncProduct($productId);
        return ['action' => 'deleted', 'cleared_config' => $clearedConfig];
    }

    private function clearAttributesIfNoVariants(int $productId): bool
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
        $st->execute([$productId]);
        $cnt = (int)($st->fetchColumn() ?: 0);
        if ($cnt > 0) {
            return false;
        }

        $this->db->prepare("DELETE FROM product_attribute_values WHERE product_id = ?")->execute([$productId]);
        $this->db->prepare("DELETE FROM product_attributes WHERE product_id = ?")->execute([$productId]);
        return true;
    }

    private function variantHasOrders(int $variantId): bool
    {
        $st = $this->db->prepare("SELECT 1 FROM order_items WHERE variant_id = ? LIMIT 1");
        $st->execute([$variantId]);
        return (bool)$st->fetchColumn();
    }

    private function ordersCountByVariant(int $productId): array
    {
        $st = $this->db->prepare("SELECT variant_id, COUNT(*) AS c FROM order_items WHERE product_id = ? AND variant_id IS NOT NULL GROUP BY variant_id");
        $st->execute([$productId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $map = [];
        foreach ($rows as $r) {
            $map[(int)($r['variant_id'] ?? 0)] = (int)($r['c'] ?? 0);
        }
        return $map;
    }

    private function deduplicateVariants(int $productId): void
    {
        $st = $this->db->prepare("SELECT id, is_active FROM product_variants WHERE product_id = ? ORDER BY id ASC");
        $st->execute([$productId]);
        $vars = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        if (count($vars) <= 1) {
            return;
        }

        $vv = $this->db->prepare("SELECT pvv.variant_id, pvv.attribute_id, pvv.value_id
                                  FROM product_variant_values pvv
                                  JOIN product_variants pv ON pv.id = pvv.variant_id
                                  WHERE pv.product_id = ?");
        $vv->execute([$productId]);
        $rows = $vv->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $mapByVariant = [];
        foreach ($vars as $v) {
            $mapByVariant[(int)$v['id']] = [];
        }
        foreach ($rows as $r) {
            $vid = (int)($r['variant_id'] ?? 0);
            $aid = (int)($r['attribute_id'] ?? 0);
            $val = (int)($r['value_id'] ?? 0);
            if ($vid && $aid && $val) {
                $mapByVariant[$vid][$aid] = $val;
            }
        }

        $groups = [];
        foreach ($mapByVariant as $vid => $m) {
            $sig = $this->signature($m);
            $groups[$sig] ??= [];
            $groups[$sig][] = (int)$vid;
        }

        $orders = $this->ordersCountByVariant($productId);
        $stStock = $this->db->prepare("SELECT variant_id, stock FROM inventory_variants WHERE variant_id IN (SELECT id FROM product_variants WHERE product_id = ?)");
        $stStock->execute([$productId]);
        $stockRows = $stStock->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $stockMap = [];
        foreach ($stockRows as $r) {
            $stockMap[(int)($r['variant_id'] ?? 0)] = (int)($r['stock'] ?? 0);
        }

        $alreadyInTx = $this->db->inTransaction();
        if (!$alreadyInTx) {
            $this->db->beginTransaction();
        }
        try {
            foreach ($groups as $ids) {
                if (count($ids) <= 1) {
                    continue;
                }

                $meta = [];
                foreach ($ids as $vid) {
                    $meta[] = [
                        'id' => (int)$vid,
                        'orders' => (int)($orders[(int)$vid] ?? 0),
                        'active' => (int)($this->findVariantActive($vars, (int)$vid) ? 1 : 0),
                    ];
                }

                usort($meta, function ($a, $b) {
                    if ($a['orders'] === 0 && $b['orders'] > 0) {
                        return -1;
                    }
                    if ($b['orders'] === 0 && $a['orders'] > 0) {
                        return 1;
                    }
                    if ($a['active'] !== $b['active']) {
                        return $b['active'] <=> $a['active'];
                    }
                    return $a['id'] <=> $b['id'];
                });

                $keeperId = (int)$meta[0]['id'];
                $sumStock = 0;
                foreach ($ids as $vid) {
                    if ((int)$vid === $keeperId) {
                        continue;
                    }
                    $sumStock += (int)($stockMap[(int)$vid] ?? 0);
                }

                if ($sumStock > 0) {
                    $current = (int)($stockMap[$keeperId] ?? 0);
                    $newStock = $current + $sumStock;
                    $up = $this->db->prepare("INSERT INTO inventory_variants (variant_id, stock, low_stock_threshold) VALUES (:v,:s,3) ON DUPLICATE KEY UPDATE stock=VALUES(stock)");
                    $up->execute([':v' => $keeperId, ':s' => $newStock]);
                    $stockMap[$keeperId] = $newStock;
                }

                foreach ($ids as $vid) {
                    $vid = (int)$vid;
                    if ($vid === $keeperId) {
                        continue;
                    }

                    $has = (int)($orders[$vid] ?? 0) > 0;
                    if ($has) {
                        $this->toggle($productId, $vid, 0);
                        $this->db->prepare("UPDATE inventory_variants SET stock=0 WHERE variant_id = ?")->execute([$vid]);
                        continue;
                    }

                    $this->db->prepare("DELETE FROM cart_items WHERE variant_id = ?")->execute([$vid]);
                    $this->db->prepare("DELETE FROM product_variants WHERE id = ? AND product_id = ?")->execute([$vid, $productId]);
                }
            }
            if (!$alreadyInTx) {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if (!$alreadyInTx) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function findVariantActive(array $variants, int $variantId): bool
    {
        foreach ($variants as $v) {
            if ((int)($v['id'] ?? 0) === $variantId) {
                return (int)($v['is_active'] ?? 1) === 1;
            }
        }
        return false;
    }

    private function refreshVariantTitles(int $productId, ?array $onlyVariantIds = null): void
    {
        $sql = "SELECT pv.id AS variant_id, a.name AS attribute_name, av.value AS value_text
                FROM product_variants pv
                JOIN product_variant_values pvv ON pvv.variant_id = pv.id
                JOIN attributes a ON a.id = pvv.attribute_id
                JOIN attribute_values av ON av.id = pvv.value_id
                WHERE pv.product_id = :pid";

        if ($onlyVariantIds && count($onlyVariantIds) > 0) {
            $sql = "SELECT pv.id AS variant_id, a.name AS attribute_name, av.value AS value_text
                    FROM product_variants pv
                    JOIN product_variant_values pvv ON pvv.variant_id = pv.id
                    JOIN attributes a ON a.id = pvv.attribute_id
                    JOIN attribute_values av ON av.id = pvv.value_id
                    WHERE pv.product_id = ?
                      AND pv.id IN (" . implode(',', array_fill(0, count($onlyVariantIds), '?')) . ")
                    ORDER BY pv.id ASC, a.name ASC";
            $st = $this->db->prepare($sql);
            $st->execute(array_merge([$productId], array_map('intval', $onlyVariantIds)));
        } else {
            $sql .= " ORDER BY pv.id ASC, a.name ASC";
            $st = $this->db->prepare($sql);
            $st->execute([':pid' => $productId]);
        }

        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $by = [];
        foreach ($rows as $r) {
            $vid = (int)$r['variant_id'];
            $by[$vid] ??= [];
            $by[$vid][] = (string)$r['value_text'];
        }

        $up = $this->db->prepare("UPDATE product_variants SET title=:t WHERE id=:id AND product_id=:pid");
        foreach ($by as $vid => $vals) {
            $title = implode(' / ', $vals);
            $up->execute([':t' => $title, ':id' => $vid, ':pid' => $productId]);
        }
    }

    private function refreshProductPriceCache(int $productId): void
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return;
        }

        $st = $this->db->prepare("SELECT MIN(price) AS min_price, MIN(list_price) AS min_list
                                  FROM product_variants
                                  WHERE product_id = ? AND is_active = 1");
        $st->execute([$productId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: ['min_price' => null, 'min_list' => null];

        try {
            $up = $this->db->prepare("UPDATE products
                                      SET price_from_cache = :p, list_price_from_cache = :l
                                      WHERE id = :id AND sale_mode = 'variants'");
            $up->execute([':p' => $row['min_price'], ':l' => $row['min_list'], ':id' => $productId]);
        } catch (Throwable $e) {
            if (strpos($e->getMessage(), 'price_from_cache') === false) {
                throw $e;
            }
        }
    }
}
