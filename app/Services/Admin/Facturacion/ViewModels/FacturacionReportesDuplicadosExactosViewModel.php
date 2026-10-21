<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionReportesDuplicadosExactosViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $conciliation = is_array($input['conciliation'] ?? null) ? $input['conciliation'] : [];
        $rows = is_array($conciliation['duplicates_exact'] ?? null) ? $conciliation['duplicates_exact'] : [];

        $rowsPrepared = [];
        foreach ($rows as $row) {
            $env = (string)($row['arca_env'] ?? '');
            $kind = (string)($row['kind'] ?? '');
            $pto = (int)($row['pto_vta'] ?? 0);
            $tipo = (int)($row['cbte_tipo'] ?? 0);
            $nro = (int)($row['cbte_nro'] ?? 0);
            $query = $this->buildQueryString([
                'status' => 'issued',
                'kind' => $kind,
                'arca_env' => $env,
                'date_field' => 'issued',
                'date_from' => (string)($filters['from'] ?? ''),
                'date_to' => (string)($filters['to'] ?? ''),
                'pto_vta' => $pto,
                'cbte_tipo' => $tipo,
                'cbte_nro' => $nro,
            ]);
            $rowsPrepared[] = [
                'env_text' => $env ?: '—',
                'kind_text' => $kind === 'credit_note' ? 'NC' : 'Factura',
                'pto' => $pto,
                'cbte' => $tipo . ' / ' . $nro,
                'count' => (int)($row['c'] ?? 0),
                'first_issued_at' => (string)($row['first_issued_at'] ?? ''),
                'last_issued_at' => (string)($row['last_issued_at'] ?? ''),
                'comprobantes_url' => base_url('admin/facturacion/comprobantes?' . $query),
            ];
        }

        $backQuery = $this->buildQueryString(array_merge($filters, ['tab' => 'conciliacion']));
        $exportQuery = $this->buildQueryString(array_merge($filters, ['tab' => null, 'mode' => 'conciliacion_duplicados']));

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturación', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Reportes', 'href' => base_url('admin/facturacion/reportes')],
                ['label' => 'Duplicados exactos', 'href' => base_url('admin/facturacion/reportes/duplicados-exactos')],
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




