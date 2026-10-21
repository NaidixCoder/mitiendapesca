<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionReportesResumenViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $totals = is_array($input['totals'] ?? null) ? $input['totals'] : [];
        $series = is_array($input['series'] ?? null) ? $input['series'] : [];
        $conciliation = is_array($input['conciliation'] ?? null) ? $input['conciliation'] : [];

        $tab = (string)($filters['tab'] ?? 'resumen');
        if ($tab !== 'conciliacion') {
            $tab = 'resumen';
        }

        $filtersNoTab = $this->mergeFilters($filters, ['tab' => null]);

        $inv = is_array($totals['invoices'] ?? null) ? $totals['invoices'] : ['count' => 0, 'total' => 0, 'neto' => 0, 'iva' => 0];
        $cn = is_array($totals['credit_notes'] ?? null) ? $totals['credit_notes'] : ['count' => 0, 'total' => 0, 'neto' => 0, 'iva' => 0];
        $net = (int)($inv['total'] ?? 0) - (int)($cn['total'] ?? 0);

        $seriesRows = [];
        foreach ($series as $row) {
            if (!is_array($row)) {
                continue;
            }
            $period = (string)($row['period'] ?? '');
            $invoicesTotal = (int)($row['invoices_total'] ?? 0);
            $creditNotesTotal = (int)($row['credit_notes_total'] ?? 0);
            $seriesRows[] = [
                'period' => $period,
                'period_url' => base_url('admin/facturacion/reportes/drill-comprobantes?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null, 'kind' => '', 'period' => $period]))),
                'invoices_count' => (int)($row['invoices_count'] ?? 0),
                'invoices_count_url' => base_url('admin/facturacion/reportes/drill-comprobantes?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null, 'kind' => 'invoice', 'period' => $period]))),
                'invoices_total_label' => money($invoicesTotal),
                'credit_notes_count' => (int)($row['credit_notes_count'] ?? 0),
                'credit_notes_count_url' => base_url('admin/facturacion/reportes/drill-comprobantes?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null, 'kind' => 'credit_note', 'period' => $period]))),
                'credit_notes_total_label' => money($creditNotesTotal),
                'net_label' => money($invoicesTotal - $creditNotesTotal),
            ];
        }

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturación', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Reportes', 'href' => base_url('admin/facturacion/reportes')],
                ['label' => 'Resumen', 'href' => base_url('admin/facturacion/reportes/resumen')],
            ],
            'filters' => $filters,
            'tab' => $tab,
            'env_options' => $this->envOptions((string)($filters['arca_env'] ?? '')),
            'group_options' => $this->groupOptions((string)($filters['group'] ?? 'day')),
            'apply_url' => base_url('admin/facturacion/reportes'),
            'reset_url' => base_url('admin/facturacion/reportes'),
            'export_summary_url' => base_url('admin/facturacion/reportes/export?' . $this->buildQueryString($filtersNoTab)),
            'export_detail_url' => base_url('admin/facturacion/reportes/export?' . $this->buildQueryString(array_merge($filtersNoTab, ['mode' => 'comprobantes']))),
            'tab_resumen_url' => base_url('admin/facturacion/reportes?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => 'resumen']))),
            'tab_conciliacion_url' => base_url('admin/facturacion/reportes?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => 'conciliacion']))),
            'summary_cards' => [
                [
                    'title' => 'Facturas emitidas',
                    'count' => (int)($inv['count'] ?? 0),
                    'count_url' => base_url('admin/facturacion/reportes/drill-comprobantes?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null, 'kind' => 'invoice', 'period' => '']))),
                    'total_label' => money((int)($inv['total'] ?? 0)),
                ],
                [
                    'title' => 'Notas de crédito emitidas',
                    'count' => (int)($cn['count'] ?? 0),
                    'count_url' => base_url('admin/facturacion/reportes/drill-comprobantes?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null, 'kind' => 'credit_note', 'period' => '']))),
                    'total_label' => money((int)($cn['total'] ?? 0)),
                ],
                [
                    'title' => 'Neto del período',
                    'count' => null,
                    'count_url' => null,
                    'total_label' => money($net),
                    'is_net' => true,
                ],
            ],
            'series_rows' => $seriesRows,
            'series_count' => count($seriesRows),
            'conc_orders_missing_invoice' => $this->prepareOrdersMissingInvoice($filters, is_array($conciliation['orders_missing_invoice'] ?? null) ? $conciliation['orders_missing_invoice'] : []),
            'conc_comprobantes_not_issued' => $this->prepareComprobantesNotIssued($filters, is_array($conciliation['comprobantes_not_issued'] ?? null) ? $conciliation['comprobantes_not_issued'] : []),
            'conc_deviations' => $this->prepareDeviations($filters, is_array($conciliation['deviations'] ?? null) ? $conciliation['deviations'] : []),
            'conc_duplicates_exact' => $this->prepareDuplicatesExact($filters, is_array($conciliation['duplicates_exact'] ?? null) ? $conciliation['duplicates_exact'] : []),
            'conc_non_billable_with_invoice' => $this->prepareNonBillableWithInvoice($filters, is_array($conciliation['non_billable_with_invoice'] ?? null) ? $conciliation['non_billable_with_invoice'] : []),
        ];
    }

    /**
     * @param array<string,mixed> $params
     */
    private function buildQueryString(array $params): string
    {
        $clean = [];
        foreach ($params as $key => $value) {
            if ($value === null || $value === '' || $value === 0) {
                continue;
            }
            $clean[$key] = $value;
        }
        return http_build_query($clean);
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<string,mixed> $extra
     * @return array<string,mixed>
     */
    private function mergeFilters(array $filters, array $extra): array
    {
        $merged = array_merge($filters, $extra);
        foreach ($merged as $key => $value) {
            if ($value === null || $value === '' || $value === 0) {
                unset($merged[$key]);
            }
        }
        return $merged;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function envOptions(string $selected): array
    {
        $options = [
            ['value' => '', 'label' => 'Todos'],
            ['value' => 'homologacion', 'label' => 'Homologación'],
            ['value' => 'produccion', 'label' => 'Producción'],
        ];
        foreach ($options as &$option) {
            $option['selected'] = ($option['value'] === $selected);
        }
        unset($option);
        return $options;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function groupOptions(string $selected): array
    {
        $options = [
            ['value' => 'day', 'label' => 'Por día'],
            ['value' => 'month', 'label' => 'Por mes'],
        ];
        foreach ($options as &$option) {
            $option['selected'] = ($option['value'] === $selected);
        }
        unset($option);
        return $options;
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    private function prepareOrdersMissingInvoice(array $filters, array $rows): array
    {
        $prepared = [];
        foreach ($rows as $row) {
            $orderId = (int)($row['order_id'] ?? 0);
            $prepared[] = [
                'order_id' => $orderId,
                'created_at' => (string)($row['created_at'] ?? ''),
                'order_status_label' => order_status_label_admin((string)($row['order_status'] ?? '')),
                'total_label' => money((int)($row['total'] ?? 0)),
                'order_url' => base_url('admin/orders/show?id=' . $orderId),
            ];
        }

        return [
            'rows' => $prepared,
            'count' => count($prepared),
            'view_all_url' => base_url('admin/facturacion/reportes/pedidos-sin-factura?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null]))),
            'export_url' => base_url('admin/facturacion/reportes/export?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null, 'mode' => 'conciliacion_pedidos']))),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    private function prepareComprobantesNotIssued(array $filters, array $rows): array
    {
        $prepared = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $orderId = (int)($row['order_id'] ?? 0);
            $prepared[] = [
                'id' => $id,
                'order_id' => $orderId,
                'kind_label' => fiscal_doc_kind_label((string)($row['kind'] ?? '')),
                'status_label' => fiscal_doc_status_label((string)($row['status'] ?? '')),
                'total_label' => money((int)($row['imp_total'] ?? 0)),
                'doc_url' => base_url('admin/facturacion/comprobantes/show?id=' . $id),
                'order_url' => base_url('admin/orders/show?id=' . $orderId),
            ];
        }

        return [
            'rows' => $prepared,
            'count' => count($prepared),
            'view_all_url' => base_url('admin/facturacion/reportes/comprobantes-pendientes?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null]))),
            'export_url' => base_url('admin/facturacion/reportes/export?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null, 'mode' => 'conciliacion_comprobantes']))),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    private function prepareDeviations(array $filters, array $rows): array
    {
        $prepared = [];
        foreach ($rows as $row) {
            $orderId = (int)($row['order_id'] ?? 0);
            $envMin = (string)($row['env_min'] ?? '');
            $envMax = (string)($row['env_max'] ?? '');
            $envText = ($envMin && $envMax && $envMin !== $envMax) ? ('Mixto (' . $envMin . ' + ' . $envMax . ')') : ($envMin ?: '-');
            $prepared[] = [
                'order_id' => $orderId,
                'order_url' => base_url('admin/orders/show?id=' . $orderId),
                'last_issued_at' => (string)($row['last_issued_at'] ?? ''),
                'order_status_label' => order_status_label_admin((string)($row['order_status'] ?? '')),
                'order_total_label' => money((int)($row['order_total'] ?? 0)),
                'invoices_count' => (int)($row['invoices_count'] ?? 0),
                'invoices_total_label' => money((int)($row['invoices_total'] ?? 0)),
                'credit_count' => (int)($row['credit_count'] ?? 0),
                'credit_total_label' => money((int)($row['credit_total'] ?? 0)),
                'net_issued_label' => money((int)($row['net_issued'] ?? 0)),
                'result_label' => (string)($row['resultado_label'] ?? 'Revisar'),
                'result_class' => $this->badgeClass((string)($row['resultado_level'] ?? 'warn')),
                'env_text' => $envText,
                'comprobantes_url' => base_url('admin/facturacion/comprobantes?' . http_build_query(['order_id' => $orderId])),
            ];
        }

        return [
            'rows' => $prepared,
            'count' => count($prepared),
            'showing_count' => min(count($prepared), 200),
            'view_all_url' => base_url('admin/facturacion/reportes/desvios?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null]))),
            'export_url' => base_url('admin/facturacion/reportes/export?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null, 'mode' => 'conciliacion_desvios']))),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    private function prepareDuplicatesExact(array $filters, array $rows): array
    {
        $prepared = [];
        foreach (array_slice($rows, 0, 200) as $row) {
            $env = (string)($row['arca_env'] ?? '');
            $kind = (string)($row['kind'] ?? '');
            $pto = (int)($row['pto_vta'] ?? 0);
            $tipo = (int)($row['cbte_tipo'] ?? 0);
            $nro = (int)($row['cbte_nro'] ?? 0);
            $query = $this->mergeFilters([
                'status' => 'issued',
                'kind' => $kind,
                'arca_env' => $env,
                'date_field' => 'issued',
                'date_from' => (string)($filters['from'] ?? ''),
                'date_to' => (string)($filters['to'] ?? ''),
                'pto_vta' => $pto,
                'cbte_tipo' => $tipo,
                'cbte_nro' => $nro,
            ], []);

            $prepared[] = [
                'env_text' => $env ?: '—',
                'kind_text' => $kind === 'credit_note' ? 'NC' : 'Factura',
                'pto' => $pto,
                'cbte' => $tipo . ' / ' . $nro,
                'count' => (int)($row['c'] ?? 0),
                'comprobantes_url' => base_url('admin/facturacion/comprobantes?' . http_build_query($query)),
            ];
        }

        return [
            'rows' => $prepared,
            'showing_count' => min(count($rows), 200),
            'view_all_url' => base_url('admin/facturacion/reportes/duplicados-exactos?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null]))),
            'export_url' => base_url('admin/facturacion/reportes/export?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null, 'mode' => 'conciliacion_duplicados']))),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    private function prepareNonBillableWithInvoice(array $filters, array $rows): array
    {
        $prepared = [];
        foreach (array_slice($rows, 0, 200) as $row) {
            $orderId = (int)($row['order_id'] ?? 0);
            $cfid = (int)($row['comprobante_id'] ?? 0);
            $pto = (int)($row['pto_vta'] ?? 0);
            $tipo = (int)($row['cbte_tipo'] ?? 0);
            $nro = (int)($row['cbte_nro'] ?? 0);
            $prepared[] = [
                'order_id' => $orderId,
                'order_url' => base_url('admin/orders/show?id=' . $orderId),
                'order_status_label' => order_status_label_admin((string)($row['order_status'] ?? '')),
                'issued_at' => (string)($row['issued_at'] ?? ''),
                'cbte' => $pto . '-' . $tipo . '-' . $nro,
                'comprobantes_url' => base_url('admin/facturacion/comprobantes?order_id=' . $orderId),
                'doc_url' => $cfid > 0 ? base_url('admin/facturacion/comprobantes/show?id=' . $cfid) : '',
            ];
        }

        return [
            'rows' => $prepared,
            'showing_count' => min(count($rows), 200),
            'view_all_url' => base_url('admin/facturacion/reportes/pedidos-no-facturables?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null]))),
            'export_url' => base_url('admin/facturacion/reportes/export?' . $this->buildQueryString($this->mergeFilters($filters, ['tab' => null, 'mode' => 'conciliacion_no_facturables']))),
        ];
    }

    private function badgeClass(string $level): string
    {
        return match ($level) {
            'ok' => 'border-emerald-200 bg-emerald-50 text-emerald-800',
            'info' => 'border-sky-200 bg-sky-50 text-sky-800',
            'danger' => 'border-rose-200 bg-rose-50 text-rose-800',
            default => 'border-amber-200 bg-amber-50 text-amber-800',
        };
    }
}




