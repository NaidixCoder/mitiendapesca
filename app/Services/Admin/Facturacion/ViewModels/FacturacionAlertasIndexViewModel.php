<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionAlertasIndexViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $loaded = is_array($input['loadedNotValidated'] ?? null) ? $input['loadedNotValidated'] : [];
        $missing = is_array($input['purchasesMissingAttachments'] ?? null) ? $input['purchasesMissingAttachments'] : [];
        $periods = is_array($input['unclosedPeriods'] ?? null) ? $input['unclosedPeriods'] : [];

        $loadedPrepared = [];
        foreach ($loaded as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int)($row['id'] ?? 0);
            $loadedPrepared[] = [
                'id' => $id,
                'issued_at' => (string)($row['issued_at'] ?? ''),
                'supplier_nombre' => (string)($row['supplier_nombre'] ?? ''),
                'supplier_cuit' => (string)($row['supplier_cuit'] ?? ''),
                'document' => (string)($row['cbte_tipo'] ?? '') . '/' . (string)($row['pto_vta'] ?? '') . '-' . (string)($row['cbte_nro'] ?? ''),
                'total_label' => $this->moneyArs((int)($row['imp_total'] ?? 0)),
                'action_url' => base_url('admin/facturacion/compras/edit?id=' . $id),
            ];
        }

        $missingPrepared = [];
        foreach ($missing as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int)($row['id'] ?? 0);
            $status = (string)($row['status'] ?? '');
            $missingPrepared[] = [
                'id' => $id,
                'issued_at' => (string)($row['issued_at'] ?? ''),
                'supplier_nombre' => (string)($row['supplier_nombre'] ?? ''),
                'supplier_cuit' => (string)($row['supplier_cuit'] ?? ''),
                'status_label' => ($status === 'validated') ? 'Validada' : 'Cargada',
                'status_badge_class' => ($status === 'validated') ? 'badge-success' : 'badge-warning',
                'total_label' => $this->moneyArs((int)($row['imp_total'] ?? 0)),
                'action_url' => base_url('admin/facturacion/compras/edit?id=' . $id),
            ];
        }

        $periodPrepared = [];
        foreach ($periods as $period) {
            $p = (string)$period;
            if ($p === '') {
                continue;
            }
            $periodPrepared[] = [
                'label' => $p,
                'url' => base_url('admin/facturacion/cierres-mensuales?period=' . urlencode($p)),
            ];
        }

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturacion', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Alertas operativas', 'href' => base_url('admin/facturacion/alertas')],
            ],
            'loaded_rows' => $loadedPrepared,
            'missing_rows' => $missingPrepared,
            'period_rows' => $periodPrepared,
            'loaded_count' => count($loadedPrepared),
            'missing_count' => count($missingPrepared),
            'period_count' => count($periodPrepared),
            'links' => [
                'pedidos_sin_factura' => base_url('admin/facturacion/reportes/pedidos-sin-factura'),
                'comprobantes_pendientes' => base_url('admin/facturacion/reportes/comprobantes-pendientes'),
                'desvios' => base_url('admin/facturacion/reportes/desvios'),
                'duplicados_exactos' => base_url('admin/facturacion/reportes/duplicados-exactos'),
            ],
        ];
    }

    private function moneyArs(int $pesos): string
    {
        return '$ ' . number_format($pesos, 2, ',', '.');
    }
}



