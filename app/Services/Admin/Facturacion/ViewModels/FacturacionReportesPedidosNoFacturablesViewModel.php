<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionReportesPedidosNoFacturablesViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $conciliation = is_array($input['conciliation'] ?? null) ? $input['conciliation'] : [];
        $rows = is_array($conciliation['non_billable_with_invoice'] ?? null) ? $conciliation['non_billable_with_invoice'] : [];

        $rowsPrepared = [];
        foreach ($rows as $row) {
            $orderId = (int)($row['order_id'] ?? 0);
            $cfid = (int)($row['comprobante_id'] ?? 0);
            $pto = (int)($row['pto_vta'] ?? 0);
            $tipo = (int)($row['cbte_tipo'] ?? 0);
            $nro = (int)($row['cbte_nro'] ?? 0);
            $rowsPrepared[] = [
                'order_id' => $orderId,
                'order_url' => base_url('admin/orders/show?id=' . $orderId),
                'order_status_label' => order_status_label_admin((string)($row['order_status'] ?? '')),
                'created_at' => (string)($row['created_at'] ?? ''),
                'issued_at' => (string)($row['issued_at'] ?? ''),
                'cbte' => $pto . '-' . $tipo . '-' . $nro,
                'comprobantes_url' => base_url('admin/facturacion/comprobantes?order_id=' . $orderId),
                'doc_url' => $cfid > 0 ? base_url('admin/facturacion/comprobantes/show?id=' . $cfid) : '',
            ];
        }

        $backQuery = $this->buildQueryString(array_merge($filters, ['tab' => 'conciliacion']));
        $exportQuery = $this->buildQueryString(array_merge($filters, ['tab' => null, 'mode' => 'conciliacion_no_facturables']));

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturación', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Reportes', 'href' => base_url('admin/facturacion/reportes')],
                ['label' => 'Pedidos no facturables', 'href' => base_url('admin/facturacion/reportes/pedidos-no-facturables')],
            ],
            'rows_prepared' => $rowsPrepared,
            'rows_count' => count($rowsPrepared),
            'back_url' => base_url('admin/facturacion/reportes?' . $backQuery),
            'export_url' => base_url('admin/facturacion/reportes/export?' . $exportQuery),
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




