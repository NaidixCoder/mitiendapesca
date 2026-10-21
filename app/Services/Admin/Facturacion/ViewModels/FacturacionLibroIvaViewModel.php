<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionLibroIvaViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $rows = is_array($input['rows'] ?? null) ? $input['rows'] : [];
        $totals = is_array($input['totals'] ?? null) ? $input['totals'] : ['total' => 0, 'neto' => 0, 'iva' => 0];
        $totalsByType = is_array($input['totals_by_type'] ?? null) ? $input['totals_by_type'] : [];

        $crumbs = [
            ['label' => 'Admin', 'href' => base_url('admin')],
            ['label' => 'Facturación', 'href' => base_url('admin/facturacion/control')],
            ['label' => 'Libro IVA', 'href' => base_url('admin/facturacion/libro-iva')],
        ];

        $envOptions = [
            ['value' => '', 'label' => 'Todos'],
            ['value' => 'homologacion', 'label' => 'Homologación'],
            ['value' => 'produccion', 'label' => 'Producción'],
        ];
        $kindOptions = [
            ['value' => '', 'label' => 'Todos'],
            ['value' => 'invoice', 'label' => 'Facturas'],
            ['value' => 'credit_note', 'label' => 'Notas de crédito'],
        ];

        $selectedEnv = (string)($filters['arca_env'] ?? '');
        foreach ($envOptions as &$option) {
            $option['selected'] = ((string)$option['value'] === $selectedEnv);
        }
        unset($option);

        $selectedKind = (string)($filters['kind'] ?? '');
        foreach ($kindOptions as &$option) {
            $option['selected'] = ((string)$option['value'] === $selectedKind);
        }
        unset($option);

        $filtersForQs = array_merge($filters, []);
        foreach ($filtersForQs as $key => $value) {
            if ($value === null || $value === '' || $value === 0) {
                unset($filtersForQs[$key]);
            }
        }

        $rowsPrepared = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int)($row['id'] ?? 0);
            $orderId = (int)($row['order_id'] ?? 0);
            $cbteTipo = (int)($row['cbte_tipo'] ?? 0);
            $pv = (int)($row['pto_vta'] ?? 0);
            $nro = (int)($row['cbte_nro'] ?? 0);

            $rowsPrepared[] = [
                'issued_at' => (string)($row['issued_at'] ?? ''),
                'order_id' => $orderId,
                'order_url' => url('/admin/orders/show?id=' . $orderId),
                'cbte_label' => $this->cbteLabel($cbteTipo),
                'pv_nro' => 'PV ' . $pv . ' / ' . $nro,
                'doc_value' => '80 / ' . (string)($row['doc_nro'] ?? ''),
                'imp_neto' => $this->moneyArs((int)($row['imp_neto'] ?? 0)),
                'imp_iva' => $this->moneyArs((int)($row['imp_iva'] ?? 0)),
                'imp_total' => $this->moneyArs((int)($row['imp_total'] ?? 0)),
                'cae' => (string)($row['cae'] ?? ''),
                'show_url' => url('/admin/facturacion/comprobantes/show?id=' . $id),
            ];
        }

        $totalsByTypePrepared = [];
        foreach ($totalsByType as $row) {
            if (!is_array($row)) {
                continue;
            }
            $kind = (string)($row['kind'] ?? '');
            $totalsByTypePrepared[] = [
                'cbte_label' => $this->cbteLabel((int)($row['cbte_tipo'] ?? 0)),
                'kind_label' => $kind === 'credit_note' ? 'Nota de crédito' : 'Factura',
                'count' => (int)($row['count'] ?? 0),
                'neto' => $this->moneyArs((int)($row['neto'] ?? 0)),
                'iva' => $this->moneyArs((int)($row['iva'] ?? 0)),
                'total' => $this->moneyArs((int)($row['total'] ?? 0)),
            ];
        }

        $regime = strtolower(trim((string)app_setting('fiscal_seller_tax_regime', 'monotributo')));
        if ($regime === '') {
            $regime = 'monotributo';
        }

        return [
            'crumbs' => $crumbs,
            'filters' => $filters,
            'env_options' => $envOptions,
            'kind_options' => $kindOptions,
            'is_ri' => ($regime === 'responsable_inscripto'),
            'export_url' => url('/admin/facturacion/libro-iva/export?' . http_build_query($filtersForQs)),
            'total_neto' => $this->moneyArs((int)($totals['neto'] ?? 0)),
            'total_iva' => $this->moneyArs((int)($totals['iva'] ?? 0)),
            'total_total' => $this->moneyArs((int)($totals['total'] ?? 0)),
            'totals_by_type_rows' => $totalsByTypePrepared,
            'totals_by_type_count' => count($totalsByTypePrepared),
            'rows_prepared' => $rowsPrepared,
            'rows_count' => count($rowsPrepared),
        ];
    }

    private function cbteLabel(int $cbteTipo): string
    {
        return match ($cbteTipo) {
            1 => 'Factura A',
            6 => 'Factura B',
            11 => 'Factura C',
            3 => 'Nota de Crédito A',
            8 => 'Nota de Crédito B',
            13 => 'Nota de Crédito C',
            default => 'Cbte ' . $cbteTipo,
        };
    }

    private function moneyArs(int $pesos): string
    {
        return '$ ' . number_format($pesos, 2, ',', '.');
    }
}




