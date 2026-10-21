<?php
namespace App\Services\Admin\Audit\ViewModels;

/**
 * Normaliza payload para app/Views/pages/admin/audit/show.php.
 */
final class AuditShowViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $item = is_array($input['item'] ?? null) ? $input['item'] : [];
        $meta = is_array($input['meta'] ?? null) ? $input['meta'] : [];
        $createdOtherRows = is_array($input['created_other_rows'] ?? null) ? $input['created_other_rows'] : [];
        $createdOtherWithChanges = [];
        foreach ($createdOtherRows as $ev) {
            if (!is_array($ev)) {
                continue;
            }
            $evChanges = is_array($ev['changes'] ?? null) ? $ev['changes'] : [];
            if (empty($evChanges)) {
                continue;
            }
            $ev['changes'] = $evChanges;
            $createdOtherWithChanges[] = $ev;
        }

        $showPayload = [
            'item' => $item,
            'back_url' => (string)($input['back_url'] ?? ($input['backUrl'] ?? base_url('/admin/audit'))),
            'meta' => $meta,
            'tone' => (string)($input['tone'] ?? 'neutral'),
            'title' => (string)($input['title'] ?? 'Detalle'),
            'entity' => is_array($input['entity'] ?? null) ? $input['entity'] : ['label' => '-', 'href' => null],
            'actor' => (string)($input['actor'] ?? 'Sistema'),
            'status_from' => (string)($input['status_from'] ?? ''),
            'status_to' => (string)($input['status_to'] ?? ''),
            'action_key' => (string)($input['action_key'] ?? ($item['action'] ?? '')),
            'created_at' => (string)($input['created_at'] ?? ($item['created_at'] ?? '')),
            'snapshot_enabled' => (bool)($input['snapshot_enabled'] ?? false),
            'snapshot_state' => (string)($input['snapshot_state'] ?? 'empty'),
            'snapshot_rows' => is_array($input['snapshot_rows'] ?? null) ? $input['snapshot_rows'] : [],
            'biz_bucket' => (string)($input['biz_bucket'] ?? ''),
            'biz_events' => is_array($input['biz_events'] ?? null) ? $input['biz_events'] : [],
            'biz_count' => (int)($input['biz_count'] ?? 0),
            'created_counts_human' => is_array($input['created_counts_human'] ?? null) ? $input['created_counts_human'] : [],
            'created_stock_rows' => is_array($input['created_stock_rows'] ?? null) ? $input['created_stock_rows'] : [],
            'created_other_rows' => $createdOtherRows,
            'created_other_rows_with_changes' => $createdOtherWithChanges,
            'created_other_rows_has_changes' => !empty($createdOtherWithChanges),
            'changes_ui' => is_array($input['changes_ui'] ?? null) ? $input['changes_ui'] : [],
            'variant_info' => is_array($input['variant_info'] ?? null) ? $input['variant_info'] : ['id' => 0, 'sku' => '', 'name' => ''],
            'receipt' => is_array($input['receipt'] ?? null) ? $input['receipt'] : [],
            'meta_json_pretty' => (string)($input['meta_json_pretty'] ?? json_encode($meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)),
        ];

        $auditShow = [
            'item' => $showPayload['item'],
            'back_url' => (string)$showPayload['back_url'],
            'tone' => (string)$showPayload['tone'],
            'title' => (string)$showPayload['title'],
            'entity' => is_array($showPayload['entity']) ? $showPayload['entity'] : ['label' => '-', 'href' => null],
            'actor' => (string)$showPayload['actor'],
            'status_from' => (string)$showPayload['status_from'],
            'status_to' => (string)$showPayload['status_to'],
            'action_key' => (string)$showPayload['action_key'],
            'created_at' => (string)$showPayload['created_at'],
            'snapshot_enabled' => (bool)$showPayload['snapshot_enabled'],
            'snapshot_state' => (string)$showPayload['snapshot_state'],
            'snapshot_rows' => is_array($showPayload['snapshot_rows']) ? $showPayload['snapshot_rows'] : [],
            'biz_bucket' => (string)$showPayload['biz_bucket'],
            'biz_events' => is_array($showPayload['biz_events']) ? $showPayload['biz_events'] : [],
            'biz_count' => (int)$showPayload['biz_count'],
            'created_counts_human' => is_array($showPayload['created_counts_human']) ? $showPayload['created_counts_human'] : [],
            'created_stock_rows' => is_array($showPayload['created_stock_rows']) ? $showPayload['created_stock_rows'] : [],
            'created_other_rows_with_changes' => is_array($showPayload['created_other_rows_with_changes']) ? $showPayload['created_other_rows_with_changes'] : [],
            'created_other_rows_has_changes' => !empty($showPayload['created_other_rows_has_changes']),
            'changes_ui' => is_array($showPayload['changes_ui']) ? $showPayload['changes_ui'] : [],
            'variant_info' => is_array($showPayload['variant_info']) ? $showPayload['variant_info'] : ['id' => 0, 'sku' => '', 'name' => ''],
            'receipt' => is_array($showPayload['receipt']) ? $showPayload['receipt'] : [],
            'meta_json_pretty' => (string)$showPayload['meta_json_pretty'],
        ];

        $auditShowView = [
            'item' => is_array($auditShow['item']) ? $auditShow['item'] : [],
            'backUrl' => (string)$auditShow['back_url'],
            'tone' => (string)$auditShow['tone'],
            'title' => (string)$auditShow['title'],
            'entity' => is_array($auditShow['entity']) ? $auditShow['entity'] : ['label' => '-', 'href' => null],
            'actor' => (string)$auditShow['actor'],
            'from' => (string)$auditShow['status_from'],
            'to' => (string)$auditShow['status_to'],
            'aKey' => (string)$auditShow['action_key'],
            'dt' => (string)$auditShow['created_at'],
            'snapshotEnabled' => (bool)$auditShow['snapshot_enabled'],
            'snapshotState' => (string)$auditShow['snapshot_state'],
            'snapshotRows' => is_array($auditShow['snapshot_rows']) ? $auditShow['snapshot_rows'] : [],
            'bizBucket' => (string)$auditShow['biz_bucket'],
            'bizEvents' => is_array($auditShow['biz_events']) ? $auditShow['biz_events'] : [],
            'bizCount' => (int)$auditShow['biz_count'],
            'createdCountsHuman' => is_array($auditShow['created_counts_human']) ? $auditShow['created_counts_human'] : [],
            'createdStockRows' => is_array($auditShow['created_stock_rows']) ? $auditShow['created_stock_rows'] : [],
            'createdOtherRowsWithChanges' => is_array($auditShow['created_other_rows_with_changes']) ? $auditShow['created_other_rows_with_changes'] : [],
            'createdOtherRowsHasChanges' => !empty($auditShow['created_other_rows_has_changes']),
            'changesUi' => is_array($auditShow['changes_ui']) ? $auditShow['changes_ui'] : [],
            'variantInfo' => is_array($auditShow['variant_info']) ? $auditShow['variant_info'] : ['id' => 0, 'sku' => '', 'name' => ''],
            'receipt' => is_array($auditShow['receipt']) ? $auditShow['receipt'] : [],
            'metaJsonPretty' => (string)$auditShow['meta_json_pretty'],
        ];

        return [
            'item' => $item,
            'meta' => $meta,
            'changes' => is_array($input['changes'] ?? null) ? $input['changes'] : [],
            'snapshot' => is_array($input['snapshot'] ?? null) ? $input['snapshot'] : [],
            'snapshot_enabled' => (bool)($input['snapshot_enabled'] ?? false),
            'biz_group' => is_array($input['biz_group'] ?? null) ? $input['biz_group'] : [],
            'links' => is_array($input['links'] ?? null) ? $input['links'] : [],
            'ui' => is_array($input['ui'] ?? null) ? $input['ui'] : [],
            'audit_show' => $auditShow,
            'audit_show_view' => $auditShowView,
            'show_payload' => $showPayload,
        ];
    }
}




