<?php
namespace App\Repositories\Seo;

use PDO;

final class CategorySeoBlocksRepository
{
    public function __construct(private PDO $db) {}

    public function getByCategoryId(int $categoryId): array
    {
        $st = $this->db->prepare('SELECT bullets_json, specs_json, faq_json FROM category_seo_blocks WHERE category_id = ? LIMIT 1');
        $st->execute([$categoryId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'bullets_json' => $row['bullets_json'] ?? null,
            'specs_json'   => $row['specs_json'] ?? null,
            'faq_json'     => $row['faq_json'] ?? null,
        ];
    }

    public function upsert(int $categoryId, ?string $bulletsJson, ?string $specsJson, ?string $faqJson): void
    {
        $sql = 'INSERT INTO category_seo_blocks (category_id, bullets_json, specs_json, faq_json)
                VALUES (:cid, :bullets, :specs, :faq)
                ON DUPLICATE KEY UPDATE
                    bullets_json = VALUES(bullets_json),
                    specs_json   = VALUES(specs_json),
                    faq_json     = VALUES(faq_json),
                    updated_at   = CURRENT_TIMESTAMP';

        $st = $this->db->prepare($sql);
        $st->execute([
            ':cid'     => $categoryId,
            ':bullets' => $bulletsJson,
            ':specs'   => $specsJson,
            ':faq'     => $faqJson,
        ]);
    }

    public function deleteByCategoryId(int $categoryId): void
    {
        $st = $this->db->prepare('DELETE FROM category_seo_blocks WHERE category_id = ?');
        $st->execute([$categoryId]);
    }
}
