<?php
namespace App\Services\Admin\Variants;

use App\Services\Catalog\ProductPublicationSyncService;
use PDO;
use Throwable;

final class VariantLifecycleService
{
    public function __construct(private PDO $db) {}

    public function toggle(int $productId, int $variantId, int $active): void
    {
        $st = $this->db->prepare("UPDATE product_variants SET is_active=:a WHERE id=:id AND product_id=:pid");
        $st->execute([':a' => $active, ':id' => $variantId, ':pid' => $productId]);
        $this->refreshProductPriceCache($productId);
        (new ProductPublicationSyncService($this->db))->syncProduct($productId);
    }

    public function deleteVariant(int $productId, int $variantId): array
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

    public function cleanupObsoleteVariants(int $productId, string $mode): array
    {
        $mode = strtolower(trim($mode));
        if (!in_array($mode, ['pause', 'delete'], true)) {
            $mode = 'pause';
        }

        $data = (new VariantReadService($this->db))->getData($productId);
        $ids = array_values(array_unique(array_map('intval', $data['obsolete_ids'] ?? [])));
        if (!$ids) {
            return ['total' => 0, 'deleted' => 0, 'paused' => 0];
        }

        $deleted = 0;
        $paused = 0;
        $alreadyInTx = $this->db->inTransaction();
        $spName = 'sp_obsolete_' . bin2hex(random_bytes(4));
        if ($alreadyInTx) {
            try { $this->db->exec("SAVEPOINT {$spName}"); } catch (Throwable $_) {}
        } else {
            $this->db->beginTransaction();
        }

        try {
            foreach ($ids as $vid) {
                if ($vid <= 0) {
                    continue;
                }
                if ($mode === 'pause') {
                    $this->toggle($productId, $vid, 0);
                    $paused++;
                    continue;
                }

                $res = $this->deleteVariant($productId, $vid);
                if (($res['action'] ?? '') === 'deleted') {
                    $deleted++;
                }
                if (($res['action'] ?? '') === 'paused') {
                    $paused++;
                }
            }

            $this->clearSelectionIfNoVariants($productId);

            if ($alreadyInTx) {
                try { $this->db->exec("RELEASE SAVEPOINT {$spName}"); } catch (Throwable $_) {}
            } else {
                $this->db->commit();
            }
        } catch (Throwable $e) {
            if ($alreadyInTx) {
                try { $this->db->exec("ROLLBACK TO SAVEPOINT {$spName}"); } catch (Throwable $_) {}
            } else {
                $this->db->rollBack();
            }
            throw $e;
        }

        (new ProductPublicationSyncService($this->db))->syncProduct($productId);
        return ['total' => count($ids), 'deleted' => $deleted, 'paused' => $paused];
    }

    public function deleteIncompleteVariants(int $productId): array
    {
        $reqSt = $this->db->prepare("SELECT COUNT(DISTINCT pa.attribute_id)
                                     FROM product_attributes pa
                                     JOIN attributes a ON a.id = pa.attribute_id
                                     WHERE pa.product_id = ? AND pa.is_required = 1 AND COALESCE(a.is_variant_axis, 0) = 1");
        $reqSt->execute([$productId]);
        $required = (int)($reqSt->fetchColumn() ?: 0);
        if ($required <= 0) {
            return ['deleted' => 0, 'paused' => 0, 'ids_deleted' => [], 'ids_paused' => []];
        }

        $sql = "
            SELECT pv.id
            FROM product_variants pv
            LEFT JOIN (
                SELECT pvv.variant_id, COUNT(DISTINCT pvv.attribute_id) AS c
                FROM product_variant_values pvv
                JOIN product_variants pv2 ON pv2.id = pvv.variant_id
                JOIN product_attributes pa ON pa.product_id = pv2.product_id
                                        AND pa.attribute_id = pvv.attribute_id
                                        AND pa.is_required = 1
                JOIN attributes a ON a.id = pa.attribute_id
                WHERE pv2.product_id = ?
                  AND COALESCE(a.is_variant_axis, 0) = 1
                GROUP BY pvv.variant_id
            ) x ON x.variant_id = pv.id
            WHERE pv.product_id = ?
              AND COALESCE(x.c, 0) < ?
        ";
        $st = $this->db->prepare($sql);
        $st->execute([$productId, $productId, $required]);
        $ids = array_map('intval', $st->fetchAll(PDO::FETCH_COLUMN) ?: []);
        if (!$ids) {
            return ['deleted' => 0, 'paused' => 0, 'ids_deleted' => [], 'ids_paused' => []];
        }

        $alreadyInTx = $this->db->inTransaction();
        $rand = function_exists('random_bytes') ? bin2hex(random_bytes(4)) : uniqid('', true);
        $spName = 'sp_delete_incomplete_' . preg_replace('/[^a-zA-Z0-9_]/', '_', (string)$rand);
        if ($alreadyInTx) {
            $this->db->exec("SAVEPOINT {$spName}");
        } else {
            $this->db->beginTransaction();
        }

        try {
            $idsDeleted = [];
            $idsPaused = [];
            foreach ($ids as $vid) {
                if ($this->variantHasOrders((int)$vid)) {
                    $this->toggle($productId, (int)$vid, 0);
                    $idsPaused[] = (int)$vid;
                    continue;
                }
                $this->db->prepare("DELETE FROM cart_items WHERE variant_id = ?")->execute([(int)$vid]);
                $this->db->prepare("DELETE FROM product_variants WHERE product_id = ? AND id = ?")->execute([$productId, (int)$vid]);
                $idsDeleted[] = (int)$vid;
            }

            $this->clearSelectionIfNoVariants($productId);

            if ($alreadyInTx) {
                $this->db->exec("RELEASE SAVEPOINT {$spName}");
            } else {
                $this->db->commit();
            }

            (new ProductPublicationSyncService($this->db))->syncProduct($productId);
            return [
                'deleted' => count($idsDeleted),
                'paused' => count($idsPaused),
                'ids_deleted' => $idsDeleted,
                'ids_paused' => $idsPaused,
            ];
        } catch (Throwable $e) {
            if ($alreadyInTx) {
                $this->db->exec("ROLLBACK TO SAVEPOINT {$spName}");
            } else {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function clearAttributesIfNoVariants(int $productId): bool
    {
        $st = $this->db->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
        $st->execute([$productId]);
        $cnt = (int)($st->fetchColumn() ?: 0);
        if ($cnt > 0) {
            return false;
        }

        $this->db->prepare("DELETE pav
                            FROM product_attribute_values pav
                            JOIN attributes a ON a.id = pav.attribute_id
                            WHERE pav.product_id = ? AND COALESCE(a.is_variant_axis, 0) = 1")->execute([$productId]);
        $this->db->prepare("DELETE pa
                            FROM product_attributes pa
                            JOIN attributes a ON a.id = pa.attribute_id
                            WHERE pa.product_id = ? AND COALESCE(a.is_variant_axis, 0) = 1")->execute([$productId]);
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
        foreach ($vars as $v) { $mapByVariant[(int)$v['id']] = []; }
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
        foreach ($stockRows as $r) { $stockMap[(int)($r['variant_id'] ?? 0)] = (int)($r['stock'] ?? 0); }

        $alreadyInTx = $this->db->inTransaction();
        if (!$alreadyInTx) $this->db->beginTransaction();
        try {
            foreach ($groups as $ids) {
                if (count($ids) <= 1) continue;
                $meta = [];
                foreach ($ids as $vid) {
                    $meta[] = [
                        'id' => (int)$vid,
                        'orders' => (int)($orders[(int)$vid] ?? 0),
                        'active' => (int)($this->findVariantActive($vars, (int)$vid) ? 1 : 0),
                    ];
                }
                usort($meta, function ($a, $b) {
                    if ($a['orders'] === 0 && $b['orders'] > 0) return -1;
                    if ($b['orders'] === 0 && $a['orders'] > 0) return 1;
                    if ($a['active'] !== $b['active']) return $b['active'] <=> $a['active'];
                    return $a['id'] <=> $b['id'];
                });

                $keeperId = (int)$meta[0]['id'];
                $sumStock = 0;
                foreach ($ids as $vid) {
                    if ((int)$vid === $keeperId) continue;
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
                    if ($vid === $keeperId) continue;
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
            if (!$alreadyInTx) $this->db->commit();
        } catch (Throwable $e) {
            if (!$alreadyInTx) $this->db->rollBack();
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

    private function clearSelectionIfNoVariants(int $productId): void
    {
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
            $st->execute([$productId]);
            $cnt = (int)($st->fetchColumn() ?: 0);
            if ($cnt === 0) {
                $this->db->prepare("DELETE pav
                                    FROM product_attribute_values pav
                                    JOIN attributes a ON a.id = pav.attribute_id
                                    WHERE pav.product_id = ? AND COALESCE(a.is_variant_axis, 0) = 1")->execute([$productId]);
            }
        } catch (Throwable $_) {}
    }

    private function syncSelectionToExistingVariants(int $productId): void
    {
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
            $st->execute([$productId]);
            $cnt = (int)($st->fetchColumn() ?: 0);
            if ($cnt <= 0) return;

            $stSel = $this->db->prepare("SELECT pav.attribute_id, pav.value_id
                                         FROM product_attribute_values pav
                                         JOIN attributes a ON a.id = pav.attribute_id
                                         WHERE pav.product_id = ? AND COALESCE(a.is_variant_axis, 0) = 1");
            $stSel->execute([$productId]);
            $selRows = $stSel->fetchAll(PDO::FETCH_ASSOC) ?: [];
            if (!$selRows) return;

            $selected = [];
            foreach ($selRows as $r) {
                $aid = (int)($r['attribute_id'] ?? 0);
                $vid = (int)($r['value_id'] ?? 0);
                if ($aid > 0 && $vid > 0) {
                    $selected[$aid] ??= [];
                    $selected[$aid][] = $vid;
                }
            }
            if (!$selected) return;

            $stUsed = $this->db->prepare(
                "SELECT pvv.attribute_id, pvv.value_id
                 FROM product_variant_values pvv
                 JOIN product_variants pv ON pv.id = pvv.variant_id
                 WHERE pv.product_id = ?"
            );
            $stUsed->execute([$productId]);
            $usedRows = $stUsed->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $used = [];
            foreach ($usedRows as $r) {
                $aid = (int)($r['attribute_id'] ?? 0);
                $vid = (int)($r['value_id'] ?? 0);
                if ($aid > 0 && $vid > 0) {
                    $used[$aid] ??= [];
                    $used[$aid][$vid] = true;
                }
            }

            $attrSvc = new VariantAttributesService($this->db);
            foreach ($selected as $aid => $ids) {
                $ids = array_values(array_unique(array_map('intval', $ids)));
                $kept = [];
                foreach ($ids as $vid) {
                    if (!empty($used[$aid][$vid])) $kept[] = $vid;
                }
                sort($ids);
                sort($kept);
                if ($ids === $kept) continue;
                $attrSvc->setAttributeValues($productId, (int)$aid, $kept);
            }
        } catch (Throwable $_) {}
    }

    private function signature(array $map): string
    {
        ksort($map);
        $parts = [];
        foreach ($map as $aid => $vid) $parts[] = (int)$aid . ':' . (int)$vid;
        return implode('|', $parts);
    }

    private function refreshProductPriceCache(int $productId): void
    {
        $productId = (int)$productId;
        if ($productId <= 0) return;

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
