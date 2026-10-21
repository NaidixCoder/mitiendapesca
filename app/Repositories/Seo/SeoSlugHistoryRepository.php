<?php
namespace App\Repositories\Seo;

use PDO;

final class SeoSlugHistoryRepository
{
    public function __construct(private PDO $db) {}

    public function add(string $entityType, int $entityId, string $slug): void
    {
        $st = $this->db->prepare("
            INSERT INTO seo_slug_history (entity_type, entity_id, slug)
            VALUES (:t, :id, :s)
            ON DUPLICATE KEY UPDATE entity_id = VALUES(entity_id)
        ");
        $st->execute([':t' => $entityType, ':id' => $entityId, ':s' => $slug]);
    }

    public function allForEntity(string $entityType, int $entityId): array
    {
        $st = $this->db->prepare("
            SELECT slug, created_at
              FROM seo_slug_history
             WHERE entity_type = :t AND entity_id = :id
             ORDER BY created_at DESC
        ");
        $st->execute([':t' => $entityType, ':id' => $entityId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
