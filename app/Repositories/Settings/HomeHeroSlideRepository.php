<?php
namespace App\Repositories\Settings;

use PDO;

final class HomeHeroSlideRepository
{
    public function __construct(private PDO $db) {}

    /** @return array<int,array<string,mixed>> */
    public function listAll(): array
    {
        try {
            $sql = 'SELECT id, is_active, title, alt_text, image_desktop, image_mobile, target_url, sort_order, starts_at, ends_at
                    FROM home_hero_slides
                    ORDER BY sort_order ASC, id ASC';
            $st = $this->db->query($sql);
            if (!$st) return [];
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @return array<int,array<string,mixed>> */
    public function listActiveForPublic(int $limit = 6): array
    {
        try {
            $limit = max(1, min(20, $limit));
            $sql = "SELECT id, title, alt_text, image_desktop, image_mobile, target_url
                    FROM home_hero_slides
                    WHERE is_active = 1
                      AND (starts_at IS NULL OR starts_at <= NOW())
                      AND (ends_at IS NULL OR ends_at >= NOW())
                    ORDER BY sort_order ASC, id ASC
                    LIMIT {$limit}";
            $st = $this->db->query($sql);
            if (!$st) return [];
            return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            return [];
        }
    }

    /** @param array<int,array<string,mixed>> $rows */
    public function replaceAll(array $rows): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->exec('DELETE FROM home_hero_slides');

            if (!empty($rows)) {
                $st = $this->db->prepare(
                    'INSERT INTO home_hero_slides (is_active, title, alt_text, image_desktop, image_mobile, target_url, sort_order, starts_at, ends_at, created_at, updated_at)
                     VALUES (:is_active, :title, :alt_text, :image_desktop, :image_mobile, :target_url, :sort_order, :starts_at, :ends_at, NOW(), NOW())'
                );

                foreach ($rows as $row) {
                    $st->execute([
                        ':is_active' => !empty($row['is_active']) ? 1 : 0,
                        ':title' => (string)($row['title'] ?? ''),
                        ':alt_text' => (string)($row['alt_text'] ?? ''),
                        ':image_desktop' => (string)($row['image_desktop'] ?? ''),
                        ':image_mobile' => (string)($row['image_mobile'] ?? ''),
                        ':target_url' => (string)($row['target_url'] ?? ''),
                        ':sort_order' => (int)($row['sort_order'] ?? 0),
                        ':starts_at' => ($row['starts_at'] ?? null) ?: null,
                        ':ends_at' => ($row['ends_at'] ?? null) ?: null,
                    ]);
                }
            }

            $this->db->commit();
        } catch (\Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }
}
