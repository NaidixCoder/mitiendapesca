<?php
namespace App\Repositories\Catalog;

use PDO;

/**
 * Lecturas simples de producto para Carrito/Checkout.
 */
final class ProductReadRepository
{
    public function __construct(private PDO $db) {}

    public function findForCart(int $id): ?array
    {
        $st = $this->db->prepare(
            "SELECT p.id, p.sku, p.slug, p.name, p.short_desc, p.price, p.list_price, p.is_active,
                    b.name AS brand_name
               FROM products p
               LEFT JOIN brands b ON b.id = p.brand_id
              WHERE p.id = ?
              LIMIT 1"
        );
        $st->execute([$id]);
        $r = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $r;
    }

    /**
     * @param int[] $ids
     * @return array<int, array<string,mixed>>
     */
    public function findManyForCart(array $ids): array
    {
        $ids = array_values(array_unique(array_filter(array_map('intval', $ids), fn($v)=>$v>0)));
        if (!$ids) return [];

        $ph = implode(',', array_fill(0, count($ids), '?'));

$cols = "p.id, p.sku, p.slug, p.name, p.short_desc, p.price, p.list_price, p.is_active, b.name AS brand_name";
if (function_exists('db_has_column') && db_has_column('products','price_wh1_override')) $cols .= ", p.price_wh1_override";
if (function_exists('db_has_column') && db_has_column('products','price_wh2_override')) $cols .= ", p.price_wh2_override";

$sql = "SELECT {$cols},
COALESCE(i.stock, 0) AS stock,
                       (SELECT pi.path
                          FROM product_images pi
                         WHERE pi.product_id = p.id
                         ORDER BY pi.is_cover DESC, pi.sort ASC, pi.id ASC
                         LIMIT 1) AS thumb
                FROM products p
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN inventory i ON i.product_id = p.id
                WHERE p.id IN ($ph)";

        $st = $this->db->prepare($sql);
        $st->execute($ids);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$it) {
            $t = $it['thumb'] ?? null;
            if ($t) {
                $it['thumb'] = function_exists('public_url') ? public_url($t) : asset($t);
            } else {
                $it['thumb'] = asset('img/placeholder.svg');
            }
        }
        unset($it);

        return $rows;
    }
}
