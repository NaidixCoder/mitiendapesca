<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionComprasIndexViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $items = is_array($input['items'] ?? null) ? $input['items'] : [];
        $suppliers = is_array($input['suppliers'] ?? null) ? $input['suppliers'] : [];

        $supplierId = (int)($filters['supplier_id'] ?? 0);
        $supplierOptions = [['value' => '', 'label' => 'Todos', 'selected' => ($supplierId === 0)]];
        foreach ($suppliers as $supplier) {
            if (!is_array($supplier)) {
                continue;
            }
            $id = (int)($supplier['id'] ?? 0);
            $supplierOptions[] = [
                'value' => (string)$id,
                'label' => (string)($supplier['nombre'] ?? '') . ' (' . (string)($supplier['cuit'] ?? '') . ')',
                'selected' => ($supplierId === $id),
            ];
        }

        $itemsPrepared = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $isNc = ((string)($item['kind'] ?? '') === 'credit_note');
            $sign = $isNc ? -1 : 1;
            $status = (string)($item['status'] ?? '');
            $id = (int)($item['id'] ?? 0);
            $itemsPrepared[] = [
                'id' => $id,
                'is_nc' => $isNc,
                'issued_at' => (string)($item['issued_at'] ?? ''),
                'supplier_nombre' => (string)($item['supplier_nombre'] ?? ''),
                'supplier_cuit' => (string)($item['supplier_cuit'] ?? ''),
                'cbte_tipo' => (int)($item['cbte_tipo'] ?? 0),
                'pto_vta' => (int)($item['pto_vta'] ?? 0),
                'cbte_nro' => (int)($item['cbte_nro'] ?? 0),
                'kind_label' => $isNc ? 'Nota de credito' : 'Factura',
                'neto_label' => $this->moneyArs($sign * (int)($item['imp_neto'] ?? 0)),
                'iva_label' => $this->moneyArs($sign * (int)($item['imp_iva'] ?? 0)),
                'total_label' => $this->moneyArs($sign * (int)($item['imp_total'] ?? 0)),
                'status' => $status,
                'status_label' => $this->statusLabel($status),
                'status_badge_class' => $this->statusBadgeClass($status),
                'edit_url' => base_url('admin/facturacion/compras/edit?id=' . $id),
                'validate_url' => base_url('admin/facturacion/compras/validate'),
                'void_url' => base_url('admin/facturacion/compras/void'),
                'show_validate' => ($status === 'loaded'),
                'show_void' => ($status !== 'void'),
            ];
        }

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturacion', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Compras', 'href' => base_url('admin/facturacion/compras')],
            ],
            'filters' => [
                'q' => (string)($filters['q'] ?? ''),
                'from' => (string)($filters['from'] ?? ''),
                'to' => (string)($filters['to'] ?? ''),
                'supplier_id' => $supplierId,
                'status' => (string)($filters['status'] ?? ''),
                'kind' => (string)($filters['kind'] ?? ''),
            ],
            'supplier_options' => $supplierOptions,
            'kind_options' => $this->kindOptions((string)($filters['kind'] ?? '')),
            'status_options' => $this->statusOptions((string)($filters['status'] ?? '')),
            'items_prepared' => $itemsPrepared,
            'has_items' => !empty($itemsPrepared),
            'new_url' => base_url('admin/facturacion/compras/new'),
            'apply_url' => base_url('admin/facturacion/compras'),
            'reset_url' => base_url('admin/facturacion/compras'),
            'csrf_field' => csrf_field(),
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

    private function moneyArs(int $pesos): string
    {
        return '$ ' . number_format($pesos, 2, ',', '.');
    }
}



