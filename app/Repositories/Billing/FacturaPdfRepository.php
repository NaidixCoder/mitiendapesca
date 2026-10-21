<?php

namespace App\Repositories\Billing;

use PDO;

final class FacturaPdfRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function findOrderWithUser(int $orderId): ?array
    {
        $sql = "
            SELECT
                o.*,
                u.id AS user_id,
                u.name AS user_name,
                u.email AS user_email
            FROM orders o
            LEFT JOIN users u ON u.id = o.user_id
            WHERE o.id = :id
            LIMIT 1
        ";
        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $orderId, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Items del pedido.
     * Intentamos usar `order_items` (snapshot) si existe.
     *
     * @return array<int,array<string,mixed>>
     */
    public function listItems(int $orderId): array
    {
        // table_exists helper no siempre está, hacemos try/catch.
        try {
            $st = $this->db->query("SHOW TABLES LIKE 'order_items'");
            $exists = (bool)$st->fetchColumn();
        } catch (\Throwable $e) {
            $exists = false;
        }

        if (!$exists) {
            return [];
        }

        $sql = "
            SELECT
                oi.product_id,
                oi.variant_id,
                oi.qty,
                oi.price,
                COALESCE(NULLIF(oi.variant_sku,''), oi.product_sku, p.sku) AS sku,
                COALESCE(oi.product_name, p.name) AS name,
                oi.variant_title
            FROM order_items oi
            LEFT JOIN products p ON p.id = oi.product_id
            WHERE oi.order_id = :oid
            ORDER BY oi.id ASC
        ";
        $st = $this->db->prepare($sql);
        $st->bindValue(':oid', $orderId, PDO::PARAM_INT);
        $st->execute();
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Normalizar + agrupar por (product_id, variant_id, name, sku, variant_title, price)
        $grouped = [];
        foreach ($rows as $r) {
            $productId = (int)($r['product_id'] ?? 0);
            $variantId = (int)($r['variant_id'] ?? 0);
            $name = (string)($r['name'] ?? '');
            $sku  = (string)($r['sku'] ?? '');
            $variantTitle = trim((string)($r['variant_title'] ?? ''));
            $price = (float)($r['price'] ?? 0);
            $key = md5($productId . '|' . $variantId . '|' . $name . '|' . $sku . '|' . $variantTitle . '|' . $price);

            if (!isset($grouped[$key])) {
                $grouped[$key] = [
                    'product_id' => $productId,
                    'variant_id' => $variantId,
                    'name' => $name,
                    'sku' => $sku,
                    'qty' => 0,
                    'price' => $price,
                    'subtotal' => 0,
                    'variant_title' => $variantTitle,
                ];
            }
            $qty = (int)($r['qty'] ?? 0);
            $grouped[$key]['qty'] += $qty;
            $grouped[$key]['subtotal'] += ($qty * $price);
        }

        return array_values($grouped);
    }
}
