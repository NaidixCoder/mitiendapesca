<?php
namespace App\Repositories\Catalog;

use PDO;

final class ProductVariantValueRepository
{
    public function __construct(private PDO $db) {}

    public function replaceForVariant(int $variantId, array $pairs): bool
    {
        $variantId = (int)$variantId;
        if ($variantId <= 0) return false;

        // $pairs: [['attribute_id'=>int,'value_id'=>int], ...]
        $this->db->prepare("DELETE FROM product_variant_values WHERE variant_id=?")->execute([$variantId]);

        if (!$pairs) return true;

        $st = $this->db->prepare("INSERT INTO product_variant_values (variant_id, attribute_id, value_id) VALUES (?,?,?)");
        foreach ($pairs as $p) {
            $aid = (int)($p['attribute_id'] ?? 0);
            $vid = (int)($p['value_id'] ?? 0);
            if ($aid <= 0 || $vid <= 0) continue;
            $st->execute([$variantId, $aid, $vid]);
        }
        return true;
    }
}

