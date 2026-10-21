<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionCierresMensualesShowViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $closure = is_array($input['closure'] ?? null) ? $input['closure'] : [];
        $period = (string)($closure['period'] ?? '');

        $ventas = $this->decodeJson((string)($closure['ventas_json'] ?? ''));
        $compras = $this->decodeJson((string)($closure['compras_json'] ?? ''));

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturacion', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Cierres mensuales', 'href' => base_url('admin/facturacion/cierres-mensuales')],
                ['label' => 'Detalle ' . $period, 'href' => base_url('admin/facturacion/cierres-mensuales/show?period=' . urlencode($period))],
            ],
            'period' => $period,
            'back_url' => base_url('admin/facturacion/cierres-mensuales'),
            'export_url' => base_url('admin/facturacion/cierres-mensuales/export?period=' . urlencode($period)),
            'summary' => [
                'iva_ventas_label' => $this->moneyArs((int)($closure['iva_ventas'] ?? 0)),
                'iva_compras_label' => $this->moneyArs((int)($closure['iva_compras'] ?? 0)),
                'saldo_iva_label' => $this->moneyArs((int)($closure['saldo_iva'] ?? 0)),
            ],
            'ventas' => [
                'neto_label' => $this->moneyArs((int)($ventas['neto'] ?? 0)),
                'iva_label' => $this->moneyArs((int)($ventas['iva'] ?? 0)),
                'total_label' => $this->moneyArs((int)($ventas['total'] ?? 0)),
                'fa_total_label' => $this->moneyArs((int)($ventas['fa_total'] ?? 0)),
                'fb_total_label' => $this->moneyArs((int)($ventas['fb_total'] ?? 0)),
                'fc_total_label' => $this->moneyArs((int)($ventas['fc_total'] ?? 0)),
                'nc_total_label' => $this->moneyArs(
                    (int)($ventas['nca_total'] ?? 0) + (int)($ventas['ncb_total'] ?? 0) + (int)($ventas['ncc_total'] ?? 0)
                ),
            ],
            'compras' => [
                'neto_label' => $this->moneyArs((int)($compras['neto'] ?? 0)),
                'iva_label' => $this->moneyArs((int)($compras['iva'] ?? 0)),
                'total_label' => $this->moneyArs((int)($compras['total'] ?? 0)),
            ],
            'closed_by_user_id' => (string)($closure['closed_by_user_id'] ?? ''),
            'closed_at' => (string)($closure['closed_at'] ?? ''),
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeJson(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }

    private function moneyArs(int $pesos): string
    {
        return '$ ' . number_format($pesos, 2, ',', '.');
    }
}



