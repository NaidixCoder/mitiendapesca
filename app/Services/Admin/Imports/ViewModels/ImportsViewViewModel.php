<?php
namespace App\Services\Admin\Imports\ViewModels;

/**
 * Normaliza payload para app/Views/pages/admin/imports/view.php.
 */
final class ImportsViewViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $job = is_array($input['job'] ?? null) ? $input['job'] : null;
        $plan = is_array($input['plan'] ?? null) ? $input['plan'] : [];
        $planDetails = is_array($input['plan_details'] ?? null) ? $input['plan_details'] : [];
        $rowsPrepared = is_array($input['rowsPrepared'] ?? null) ? $input['rowsPrepared'] : [];
        $warnList = is_array($input['warnList'] ?? null) ? $input['warnList'] : [];
        $errList = is_array($input['errList'] ?? null) ? $input['errList'] : [];
        $optUsedLabels = is_array($input['opt_used_labels'] ?? null) ? $input['opt_used_labels'] : [];
        $revertSummary = is_array($input['revertSummary'] ?? null) ? $input['revertSummary'] : null;

        $planStats = [
            'create_products' => (int)($plan['create_products'] ?? 0),
            'update_products' => (int)($plan['update_products'] ?? 0),
            'noop_products' => (int)($plan['noop_products'] ?? 0),
            'create_variants' => (int)($plan['create_variants'] ?? 0),
            'update_variants' => (int)($plan['update_variants'] ?? 0),
            'noop_variants' => (int)($plan['noop_variants'] ?? 0),
            'create_parents_from_variants' => (int)($plan['create_parents_from_variants'] ?? 0),
            'create_brands' => (int)($plan['create_brands'] ?? 0),
            'create_categories' => (int)($plan['create_categories'] ?? 0),
            'create_suppliers' => (int)($plan['create_suppliers'] ?? 0),
        ];

        $catalogLists = [];
        $brandsAll = is_array($planDetails['brands_all'] ?? null) ? (array)$planDetails['brands_all'] : [];
        $categoriesAll = is_array($planDetails['categories_all'] ?? null) ? (array)$planDetails['categories_all'] : [];
        $suppliersAll = is_array($planDetails['suppliers_all'] ?? null) ? (array)$planDetails['suppliers_all'] : [];

        if ($planStats['create_brands'] > 0) {
            $catalogLists[] = [
                'key' => 'brands',
                'title' => 'Marcas',
                'count' => $planStats['create_brands'],
                'sample' => is_array($planDetails['brands_sample'] ?? null) ? (array)$planDetails['brands_sample'] : [],
                'more' => (int)($planDetails['brands_more'] ?? 0),
                'all' => $brandsAll,
            ];
        }
        if ($planStats['create_categories'] > 0) {
            $catalogLists[] = [
                'key' => 'categories',
                'title' => 'Categorías',
                'count' => $planStats['create_categories'],
                'sample' => is_array($planDetails['categories_sample'] ?? null) ? (array)$planDetails['categories_sample'] : [],
                'more' => (int)($planDetails['categories_more'] ?? 0),
                'all' => $categoriesAll,
            ];
        }
        if ($planStats['create_suppliers'] > 0) {
            $catalogLists[] = [
                'key' => 'suppliers',
                'title' => 'Proveedores',
                'count' => $planStats['create_suppliers'],
                'sample' => is_array($planDetails['suppliers_sample'] ?? null) ? (array)$planDetails['suppliers_sample'] : [],
                'more' => (int)($planDetails['suppliers_more'] ?? 0),
                'all' => $suppliersAll,
            ];
        }

        return [
            'job' => $job ?? [],
            'summary' => is_array($input['summary'] ?? null) ? $input['summary'] : [],
            'events' => is_array($input['events'] ?? null) ? $input['events'] : [],
            'rows' => is_array($input['rows'] ?? null) ? $input['rows'] : [],
            'links' => is_array($input['links'] ?? null) ? $input['links'] : [],
            'ui' => is_array($input['ui'] ?? null) ? $input['ui'] : [],
            'show_payload' => [
                'job' => $job,
                'actor_name' => (string)($input['actor_name'] ?? ''),
                'last_revertible_id' => (int)($input['lastRevertibleId'] ?? 0),
                'can_rollback' => (bool)($input['canRollback'] ?? false),
                'rollback_file_ok' => (bool)($input['rollbackFileOk'] ?? false),
                'rollback_version' => $input['rollbackVersion'] ?? null,
                'revert_status' => (string)($input['revertStatus'] ?? ($job ? (string)($job['revert_status'] ?? 'not_reverted') : 'not_reverted')),
                'revert_summary' => $revertSummary,
                'status_key' => (string)($input['statusKey'] ?? ($job ? (string)($job['status'] ?? '') : '')),
                'status_badge' => (string)($input['statusBadge'] ?? 'badge-pill badge-neutral'),
                'total_rows' => (int)($input['totalRows'] ?? ($job ? (int)($job['total_rows'] ?? 0) : 0)),
                'ok_rows' => (int)($input['okRows'] ?? ($job ? (int)($job['ok_rows'] ?? 0) : 0)),
                'err_rows' => (int)($input['errRows'] ?? ($job ? (int)($job['err_rows'] ?? 0) : 0)),
                'is_success' => (bool)($input['isSuccess'] ?? false),
                'has_diagnostics' => (bool)($input['hasDiagnostics'] ?? false),
                'plan' => $plan,
                'plan_stats' => $planStats,
                'catalog_lists' => $catalogLists,
                'warn_list' => $warnList,
                'err_list' => $errList,
                'rows_prepared' => $rowsPrepared,
                'rows_count' => (int)($input['rowsCount'] ?? count($rowsPrepared)),
                'has_catalog_lists' => (bool)($input['hasCatalogLists'] ?? false),
                'plan_details' => $planDetails,
                'opt_used_labels' => $optUsedLabels,
            ],
        ];
    }
}
