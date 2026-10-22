<?php
namespace App\Services\Admin\Imports\ViewModels;

/**
 * Normaliza payload para app/Views/pages/admin/imports/index.php.
 */
final class ImportsIndexViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $rows = is_array($input['rows'] ?? null) ? $input['rows'] : [];
        $preflight = is_array($input['preflight'] ?? null) ? $input['preflight'] : [];
        $preview = is_array($input['preview'] ?? null) ? $input['preview'] : [];
        $confirm = is_array($input['confirm'] ?? null) ? $input['confirm'] : [];
        $historyRows = is_array($input['history_rows'] ?? null) ? $input['history_rows'] : [];
        $historyRowsPrepared = [];
        foreach ($historyRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $rollbackUi = is_array($row['rollback_ui'] ?? null) ? $row['rollback_ui'] : [];
            $historyRowsPrepared[] = [
                'id' => (int)($row['id'] ?? 0),
                'file_name' => (string)($row['file_name'] ?? ''),
                'status_badge' => (string)($row['status_badge'] ?? 'badge-pill badge-neutral'),
                'status_label' => (string)($row['status_label'] ?? ''),
                'ok_rows' => (int)($row['ok_rows'] ?? 0),
                'err_rows' => (int)($row['err_rows'] ?? 0),
                'created_at' => (string)($row['created_at'] ?? ''),
                'detail_url' => (string)($row['detail_url'] ?? url('/admin/imports')),
                'rollback_ui' => [
                    'kind' => (string)($rollbackUi['kind'] ?? 'none'),
                    'label' => (string)($rollbackUi['label'] ?? ''),
                ],
            ];
        }

        $previewData = null;
        $detailRows = [];
        $errorsCount = 0;
        $warningsCount = 0;

        if (!empty($preview) && !empty($preview['result']) && is_array($preview['result'])) {
            $result = (array)$preview['result'];
            $plan = (array)($result['plan'] ?? []);
            $rowsPreview = (array)($result['rows'] ?? []);
            $errorsCount = (int)($result['err'] ?? 0);
            $warningsCount = (int)($result['warnings'] ?? 0);
            $totalCount = (int)($result['total'] ?? 0);
            $okCount = (int)($result['ok'] ?? 0);

            // Fallback cuando no llega detalle por fila en rows.
            if (empty($rowsPreview)) {
                foreach ((array)($result['errors'] ?? []) as $errorRow) {
                    $rowsPreview[] = [
                        'row' => (int)($errorRow['row'] ?? 0),
                        'tipo_fila' => '',
                        'sku' => '',
                        'sku_padre' => '',
                        'entidad' => 'Validación',
                        'accion' => 'Bloquea',
                        'notas' => [(string)($errorRow['error'] ?? 'Error')],
                        'level' => 'error',
                    ];
                }
                foreach ((array)($result['warn_list'] ?? []) as $warnRow) {
                    $rowsPreview[] = [
                        'row' => (int)($warnRow['row'] ?? 0),
                        'tipo_fila' => '',
                        'sku' => '',
                        'sku_padre' => '',
                        'entidad' => 'Validación',
                        'accion' => 'Revisar',
                        'notas' => [(string)($warnRow['warning'] ?? 'Warning')],
                        'level' => 'warning',
                    ];
                }
            }

            $maxRows = 350;
            $rowsTruncated = count($rowsPreview) > $maxRows;
            $rowsPreview = array_slice($rowsPreview, 0, $maxRows);

            $previewData = [
                'original_name' => (string)($preview['original_name'] ?? 'archivo.csv'),
                'file' => (string)($preview['file'] ?? ''),
                'plan' => $plan,
                'total' => $totalCount,
                'ok' => $okCount,
                'errors' => $errorsCount,
                'warnings' => $warningsCount,
                'rows_truncated' => $rowsTruncated,
            ];
            $detailRows = $rowsPreview;
        }

        $effectivePlan = (array)(
            (is_array($previewData) && !empty($previewData['plan']))
                ? $previewData['plan']
                : ($preflight['plan'] ?? [])
        );

        $planCreateBrands = (int)($effectivePlan['create_brands'] ?? 0);
        $planCreateCategories = (int)($effectivePlan['create_categories'] ?? 0);

        $details = (array)($preflight['details'] ?? []);
        $planDetails = (array)($details['plan_details'] ?? ($preview['result']['plan_details'] ?? []));
        $allBrands = array_values(array_unique(array_filter((array)($planDetails['brands'] ?? []))));
        $allCategories = array_values(array_unique(array_filter((array)($planDetails['categories'] ?? []))));
        $brandNames = array_slice($allBrands, 0, 20);
        $categoryNames = array_slice($allCategories, 0, 20);
        $moreBrands = max(0, count($allBrands) - 20);
        $moreCategories = max(0, count($allCategories) - 20);

        $countAll = count($detailRows);
        $countErrors = 0;
        $countWarnings = 0;
        foreach ($detailRows as $detailRow) {
            $level = strtolower(trim((string)($detailRow['level'] ?? '')));
            if ($level === 'warn') { $level = 'warning'; }
            if ($level === 'error') {
                $countErrors++;
            } elseif ($level === 'warning') {
                $countWarnings++;
            }
        }
        $countOk = max(0, $countAll - $countErrors - $countWarnings);
        $extraErrors = max(0, $errorsCount - $countErrors);
        $extraWarnings = max(0, $warningsCount - $countWarnings);

        $detailRowsPrepared = [];
        foreach ($detailRows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $level = strtolower(trim((string)($row['level'] ?? '')));
            if ($level === 'warn') { $level = 'warning'; }
            $badgeClass = 'av3-ui-badge--neutral';
            if ($level === 'error') {
                $badgeClass = 'av3-ui-badge--danger';
            } elseif ($level === 'warning') {
                $badgeClass = 'av3-ui-badge--warning';
            }
            $detailRowsPrepared[] = [
                'row' => (int)($row['row'] ?? 0),
                'tipo_fila' => (string)($row['tipo_fila'] ?? ''),
                'sku' => (string)($row['sku'] ?? ''),
                'sku_padre' => (string)($row['sku_padre'] ?? ''),
                'entidad' => (string)($row['entidad'] ?? ''),
                'accion' => (string)($row['accion'] ?? ''),
                'notas' => is_array($row['notas'] ?? null) ? array_values((array)$row['notas']) : [],
                'level' => $level,
                'level_badge_class' => $badgeClass,
            ];
        }

        $canApply = !empty($confirm)
            && is_array($previewData)
            && ((int)($previewData['errors'] ?? 0) === 0)
            && ((int)($previewData['total'] ?? 0) > 0);
        $needsPhrase = !empty($preflight['needs']) || !empty($preflight['phrase']);

        $importsIndex = [
            'rows' => $rows,
            'preflight' => $preflight,
            'preview' => $preview,
            'confirm' => $confirm,
            'flash' => is_array($input['flash'] ?? null) ? $input['flash'] : [],
            'lastRevertibleId' => (int)($input['lastRevertibleId'] ?? 0),
            'links' => is_array($input['links'] ?? null) ? $input['links'] : [],
            'history_rows' => $historyRowsPrepared,
            'preflight_reason' => (string)($preflight['reason'] ?? ''),
            'preflight_phrase' => (string)($preflight['phrase'] ?? ''),
            'index_payload' => [
                'preview_data' => $previewData,
                'detail_rows_prepared' => $detailRowsPrepared,
                'effective_plan' => $effectivePlan,
                'plan_create_brands' => $planCreateBrands,
                'plan_create_categories' => $planCreateCategories,
                'all_brands' => $allBrands,
                'all_categories' => $allCategories,
                'brand_names' => $brandNames,
                'category_names' => $categoryNames,
                'more_brands' => $moreBrands,
                'more_categories' => $moreCategories,
                'count_all' => $countAll,
                'count_errors' => $countErrors,
                'count_warnings' => $countWarnings,
                'count_ok' => $countOk,
                'extra_errors' => $extraErrors,
                'extra_warnings' => $extraWarnings,
                'can_apply' => $canApply,
                'needs_phrase' => $needsPhrase,
            ],
        ];

        $importsIndexView = [
            'vm' => $importsIndex,
            'historyRows' => $historyRowsPrepared,
            'preflight' => $preflight,
            'confirm' => $confirm,
            'previewData' => $previewData,
            'detailRowsPrepared' => $detailRowsPrepared,
            'plan' => $effectivePlan,
            'planCreateBrands' => $planCreateBrands,
            'planCreateCats' => $planCreateCategories,
            'allBrands' => $allBrands,
            'allCats' => $allCategories,
            'brandNames' => $brandNames,
            'catNames' => $categoryNames,
            'moreBrands' => $moreBrands,
            'moreCats' => $moreCategories,
            'cntAll' => $countAll,
            'cntErr' => $countErrors,
            'cntWarn' => $countWarnings,
            'cntOk' => $countOk,
            'extraErr' => $extraErrors,
            'extraWarn' => $extraWarnings,
            'canApply' => $canApply,
            'needsPhrase' => $needsPhrase,
            'preflightReason' => (string)($preflight['reason'] ?? ''),
            'preflightPhrase' => (string)($preflight['phrase'] ?? ''),
            'showPreflight' => !empty($preflight),
            'showUploadAndHistory' => empty($previewData),
        ];

        return [
            'imports_index' => $importsIndex,
            'imports_index_view' => $importsIndexView,
        ];
    }
}
