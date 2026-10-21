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
    public function revertJob(int $jobId, int $userId): array
    {
        $jobId = (int)$jobId;
        if ($jobId <= 0) return ['ok'=>false,'error'=>'Importación inválida.'];

        $job = $this->jobs->find($jobId);
        if (!$job) return ['ok'=>false,'error'=>'Importación no encontrada.'];

        $status = (string)($job['status'] ?? '');
        if (!in_array($status, ['finished','ok'], true)) {
            return ['ok'=>false,'error'=>'Solo se puede revertir una importación que se haya ejecutado correctamente.'];
        }

        $revertStatus = (string)($job['revert_status'] ?? 'not_reverted');
        if (in_array($revertStatus, ['reverted','reverted_with_warnings'], true)) {
            return ['ok'=>true,'summary'=>['already_reverted'=>true]];
        }

        // Guardrail LIFO: solo el ÚLTIMO import revertible.
        $lastId = $this->computeLastRevertibleJobId();
        if ($lastId > 0 && $lastId !== $jobId) {
            $sum = [
                'job_id' => $jobId,
                'blocked' => 'not_last',
                'last_revertible_job_id' => $lastId,
                'message' => 'No disponible: solo se puede revertir el último import.'
            ];
            $this->jobs->markReverted($jobId, $userId, 'blocked_not_last', $sum);
            Audit::log('import_revert_blocked','import_job',$jobId,$sum + ['reason'=>'not_last']);
            return ['ok'=>false,'error'=>'No disponible: solo se puede revertir el último import.'];
        }

        $rbAbs = $this->resolveRollbackPath((string)($job['file_path'] ?? ''), (string)($job['file_name'] ?? ''));
        if (!$rbAbs || !is_file($rbAbs)) {
            return ['ok'=>false,'error'=>'No se encontró el punto de restauración para esta importación.'];
        }

        $raw = @file_get_contents($rbAbs);
        $json = $raw ? json_decode($raw, true) : null;
        if (!is_array($json)) {
            return ['ok'=>false,'error'=>'Archivo de rollback inválido.'];
        }

        $version = (int)($json['version'] ?? 0);
        if ($version !== 2) {
            return ['ok'=>false,'error'=>'Este rollback es legacy (v1) y no es compatible con Revert v2. Reimportá para regenerar el punto de restauración.'];
        }

        $ops = $json['ops'] ?? null;
        if (!is_array($ops) || !$ops) {
            return ['ok'=>false,'error'=>'No hay operaciones para revertir.'];
        }

        $summary = [
            'job_id' => $jobId,
            'rollback_file' => basename($rbAbs),
            'imported_at' => substr((string)($job['finished_at'] ?? $job['created_at'] ?? ''), 0, 19),
            'restored' => 0,
            'deleted' => 0,
            'warnings' => [],
            'counts' => [
                'restore_ok' => 0,
                'restore_fail' => 0,
                'delete_ok' => 0,
                'delete_fail' => 0,
            ],
        ];

        // Phase A: RESTORE updates
        // Phase B: DELETE creates (en orden inverso)
        $updates = [];
        $creates = [];
        foreach ($ops as $op) {
            if (!is_array($op)) continue;
            $entity = (string)($op['entity'] ?? '');
            $action = (string)($op['action'] ?? '');
            $pk = is_array($op['pk'] ?? null) ? (array)$op['pk'] : [];
            $before = is_array($op['before'] ?? null) ? (array)$op['before'] : [];
            if ($entity === '' || $action === '' || empty($pk['id'])) continue;

            if ($action === 'update') {
                $updates[] = ['entity'=>$entity,'pk'=>$pk,'before'=>$before];
            } elseif ($action === 'create') {
                $creates[] = ['entity'=>$entity,'pk'=>$pk];
            }
        }

        if (!$updates && !$creates) {
            return ['ok'=>false,'error'=>'Este rollback no contiene acciones aplicables.'];
        }

        // Guardrail conflictos (manual edits) sobre entidades sensibles (product/variant)
        $importedAt = (string)($summary['imported_at'] ?? '');
        if ($importedAt !== '') {
            $conflicts = $this->detectConflicts($updates, $creates, $importedAt);
            if ($conflicts) {
                $blocked = [
                    'job_id' => $jobId,
                    'blocked' => 'conflict',
                    'imported_at' => $importedAt,
                    'conflicts' => $conflicts,
                ];
                $this->jobs->markReverted($jobId, $userId, 'blocked_conflict', $blocked);
                Audit::log('import_revert_blocked','import_job',$jobId,$blocked + ['reason'=>'conflict']);
                return ['ok'=>false,'error'=>'No se puede revertir: hubo ediciones manuales posteriores al import.'];
            }
        }

        try {
            $this->db->beginTransaction();

            foreach ($updates as $u) {
                $id = (int)($u['pk']['id'] ?? 0);
                if ($id > 0 && !$this->revertRepo->exists((string)$u['entity'], $id)) {
                    $summary['counts']['restore_fail']++;
                    $summary['warnings'][] = [
                        'type' => 'missing_target',
                        'entity' => $u['entity'],
                        'pk' => $u['pk'],
                    ];
                    continue;
                }
                $ok = $this->revertRepo->restore((string)$u['entity'], (array)$u['pk'], (array)$u['before']);
                if ($ok) {
                    $summary['restored']++;
                    $summary['counts']['restore_ok']++;
                } else {
                    $summary['counts']['restore_fail']++;
                    $summary['warnings'][] = [
                        'type' => 'restore_failed',
                        'entity' => $u['entity'],
                        'pk' => $u['pk'],
                    ];
                }
            }

            // Deletes en orden inverso
            for ($i = count($creates) - 1; $i >= 0; $i--) {
                $c = $creates[$i];
                try {
                    $res = $this->revertRepo->deleteResult((string)$c['entity'], (array)$c['pk']);
                    if (!empty($res['ok'])) {
                        $summary['deleted']++;
                        $summary['counts']['delete_ok']++;
                    } else {
                        $summary['counts']['delete_fail']++;
                        $summary['warnings'][] = [
                            'type' => !empty($res['fk_block']) ? 'delete_fk_blocked' : 'delete_failed',
                            'entity' => $c['entity'],
                            'pk' => $c['pk'],
                            'deactivated' => (bool)($res['deactivated'] ?? false),
                        ];
                    }
                } catch (Throwable $e) {
                    $summary['counts']['delete_fail']++;
                    $summary['warnings'][] = [
                        'type' => 'delete_exception',
                        'entity' => $c['entity'],
                        'pk' => $c['pk'],
                        'error' => $e->getMessage(),
                    ];
                    // No abortamos: seguimos con el resto.
                }
            }

            $this->db->commit();
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['ok'=>false,'error'=>'Rollback falló: ' . $e->getMessage()];
        }

        $finalStatus = !empty($summary['warnings']) ? 'reverted_with_warnings' : 'reverted';
        $this->jobs->markReverted($jobId, $userId, $finalStatus, $summary);

        // Auditoría (evento humano + metadata operativa)
        $meta = [
            'job_id' => $jobId,
            'file_name' => (string)($job['file_name'] ?? ''),
            'restored' => (int)($summary['restored'] ?? 0),
            'deleted' => (int)($summary['deleted'] ?? 0),
            'warnings_count' => is_array($summary['warnings'] ?? null) ? count($summary['warnings']) : 0,
            'warnings' => $summary['warnings'] ?? [],
        ];
        $actionKey = ($finalStatus === 'reverted_with_warnings') ? 'import_reverted_warn' : 'import_reverted';
        Audit::log($actionKey,'import_job',$jobId,$meta);

        return ['ok'=>true,'summary'=>$summary];
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
