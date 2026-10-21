<?php
namespace App\Repositories\Admin;

use PDO;

final class InventoryItemsRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Devuelve el inventario como "ítems vendibles":
     * - Producto simple => 1 fila (products + inventory)
     * - Producto con variantes => 1 fila por variante (product_variants + inventory_variants)
     *
     * @return array<int, array<string, mixed>>
     */
    public function listItems(string $q = ''): array
    {
        $q = trim($q);
        $params = [];

        $whereProduct = "p.sale_mode = 'simple'";
        $whereVariant = "p.sale_mode = 'variants'";

        if ($q !== '') {
            // Buscar por SKU / nombre / título de variante
            $whereProduct .= " AND (p.sku LIKE ? OR p.name LIKE ?)";
            $whereVariant .= " AND (pv.sku LIKE ? OR p.name LIKE ? OR pv.title LIKE ?)";
            $like = '%' . $q . '%';
            $params = [$like, $like, $like, $like, $like];
        }

        $sql = "
            SELECT
              'product' AS item_type,
              p.id AS product_id,
              NULL AS variant_id,
              p.sku AS sku,
              p.name AS product_name,
              NULL AS variant_title,
              COALESCE(i.stock, 0) AS stock,
              COALESCE(p.iva_rate, 0) AS iva_rate,
              COALESCE(p.cost, 0) AS cost,
              COALESCE(p.price, 0) AS price
            FROM products p
            LEFT JOIN inventory i ON i.product_id = p.id
            WHERE {$whereProduct}

            UNION ALL

            SELECT
              'variant' AS item_type,
              p.id AS product_id,
              pv.id AS variant_id,
              pv.sku AS sku,
              p.name AS product_name,
              pv.title AS variant_title,
              COALESCE(iv.stock, 0) AS stock,
              COALESCE(p.iva_rate, 0) AS iva_rate,
              COALESCE(pv.cost, 0) AS cost,
              COALESCE(pv.price, 0) AS price
            FROM product_variants pv
            INNER JOIN products p ON p.id = pv.product_id
            LEFT JOIN inventory_variants iv ON iv.variant_id = pv.id
            WHERE {$whereVariant}

            ORDER BY product_name ASC, variant_title ASC
        ";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
