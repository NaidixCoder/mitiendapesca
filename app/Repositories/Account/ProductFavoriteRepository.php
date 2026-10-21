<?php

namespace App\Repositories\Account;

use PDO;

final class ProductFavoriteRepository
{
    public function __construct(private PDO $db)
    {
    }

    public function productExists(int $productId): bool
    {
        $st = $this->db->prepare('SELECT 1 FROM products WHERE id = ? AND is_active = 1 LIMIT 1');
        $st->execute([$productId]);
        return (bool)$st->fetch(PDO::FETCH_ASSOC);
    }

    public function isFavorite(int $userId, int $productId): bool
    {
        $st = $this->db->prepare('SELECT 1 FROM user_product_favorites WHERE user_id = ? AND product_id = ? LIMIT 1');
        $st->execute([$userId, $productId]);
        return (bool)$st->fetch(PDO::FETCH_ASSOC);
    }

    public function add(int $userId, int $productId): void
    {
        $st = $this->db->prepare('INSERT IGNORE INTO user_product_favorites (user_id, product_id) VALUES (?, ?)');
        $st->execute([$userId, $productId]);
    }

    public function remove(int $userId, int $productId): void
    {
        $st = $this->db->prepare('DELETE FROM user_product_favorites WHERE user_id = ? AND product_id = ?');
        $st->execute([$userId, $productId]);
    }

    public function toggle(int $userId, int $productId): bool
    {
        if ($this->isFavorite($userId, $productId)) {
            $this->remove($userId, $productId);
            return false;
        }

        $this->add($userId, $productId);
        return true;
    }

    public function countByUser(int $userId): int
    {
        $st = $this->db->prepare('SELECT COUNT(*) FROM user_product_favorites f INNER JOIN products p ON p.id = f.product_id WHERE f.user_id = ? AND p.is_active = 1');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }

    /** @return array<int,array<string,mixed>> */
    public function listByUser(int $userId, int $limit = 100): array
    {
        $limit = max(1, min(200, $limit));
                $sql = "SELECT
                    p.id,
                    p.slug,
                    p.name,
                    p.sale_mode,
                    CASE
                      WHEN p.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF(v.price,0)) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1),
                        p.price
                      )
                      ELSE p.price
                    END AS price,
                    CASE
                      WHEN p.sale_mode = 'variants' THEN COALESCE(
                        (SELECT MIN(NULLIF(v.list_price,0)) FROM product_variants v WHERE v.product_id = p.id AND v.is_active = 1),
                        p.list_price
                      )
                      ELSE p.list_price
                    END AS list_price,
                    b.name AS brand_name,
                    c.name AS category_name,
                    (
                      SELECT i.path
                      FROM product_images i
                      WHERE i.product_id = p.id
                      ORDER BY i.is_cover DESC, i.id ASC
                      LIMIT 1
                    ) AS cover_url,
                    f.created_at AS favorited_at
                FROM user_product_favorites f
                INNER JOIN products p ON p.id = f.product_id
                LEFT JOIN brands b ON b.id = p.brand_id
                LEFT JOIN categories c ON c.id = p.category_id
                WHERE f.user_id = :uid
                  AND p.is_active = 1
                ORDER BY f.created_at DESC
                LIMIT {$limit}";
        $st = $this->db->prepare($sql);
        $st->execute([':uid' => $userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
