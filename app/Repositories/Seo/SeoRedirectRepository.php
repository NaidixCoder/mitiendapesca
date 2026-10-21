<?php
namespace App\Repositories\Seo;

use PDO;

final class SeoRedirectRepository
{
    public function __construct(private PDO $db) {}

    public function findTargetByFrom(string $fromPath): ?array
    {
        $st = $this->db->prepare("SELECT * FROM seo_redirects WHERE from_path = :p LIMIT 1");
        $st->execute([':p' => $fromPath]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function upsert(string $fromPath, string $toPath, int $statusCode = 301): void
    {
        $st = $this->db->prepare("
            INSERT INTO seo_redirects (from_path, to_path, status_code)
            VALUES (:f, :t, :c)
            ON DUPLICATE KEY UPDATE
              to_path = VALUES(to_path),
              status_code = VALUES(status_code),
              updated_at = CURRENT_TIMESTAMP
        ");
        $st->execute([':f' => $fromPath, ':t' => $toPath, ':c' => $statusCode]);
    }

    /**
     * Evita cadenas de redirects: si había registros que apuntan a $oldTo, los actualiza a $newTo.
     * (Ej: A -> B y ahora B -> C, retargeteamos A -> C)
     */
    public function retargetTo(string $oldTo, string $newTo): void
    {
        if ($oldTo === '' || $newTo === '' || $oldTo === $newTo) return;
        $st = $this->db->prepare("UPDATE seo_redirects SET to_path = :n, updated_at = CURRENT_TIMESTAMP WHERE to_path = :o");
        $st->execute([':n' => $newTo, ':o' => $oldTo]);
    }

    public function registerHit(string $fromPath): void
    {
        $st = $this->db->prepare("
            UPDATE seo_redirects
               SET hits = hits + 1,
                   last_hit_at = CURRENT_TIMESTAMP
             WHERE from_path = :f
             LIMIT 1
        ");
        $st->execute([':f' => $fromPath]);
    }
}
