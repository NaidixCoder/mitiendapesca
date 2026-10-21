<?php
namespace App\Services\Admin\Variants;

use App\Repositories\Admin\CatalogTagsRepository;
use App\Services\Catalog\ProductPublicationSyncService;
use PDO;
use Throwable;

final class VariantWriteService
{
    public function __construct(private PDO $db) {}

    public function generateVariants(int $productId): array
    {
        $alreadyInTx = $this->db->inTransaction();
        $sp = null;
        if ($alreadyInTx) {
            $sp = 'sp_pv_gen_' . bin2hex(random_bytes(4));
            $this->db->exec('SAVEPOINT ' . $sp);
        } else {
            $this->db->beginTransaction();
        }

        try {
            $pa = $this->db->prepare("SELECT pa.attribute_id, a.name
                                      FROM product_attributes pa
                                      JOIN attributes a ON a.id = pa.attribute_id
                                      WHERE pa.product_id = :pid AND pa.is_required = 1 AND COALESCE(a.is_variant_axis, 0) = 1
                                      ORDER BY pa.sort ASC, a.name ASC");
            $pa->execute([':pid' => $productId]);
            $attrs = $pa->fetchAll(PDO::FETCH_ASSOC) ?: [];

            if (!$attrs) {
                if ($alreadyInTx) {
                    $this->db->exec('RELEASE SAVEPOINT ' . $sp);
                } else {
                    $this->db->commit();
                }
                return ['created' => 0, 'skipped' => 0, 'message' => 'No hay opciones de variante configuradas.'];
            }

            $values = [];
            foreach ($attrs as $a) {
                $aid = (int)$a['attribute_id'];
                $cnt = $this->db->prepare("SELECT COUNT(*) FROM product_attribute_values WHERE product_id=? AND attribute_id=?");
                $cnt->execute([$productId, $aid]);
                $hasSel = ((int)$cnt->fetchColumn() > 0);

                if ($hasSel) {
                    $sv = $this->db->prepare("SELECT av.id, av.value
                                              FROM product_attribute_values pav
                                              JOIN attribute_values av ON av.id = pav.value_id
                                              WHERE pav.product_id=? AND pav.attribute_id=?
                                              ORDER BY av.sort ASC, av.value ASC, av.id ASC");
                    $sv->execute([$productId, $aid]);
                } else {
                    $sv = $this->db->prepare("SELECT id, value FROM attribute_values WHERE attribute_id=? ORDER BY sort ASC, value ASC, id ASC");
                    $sv->execute([$aid]);
                }
                $vals = $sv->fetchAll(PDO::FETCH_ASSOC) ?: [];
                if (!$vals) {
                    if ($alreadyInTx) {
                        $this->db->exec('RELEASE SAVEPOINT ' . $sp);
                    } else {
                        $this->db->commit();
                    }
                    return ['created' => 0, 'skipped' => 0, 'message' => 'Faltan valores en: ' . $a['name']];
                }
                $values[] = ['attribute_id' => $aid, 'attribute_name' => $a['name'], 'values' => $vals];
            }

            $existing = [];
            $st = $this->db->prepare("SELECT pv.id AS variant_id, pvv.attribute_id, pvv.value_id
                                      FROM product_variants pv
                                      LEFT JOIN product_variant_values pvv ON pvv.variant_id = pv.id
                                      WHERE pv.product_id = :pid
                                      ORDER BY pv.id ASC");
            $st->execute([':pid' => $productId]);
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

            $tmp = [];
            foreach ($rows as $r) {
                $vid = (int)$r['variant_id'];
                $tmp[$vid] ??= [];
                if (!empty($r['attribute_id']) && !empty($r['value_id'])) {
                    $tmp[$vid][(int)$r['attribute_id']] = (int)$r['value_id'];
                }
            }
            foreach ($tmp as $vid => $map) {
                $existing[$this->signature($map)] = $vid;
            }

            $combos = [[]];
            foreach ($values as $bundle) {
                $next = [];
                foreach ($combos as $c) {
                    foreach ($bundle['values'] as $v) {
                        $c2 = $c;
                        $c2[] = [
                            'attribute_id' => $bundle['attribute_id'],
                            'attribute_name' => $bundle['attribute_name'],
                            'value_id' => (int)$v['id'],
                            'value' => (string)$v['value'],
                        ];
                        $next[] = $c2;
                    }
                }
                $combos = $next;
            }

            $created = 0;
            $skipped = 0;
            foreach ($combos as $combo) {
                $map = [];
                foreach ($combo as $x) {
                    $map[(int)$x['attribute_id']] = (int)$x['value_id'];
                }

                $sig = $this->signature($map);
                if (isset($existing[$sig])) {
                    $skipped++;
                    continue;
                }

                $title = implode(' / ', array_map(fn($x) => $x['value'], $combo));
                $variantId = 0;
                try {
                    $iv = $this->db->prepare("INSERT INTO product_variants (product_id, sku, title, price, cost, is_active) VALUES (:p, NULL, :t, NULL, NULL, 1)");
                    $iv->execute([':p' => $productId, ':t' => $title]);
                    $variantId = (int)$this->db->lastInsertId();
                } catch (Throwable) {
                    $tmpSku = $this->makeTmpSku($productId, $sig);
                    $iv = $this->db->prepare("INSERT INTO product_variants (product_id, sku, title, price, cost, is_active) VALUES (:p, :sku, :t, NULL, NULL, 1)");
                    $iv->execute([':p' => $productId, ':sku' => $tmpSku, ':t' => $title]);
                    $variantId = (int)$this->db->lastInsertId();
                }

                $mv = $this->db->prepare("INSERT INTO product_variant_values (variant_id, attribute_id, value_id) VALUES (:v,:a,:val)");
                foreach ($combo as $x) {
                    $mv->execute([':v' => $variantId, ':a' => (int)$x['attribute_id'], ':val' => (int)$x['value_id']]);
                }

                $inv = $this->db->prepare("INSERT INTO inventory_variants (variant_id, stock, low_stock_threshold) VALUES (:v, 0, 3)");
                $inv->execute([':v' => $variantId]);
                $created++;
            }

            if ($alreadyInTx) {
                $this->db->exec('RELEASE SAVEPOINT ' . $sp);
            } else {
                $this->db->commit();
            }

            (new ProductPublicationSyncService($this->db))->syncProduct($productId);

            return ['created' => $created, 'skipped' => $skipped, 'message' => 'Variantes generadas.'];
        } catch (Throwable $e) {
            if ($alreadyInTx) {
                try { $this->db->exec('ROLLBACK TO SAVEPOINT ' . $sp); } catch (Throwable $_) {}
                try { $this->db->exec('RELEASE SAVEPOINT ' . $sp); } catch (Throwable $_) {}
            } else {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    public function saveVariant(int $productId, int $variantId, array $payload): void
    {
        $this->db->beginTransaction();
        try {
            $st = $this->db->prepare("SELECT id FROM product_variants WHERE id=:id AND product_id=:pid LIMIT 1");
            $st->execute([':id' => $variantId, ':pid' => $productId]);
            if (!$st->fetchColumn()) {
                throw new \RuntimeException('Variante inválida.');
            }

            $skuIn = trim((string)($payload['sku'] ?? ''));
            $sku = '';
            if ($skuIn !== '') {
                $sku = $this->normalizeSku($skuIn);
                $sku = $this->ensureUniqueSku($sku, $variantId, false);
            } else {
                try {
                    $stSku = $this->db->prepare("SELECT sku FROM product_variants WHERE id=:id AND product_id=:pid LIMIT 1");
                    $stSku->execute([':id' => $variantId, ':pid' => $productId]);
                    $sku = (string)($stSku->fetchColumn() ?: '');
                } catch (Throwable) {
                    $sku = '';
                }
            }

            $upMeta = $this->db->prepare("UPDATE product_variants
                                          SET sku=:sku, is_active=:is_active
                                          WHERE id=:id AND product_id=:pid");
            $upMeta->execute([
                ':sku' => $sku,
                ':is_active' => (int)($payload['is_active'] ?? 1),
                ':id' => $variantId,
                ':pid' => $productId,
            ]);

            (new VariantPricingService($this->db))->savePricing($variantId, [
                'product_id' => $productId,
                'price' => $payload['price'] ?? null,
                'list_price' => $payload['list_price'] ?? null,
                'cost' => $payload['cost'] ?? null,
                'margin_pct' => $payload['margin_pct'] ?? null,
            ]);

            try {
                $upShip = $this->db->prepare("UPDATE product_variants
                                             SET ship_weight=:w, ship_height=:h, ship_width=:wi, ship_length=:l
                                             WHERE id=:id AND product_id=:pid");
                $upShip->execute([
                    ':w' => $payload['ship_weight'] ?? null,
                    ':h' => $payload['ship_height'] ?? null,
                    ':wi' => $payload['ship_width'] ?? null,
                    ':l' => $payload['ship_length'] ?? null,
                    ':id' => $variantId,
                    ':pid' => $productId,
                ]);
            } catch (Throwable) {
                // compat
            }

            (new VariantStockService($this->db))->saveStock(
                $variantId,
                isset($payload['stock']) ? (int)$payload['stock'] : 0,
                isset($payload['low_stock_threshold']) ? (int)$payload['low_stock_threshold'] : 3
            );

            $tagIds = $payload['tag_ids'] ?? [];
            if (!is_array($tagIds)) {
                $tagIds = [];
            }
            (new CatalogTagsRepository($this->db))->syncVariantTags($variantId, $tagIds);

            $this->refreshVariantTitles($productId, [$variantId]);
            $this->refreshProductPriceCache($productId);
            (new ProductPublicationSyncService($this->db))->syncProduct($productId);

            $this->db->commit();
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    private function isTmpSku(string $sku): bool
    {
        return str_starts_with($sku, '__TMP__');
    }

    private function makeTmpSku(int $productId, string $sig): string
    {
        return '__TMP__' . $productId . '_' . substr(md5($sig), 0, 12);
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

    private function normalizeSku(string $s): string
    {
        $s = trim($s);
        $s = strtoupper($s);
        $s = str_replace([' ', '_'], '-', $s);
        if (function_exists('iconv')) {
            $tmp = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
            if ($tmp !== false) {
                $s = $tmp;
            }
        }
        $s = preg_replace('/[^A-Z0-9\-]+/', '-', $s);
        $s = preg_replace('/\-+/', '-', $s);
        return trim($s, '-');
    }

    private function ensureUniqueSku(string $sku, int $variantId, bool $allowIncrement): string
    {
        $base = $sku;
        $n = 1;
        while (true) {
            $st = $this->db->prepare("SELECT id FROM product_variants WHERE sku = :sku AND id <> :id LIMIT 1");
            $st->execute([':sku' => $sku, ':id' => $variantId]);
            if (!$st->fetchColumn()) {
                return $sku;
            }
            if (!$allowIncrement) {
                throw new \RuntimeException('El SKU ya existe.');
            }
            $n++;
            $sku = $base . '-' . $n;
        }
    }

    private function signature(array $map): string
    {
        ksort($map);
        $parts = [];
        foreach ($map as $aid => $vid) {
            $parts[] = (int)$aid . ':' . (int)$vid;
        }
        return implode('|', $parts);
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
