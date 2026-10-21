<?php
namespace App\Services\Admin\Audit;

use PDO;

/**
 * Enriquecimiento UX para auditorías:
 * - Si metadata trae variant_id, añadimos variant_sku y variant_name (combinación de valores de atributos).
 * - Si es stock_updated sin variant_id (producto simple), añadimos product_sku.
 */
final class AuditEnrichmentService
{
    public function enrichItems(PDO $db, array &$items): void
    {
        $variantIds = [];
        $productIdsNeedSku = [];

        foreach ($items as $it) {
            $action = (string)($it['action'] ?? '');
            if ($action === '') continue;
            $metaRaw = (string)($it['metadata'] ?? '');
            if ($metaRaw === '') continue;
            $meta = json_decode($metaRaw, true);
            if (!is_array($meta)) continue;

            $variantId = isset($meta['variant_id']) ? (int)$meta['variant_id'] : 0;
            if ($variantId > 0) {
                $variantIds[$variantId] = true;
                continue;
            }

            // Producto simple: stock_updated suele venir sin variant_id
            if ($action === 'stock_updated' && (string)($it['entity_type'] ?? '') === 'product') {
                $pid = (int)($it['entity_id'] ?? 0);
                if ($pid > 0) $productIdsNeedSku[$pid] = true;
            }
        }

        // Bulk lookup variantes (sku + nombre)
        $variantInfo = [];
        if (!empty($variantIds)) {
            $ids = array_keys($variantIds);
            $in = implode(',', array_fill(0, count($ids), '?'));
            $sql = "
                SELECT
                  pv.id AS variant_id,
                  pv.sku AS variant_sku,
                  GROUP_CONCAT(av.value ORDER BY COALESCE(pa.sort, 999999), av.sort SEPARATOR ' · ') AS variant_name
                FROM product_variants pv
                LEFT JOIN product_variant_values pvv ON pvv.variant_id = pv.id
                LEFT JOIN attribute_values av ON av.id = pvv.value_id
                LEFT JOIN product_attributes pa
                  ON pa.product_id = pv.product_id AND pa.attribute_id = pvv.attribute_id
                WHERE pv.id IN ($in)
                GROUP BY pv.id, pv.sku
            ";
            try {
                $st = $db->prepare($sql);
                $st->execute($ids);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $vid = (int)($r['variant_id'] ?? 0);
                    if ($vid <= 0) continue;
                    $variantInfo[$vid] = [
                        'sku' => (string)($r['variant_sku'] ?? ''),
                        'name' => (string)($r['variant_name'] ?? ''),
                    ];
                }
            } catch (\Throwable $e) {
                // compat: si faltan tablas, no rompemos
                $variantInfo = [];
            }
        }

        // Bulk lookup SKU producto simple
        $productSku = [];
        if (!empty($productIdsNeedSku)) {
            $pids = array_keys($productIdsNeedSku);
            $in = implode(',', array_fill(0, count($pids), '?'));
            try {
                $st = $db->prepare("SELECT id, sku FROM products WHERE id IN ($in)");
                $st->execute($pids);
                $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $pid = (int)($r['id'] ?? 0);
                    if ($pid <= 0) continue;
                    $productSku[$pid] = (string)($r['sku'] ?? '');
                }
            } catch (\Throwable $e) {
                $productSku = [];
            }
        }

        // Inject en metadata
        foreach ($items as &$it) {
            $metaRaw = (string)($it['metadata'] ?? '');
            if ($metaRaw === '') continue;
            $meta = json_decode($metaRaw, true);
            if (!is_array($meta)) continue;

            $variantId = isset($meta['variant_id']) ? (int)$meta['variant_id'] : 0;
            if ($variantId > 0) {
                if (!isset($meta['variant_sku']) && isset($variantInfo[$variantId])) {
                    $meta['variant_sku'] = $variantInfo[$variantId]['sku'] ?? '';
                }
                if (!isset($meta['variant_name']) && isset($variantInfo[$variantId])) {
                    $meta['variant_name'] = $variantInfo[$variantId]['name'] ?? '';
                }
                $it['metadata'] = json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                continue;
            }

            if ((string)($it['action'] ?? '') === 'stock_updated' && (string)($it['entity_type'] ?? '') === 'product') {
                $pid = (int)($it['entity_id'] ?? 0);
                if ($pid > 0 && !isset($meta['product_sku']) && isset($productSku[$pid])) {
                    $meta['product_sku'] = $productSku[$pid];
                    $it['metadata'] = json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                }
            }
        }
        unset($it);
    }
}
