<?php
namespace App\Services\Import;

use App\Repositories\Imports\ImportJobRepository;
use App\Repositories\Imports\ImportRevertRepository;
use App\Services\Audit\Audit;
use PDO;
use Throwable;

final class ImportRevertService
{
    public function __construct(
        private PDO $db,
        private ImportJobRepository $jobs,
        private ImportRevertRepository $revertRepo,
    ) {}

    public static function fromDb(PDO $db): self
    {
        return new self(
            $db,
            new ImportJobRepository($db),
            new ImportRevertRepository($db),
        );
    }

    /**
     * @return array{ok:bool,error?:string,summary?:array<string,mixed>}
     */
    /**
     * Solo un snapshot v3 COMPLETO y con after_sha256 de cada operación
     * habilita reversión automática. V1/V2 quedan para recuperación manual.
     */
    public static function isSnapshotSafe(?array $snapshot): bool
    {
        if (!is_array($snapshot) || ($snapshot['version'] ?? null) !== 3
            || ($snapshot['complete'] ?? null) !== true || !is_array($snapshot['ops'] ?? null)
            || !$snapshot['ops']) return false;

        $seen = [];
        foreach ($snapshot['ops'] as $op) {
            if (!is_array($op)) return false;
            $entity = $op['entity'] ?? null;
            $action = $op['action'] ?? null;
            $id = $op['pk']['id'] ?? null;
            $hash = $op['after_sha256'] ?? null;
            if (!is_string($entity) || $entity === '' || !in_array($action, ['update','create'], true)
                || !is_numeric($id) || (int)$id <= 0
                || !is_string($hash) || !preg_match('/^[a-f0-9]{64}$/D', $hash)) return false;
            if ($action === 'update' && (!is_array($op['before'] ?? null) || !$op['before'])) return false;
            if ($action === 'create' && (!is_string($op['after_rel_sha256'] ?? null)
                || !preg_match('/^[a-f0-9]{64}$/D', (string)$op['after_rel_sha256']))) return false;
            $key = $entity.':'.(int)$id;
            if (isset($seen[$key])) return false;
            $seen[$key] = true;
        }
        return true;
    }

    public function revertJob(int $jobId, int $userId): array
    {
        if ($jobId <= 0) return ['ok'=>false,'error'=>'Importación inválida.'];

        $job = $this->jobs->find($jobId);
        if (!$job) return ['ok'=>false,'error'=>'Importación no encontrada.'];
        $rbAbs = $this->resolveRollbackPath((string)($job['file_path'] ?? ''), (string)($job['file_name'] ?? ''));
        $raw = $rbAbs && is_file($rbAbs) ? @file_get_contents($rbAbs) : false;
        $snapshot = is_string($raw) && $raw !== '' ? json_decode($raw, true) : null;
        $snapshotSafe = self::isSnapshotSafe(is_array($snapshot) ? $snapshot : null)
            && (int)($snapshot['job_id'] ?? 0) === $jobId;

        // Sin snapshot verificable, NO restaurar v2 ni v1, ni completar por timestamps.
        $summary = [
            'job_id' => $jobId,
            'restored' => 0,
            'deleted' => 0,
            'warnings' => [],
            'conflicts' => [],
        ];
        $blockError = null;
        $blockReason = null;
        $success = false;

        try {
            // En InnoDB RR, el barrido PRIMARY FOR UPDATE toma locks de filas
            // y gaps: una inserción o cambio concurrente a review_required
            // no puede adelantarse entre el guard y el rollback comercial.
            // SET TRANSACTION solo afecta esta operación, no la sesión global.
            if ($this->db->exec('SET TRANSACTION ISOLATION LEVEL REPEATABLE READ') === false) {
                throw new \RuntimeException('No se pudo asegurar el aislamiento del rollback');
            }
            $this->db->beginTransaction();
            // PRIMERO barrera de conciliación: adquisición consistente DESC
            // antes de lockLatestPendingJobId y del job objetivo.
            $reviews = $this->revertRepo->lockPendingReconciliationJobIds();
            $lastId = $this->revertRepo->lockLatestPendingJobId();
            $lockedJob = $this->revertRepo->lockJob($jobId);
            if (!$lockedJob) throw new \RuntimeException('Importación desaparecida.');
            $lockedStatus = (string)($lockedJob['status'] ?? '');
            if ($reviews) {
                $blockReason = 'review_required_pending';
                $blockError = 'Reversión suspendida: existen importaciones con confirmación SQL pendiente de conciliación supervisada.';
                // No modificar el estado del objetivo; solo auditoría del intento.
                $summary['pending_review_job_ids'] = array_slice($reviews, 0, 10);
                $summary['pending_review_count'] = count($reviews);
            } elseif (!in_array($lockedStatus, ['finished','ok'], true)) {
                $blockReason = 'job_not_eligible';
                $blockError = 'Solo se puede revertir una importación ejecutada correctamente.';
            } elseif (in_array((string)($lockedJob['revert_status'] ?? ''), ['reverted','reverted_with_warnings'], true)) {
                $this->db->rollBack();
                return ['ok'=>true,'summary'=>['already_reverted'=>true]];
            } elseif ($lastId !== $jobId) {
                $blockReason = 'not_last';
                $blockError = 'Solo puede revertirse la última importación no revertida (LIFO).';
                $summary['last_revertible_job_id'] = $lastId;
            } elseif (!$snapshotSafe) {
                $blockReason = 'unverifiable_snapshot';
                $blockError = 'Punto de restauración ausente, anterior o incompleto: no permite comprobar los cambios posteriores. Se requiere recuperación manual.';
            } else {
                // Check ALL entities before the first write; deterministic lock order.
                $ops = $snapshot['ops'];
                $sorted = $ops;
                usort($sorted, static fn($a, $b) => strcmp(
                    (string)$a['entity'].':'.sprintf('%020d',(int)$a['pk']['id']),
                    (string)$b['entity'].':'.sprintf('%020d',(int)$b['pk']['id'])
                ));
                foreach ($sorted as $op) {
                    $ent = (string)$op['entity'];
                    $id = (int)$op['pk']['id'];
                    $fingerprint = $this->revertRepo->fingerprint($ent, $id, true);
                    if ($fingerprint === null || !hash_equals((string)$op['after_sha256'], $fingerprint)) {
                        $summary['conflicts'][] = ['entity'=>$ent,'id'=>$id,'reason'=>$fingerprint===null?'missing_row':'state_changed'];
                    }
                    if ($fingerprint !== null && $op['action'] === 'create') {
                        $related = $this->revertRepo->dependentFingerprint($ent, $id, true);
                        if (!hash_equals((string)$op['after_rel_sha256'], $related)) {
                            $summary['conflicts'][] = ['entity'=>$ent,'id'=>$id,'reason'=>'related_records_changed'];
                        }
                    }
                }
                if ($summary['conflicts']) {
                    $blockReason = 'state_conflict';
                    $blockError = 'Reversión bloqueada: un registro fue modificado o no puede verificarse desde la importación.';
                } else {
                    // Restore BEFORE values then remove creates in reverse order.
                    // Any failure aborts the entire transaction, never best-effort writes.
                    foreach ($ops as $op) {
                        if ($op['action'] !== 'update') continue;
                        if (!$this->revertRepo->restore((string)$op['entity'], (array)$op['pk'], (array)$op['before'])) {
                            throw new \RuntimeException('Falló la restauración de '.$op['entity'].' #'.$op['pk']['id']);
                        }
                        $summary['restored']++;
                    }
                    for ($i=count($ops)-1; $i>=0; $i--) {
                        $op=$ops[$i];
                        if ($op['action'] !== 'create') continue;
                        $out=$this->revertRepo->deleteResult((string)$op['entity'], (array)$op['pk']);
                        if (empty($out['ok']) || !empty($out['fk_block'])) {
                            throw new \RuntimeException('No se pudo eliminar '.$op['entity'].' #'.$op['pk']['id'].'. No se aplicaron cambios.');
                        }
                        $summary['deleted']++;
                    }
                    $this->jobs->markReverted($jobId, $userId, 'reverted', $summary);
                    $success = true;
                }
            }

            if ($blockReason !== null) {
                $summary['blocked'] = $blockReason;
                if (!in_array($blockReason, ['review_required_pending', 'job_not_eligible'], true)) {
                    $this->jobs->markReverted($jobId, $userId,
                        $blockReason === 'not_last' ? 'blocked_not_last' : 'blocked_conflict', $summary);
                }
            }
            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['ok'=>false,'error'=>'Reversión cancelada sin cambios: '.$e->getMessage()];
        }

        if ($success) {
            Audit::log('import_reverted','import_job',$jobId, $summary);
            return ['ok'=>true,'summary'=>$summary];
        }
        Audit::log('import_revert_blocked','import_job',$jobId,$summary);
        return ['ok'=>false,'error'=>$blockError ?? 'Reversión no disponible.'];
    }

    private function resolveRollbackPath(string $filePath, string $fileName): ?string
    {
        $filePath = (string)$filePath;
        if ($filePath !== '' && is_file($filePath)) {
            $csvAbs = $filePath;
        } else {
            // file_path puede venir relativo (imports/...) o solo filename
            $csvAbs = storage_path($filePath !== '' ? $filePath : ('imports/' . $fileName));
        }

        if (!$csvAbs || !is_file($csvAbs)) {
            return null;
        }

        $rbAbs = preg_replace('/\.csv$/', '_rollback.json', $csvAbs);
        return $rbAbs ?: null;
    }

    /**
     * Solo el último import revertible (LIFO).
     * Criterio: status OK + revert_status not_reverted + rollback v2 existe.
     */
    private function computeLastRevertibleJobId(): int
    {
        $rows = $this->jobs->listRecent(50);
        foreach ($rows as $r) {
            $id = (int)($r['id'] ?? 0);
            $st = (string)($r['status'] ?? '');
            $rs = (string)($r['revert_status'] ?? 'not_reverted');
            if ($id <= 0) continue;
            if (!in_array($st, ['finished','ok'], true)) continue;
            if ($rs !== 'not_reverted') continue;

            $rbAbs = $this->resolveRollbackPath((string)($r['file_path'] ?? ''), (string)($r['file_name'] ?? ''));
            if (!$rbAbs || !is_file($rbAbs)) continue;
            $raw = @file_get_contents($rbAbs);
            $j = $raw ? json_decode($raw, true) : null;
            $ver = is_array($j) ? (int)($j['version'] ?? 0) : 0;
            if ($ver !== 2) continue;

            return $id;
        }
        return 0;
    }

    /**
     * Conflicto: si un producto/variante tocado por el import fue editado después (updated_at > imported_at).
     * @param array<int,array{entity:string,pk:array,before:array}> $updates
     * @param array<int,array{entity:string,pk:array}> $creates
     * @return array<int,array{entity:string,id:int,updated_at:string}>
     */
    private function detectConflicts(array $updates, array $creates, string $importedAt): array
    {
        $importedAt = substr((string)$importedAt, 0, 19);
        if ($importedAt === '') return [];

        $ids = [];
        foreach ($updates as $u) {
            $ent = (string)($u['entity'] ?? '');
            $id = (int)($u['pk']['id'] ?? 0);
            if ($id <= 0) continue;
            if (!in_array($ent, ['product','variant'], true)) continue;
            $ids[$ent][$id] = true;
        }
        foreach ($creates as $c) {
            $ent = (string)($c['entity'] ?? '');
            $id = (int)($c['pk']['id'] ?? 0);
            if ($id <= 0) continue;
            if (!in_array($ent, ['product','variant'], true)) continue;
            $ids[$ent][$id] = true;
        }

        $conflicts = [];
        foreach ($ids as $entity => $map) {
            foreach (array_keys($map) as $id) {
                $updatedAt = $this->revertRepo->getUpdatedAt((string)$entity, (int)$id);
                if (!$updatedAt) continue;
                // Comparación lexicográfica válida en formato Y-m-d H:i:s
                if ($updatedAt > $importedAt) {
                    $conflicts[] = ['entity'=>(string)$entity,'id'=>(int)$id,'updated_at'=>$updatedAt];
                }
            }
        }

        return $conflicts;
    }
}
