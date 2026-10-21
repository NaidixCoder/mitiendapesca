<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionComprobantesIndexViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $filters = is_array($input['filters'] ?? null) ? $input['filters'] : [];
        $rows = is_array($input['rows'] ?? null) ? $input['rows'] : [];
        $pagination = is_array($input['pagination'] ?? null) ? $input['pagination'] : [];

        $normalized = $this->normalizeFilters($filters);
        $rowsPrepared = $this->prepareRows($rows);

        $page = (int)($pagination['page'] ?? 1);
        $pages = (int)($pagination['pages'] ?? 1);
        $total = (int)($pagination['total'] ?? 0);

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturacion', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Comprobantes', 'href' => base_url('admin/facturacion/comprobantes')],
            ],
            'filters' => $normalized,
            'status_options' => $this->statusOptions((string)$normalized['status']),
            'kind_options' => $this->kindOptions((string)$normalized['kind']),
            'rows_prepared' => $rowsPrepared,
            'has_rows' => !empty($rowsPrepared),
            'total' => $total,
            'page' => $page,
            'pages' => $pages,
            'show_pagination' => ($pages > 1),
            'prev_url' => ($page > 1)
                ? base_url('admin/facturacion/comprobantes?' . $this->buildQueryString(array_merge($normalized, ['page' => $page - 1])))
                : '',
            'next_url' => ($page < $pages)
                ? base_url('admin/facturacion/comprobantes?' . $this->buildQueryString(array_merge($normalized, ['page' => $page + 1])))
                : '',
            'apply_url' => base_url('admin/facturacion/comprobantes'),
            'reset_url' => base_url('admin/facturacion/comprobantes'),
        ];
    }

    /**
     * @param array<string,mixed> $filters
     * @return array<string,mixed>
     */
    private function normalizeFilters(array $filters): array
    {
        $dateField = (string)($filters['date_field'] ?? '');
        $dateFrom = (string)($filters['date_from'] ?? '');
        $dateTo = (string)($filters['date_to'] ?? '');

        if ($dateFrom === '' && !empty($filters['issued_from'])) {
            $dateField = 'issued';
            $dateFrom = (string)$filters['issued_from'];
        }
        if ($dateTo === '' && !empty($filters['issued_to'])) {
            if ($dateField === '') {
                $dateField = 'issued';
            }
            $dateTo = (string)$filters['issued_to'];
        }
        if ($dateFrom === '' && !empty($filters['created_from'])) {
            $dateField = 'created';
            $dateFrom = (string)$filters['created_from'];
        }
        if ($dateTo === '' && !empty($filters['created_to'])) {
            if ($dateField === '') {
                $dateField = 'created';
            }
            $dateTo = (string)$filters['created_to'];
        }
        if ($dateField !== 'created') {
            $dateField = 'issued';
        }

        return [
            'status' => (string)($filters['status'] ?? ''),
            'kind' => (string)($filters['kind'] ?? ''),
            'q' => (string)($filters['q'] ?? ''),
            'date_field' => $dateField,
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'arca_env' => (string)($filters['arca_env'] ?? ''),
            'pto_vta' => (int)($filters['pto_vta'] ?? 0),
            'cbte_tipo' => (int)($filters['cbte_tipo'] ?? 0),
            'cbte_nro' => (int)($filters['cbte_nro'] ?? 0),
            'issued_from' => (string)($filters['issued_from'] ?? ''),
            'issued_to' => (string)($filters['issued_to'] ?? ''),
            'created_from' => (string)($filters['created_from'] ?? ''),
            'created_to' => (string)($filters['created_to'] ?? ''),
            'order_id' => (int)($filters['order_id'] ?? 0),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function prepareRows(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int)($row['id'] ?? 0);
            $orderId = (int)($row['order_id'] ?? 0);
            $status = (string)($row['status'] ?? '');
            $tipo = (int)($row['cbte_tipo'] ?? 0);

            $out[] = [
                'id' => $id,
                'order_id' => $orderId,
                'order_url' => $orderId > 0 ? base_url('admin/orders/show?id=' . $orderId) : '',
                'status_label' => $this->statusLabel($status),
                'status_badge_class' => $this->statusBadgeClass($status),
                'kind_label' => in_array($tipo, [8, 13], true) ? 'Nota de Credito' : 'Factura',
                'kind_badge_class' => in_array($tipo, [8, 13], true)
                    ? 'border-rose-200 bg-rose-50 text-rose-800'
                    : 'border-slate-200 bg-slate-50 text-slate-700',
                'pto_vta' => (int)($row['pto_vta'] ?? 0),
                'cbte_tipo' => $tipo,
                'cbte_label' => $this->cbteLabel($tipo),
                'cbte_nro' => (int)($row['cbte_nro'] ?? 0),
                'cae' => (string)($row['cae'] ?? ''),
                'imp_total_label' => '$ ' . money($row['imp_total'] ?? 0),
                'created_at' => (string)($row['created_at'] ?? ''),
                'open_url' => base_url('admin/facturacion/comprobantes/show?id=' . $id),
            ];
        }
        return $out;
    }

    private function statusOptions(string $selected): array
    {
        $options = [
            ['value' => '', 'label' => 'Todos'],
            ['value' => 'pending', 'label' => 'Pendiente'],
            ['value' => 'issued', 'label' => 'Emitida'],
            ['value' => 'error', 'label' => 'Error'],
        ];
        foreach ($options as &$option) {
            $option['selected'] = ($option['value'] === $selected);
        }
        unset($option);
        return $options;
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

    private function statusLabel(string $status): string
    {
        if ($status === 'pending') {
            return 'Pendiente';
        }
        if ($status === 'issued') {
            return 'Emitida';
        }
        if ($status === 'error') {
            return 'Error';
        }
        return $status;
    }

    private function statusBadgeClass(string $status): string
    {
        if ($status === 'pending') {
            return 'border-amber-200 bg-amber-50 text-amber-800';
        }
        if ($status === 'issued') {
            return 'border-emerald-200 bg-emerald-50 text-emerald-800';
        }
        if ($status === 'error') {
            return 'border-rose-200 bg-rose-50 text-rose-800';
        }
        return 'border-slate-200 bg-slate-50 text-slate-700';
    }

    private function cbteLabel(int $cbteTipo): string
    {
        if ($cbteTipo === 1) {
            return 'Factura A';
        }
        if ($cbteTipo === 6) {
            return 'Factura B';
        }
        if ($cbteTipo === 11) {
            return 'Factura C';
        }
        if ($cbteTipo === 8) {
            return 'Nota de Credito B';
        }
        if ($cbteTipo === 13) {
            return 'Nota de Credito C';
        }
        return 'Cbte ' . $cbteTipo;
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



