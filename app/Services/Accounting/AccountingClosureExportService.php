<?php
namespace App\Services\Accounting;

use RuntimeException;

final class AccountingClosureExportService
{
    /**
     * CSV auditable: SOLO desde snapshot (sin lógica contable).
     * @param array<string,mixed> $closureRow
     */
    public function toCsvRows(array $closureRow): array
    {
        $period = (string)($closureRow['period'] ?? '');
        if ($period === '') throw new RuntimeException('Snapshot inválido.');

        $ventas = $this->decodeJsonRow($closureRow['ventas_json'] ?? null);
        $compras = $this->decodeJsonRow($closureRow['compras_json'] ?? null);

        return [
            [
                'periodo' => $period,
                'iva_ventas' => (int)($closureRow['iva_ventas'] ?? 0),
                'iva_compras' => (int)($closureRow['iva_compras'] ?? 0),
                'saldo_iva' => (int)($closureRow['saldo_iva'] ?? 0),

                // Ventas (reporte mensual IVA)
                'ventas_comprobantes' => (int)($ventas['comprobantes_count'] ?? 0),
                'ventas_neto' => (int)($ventas['neto'] ?? 0),
                'ventas_total' => (int)($ventas['total'] ?? 0),

                'ventas_fa_total' => (int)($ventas['fa_total'] ?? 0),
                'ventas_fb_total' => (int)($ventas['fb_total'] ?? 0),
                'ventas_fc_total' => (int)($ventas['fc_total'] ?? 0),

                'ventas_nca_total' => (int)($ventas['nca_total'] ?? 0),
                'ventas_ncb_total' => (int)($ventas['ncb_total'] ?? 0),
                'ventas_ncc_total' => (int)($ventas['ncc_total'] ?? 0),

                // Compras (resumen mensual)
                'compras_comprobantes' => (int)($compras['comprobantes_count'] ?? 0),
                'compras_neto' => (int)($compras['neto'] ?? 0),
                'compras_total' => (int)($compras['total'] ?? 0),
            ]
        ];
    }

    private function decodeJsonRow($json): array
    {
        if (!is_string($json) || trim($json) === '') return [];
        $d = json_decode($json, true);
        return is_array($d) ? $d : [];
    }
}
