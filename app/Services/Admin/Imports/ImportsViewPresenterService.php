<?php
namespace App\Services\Admin\Imports;

final class ImportsViewPresenterService
{
    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    /**
     * El Controller aún calcula LIFO v2: resolver el pendiente REAL sin
     * modificar ese controlador compartido ni saltear jobs bloqueados.
     */
    /** Suspende acciones visuales cuando existe un commit sin conciliar. */
    private function hasPendingReview(): bool
    {
        if (!function_exists('db')) return false;
        try {
            $st = db()->query("SELECT id FROM import_jobs WHERE status='review_required' LIMIT 1");
            return $st && $st->fetchColumn() !== false;
        } catch (\Throwable $e) {
            return true; // Falla cerrada ante lector no disponible.
        }
    }

    private function latestPending(int $fallback, array $rows = []): int
    {
        if (function_exists('db')) {
            try {
                $st = db()->query("SELECT id FROM import_jobs WHERE status IN ('finished','ok')
                    AND COALESCE(revert_status,'not_reverted') NOT IN ('reverted','reverted_with_warnings')
                    ORDER BY id DESC LIMIT 1");
                return (int)($st->fetchColumn() ?: 0);
            } catch (\Throwable $e) {
                // Cerrar disponibilidad si no se puede asegurar orden.
                return 0;
            }
        }
        if ($rows) {
            foreach ($rows as $r) {
                if (in_array((string)($r['status'] ?? ''), ['finished','ok'], true)
                    && !in_array((string)($r['revert_status'] ?? 'not_reverted'),
                        ['reverted','reverted_with_warnings'], true)) return (int)$r['id'];
            }
        }
        return $fallback;
    }

    private function rollbackSnapshot(string $path, int $jobId): array
    {
        $csv = $path !== '' && is_file($path) ? $path :
            ($path !== '' && function_exists('storage_path') ? (string)storage_path($path) : '');
        $file = $csv !== '' ? preg_replace('/\.csv$/', '_rollback.json', $csv) : null;
        if (!$file || !is_file($file)) return ['valid'=>false,'version'=>null];
        $raw = @file_get_contents($file);
        $snap = $raw ? json_decode($raw, true) : null;
        $version = is_array($snap) ? (int)($snap['version'] ?? 0) : null;
        $valid = is_array($snap)
            && \App\Services\Import\ImportRevertService::isSnapshotSafe($snap)
            && (int)($snap['job_id'] ?? 0) === $jobId;
        return ['valid'=>$valid, 'version'=>$version];
    }

    public function presentIndex(array $rows, int $lastRevertibleId): array
    {
        $lastRevertibleId = $this->latestPending($lastRevertibleId, $rows);
        $reviewPending = $this->hasPendingReview();
        $prepared = [];
        foreach ($rows as $r) {
            if (!is_array($r)) {
                continue;
            }

            $status = (string)($r['status'] ?? '');
            $badge = 'badge-pill badge-neutral';
            $label = $status;
            if ($status === 'finished') {
                $badge = 'badge-pill badge-success';
                $label = 'OK';
            }
            if ($status === 'dry-failed' || $status === 'failed') {
                $badge = 'badge-pill badge-danger';
                $label = 'Con errores';
            }
            if ($status === 'processing') {
                $badge = 'badge-pill badge-warning';
                $label = 'Procesando';
            }
            if ($status === 'review_required') {
                $badge = 'av3-ui-badge--warning';
                $label = 'Revisión requerida';
            }

            $rs = (string)($r['revert_status'] ?? 'not_reverted');
            $rollbackDone = in_array($rs, ['reverted', 'reverted_with_warnings'], true);
            $blocked = in_array($rs, ['blocked_not_last', 'blocked_conflict'], true);
            if ($rollbackDone) {
                $label = $rs === 'reverted' ? 'Revertida' : 'Revertida con advertencias';
            } elseif ($blocked) {
                $label = 'Reversión bloqueada';
            }

            $canRollback = false;
            $legacyRollback = false;
            if (in_array($status, ['finished','ok'], true) && !$rollbackDone && $rs === 'not_reverted') {
                $check=$this->rollbackSnapshot((string)($r['file_path'] ?? ''),(int)($r['id'] ?? 0));
                $canRollback = (bool)$check['valid'];
                $legacyRollback = !$canRollback;
            }

            $id = (int)($r['id'] ?? 0);
            $rollbackUi = [
                'kind' => 'none',
                'label' => '',
            ];
            if ($canRollback && $reviewPending) {
                $rollbackUi = ['kind' => 'blocked', 'label' => 'Reversión suspendida: revisión pendiente'];
            } elseif ($canRollback && $id === $lastRevertibleId) {
                $rollbackUi = ['kind' => 'can_revert_last', 'label' => 'Revertir'];
            } elseif ($canRollback && $id !== $lastRevertibleId) {
                $rollbackUi = ['kind' => 'can_revert_non_last', 'label' => 'Solo ultimo import'];
            } elseif ($rollbackDone) {
                $rollbackUi = [
                    'kind' => 'done',
                    'label' => $rs === 'reverted_with_warnings' ? 'Revertida (warn)' : 'Revertida',
                ];
            } elseif ($blocked) {
                $rollbackUi = [
                    'kind' => 'blocked',
                    'label' => $rs === 'blocked_conflict' ? 'Revert bloqueado (conflicto)' : 'Revert bloqueado',
                ];
            } elseif ($legacyRollback) {
                $rollbackUi = ['kind' => 'legacy', 'label' => 'Revisión manual necesaria'];
            }

            $prepared[] = [
                'id' => $id,
                'file_name' => (string)($r['file_name'] ?? ''),
                'status_badge' => $badge,
                'status_label' => $label,
                'ok_rows' => (int)($r['ok_rows'] ?? 0),
                'err_rows' => (int)($r['err_rows'] ?? 0),
                'created_at' => (string)($r['created_at'] ?? ''),
                'detail_url' => url('/admin/imports/show?id=' . $id),
                'rollback_ui' => $rollbackUi,
            ];
        }

        return [
            'history_rows' => $prepared,
            'lastRevertibleId' => $lastRevertibleId,
        ];
    }

    /**
     * @param array<string,mixed>|null $job
     * @param array<string,mixed>|null $summary
     * @return array<string,mixed>
     */
    public function presentShow(?array $job, ?array $summary, int $lastRevertibleId): array
    {
        $job = is_array($job) ? $job : null;
        $summary = is_array($summary) ? $summary : null;

        $lastRevertibleId = $this->latestPending($lastRevertibleId);
        $canRollback = false;
        $rollbackFileOk = false;
        $rollbackVersion = null;
        $revertStatus = $job ? (string)($job['revert_status'] ?? 'not_reverted') : 'not_reverted';

        if ($job && in_array((string)($job['status'] ?? ''), ['finished', 'ok'], true)) {
            $check = $this->rollbackSnapshot((string)($job['file_path'] ?? ''), (int)($job['id'] ?? 0));
            $rollbackVersion = $check['version'];
            $rollbackFileOk = $check['valid'];

            if ($rollbackFileOk && !$this->hasPendingReview() && $revertStatus === 'not_reverted' && (int)($job['id'] ?? 0) === $lastRevertibleId) {
                $canRollback = true;
            }
        }

        $revertSummary = null;
        if ($job) {
            $sumRaw = $job['revert_summary_json'] ?? null;
            if (is_string($sumRaw) && trim($sumRaw) !== '') {
                $decoded = json_decode($sumRaw, true);
                if (is_array($decoded)) {
                    $revertSummary = $decoded;
                }
            }
        }

        $statusKey = $job ? (string)($job['status'] ?? '') : '';
        $statusBadge = 'badge-pill badge-neutral';
        if ($statusKey === 'finished') {
            $statusBadge = 'badge-pill badge-success';
        } elseif ($statusKey === 'dry-failed' || $statusKey === 'failed') {
            $statusBadge = 'badge-pill badge-danger';
        } elseif ($statusKey === 'review_required') {
            $statusBadge = 'av3-ui-badge--warning';
        }

        $totalRows = (int)($job['total_rows'] ?? 0);
        $okRows = (int)($job['ok_rows'] ?? 0);
        $errRows = (int)($job['err_rows'] ?? 0);
        if (is_array($summary)) {
            $totalRows = (int)($summary['total'] ?? $totalRows);
            $okRows = (int)($summary['ok'] ?? $okRows);
            $errRows = (int)($summary['err'] ?? $errRows);
        }

        $plan = is_array($summary) ? (array)($summary['plan'] ?? []) : [];
        $rowsList = is_array($summary) ? (array)($summary['rows'] ?? []) : [];
        $warnList = is_array($summary) ? (array)($summary['warn_list'] ?? []) : [];
        $errList = is_array($summary) ? (array)($summary['errors'] ?? []) : [];
        $planDetails = is_array($summary) ? (array)($summary['plan_details'] ?? []) : [];
        $optUsed = is_array($summary) ? (array)($summary['options'] ?? []) : [];

        $pdBrands = array_values(array_filter((array)($planDetails['brands'] ?? []), static fn($v) => trim((string)$v) !== ''));
        $pdCats = array_values(array_filter((array)($planDetails['categories'] ?? []), static fn($v) => trim((string)$v) !== ''));
        $pdSup = array_values(array_filter((array)($planDetails['suppliers'] ?? []), static fn($v) => trim((string)$v) !== ''));
        $pdMaxChips = 12;

        $optBadges = [
            'update_internal_code' => 'Codigo interno',
            'update_name' => 'Nombre / titulo',
            'update_descriptions' => 'Descripciones (corta/larga)',
            'update_category' => 'Categoria',
            'update_brand' => 'Marca',
            'update_supplier' => 'Proveedor',
            'update_cost' => 'Costo',
            'update_price' => 'Precio',
            'update_list_price' => 'Precio lista',
            'update_iva' => 'IVA (solo producto padre)',
            'update_publish' => 'Publicacion (activo)',
            'update_stock' => 'Stock + umbral',
            'update_attributes' => 'Atributos de variantes',
            'allow_parent_from_variants' => 'Padre implicito desde variantes',
            'create_missing_brands' => 'Crear marcas si no existen',
            'create_missing_categories' => 'Crear categorias si no existen',
            'create_missing_suppliers' => 'Crear proveedores si no existen',
        ];
        $optUsedLabels = [];
        foreach ($optBadges as $k => $label) {
            if (!empty($optUsed[$k])) {
                $optUsedLabels[] = $label;
            }
        }

        $rowsPrepared = [];
        foreach ($rowsList as $rr) {
            if (!is_array($rr)) {
                continue;
            }
            $lvl = strtolower(trim((string)($rr['level'] ?? 'ok')));
            if ($lvl === 'warn') { $lvl = 'warning'; }
            $rowClass = '';
            if ($lvl === 'error') {
                $rowClass = 'bg-red-50/40';
            } elseif ($lvl === 'warning') {
                $rowClass = '';
            }

            $resultBadge = 'badge-pill badge-success';
            $resultLabel = 'OK';
            if ($lvl === 'warning') {
                $resultBadge = 'av3-ui-badge--warning';
                $resultLabel = 'Aviso';
            } elseif ($lvl === 'error') {
                $resultBadge = 'badge-pill badge-danger';
                $resultLabel = 'Error';
            }

            $accionTxt = (string)($rr['accion'] ?? '');
            // I05: un diagnóstico positivo no implica una escritura confirmada.
            // En jobs cancelados las acciones son PREVISTAS, no ejecutadas.
            if (in_array($statusKey, ['failed', 'dry-failed'], true) && $lvl !== 'error') {
                $resultLabel = $lvl === 'warning' ? 'Aviso · no aplicada' : 'Válida · no aplicada';
                $resultBadge = 'av3-ui-badge--neutral';
                if ($accionTxt !== '') {
                    $accionTxt = 'Prevista: '.$accionTxt;
                }
            }
            if ($statusKey === 'review_required' && $lvl !== 'error') {
                $resultLabel = 'Confirmación pendiente';
                $resultBadge = 'av3-ui-badge--warning';
            }
            $actionBadge = 'badge-pill badge-neutral';
            if (stripos($accionTxt, 'Crear') !== false) {
                $actionBadge = 'badge-pill badge-success';
            }

            $cambios = [];
            foreach ((array)($rr['notas'] ?? []) as $n) {
                $note = (string)$n;
                if (preg_match('/^(.+?):\s*(.+?)\s*->\s*(.+)$/u', $note, $mm)) {
                    $cambios[] = [
                        'type' => 'diff',
                        'k' => trim((string)($mm[1] ?? '')),
                        'from' => trim((string)($mm[2] ?? '')),
                        'to' => trim((string)($mm[3] ?? '')),
                    ];
                } else {
                    // Lectura de metadatos ya emitidos por el motor: no recalcula importes.
                    $segments = preg_split('/\\s*·\\s*/u', $note) ?: [];
                    $metaItems = [];
                    $metaLabels = [
                        'costo_sin_iva' => 'Costo neto', 'iva' => 'IVA',
                        'costo_final_ars' => 'Costo con IVA', 'precio_ars' => 'Precio',
                        'precio_lista_ars' => 'Precio de lista',
                    ];
                    foreach ($segments as $segment) {
                        if (!preg_match('/^([a-z_]+):\\s*(.+)$/iu', trim($segment), $parts)) {
                            $metaItems = [];
                            break;
                        }
                        $key = trim((string)$parts[1]);
                        $metaItems[] = ['label' => $metaLabels[$key] ?? str_replace('_', ' ', $key), 'value' => trim((string)$parts[2])];
                    }
                    $cambios[] = count($metaItems) > 1
                        ? ['type' => 'metadata', 'items' => $metaItems]
                        : ['type' => 'note', 'raw' => $note];
                }
            }

            $isNoop = (stripos($accionTxt, 'Sin cambios') !== false)
                || (stripos($accionTxt, 'No cambiar') !== false)
                || (stripos($accionTxt, 'noop') !== false);

            $rowsPrepared[] = [
                'row' => (int)($rr['row'] ?? 0),
                'level' => $lvl,
                'tipo_fila' => (string)($rr['tipo_fila'] ?? ''),
                'sku' => (string)($rr['sku'] ?? ''),
                'sku_padre' => (string)($rr['sku_padre'] ?? ''),
                'entidad' => (string)($rr['entidad'] ?? ''),
                'row_class' => $rowClass,
                'result_badge' => $resultBadge,
                'result_label' => $resultLabel,
                'action_badge' => $actionBadge,
                'accion' => $accionTxt,
                'is_noop' => $isNoop,
                'cambios' => $cambios,
            ];
        }

        $hasDiagnostics = is_array($summary) && is_array($summary['rows'] ?? null)
            && is_array($summary['plan'] ?? null);
        $isSuccess = ($statusKey === 'finished' && $errRows === 0);
        $hasCatalogLists = (
            (int)($plan['create_brands'] ?? 0) > 0
            || (int)($plan['create_categories'] ?? 0) > 0
            || (int)($plan['create_suppliers'] ?? 0) > 0
        );

        return [
            'canRollback' => $canRollback,
            'rollbackFileOk' => $rollbackFileOk,
            'rollbackVersion' => $rollbackVersion,
            'revertStatus' => $revertStatus,
            'revertSummary' => $revertSummary,
            'statusKey' => $statusKey === 'review_required' ? 'Revisión requerida' : $statusKey,
            'statusBadge' => $statusBadge,
            'totalRows' => $totalRows,
            'okRows' => $okRows,
            'errRows' => $errRows,
            'lastRevertibleId' => $lastRevertibleId,
            'isSuccess' => $isSuccess,
            'hasDiagnostics' => $hasDiagnostics,
            'plan' => $plan,
            'warnList' => $warnList,
            'errList' => $errList,
            'rowsPrepared' => $rowsPrepared,
            'rowsCount' => count($rowsPrepared),
            'plan_details' => [
                'brands_sample' => array_slice($pdBrands, 0, $pdMaxChips),
                'categories_sample' => array_slice($pdCats, 0, $pdMaxChips),
                'suppliers_sample' => array_slice($pdSup, 0, $pdMaxChips),
                'brands_more' => max(0, count($pdBrands) - min(count($pdBrands), $pdMaxChips)),
                'categories_more' => max(0, count($pdCats) - min(count($pdCats), $pdMaxChips)),
                'suppliers_more' => max(0, count($pdSup) - min(count($pdSup), $pdMaxChips)),
                'brands_all' => $pdBrands,
                'categories_all' => $pdCats,
                'suppliers_all' => $pdSup,
            ],
            'opt_used_labels' => $optUsedLabels,
            'hasCatalogLists' => $hasCatalogLists,
        ];
    }
}
