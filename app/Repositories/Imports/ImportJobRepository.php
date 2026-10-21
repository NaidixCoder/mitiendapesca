<?php
namespace App\Repositories\Imports;

use PDO;
use Throwable;

final class ImportJobRepository
{
    public function __construct(private PDO $db) {}

    /**
     * FIX553: lista jobs más viejos que un cutoff UTC.
     * @return array<int, array{id:int,file_path:?string,created_at:?string}>
     */
    public function listOlderThanUtc(string $cutoffUtc, int $limit = 500): array
    {
        $limit = max(1, min(5000, (int)$limit));
        $st = $this->db->prepare("SELECT id, file_path, created_at
                                   FROM import_jobs
                                  WHERE created_at < ?
                               ORDER BY id ASC
                                  LIMIT {$limit}");
        $st->execute([(string)$cutoffUtc]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int)($r['id'] ?? 0),
                'file_path' => isset($r['file_path']) ? (string)$r['file_path'] : null,
                'created_at' => isset($r['created_at']) ? (string)$r['created_at'] : null,
            ];
        }
        return $out;
    }

    /**
     * FIX553: borra jobs por IDs.
     * @param array<int,int> $ids
     * @return array{deleted:int,failed:int,warnings:array<int,string>}
     */
    public function deleteByIds(array $ids): array
    {
        $out = ['deleted'=>0,'failed'=>0,'warnings'=>[]];

        $ids = array_values(array_filter(array_map('intval', $ids), fn($v) => $v > 0));
        $ids = array_values(array_unique($ids));
        if (!$ids) return $out;

        // Borrado por lote (IN)
        $placeholders = implode(',', array_fill(0, count($ids), '?'));
        try {
            $st = $this->db->prepare("DELETE FROM import_jobs WHERE id IN ({$placeholders})");
            $ok = $st->execute($ids);
            if ($ok) {
                $out['deleted'] = (int)$st->rowCount();
            } else {
                $out['failed'] = count($ids);
                $out['warnings'][] = 'No se pudo borrar import_jobs (execute=false)';
            }
        } catch (\Throwable $e) {
            $out['failed'] = count($ids);
            $out['warnings'][] = 'Error al borrar import_jobs: ' . $e->getMessage();
        }

        return $out;
    }

    /** @return array<int, array<string,mixed>> */
    public function listRecent(int $limit = 50): array
    {
        $limit = max(1, min(200, (int)$limit));
        try {
            $st = $this->db->query("SELECT id, job_type, status, revert_status, reverted_at_utc, file_name, file_path, total_rows, ok_rows, err_rows, created_at, finished_at
                                      FROM import_jobs
                                  ORDER BY id DESC
                                     LIMIT {$limit}");
            return $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            // Compat: esquema sin columnas de revert v2.
            $st = $this->db->query("SELECT id, job_type, status, file_name, file_path, total_rows, ok_rows, err_rows, created_at, finished_at
                                      FROM import_jobs
                                  ORDER BY id DESC
                                     LIMIT {$limit}");
            $rows = $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
            foreach ($rows as &$r) {
                if (!array_key_exists('revert_status', $r)) {
                    $r['revert_status'] = 'not_reverted';
                }
                if (!array_key_exists('reverted_at_utc', $r)) {
                    $r['reverted_at_utc'] = null;
                }
            }
            unset($r);
            return $rows;
        }
    }

    public function create(string $jobType, string $fileName, string $filePath): int
    {
        $st = $this->db->prepare("INSERT INTO import_jobs (job_type, status, file_name, file_path, total_rows, ok_rows, err_rows, created_at)
                                  VALUES (?,?,?,?,NULL,NULL,NULL, NOW())");
        $ok = $st->execute([$jobType, 'queued', $fileName, $filePath]);
        return $ok ? (int)$this->db->lastInsertId() : 0;
    }

    public function find(int $id): ?array
    {
        $st = $this->db->prepare("SELECT * FROM import_jobs WHERE id=? LIMIT 1");
        $st->execute([(int)$id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markDryFailed(int $id, int $total, int $ok, int $errors): void
    {
        $this->db->prepare("UPDATE import_jobs
                               SET total_rows=?, ok_rows=?, err_rows=?, status=?, finished_at=NOW()
                             WHERE id=?")
                 ->execute([(int)$total,(int)$ok,(int)$errors,'dry-failed',(int)$id]);
    }

    public function markFinished(int $id, int $total, int $ok, int $errors): void
    {
        $this->db->prepare("UPDATE import_jobs
                               SET total_rows=?, ok_rows=?, err_rows=?, status=?, finished_at=NOW()
                             WHERE id=?")
                 ->execute([(int)$total,(int)$ok,(int)$errors,'finished',(int)$id]);
    }

    public function markFailed(int $id, string $message = ''): void
    {
        // status simple; el detalle queda en errors.json
        $this->db->prepare("UPDATE import_jobs SET status=?, finished_at=NOW() WHERE id=?")
                 ->execute(['failed',(int)$id]);
    }

    /** @param array<string,mixed> $summary */
    public function markReverted(int $id, int $userId, string $revertStatus, array $summary): void
    {
        $id = (int)$id;
        if ($id <= 0) return;

        $allowed = [
            'not_reverted',
            'reverted',
            'reverted_with_warnings',
            'blocked_conflict',
            'blocked_not_last',
        ];

        if (!in_array($revertStatus, $allowed, true)) {
            $revertStatus = 'reverted_with_warnings';
        }

        $json = json_encode($summary, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        // Solo marcamos "reverted_at" si realmente se revirtió.
        if (in_array($revertStatus, ['reverted','reverted_with_warnings'], true)) {
            $this->db->prepare("UPDATE import_jobs
                                   SET revert_status=?, reverted_at_utc=UTC_TIMESTAMP(), reverted_by_user_id=?, revert_summary_json=?
                                 WHERE id=?")
                     ->execute([$revertStatus, (int)$userId, $json, $id]);
            return;
        }

        // Estados bloqueados: persistimos status + summary, pero NO es una reversión.
        $this->db->prepare("UPDATE import_jobs
                               SET revert_status=?, reverted_at_utc=NULL, reverted_by_user_id=?, revert_summary_json=?
                             WHERE id=?")
                 ->execute([$revertStatus, (int)$userId, $json, $id]);
    }
}

