<?php
namespace App\Services\Admin\Imports;

final class ImportsViewPresenterService
{
    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    public function presentIndex(array $rows, int $lastRevertibleId): array
    {
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

            $rs = (string)($r['revert_status'] ?? 'not_reverted');
            $rollbackDone = in_array($rs, ['reverted', 'reverted_with_warnings'], true);
            $blocked = in_array($rs, ['blocked_not_last', 'blocked_conflict'], true);

            $canRollback = false;
            $legacyRollback = false;
            if (in_array($status, ['finished', 'ok'], true) && !$rollbackDone && $rs === 'not_reverted') {
                $csvPath = (string)($r['file_path'] ?? '');
                $csvAbs = '';
                if ($csvPath !== '' && is_file($csvPath)) {
                    $csvAbs = $csvPath;
                } elseif ($csvPath !== '' && function_exists('storage_path')) {
                    $csvAbs = (string)storage_path($csvPath);
                }

                $rbAbs = $csvAbs !== '' ? preg_replace('/\.csv$/', '_rollback.json', $csvAbs) : null;
                if (is_string($rbAbs) && $rbAbs !== '' && is_file($rbAbs)) {
                    $rawRb = @file_get_contents($rbAbs);
                    $jRb = is_string($rawRb) && $rawRb !== '' ? json_decode($rawRb, true) : null;
                    $ver = is_array($jRb) ? (int)($jRb['version'] ?? 0) : 0;
                    if ($ver === 2) {
                        $canRollback = true;
                    } else {
                        $legacyRollback = true;
                    }
                }
            }

            $id = (int)($r['id'] ?? 0);
            $rollbackUi = [
                'kind' => 'none',
                'label' => '',
            ];
            if ($canRollback && $id === $lastRevertibleId) {
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
                $rollbackUi = ['kind' => 'legacy', 'label' => 'Rollback legacy'];
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

        $canRollback = false;
        $rollbackFileOk = false;
        $rollbackVersion = null;
        $revertStatus = $job ? (string)($job['revert_status'] ?? 'not_reverted') : 'not_reverted';

        if ($job && in_array((string)($job['status'] ?? ''), ['finished', 'ok'], true)) {
            $csvPath = (string)($job['file_path'] ?? '');
            $csvAbs = '';
            if ($csvPath !== '' && is_file($csvPath)) {
                $csvAbs = $csvPath;
            } elseif ($csvPath !== '' && function_exists('storage_path')) {
                $csvAbs = (string)storage_path($csvPath);
            }

            $rbAbs = $csvAbs !== '' ? preg_replace('/\.csv$/', '_rollback.json', $csvAbs) : null;
            if (is_string($rbAbs) && $rbAbs !== '' && is_file($rbAbs)) {
                $raw = @file_get_contents($rbAbs);
                $j = (is_string($raw) && $raw !== '') ? json_decode($raw, true) : null;
                if (is_array($j)) {
                    $rollbackVersion = (int)($j['version'] ?? 0);
                    $rollbackFileOk = ($rollbackVersion === 2);
                }
            }

            if ($rollbackFileOk && $revertStatus === 'not_reverted' && (int)($job['id'] ?? 0) === $lastRevertibleId) {
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
            $lvl = (string)($rr['level'] ?? 'ok');
            $rowClass = '';
            if ($lvl === 'error') {
                $rowClass = 'bg-red-50/40';
            } elseif ($lvl === 'warn') {
                $rowClass = 'bg-amber-50/40';
            }

            $resultBadge = 'badge-pill badge-success';
            $resultLabel = 'OK';
            if ($lvl === 'warn') {
                $resultBadge = 'badge-pill badge-neutral';
                $resultLabel = 'Aviso';
            } elseif ($lvl === 'error') {
                $resultBadge = 'badge-pill badge-danger';
                $resultLabel = 'Error';
            }

            $accionTxt = (string)($rr['accion'] ?? '');
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
                    $cambios[] = ['type' => 'note', 'raw' => $note];
                }
            }

            $isNoop = (stripos($accionTxt, 'Sin cambios') !== false)
                || (stripos($accionTxt, 'No cambiar') !== false)
                || (stripos($accionTxt, 'noop') !== false);

            $rowsPrepared[] = [
                'row' => (int)($rr['row'] ?? 0),
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
            'statusKey' => $statusKey,
            'statusBadge' => $statusBadge,
            'totalRows' => $totalRows,
            'okRows' => $okRows,
            'errRows' => $errRows,
            'lastRevertibleId' => $lastRevertibleId,
            'isSuccess' => $isSuccess,
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
