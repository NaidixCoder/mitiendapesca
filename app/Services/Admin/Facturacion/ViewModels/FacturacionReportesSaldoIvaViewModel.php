<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionReportesSaldoIvaViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $rows = is_array($input['rows'] ?? null) ? $input['rows'] : [];
        $totals = is_array($input['totals'] ?? null) ? $input['totals'] : [];

        $env = (string)($filters['arca_env'] ?? '');

        $rowsPrepared = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $saldo = (int)($row['saldo_iva'] ?? 0);
            $rowsPrepared[] = [
                'ym' => (string)($row['ym'] ?? ''),
                'ventas_iva_label' => $this->moneyArs((int)($row['ventas_iva'] ?? 0)),
                'compras_iva_label' => $this->moneyArs((int)($row['compras_iva'] ?? 0)),
                'saldo_label' => $this->signedMoneyArs($saldo),
                'ventas_total_label' => $this->moneyArs((int)($row['ventas_total'] ?? 0)),
                'compras_total_label' => $this->moneyArs((int)($row['compras_total'] ?? 0)),
                'ventas_comprobantes' => (int)($row['ventas_comprobantes'] ?? 0),
                'compras_comprobantes' => (int)($row['compras_comprobantes'] ?? 0),
            ];
        }

        $saldoTotal = (int)($totals['saldo_iva'] ?? 0);

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturacion', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Reportes', 'href' => base_url('admin/facturacion/reportes')],
                ['label' => 'Saldo IVA', 'href' => base_url('admin/facturacion/reportes/saldo-iva')],
            ],
            'filters' => [
                'from_month' => (string)($filters['from_month'] ?? ''),
                'to_month' => (string)($filters['to_month'] ?? ''),
                'arca_env' => $env,
                'from_date' => (string)($filters['from_date'] ?? ''),
                'to_date' => (string)($filters['to_date'] ?? ''),
            ],
            'env_options' => $this->envOptions($env),
            'apply_url' => url('/admin/facturacion/reportes/saldo-iva'),
            'reset_url' => url('/admin/facturacion/reportes/saldo-iva'),
            'export_url' => url('/admin/facturacion/reportes/saldo-iva/export?' . $this->buildQueryString($filters)),
            'totals_cards' => [
                'iva_debito_label' => $this->moneyArs((int)($totals['iva_debito'] ?? 0)),
                'iva_credito_label' => $this->moneyArs((int)($totals['iva_credito'] ?? 0)),
                'saldo_label' => $this->signedMoneyArs($saldoTotal),
                'saldo_hint' => $saldoTotal >= 0 ? 'IVA a ingresar (estimado)' : 'Saldo a favor (estimado)',
            ],
            'rows_prepared' => $rowsPrepared,
            'has_rows' => !empty($rowsPrepared),
        ];
    }

    private function envOptions(string $selected): array
    {
        $options = [
            ['value' => '', 'label' => 'Todos'],
            ['value' => 'homologacion', 'label' => 'Homologacion'],
            ['value' => 'produccion', 'label' => 'Produccion'],
        ];
        foreach ($options as &$option) {
            $option['selected'] = ($option['value'] === $selected);
        }
        unset($option);
        return $options;
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

    private function moneyArs(int $pesos): string
    {
        return number_format($pesos, 2, ',', '.');
    }

    private function signedMoneyArs(int $pesos): string
    {
        $abs = number_format(abs($pesos), 2, ',', '.');
        return ($pesos < 0 ? '-' : '') . $abs;
    }
}



