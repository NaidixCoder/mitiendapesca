<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionReportesComprobantesPendientesViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $conciliation = is_array($input['conciliation'] ?? null) ? $input['conciliation'] : [];
        $rows = is_array($conciliation['comprobantes_not_issued'] ?? null) ? $conciliation['comprobantes_not_issued'] : [];

        $rowsPrepared = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $orderId = (int)($row['order_id'] ?? 0);
            $errorCode = (string)($row['error_code'] ?? '');
            $errorMessage = (string)($row['error_message'] ?? '');
            $rowsPrepared[] = [
                'id' => $id,
                'order_id' => $orderId,
                'kind_label' => fiscal_doc_kind_label((string)($row['kind'] ?? '')),
                'status_label' => fiscal_doc_status_label((string)($row['status'] ?? '')),
                'arca_env' => (string)($row['arca_env'] ?? ''),
                'total_label' => money((int)($row['imp_total'] ?? 0)),
                'error_text' => $errorCode . ($errorMessage !== '' ? (' — ' . $errorMessage) : ''),
                'doc_url' => base_url('admin/facturacion/comprobantes/show?id=' . $id),
                'order_url' => base_url('admin/orders/show?id=' . $orderId),
            ];
        }

        $queryBack = $this->buildQueryString(array_merge($filters, ['tab' => 'conciliacion']));
        $queryExport = $this->buildQueryString(array_merge($filters, ['mode' => 'conciliacion_comprobantes']));
        $queryOpen = $this->buildQueryString([
            'status' => 'pending',
            'arca_env' => (string)($filters['arca_env'] ?? ''),
            'created_from' => (string)($filters['from'] ?? ''),
            'created_to' => (string)($filters['to'] ?? ''),
        ]);

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturación', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Reportes', 'href' => base_url('admin/facturacion/reportes')],
                ['label' => 'Comprobantes pendientes', 'href' => base_url('admin/facturacion/reportes/comprobantes-pendientes')],
            ],
            'rows_prepared' => $rowsPrepared,
            'rows_count' => count($rowsPrepared),
            'back_url' => base_url('admin/facturacion/reportes?' . $queryBack),
            'export_url' => base_url('admin/facturacion/reportes/export?' . $queryExport),
            'open_in_docs_url' => base_url('admin/facturacion/comprobantes?' . $queryOpen),
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
}




