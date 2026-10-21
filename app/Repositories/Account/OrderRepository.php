<?php
namespace App\Repositories\Account;

use PDO;

class OrderRepository
{
    public function __construct(private PDO $db) {}

    public function tableExists(): bool
    {
        try {
            $has = $this->db->query("SHOW TABLES LIKE 'orders'")->fetchColumn();
            return (bool)$has;
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function countByUser(int $userId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ?');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }

    /** @return array<int, array<string,mixed>> */
    public function listByUser(int $userId, int $limit = 50): array
    {
        $limit = max(1, min(200, $limit));
        $st = $this->db->prepare(
            'SELECT o.id, o.status, o.total, o.created_at,
                    (
                      SELECT COALESCE(SUM(oi.qty), 0)
                      FROM order_items oi
                      WHERE oi.order_id = o.id
                    ) AS items_count
             FROM orders o
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC
             LIMIT ' . $limit
        );
        $st->execute([$userId]);
        /** @var array<int, array<string,mixed>> $rows */
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $rows;
    }

    /** @return array<string,mixed>|null */
    public function findByIdForUser(int $orderId, int $userId): ?array
    {
        $st = $this->db->prepare(
            'SELECT o.*
             FROM orders o
             WHERE o.id = ? AND o.user_id = ?
             LIMIT 1'
        );
        $st->execute([$orderId, $userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row;
    }

    /** @return array<int, array<string,mixed>> */
    public function itemsForOrder(int $orderId): array
    {
        // Snapshot first (si existe), fallback a products.
        $st = $this->db->prepare(
            'SELECT oi.id, oi.product_id, oi.variant_id, oi.qty, oi.price,
                    oi.variant_sku,
                    COALESCE(NULLIF(oi.variant_sku,""), oi.product_sku, p.sku) AS sku,
                    COALESCE(oi.product_name, p.name) AS name,
                    p.slug AS product_slug,
                    oi.variant_title,
                    oi.variant_attributes_json,
                    (
                        SELECT pi.path
                        FROM product_images pi
                        WHERE pi.variant_id = oi.variant_id
                        ORDER BY pi.is_cover DESC, pi.sort ASC, pi.id ASC
                        LIMIT 1
                    ) AS variant_image_path,
                    (
                        SELECT pi.path
                        FROM product_images pi
                        WHERE pi.product_id = oi.product_id AND pi.variant_id IS NULL
                        ORDER BY pi.is_cover DESC, pi.sort ASC, pi.id ASC
                        LIMIT 1
                    ) AS product_image_path
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id ASC'
        );
        $st->execute([$orderId]);
        /** @var array<int, array<string,mixed>> $rows */
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        return $rows;
    }

    public function findActiveVariantIdBySku(int $productId, string $sku): int
    {
        $productId = (int)$productId;
        $sku = trim($sku);
        if ($productId <= 0 || $sku === '') {
            return 0;
        }

        $st = $this->db->prepare(
            'SELECT id
             FROM product_variants
             WHERE product_id = ? AND sku = ? AND is_active = 1
             LIMIT 1'
        );
        $st->execute([$productId, $sku]);
        return (int)($st->fetchColumn() ?: 0);
    }

    public function requiresVariantSelection(int $productId): bool
    {
        $productId = (int)$productId;
        if ($productId <= 0) {
            return false;
        }

        try {
            $st = $this->db->prepare(
                'SELECT sale_mode
                 FROM products
                 WHERE id = ?
                 LIMIT 1'
            );
            $st->execute([$productId]);
            $saleMode = strtolower(trim((string)($st->fetchColumn() ?: '')));
            if ($saleMode === 'variants') {
                return true;
            }
        } catch (\Throwable $e) {
            // fallback por variantes activas
        }

        $st = $this->db->prepare(
            'SELECT 1
             FROM product_variants
             WHERE product_id = ? AND is_active = 1
             LIMIT 1'
        );
        $st->execute([$productId]);
        return (bool)$st->fetchColumn();
    }

    /** @return array<string,mixed>|null */
    public function latestByUser(int $userId): ?array
    {
        $st = $this->db->prepare(
            'SELECT o.id, o.status, o.total, o.created_at,
                    (
                      SELECT COALESCE(SUM(oi.qty), 0)
                      FROM order_items oi
                      WHERE oi.order_id = o.id
                    ) AS items_count
             FROM orders o
             WHERE o.user_id = ?
             ORDER BY o.created_at DESC
             LIMIT 1'
        );
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row;
    }
}
