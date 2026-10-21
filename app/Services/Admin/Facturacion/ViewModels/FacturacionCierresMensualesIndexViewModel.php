<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionCierresMensualesIndexViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $closures = is_array($input['closures'] ?? null) ? $input['closures'] : [];
        $periodOptions = is_array($input['periodOptions'] ?? null) ? $input['periodOptions'] : [];
        $defaultPeriod = (string)($input['defaultPeriod'] ?? '');
        $isRI = (bool)($input['is_ri'] ?? false);

        $periodPrepared = [];
        foreach ($periodOptions as $option) {
            if (!is_array($option)) {
                continue;
            }
            $period = (string)($option['period'] ?? '');
            $label = (string)($option['label'] ?? $period);
            if (!empty($option['is_closed'])) {
                $label .= ' - Cerrado';
            }
            if (empty($option['has_movements'])) {
                $label .= ' - Sin movimientos';
            }
            $periodPrepared[] = [
                'period' => $period,
                'label' => $label,
                'has_movements' => !empty($option['has_movements']),
                'is_closed' => !empty($option['is_closed']),
                'selected' => ($defaultPeriod === $period),
            ];
        }

        $closuresPrepared = [];
        foreach ($closures as $closure) {
            if (!is_array($closure)) {
                continue;
            }
            $period = (string)($closure['period'] ?? '');
            $closuresPrepared[] = [
                'period' => $period,
                'iva_ventas_label' => $this->moneyArs((int)($closure['iva_ventas'] ?? 0)),
                'iva_compras_label' => $this->moneyArs((int)($closure['iva_compras'] ?? 0)),
                'saldo_iva_label' => $this->moneyArs((int)($closure['saldo_iva'] ?? 0)),
                'closed_at' => (string)($closure['closed_at'] ?? ''),
                'show_url' => base_url('admin/facturacion/cierres-mensuales/show?period=' . urlencode($period)),
                'csv_url' => base_url('admin/facturacion/cierres-mensuales/export?period=' . urlencode($period)),
            ];
        }

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturacion', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Cierres mensuales', 'href' => base_url('admin/facturacion/cierres-mensuales')],
            ],
            'is_ri' => $isRI,
            'period_options' => $periodPrepared,
            'closures_prepared' => $closuresPrepared,
            'has_closures' => !empty($closuresPrepared),
            'closures_count' => count($closuresPrepared),
            'close_url' => base_url('admin/facturacion/cierres-mensuales/close'),
            'reportes_url' => base_url('admin/facturacion/reportes'),
            'csrf' => csrf_token(),
        ];
    }

    private function moneyArs(int $pesos): string
    {
        return '$ ' . number_format($pesos, 2, ',', '.');
    }
}



