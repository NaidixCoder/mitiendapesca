<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionComprasFormViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $item = is_array($input['item'] ?? null) ? $input['item'] : null;
        $old = is_array($input['old'] ?? null) ? $input['old'] : [];
        $errors = is_array($input['errors'] ?? null) ? $input['errors'] : [];
        $suppliers = is_array($input['suppliers'] ?? null) ? $input['suppliers'] : [];
        $attachments = is_array($input['attachments'] ?? null) ? $input['attachments'] : [];

        $isEdit = is_array($item) && !empty($item['id']);
        $itemId = $isEdit ? (int)$item['id'] : 0;

        $values = [];
        $keys = [
            'supplier_id',
            'issued_at',
            'cbte_tipo',
            'pto_vta',
            'cbte_nro',
            'imp_neto',
            'imp_iva',
            'perc_iva',
            'perc_iibb',
            'ret_ganancias',
            'imp_total',
            'status',
            'notes',
        ];
        foreach ($keys as $key) {
            if (array_key_exists($key, $old)) {
                $values[$key] = (string)$old[$key];
                continue;
            }
            if (is_array($item) && array_key_exists($key, $item)) {
                $values[$key] = (string)$item[$key];
                continue;
            }
            $values[$key] = '';
        }

        if ($values['issued_at'] === '') {
            $values['issued_at'] = date('Y-m-d');
        }
        if ($values['cbte_tipo'] === '') {
            $values['cbte_tipo'] = '1';
        }
        if ($values['pto_vta'] === '') {
            $values['pto_vta'] = '0';
        }
        foreach (['imp_neto', 'imp_iva', 'perc_iva', 'perc_iibb', 'ret_ganancias', 'imp_total'] as $k) {
            if ($values[$k] === '') {
                $values[$k] = '0';
            }
        }
        if ($values['status'] === '') {
            $values['status'] = 'loaded';
        }

        $cbteTipo = (int)$values['cbte_tipo'];

        $crumbs = [
            ['label' => 'Admin', 'href' => base_url('admin')],
            ['label' => 'Facturación', 'href' => base_url('admin/facturacion/control')],
            ['label' => 'Compras', 'href' => base_url('admin/facturacion/compras')],
            [
                'label' => $isEdit ? 'Editar' : 'Nueva',
                'href' => base_url($isEdit ? ('admin/facturacion/compras/edit?id=' . $itemId) : 'admin/facturacion/compras/new'),
            ],
        ];

        $supplierOptions = [];
        foreach ($suppliers as $supplier) {
            if (!is_array($supplier)) {
                continue;
            }
            $supplierId = (int)($supplier['id'] ?? 0);
            $supplierOptions[] = [
                'id' => $supplierId,
                'label' => (string)($supplier['nombre'] ?? ''),
                'cuit' => (string)($supplier['cuit'] ?? ''),
                'selected' => (int)$values['supplier_id'] === $supplierId,
            ];
        }

        $attachmentsPrepared = [];
        foreach ($attachments as $attachment) {
            if (!is_array($attachment)) {
                continue;
            }
            $file = (string)($attachment['file'] ?? '');
            $size = (int)($attachment['size'] ?? 0);
            $attachmentsPrepared[] = [
                'name' => (string)($attachment['name'] ?? ''),
                'file' => $file,
                'size_kb' => number_format($size / 1024, 1, ',', '.'),
                'created_at' => (string)($attachment['created_at'] ?? ''),
                'view_url' => base_url('admin/facturacion/compras/adjunto/ver?id=' . $itemId . '&file=' . urlencode($file)),
                'download_url' => base_url('admin/facturacion/compras/adjunto/descargar?id=' . $itemId . '&file=' . urlencode($file)),
            ];
        }

        $cbteOptions = [
            ['value' => 1, 'label' => 'Factura A (1)'],
            ['value' => 6, 'label' => 'Factura B (6)'],
            ['value' => 11, 'label' => 'Factura C (11)'],
            ['value' => 3, 'label' => 'Nota de Credito A (3)'],
            ['value' => 8, 'label' => 'Nota de Credito B (8)'],
            ['value' => 13, 'label' => 'Nota de Credito C (13)'],
        ];
        foreach ($cbteOptions as &$row) {
            $row['selected'] = ((int)$row['value'] === $cbteTipo);
        }
        unset($row);

        $statusOptions = [
            ['value' => 'loaded', 'label' => 'Cargada'],
            ['value' => 'validated', 'label' => 'Validada'],
        ];
        foreach ($statusOptions as &$row) {
            $row['selected'] = ((string)$row['value'] === (string)$values['status']);
        }
        unset($row);

        return [
            'item' => $item,
            'is_edit' => $isEdit,
            'item_id' => $itemId,
            'crumbs' => $crumbs,
            'values' => $values,
            'errors' => $errors,
            'cbte_tipo' => $cbteTipo,
            'status' => (string)$values['status'],
            'supplier_options' => $supplierOptions,
            'attachments_prepared' => $attachmentsPrepared,
            'cbte_options' => $cbteOptions,
            'status_options' => $statusOptions,
        ];
    }
}




