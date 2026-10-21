<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionReportesMensualViewModel
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

        $fromMonth = (string)($filters['from_month'] ?? '');
        $toMonth = (string)($filters['to_month'] ?? '');
        $arcaEnv = (string)($filters['arca_env'] ?? '');

        $rowsPrepared = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $ym = (string)($row['ym'] ?? '');
            $from = $ym !== '' ? ($ym . '-01') : '';
            $to = $from !== '' ? (string)date('Y-m-t', strtotime($from)) : '';
            $libroUrl = '';
            if ($from !== '' && $to !== '') {
                $libroUrl = base_url('admin/facturacion/libro-iva?' . http_build_query([
                    'from' => $from,
                    'to' => $to,
                    'arca_env' => $arcaEnv,
                ]));
            }

            $rowsPrepared[] = [
                'ym' => $ym,
                'libro_url' => $libroUrl,
                'comprobantes_count' => (int)($row['comprobantes_count'] ?? 0),
                'facturas_count' => (int)($row['facturas_count'] ?? 0),
                'notas_credito_count' => (int)($row['notas_credito_count'] ?? 0),
                'neto_label' => $this->moneyArs((int)($row['neto'] ?? 0)),
                'iva_label' => $this->moneyArs((int)($row['iva'] ?? 0)),
                'total_label' => $this->moneyArs((int)($row['total'] ?? 0)),
            ];
        }

        $filtersPrepared = [
            'from_month' => $fromMonth,
            'to_month' => $toMonth,
            'arca_env' => $arcaEnv,
            'from_date' => (string)($filters['from_date'] ?? ''),
            'to_date' => (string)($filters['to_date'] ?? ''),
        ];

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturacion', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Reportes', 'href' => base_url('admin/facturacion/reportes')],
                ['label' => 'Reporte mensual', 'href' => base_url('admin/facturacion/reportes/mensual')],
            ],
            'filters' => $filtersPrepared,
            'env_options' => $this->envOptions($arcaEnv),
            'apply_url' => base_url('admin/facturacion/reportes/mensual'),
            'reset_url' => base_url('admin/facturacion/reportes/mensual'),
            'export_url' => base_url('admin/facturacion/reportes/mensual/export?' . $this->buildQueryString($filtersPrepared)),
            'totals' => [
                'comprobantes_count' => (int)($totals['comprobantes_count'] ?? 0),
                'facturas_count' => (int)($totals['facturas_count'] ?? 0),
                'notas_credito_count' => (int)($totals['notas_credito_count'] ?? 0),
                'neto_label' => $this->moneyArs((int)($totals['neto'] ?? 0)),
                'iva_label' => $this->moneyArs((int)($totals['iva'] ?? 0)),
                'total_label' => $this->moneyArs((int)($totals['total'] ?? 0)),
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
        return '$ ' . number_format($pesos, 2, ',', '.');
    }
}



