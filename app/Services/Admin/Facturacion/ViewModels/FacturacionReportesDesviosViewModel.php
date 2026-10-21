<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionReportesDesviosViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $conciliation = is_array($input['conciliation'] ?? null) ? $input['conciliation'] : [];
        $rows = is_array($conciliation['deviations'] ?? null) ? $conciliation['deviations'] : [];

        $filtersWithoutTab = $filters;
        unset($filtersWithoutTab['tab']);
        foreach ($filtersWithoutTab as $key => $value) {
            if ($value === null || $value === '' || $value === 0) {
                unset($filtersWithoutTab[$key]);
            }
        }

        $filtersConciliacion = $filters;
        $filtersConciliacion['tab'] = 'conciliacion';
        foreach ($filtersConciliacion as $key => $value) {
            if ($value === null || $value === '' || $value === 0) {
                unset($filtersConciliacion[$key]);
            }
        }

        $rowsPrepared = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $orderId = (int)($row['order_id'] ?? 0);
            $envMin = (string)($row['env_min'] ?? '');
            $envMax = (string)($row['env_max'] ?? '');
            $envText = ($envMin && $envMax && $envMin !== $envMax)
                ? ('Mixto (' . $envMin . ' + ' . $envMax . ')')
                : ($envMin ?: '-');

            $resultLevel = (string)($row['resultado_level'] ?? 'warn');
            $rowsPrepared[] = [
                'order_id' => $orderId,
                'order_url' => base_url('admin/orders/show?id=' . $orderId),
                'last_issued_at' => (string)($row['last_issued_at'] ?? ''),
                'order_status_label' => order_status_label_admin((string)($row['order_status'] ?? '')),
                'order_total' => money((int)($row['order_total'] ?? 0)),
                'invoices_count' => (int)($row['invoices_count'] ?? 0),
                'invoices_total' => money((int)($row['invoices_total'] ?? 0)),
                'credit_count' => (int)($row['credit_count'] ?? 0),
                'credit_total' => money((int)($row['credit_total'] ?? 0)),
                'net_issued' => money((int)($row['net_issued'] ?? 0)),
                'result_label' => (string)($row['resultado_label'] ?? 'Revisar'),
                'result_class' => $this->badgeClass($resultLevel),
                'env_text' => $envText,
                'comprobantes_url' => base_url('admin/facturacion/comprobantes?' . http_build_query(['order_id' => $orderId])),
            ];
        }

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturación', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Reportes', 'href' => base_url('admin/facturacion/reportes')],
                ['label' => 'Desvíos', 'href' => base_url('admin/facturacion/reportes/desvios')],
            ],
            'rows_prepared' => $rowsPrepared,
            'rows_count' => count($rowsPrepared),
            'back_url' => base_url('admin/facturacion/reportes?' . http_build_query($filtersConciliacion)),
            'export_url' => base_url('admin/facturacion/reportes/export?' . http_build_query(array_merge($filtersWithoutTab, ['mode' => 'conciliacion_desvios']))),
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




