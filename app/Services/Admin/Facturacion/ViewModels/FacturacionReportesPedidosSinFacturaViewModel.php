<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionReportesPedidosSinFacturaViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $conciliation = is_array($input['conciliation'] ?? null) ? $input['conciliation'] : [];
        $rows = is_array($conciliation['orders_missing_invoice'] ?? null) ? $conciliation['orders_missing_invoice'] : [];

        $rowsPrepared = [];
        foreach ($rows as $row) {
            $orderId = (int)($row['order_id'] ?? 0);
            $comprobanteId = (int)($row['comprobante_id'] ?? 0);
            $rowsPrepared[] = [
                'order_id' => $orderId,
                'created_at' => (string)($row['created_at'] ?? ''),
                'order_status_label' => order_status_label_admin((string)($row['order_status'] ?? '')),
                'total_label' => money((int)($row['total'] ?? 0)),
                'tax_condition' => (string)($row['customer_tax_condition'] ?? ''),
                'cuit' => (string)($row['customer_cuit'] ?? ''),
                'comprobante_text' => $comprobanteId > 0
                    ? ('#' . $comprobanteId . ' (' . fiscal_doc_status_label((string)($row['cf_status'] ?? '')) . ')')
                    : '—',
                'order_url' => base_url('admin/orders/show?id=' . $orderId),
            ];
        }

        $queryBack = $this->buildQueryString(array_merge($filters, ['tab' => 'conciliacion']));
        $queryExport = $this->buildQueryString(array_merge($filters, ['mode' => 'conciliacion_pedidos']));

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturación', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Reportes', 'href' => base_url('admin/facturacion/reportes')],
                ['label' => 'Pedidos sin factura', 'href' => base_url('admin/facturacion/reportes/pedidos-sin-factura')],
            ],
            'rows_prepared' => $rowsPrepared,
            'rows_count' => count($rowsPrepared),
            'back_url' => base_url('admin/facturacion/reportes?' . $queryBack),
            'export_url' => base_url('admin/facturacion/reportes/export?' . $queryExport),
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




