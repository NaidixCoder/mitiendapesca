<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionLibroIvaComprasViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $rows = is_array($input['rows'] ?? null) ? $input['rows'] : [];
        $totals = is_array($input['totals'] ?? null) ? $input['totals'] : ['neto' => 0, 'iva' => 0, 'total' => 0];
        $byType = is_array($input['totals_by_type'] ?? null) ? $input['totals_by_type'] : [];
        $suppliers = is_array($input['suppliers'] ?? null) ? $input['suppliers'] : [];
        $isRI = (bool)($input['is_ri'] ?? false);

        $supplierSelected = (int)($filters['supplier_id'] ?? 0);
        $supplierOptions = [['value' => '', 'label' => 'Todos', 'selected' => ($supplierSelected === 0)]];
        foreach ($suppliers as $supplier) {
            if (!is_array($supplier)) {
                continue;
            }
            $id = (int)($supplier['id'] ?? 0);
            $supplierOptions[] = [
                'value' => (string)$id,
                'label' => (string)($supplier['nombre'] ?? '') . ' (' . (string)($supplier['cuit'] ?? '') . ')',
                'selected' => ($supplierSelected === $id),
            ];
        }

        $rowsByType = [];
        foreach ($byType as $row) {
            if (!is_array($row)) {
                continue;
            }
            $isNc = ((string)($row['kind'] ?? '') === 'credit_note');
            $rowsByType[] = [
                'is_nc' => $isNc,
                'kind_label' => $isNc ? 'NC' : 'Factura',
                'cbte_tipo' => (int)($row['cbte_tipo'] ?? 0),
                'neto_label' => $this->moneyArs((int)($row['neto'] ?? 0)),
                'iva_label' => $this->moneyArs((int)($row['iva'] ?? 0)),
                'total_label' => $this->moneyArs((int)($row['total'] ?? 0)),
            ];
        }

        $rowsPrepared = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $isNc = ((string)($row['kind'] ?? '') === 'credit_note');
            $sign = $isNc ? -1 : 1;
            $status = (string)($row['status'] ?? '');
            $rowsPrepared[] = [
                'is_nc' => $isNc,
                'issued_at' => (string)($row['issued_at'] ?? ''),
                'supplier_nombre' => (string)($row['supplier_nombre'] ?? ''),
                'supplier_cuit' => (string)($row['supplier_cuit'] ?? ''),
                'cbte_tipo' => (int)($row['cbte_tipo'] ?? 0),
                'pto_vta' => (int)($row['pto_vta'] ?? 0),
                'cbte_nro' => (int)($row['cbte_nro'] ?? 0),
                'neto_label' => $this->signedMoneyArs($sign * (int)($row['imp_neto'] ?? 0)),
                'iva_label' => $this->signedMoneyArs($sign * (int)($row['imp_iva'] ?? 0)),
                'total_label' => $this->signedMoneyArs($sign * (int)($row['imp_total'] ?? 0)),
                'status_label' => $this->statusLabel($status),
                'status_badge_class' => $this->statusBadgeClass($status),
            ];
        }

        $filtersPrepared = [
            'from' => (string)($filters['from'] ?? ''),
            'to' => (string)($filters['to'] ?? ''),
            'supplier_id' => $supplierSelected,
            'kind' => (string)($filters['kind'] ?? ''),
            'status' => (string)($filters['status'] ?? ''),
        ];

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturacion', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Libro IVA Compras', 'href' => base_url('admin/facturacion/libro-iva-compras')],
            ],
            'is_ri' => $isRI,
            'filters' => $filtersPrepared,
            'supplier_options' => $supplierOptions,
            'kind_options' => $this->kindOptions((string)$filtersPrepared['kind']),
            'status_options' => $this->statusOptions((string)$filtersPrepared['status']),
            'apply_url' => base_url('admin/facturacion/libro-iva-compras'),
            'reset_url' => base_url('admin/facturacion/libro-iva-compras'),
            'export_url' => base_url('admin/facturacion/libro-iva-compras/export?' . $this->buildQueryString($filtersPrepared)),
            'totals' => [
                'neto_label' => $this->moneyArs((int)($totals['neto'] ?? 0)),
                'iva_label' => $this->moneyArs((int)($totals['iva'] ?? 0)),
                'total_label' => $this->moneyArs((int)($totals['total'] ?? 0)),
            ],
            'rows_by_type_prepared' => $rowsByType,
            'rows_prepared' => $rowsPrepared,
            'has_rows_by_type' => !empty($rowsByType),
            'has_rows' => !empty($rowsPrepared),
        ];
    }

    private function kindOptions(string $selected): array
    {
        $options = [
            ['value' => '', 'label' => 'Todos'],
            ['value' => 'invoice', 'label' => 'Facturas'],
            ['value' => 'credit_note', 'label' => 'Notas de credito'],
        ];
        foreach ($options as &$option) {
            $option['selected'] = ($option['value'] === $selected);
        }
        unset($option);
        return $options;
    }

    private function statusOptions(string $selected): array
    {
        $options = [
            ['value' => '', 'label' => 'Activas'],
            ['value' => 'loaded', 'label' => 'Cargada'],
            ['value' => 'validated', 'label' => 'Validada'],
            ['value' => 'void', 'label' => 'Anulada'],
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

    private function signedMoneyArs(int $pesos): string
    {
        $prefix = $pesos < 0 ? '-$ ' : '$ ';
        return $prefix . number_format(abs($pesos), 2, ',', '.');
    }

    private function statusLabel(string $status): string
    {
        if ($status === 'validated') {
            return 'Validada';
        }
        if ($status === 'loaded') {
            return 'Cargada';
        }
        return 'Anulada';
    }

    private function statusBadgeClass(string $status): string
    {
        if ($status === 'validated') {
            return 'badge-success';
        }
        if ($status === 'loaded') {
            return 'badge-secondary';
        }
        return 'badge-danger';
    }
}



