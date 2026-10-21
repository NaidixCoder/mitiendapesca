<?php
namespace App\Services\Import;

use App\Repositories\Imports\ImportJobRepository;

/**
 * FIX553: Retención dura de importaciones.
 *
 * Política:
 * - Importaciones con antigüedad > N días (default 183 â‰ˆ 6 meses): borrar archivos y DB.
 * - Se borra: CSV + _rollback.json + _errors.json (si existen).
 */
final class ImportPurgeService
{
    public function __construct(private ImportJobRepository $jobs) {}

    /**
     * @return array{cutoff_utc:string, scanned:int, purged:int, file_deleted:int, file_missing:int, file_failed:int, db_deleted:int, db_failed:int, warnings:array<int,string>}
     */
    public function purgeOlderThanDays(int $days = 183, int $batchLimit = 500): array
    {
        $days = max(1, min(3650, (int)$days));
        $batchLimit = max(1, min(5000, (int)$batchLimit));

        $cutoffUtc = gmdate('Y-m-d H:i:s', time() - ($days * 86400));

        $rows = $this->jobs->listOlderThanUtc($cutoffUtc, $batchLimit);

        $out = [
            'cutoff_utc'    => $cutoffUtc,
            'scanned'       => count($rows),
            'purged'        => 0,
            'file_deleted'  => 0,
            'file_missing'  => 0,
            'file_failed'   => 0,
            'db_deleted'    => 0,
            'db_failed'     => 0,
            'warnings'      => [],
        ];

        if (!$rows) return $out;

        $idsToDelete = [];
        foreach ($rows as $r) {
            $id = (int)($r['id'] ?? 0);
            $filePath = (string)($r['file_path'] ?? '');
            if ($id <= 0) continue;

            $fileStats = $this->deleteJobFiles($filePath);
            $out['file_deleted'] += $fileStats['deleted'];
            $out['file_missing'] += $fileStats['missing'];
            $out['file_failed']  += $fileStats['failed'];
            foreach ($fileStats['warnings'] as $w) $out['warnings'][] = "job#$id: $w";

            // DB: borramos el job siempre (política dura) aunque falten archivos.
            $idsToDelete[] = $id;
        }

        if ($idsToDelete) {
            $dbRes = $this->jobs->deleteByIds($idsToDelete);
            $out['db_deleted'] = $dbRes['deleted'];
            $out['db_failed']  = $dbRes['failed'];
            foreach ($dbRes['warnings'] as $w) $out['warnings'][] = $w;
        }

        $out['purged'] = $out['db_deleted'];
        return $out;
    }

    /**
     * @return array{deleted:int,missing:int,failed:int,warnings:array<int,string>}
     */
    private function deleteJobFiles(string $filePath): array
    {
        $out = ['deleted'=>0,'missing'=>0,'failed'=>0,'warnings'=>[]];

        $candidates = [];
        if ($filePath !== '') $candidates[] = $filePath;
        if (str_ends_with($filePath, '.csv')) {
            $candidates[] = preg_replace('/\.csv$/', '_rollback.json', $filePath) ?: '';
            $candidates[] = preg_replace('/\.csv$/', '_errors.json', $filePath) ?: '';
        }

        // Normalizamos y solo permitimos borrar dentro de storage/imports
        $importsDir = realpath(storage_path('imports'));
        if (!$importsDir) {
            $out['warnings'][] = 'storage/imports no existe (no se borraron archivos)';
            return $out;
        }
        $importsDir = rtrim($importsDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        foreach ($candidates as $p) {
            if (!$p) continue;

            // Si viene relativo, lo interpretamos relativo a BASE_PATH
            $abs = $p;
            if (!str_starts_with($abs, DIRECTORY_SEPARATOR) && !preg_match('~^[A-Za-z]:\\\\~', $abs)) {
                $abs = base_path($abs);
            }

            $real = realpath($abs);
            if ($real === false) {
                $out['missing']++;
                continue;
            }

            // Seguridad: solo dentro de storage/imports
            if (!str_starts_with($real . DIRECTORY_SEPARATOR, $importsDir) && !str_starts_with($real, rtrim($importsDir, DIRECTORY_SEPARATOR))) {
                $out['warnings'][] = 'archivo fuera de storage/imports (skip): ' . $real;
                continue;
            }

            if (@unlink($real)) {
                $out['deleted']++;
            } else {
                $out['failed']++;
                $out['warnings'][] = 'no se pudo borrar: ' . $real;
            }
        }

        return $out;
    }
}

