<?php
namespace App\Repositories\Seo;

use PDO;

final class ProductSeoBlocksRepository
{
    public function __construct(private PDO $db) {}

    public function getByProductId(int $productId): array
    {
        $st = $this->db->prepare('SELECT bullets_json, specs_json, faq_json FROM product_seo_blocks WHERE product_id = ? LIMIT 1');
        $st->execute([$productId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'bullets_json' => $row['bullets_json'] ?? null,
            'specs_json'   => $row['specs_json'] ?? null,
            'faq_json'     => $row['faq_json'] ?? null,
        ];
    }

    public function upsert(int $productId, ?string $bulletsJson, ?string $specsJson, ?string $faqJson): void
    {
        $sql = 'INSERT INTO product_seo_blocks (product_id, bullets_json, specs_json, faq_json)
                VALUES (:pid, :bullets, :specs, :faq)
                ON DUPLICATE KEY UPDATE
                    bullets_json = VALUES(bullets_json),
                    specs_json   = VALUES(specs_json),
                    faq_json     = VALUES(faq_json),
                    updated_at   = CURRENT_TIMESTAMP';

        $st = $this->db->prepare($sql);
        $st->execute([
            ':pid'     => $productId,
            ':bullets' => $bulletsJson,
            ':specs'   => $specsJson,
            ':faq'     => $faqJson,
        ]);
    }

    public function deleteByProductId(int $productId): void
    {
        $st = $this->db->prepare('DELETE FROM product_seo_blocks WHERE product_id = ?');
        $st->execute([$productId]);
    }
}
