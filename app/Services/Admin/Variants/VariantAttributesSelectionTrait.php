<?php
namespace App\Services\Admin\Variants;

use Throwable;

trait VariantAttributesSelectionTrait
{
    private function clearSelectionIfNoVariants(int $productId): void
    {
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
            $st->execute([$productId]);
            $cnt = (int)($st->fetchColumn() ?: 0);
            if ($cnt === 0) {
                $this->db->prepare("DELETE FROM product_attribute_values WHERE product_id = ?")->execute([$productId]);
            }
        } catch (Throwable $_) {
        }
    }

    private function syncSelectionToExistingVariants(int $productId): void
    {
        try {
            $st = $this->db->prepare("SELECT COUNT(*) FROM product_variants WHERE product_id = ?");
            $st->execute([$productId]);
            $cnt = (int)($st->fetchColumn() ?: 0);
            if ($cnt <= 0) {
                return;
            }

            $stSel = $this->db->prepare("SELECT attribute_id, value_id FROM product_attribute_values WHERE product_id = ?");
            $stSel->execute([$productId]);
            $selRows = $stSel->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            if (!$selRows) {
                return;
            }

            $selected = [];
            foreach ($selRows as $r) {
                $aid = (int)($r['attribute_id'] ?? 0);
                $vid = (int)($r['value_id'] ?? 0);
                if ($aid > 0 && $vid > 0) {
                    $selected[$aid] ??= [];
                    $selected[$aid][] = $vid;
                }
            }
            if (!$selected) {
                return;
            }

            $stUsed = $this->db->prepare(
                "SELECT pvv.attribute_id, pvv.value_id
                 FROM product_variant_values pvv
                 JOIN product_variants pv ON pv.id = pvv.variant_id
                 WHERE pv.product_id = ?"
            );
            $stUsed->execute([$productId]);
            $usedRows = $stUsed->fetchAll(\PDO::FETCH_ASSOC) ?: [];

            $used = [];
            foreach ($usedRows as $r) {
                $aid = (int)($r['attribute_id'] ?? 0);
                $vid = (int)($r['value_id'] ?? 0);
                if ($aid > 0 && $vid > 0) {
                    $used[$aid] ??= [];
                    $used[$aid][$vid] = true;
                }
            }

            foreach ($selected as $aid => $ids) {
                $ids = array_values(array_unique(array_map('intval', $ids)));
                $kept = [];
                foreach ($ids as $vid) {
                    if (!empty($used[$aid][$vid])) {
                        $kept[] = $vid;
                    }
                }

                sort($ids);
                sort($kept);
                if ($ids === $kept) {
                    continue;
                }

                $this->setAttributeValues($productId, (int)$aid, $kept);
            }
        } catch (Throwable $_) {
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
}
