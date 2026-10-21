<?php
namespace App\Repositories\Seo;

use PDO;

final class SeoSettingsRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Devuelve settings avanzados (canonical/robots/social/schema) para una entidad.
     */
    public function get(string $entityType, int $entityId): ?array
    {
        $st = $this->db->prepare("
            SELECT *
              FROM seo_settings
             WHERE entity_type = :t AND entity_id = :id
             LIMIT 1
        ");
        $st->execute([':t' => $entityType, ':id' => $entityId]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Upsert de settings avanzados.
     * $data permite: canonical_url, robots_index, robots_follow, og_title, og_description, og_image_path,
     * twitter_card, twitter_title, twitter_description, twitter_image_path, schema_override.
     */
    public function upsert(string $entityType, int $entityId, array $data): void
    {
        // Normalización mínima
        $robotsIndex  = array_key_exists('robots_index', $data) ? (int)(bool)$data['robots_index'] : 1;
        $robotsFollow = array_key_exists('robots_follow', $data) ? (int)(bool)$data['robots_follow'] : 1;

        $payload = [
            ':t' => $entityType,
            ':id' => $entityId,
            ':canonical_url' => $data['canonical_url'] ?? null,
            ':robots_index' => $robotsIndex,
            ':robots_follow' => $robotsFollow,
            ':og_title' => $data['og_title'] ?? null,
            ':og_description' => $data['og_description'] ?? null,
            ':og_image_path' => $data['og_image_path'] ?? null,
            ':twitter_card' => $data['twitter_card'] ?? 'summary_large_image',
            ':twitter_title' => $data['twitter_title'] ?? null,
            ':twitter_description' => $data['twitter_description'] ?? null,
            ':twitter_image_path' => $data['twitter_image_path'] ?? null,
            ':schema_override' => $data['schema_override'] ?? null,
        ];

        $sql = "
            INSERT INTO seo_settings
              (entity_type, entity_id, canonical_url, robots_index, robots_follow,
               og_title, og_description, og_image_path,
               twitter_card, twitter_title, twitter_description, twitter_image_path,
               schema_override)
            VALUES
              (:t, :id, :canonical_url, :robots_index, :robots_follow,
               :og_title, :og_description, :og_image_path,
               :twitter_card, :twitter_title, :twitter_description, :twitter_image_path,
               :schema_override)
            ON DUPLICATE KEY UPDATE
              canonical_url = VALUES(canonical_url),
              robots_index = VALUES(robots_index),
              robots_follow = VALUES(robots_follow),
              og_title = VALUES(og_title),
              og_description = VALUES(og_description),
              og_image_path = VALUES(og_image_path),
              twitter_card = VALUES(twitter_card),
              twitter_title = VALUES(twitter_title),
              twitter_description = VALUES(twitter_description),
              twitter_image_path = VALUES(twitter_image_path),
              schema_override = VALUES(schema_override),
              updated_at = CURRENT_TIMESTAMP
        ";

        $st = $this->db->prepare($sql);
        $st->execute($payload);
    }
}
