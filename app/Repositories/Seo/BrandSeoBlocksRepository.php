<?php
namespace App\Repositories\Seo;

use PDO;

final class BrandSeoBlocksRepository
{
    public function __construct(private PDO $db) {}

    public function getByBrandId(int $brandId): array
    {
        $st = $this->db->prepare('SELECT bullets_json, specs_json, faq_json FROM brand_seo_blocks WHERE brand_id = ? LIMIT 1');
        $st->execute([$brandId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'bullets_json' => $row['bullets_json'] ?? null,
            'specs_json'   => $row['specs_json'] ?? null,
            'faq_json'     => $row['faq_json'] ?? null,
        ];
    }

    public function upsert(int $brandId, ?string $bulletsJson, ?string $specsJson, ?string $faqJson): void
    {
        $sql = 'INSERT INTO brand_seo_blocks (brand_id, bullets_json, specs_json, faq_json)
                VALUES (:bid, :bullets, :specs, :faq)
                ON DUPLICATE KEY UPDATE
                    bullets_json = VALUES(bullets_json),
                    specs_json   = VALUES(specs_json),
                    faq_json     = VALUES(faq_json),
                    updated_at   = CURRENT_TIMESTAMP';

        $st = $this->db->prepare($sql);
        $st->execute([
            ':bid'     => $brandId,
            ':bullets' => $bulletsJson,
            ':specs'   => $specsJson,
            ':faq'     => $faqJson,
        ]);
    }

    public function deleteByBrandId(int $brandId): void
    {
        $st = $this->db->prepare('DELETE FROM brand_seo_blocks WHERE brand_id = ?');
        $st->execute([$brandId]);
    }
}
