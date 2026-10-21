<?php
namespace App\Repositories\Catalog;

use PDO;

final class VariantRepository
{
    public function __construct(private PDO $db) {}

    /** @return array<string,mixed>|null */
    public function find(int $variantId): ?array
    {
        if ($variantId <= 0) return null;

        $st = $this->db->prepare("SELECT id, product_id, sku, barcode, title, price, cost, is_active
                                  FROM product_variants
                                  WHERE id = ?
                                  LIMIT 1");
        $st->execute([$variantId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function belongsToProduct(int $variantId, int $productId): bool
    {
        if ($variantId <= 0 || $productId <= 0) return false;

        $st = $this->db->prepare("SELECT 1
                                  FROM product_variants
                                  WHERE id = ? AND product_id = ? AND is_active = 1
                                  LIMIT 1");
        $st->execute([$variantId, $productId]);
        return (bool)$st->fetchColumn();
    }

    /**
     * Variantes activas para Quick Add (modal en catálogo).
     * @return array<int, array{id:int,title:string,price:int,list_price:int,stock:int,in_stock:bool,attribute_name?:string,attribute_value?:string}>
     */
    public function listActiveForProductQuickAdd(int $productId): array
    {
        if ($productId <= 0) return [];

        $extraCols = '';
        if (function_exists('db_has_column') && db_has_column('product_variants','price_wh1_override')) $extraCols .= "                v.price_wh1_override,\n";
        if (function_exists('db_has_column') && db_has_column('product_variants','price_wh2_override')) $extraCols .= "                v.price_wh2_override,\n";

        $st = $this->db->prepare(
            "SELECT
                v.id,
                v.title,
                v.price,
                v.list_price,
{$extraCols}
                iv.stock AS stock,
                (
                    SELECT pi.path
                    FROM product_images pi
                    WHERE pi.product_id = v.product_id AND pi.variant_id = v.id
                    ORDER BY pi.is_cover DESC, pi.sort ASC, pi.id ASC
                    LIMIT 1
                ) AS thumb_path,
                TRIM(COALESCE(GROUP_CONCAT(av.value ORDER BY a.id SEPARATOR ' - '), '')) AS attrs_label,
                TRIM(COALESCE(GROUP_CONCAT(a.name ORDER BY a.id SEPARATOR ' / '), '')) AS attrs_names
             FROM product_variants v
             LEFT JOIN inventory_variants iv ON iv.variant_id = v.id
             LEFT JOIN product_variant_values pvv ON pvv.variant_id = v.id
             LEFT JOIN attributes a ON a.id = pvv.attribute_id
             LEFT JOIN attribute_values av ON av.id = pvv.value_id
             WHERE v.product_id = ? AND v.is_active = 1
             GROUP BY v.id
             ORDER BY v.id ASC"
        );
        $st->execute([$productId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $id = (int)($r['id'] ?? 0);
            if ($id <= 0) continue;
            $title = trim((string)($r['title'] ?? ''));
            $attributeValue = trim((string)($r['attrs_label'] ?? ''));
            $attributeNames = trim((string)($r['attrs_names'] ?? ''));
            if ($title === '') {
                $title = $attributeValue;
            }
            if ($title === '') $title = 'Variante #' . $id;

            $price = (int)($r['price'] ?? 0);
            $list  = (int)($r['list_price'] ?? 0);
            $stockRaw = $r['stock'] ?? null;
            $hasInventoryRow = ($stockRaw !== null);
            $stock = $hasInventoryRow ? (int)$stockRaw : 0;

            $thumbUrl = '';
            $thumbPath = (string)($r['thumb_path'] ?? '');
            if ($thumbPath !== '') {
                $norm = function_exists('normalize_upload_path') ? \normalize_upload_path($thumbPath) : $thumbPath;
                $thumbUrl = base_url(ltrim($norm, '/'));
            }

            $out[] = [
                'id' => $id,
                'title' => $title,
                'price' => $price,
                'list_price' => $list,
                'stock' => $stock,
                'thumb_url' => $thumbUrl,
                'attribute_name' => $attributeNames,
                'attribute_value' => $attributeValue,
                // Si no existe fila en inventory_variants, consideramos disponible
                // para no bloquear quick-add por datos incompletos legacy.
                'in_stock' => $hasInventoryRow ? ($stock > 0) : true,
            ];
        }
        return $out;
    }

    /**
     * Snapshot de variante para order_items.
     * @return array{variant_id:int,variant_sku:?string,variant_title:?string,variant_attributes_json:?string}|null
     */
    public function snapshot(int $variantId): ?array
    {
        $v = $this->find($variantId);
        if (!$v || empty($v['is_active'])) return null;

        $st = $this->db->prepare("SELECT a.slug AS attr_slug, a.name AS attr_name, av.value AS value
                                  FROM product_variant_values pvv
                                  INNER JOIN attributes a ON a.id = pvv.attribute_id
                                  INNER JOIN attribute_values av ON av.id = pvv.value_id
                                  WHERE pvv.variant_id = ?
                                  ORDER BY a.id ASC");
        $st->execute([$variantId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $attrs = [];
        foreach ($rows as $r) {
            $key = (string)($r['attr_slug'] ?? $r['attr_name'] ?? '');
            if ($key === '') continue;
            $attrs[$key] = (string)($r['value'] ?? '');
        }

        return [
            'variant_id' => (int)$v['id'],
            'variant_sku' => $v['sku'] ?? null,
            'variant_title' => $v['title'] ?? null,
            'variant_attributes_json' => $attrs ? json_encode($attrs, JSON_UNESCAPED_UNICODE) : null,
        ];
    }
}

