<?php
namespace App\Services\Admin\Dashboard;

final class DashboardPartialsViewModelService
{
    /**
     * @param array<int,array<string,mixed>> $kpis
     * @return array<int,array<string,mixed>>
     */
    public function prepareKpis(array $kpis): array
    {
        $out = [];
        foreach ($kpis as $kpi) {
            $href = trim((string)($kpi['href'] ?? ''));
            $out[] = [
                'label' => (string)($kpi['label'] ?? ''),
                'value' => (string)($kpi['value'] ?? '0'),
                'sub' => (string)($kpi['sub'] ?? ''),
                'href' => $href,
                'is_link' => $href !== '',
            ];
        }
        return $out;
    }

    /**
     * @param array<int,array<string,mixed>> $catalogCards
     * @param array<int,array<string,mixed>> $opsCards
     * @return array<int,array<string,mixed>>
     */
    public function prepareCombinedKpis(array $catalogCards, array $opsCards): array
    {
        return array_merge($this->prepareKpis($catalogCards), $this->prepareKpis($opsCards));
    }

    /**
     * @param array<string,mixed> $todos
     * @return array<int,array<string,mixed>>
     */
    public function prepareTodosCards(array $todos): array
    {
        return [
            ['label' => 'A preparar', 'value' => (int)($todos['to_prepare'] ?? 0), 'href' => url('/admin/orders?status=paid')],
            ['label' => 'Pagos pendientes', 'value' => (int)($todos['payments_pending'] ?? 0), 'href' => url('/admin/orders?status=pending_payment')],
            ['label' => 'Stock en 0', 'value' => (int)($todos['stock_zero'] ?? 0), 'href' => url('/admin/products')],
            ['label' => 'Stock bajo', 'value' => (int)($todos['low_stock'] ?? 0), 'href' => url('/admin/products')],
            ['label' => '+24 h sin resolver', 'value' => (int)($todos['orders_over24'] ?? 0), 'href' => url('/admin/orders')],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $orders
     * @return array<string,mixed>
     */
    public function preparePendingOrdersPanel(array $orders, ?string $ordersBase, ?string $orderShowBase, ?string $orderStatusEndpoint): array
    {
        $attrs = [];
        if (!empty($ordersBase)) {
            $attrs['index_base'] = (string)$ordersBase;
        }
        if (!empty($orderShowBase)) {
            $attrs['show_base'] = (string)$orderShowBase;
        }
        if (!empty($orderStatusEndpoint)) {
            $attrs['status_endpoint'] = (string)$orderStatusEndpoint;
        }

        $rows = [];
        foreach ($orders as $order) {
            $id = (int)($order['id'] ?? 0);
            $href = trim((string)($order['href'] ?? ''));
            if ($href === '' && $id > 0) {
                $href = url('/admin/orders/show?id=' . $id);
            }
            $total = (int)($order['total'] ?? 0);
            $rows[] = [
                'id_label' => '#' . $id,
                'href' => $href,
                'created_label' => $this->toDateOnly((string)($order['created_at'] ?? '')),
                'is_urgent' => !empty($order['_urgent']),
                'customer_label' => (string)($order['customer'] ?? '-'),
                'status_label' => (string)($order['status_label'] ?? ($order['status'] ?? '')),
                'badge_class' => (string)($order['badge_class'] ?? 'badge'),
                'total_label' => '$ ' . number_format($total, 0, ',', '.'),
                'row_class' => !empty($order['_urgent']) ? 'border-l-4 border-red-500' : '',
            ];
        }

        return [
            'attrs' => $attrs,
            'orders_base' => (string)($ordersBase ?? ''),
            'rows' => $rows,
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $alerts
     * @return array<int,array<string,string>>
     */
    public function prepareAlerts(array $alerts): array
    {
        $out = [];
        foreach ($alerts as $item) {
            $text = trim((string)($item['text'] ?? ''));
            if ($text === '') {
                continue;
            }
            $level = (string)($item['level'] ?? 'info');
            $out[] = [
                'text' => $text,
                'href' => (string)($item['href'] ?? ''),
                'tone' => match ($level) {
                    'danger' => 'is-danger',
                    'warning' => 'is-warn',
                    default => 'is-info',
                },
                'badge_class' => $this->alertBadgeClass($level),
            ];
        }
        return $out;
    }

    /**
     * @param ?array<string,mixed> $job
     * @return ?array<string,mixed>
     */
    public function prepareLastImport(?array $job): ?array
    {
        if (empty($job)) {
            return null;
        }

        $status = (string)($job['status'] ?? 'unknown');
        return [
            'job_type' => (string)($job['job_type'] ?? '-'),
            'status' => $status,
            'status_badge_class' => $this->importBadgeClass($status),
            'file_name' => (string)($job['file_name'] ?? ''),
            'ok_rows' => (int)($job['ok_rows'] ?? 0),
            'err_rows' => (int)($job['err_rows'] ?? 0),
            'total_rows' => (int)($job['total_rows'] ?? 0),
            'created_at_label' => $this->dateSlice((string)($job['created_at'] ?? '')),
            'finished_at_label' => $this->dateSlice((string)($job['finished_at'] ?? '')),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function prepareTopClients(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $name = trim((string)($row['name'] ?? ''));
            if ($name === '') {
                $name = 'Cliente #' . $id;
            }

            $lastRaw = (string)($row['last_order_at'] ?? '');
            $last = '-';
            if ($lastRaw !== '') {
                $ts = strtotime($lastRaw);
                if ($ts !== false) {
                    $last = date('d/m/Y', $ts);
                }
            }

            $out[] = [
                'id' => $id,
                'display_name' => $name,
                'display_email' => trim((string)($row['email'] ?? '')),
                'orders_count' => (int)($row['orders_count'] ?? 0),
                'total_spent' => (int)($row['total_spent'] ?? 0),
                'last_order_label' => $last,
                'profile_href' => url('/admin/users/show?id=' . $id),
            ];
        }
        return $out;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function prepareLowStock(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $stock = (int)($row['stock'] ?? 0);
            $thr = (int)($row['thr'] ?? 0);
            $rowClass = '';
            if ($stock <= 0) {
                $rowClass = 'stock-critical';
            } elseif ($thr > 0 && $stock <= (int)floor($thr / 2)) {
                $rowClass = 'stock-warning';
            }

            $id = (int)($row['id'] ?? 0);
            $out[] = [
                'sku' => (string)($row['sku'] ?? ''),
                'name' => (string)($row['name'] ?? ''),
                'variant_title' => (string)($row['variant_title'] ?? ''),
                'stock' => $stock,
                'thr' => $thr,
                'row_class' => $rowClass,
                'edit_href' => $id > 0 ? url('/admin/products/edit?id=' . $id) : '',
            ];
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $data
     * @return array<string,mixed>
     */
    public function prepareNavigation(array $data): array
    {
        $days = (int)($data['navDays'] ?? 30);
        $funnel = is_array($data['navFunnel'] ?? null) ? $data['navFunnel'] : [];

        return [
            'days' => $days,
            'funnel_cards' => [
                ['label' => 'Visitas al checkout', 'value' => (int)($funnel['checkout_views'] ?? 0)],
                ['label' => 'Compras (registradas)', 'value' => (int)($funnel['purchases'] ?? 0)],
                ['label' => 'Conversion (checkout -> compra)', 'value' => number_format((float)($funnel['conversion'] ?? 0.0), 2, ',', '.') . '%'],
            ],
            'top_pages' => $this->preparePathRows((array)($data['navTopPages'] ?? []), 'views'),
            'landing_pages' => $this->preparePathRows((array)($data['navLandingPages'] ?? []), 'sessions'),
            'top_products' => $this->prepareProductRows((array)($data['navTopProducts'] ?? []), 'views'),
            'top_add_to_cart' => $this->prepareVariantRows((array)($data['navTopAddToCart'] ?? []), 'adds'),
            'top_view_to_cart' => $this->prepareVariantRowsWithConversion((array)($data['navTopViewToCart'] ?? [])),
            'top_sources' => $this->prepareSources((array)($data['navTopSources'] ?? [])),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function prepareFeaturedProducts(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $id = (int)($row['id'] ?? 0);
            $amount = (int)($row['amount'] ?? 0);
            $out[] = [
                'sku' => (string)($row['sku'] ?? '-'),
                'product_name' => (string)($row['product_name'] ?? '-'),
                'variant_title' => (string)($row['variant_title'] ?? ''),
                'qty' => (int)($row['qty'] ?? 0),
                'amount_label' => '$ ' . number_format($amount, 0, ',', '.'),
                'edit_href' => $id > 0 ? url('/admin/products/edit?id=' . $id) : '',
            ];
        }
        return $out;
    }

    /**
     * @param array<int,array<string,mixed>> $events
     * @return array<int,array<string,mixed>>
     */
    public function prepareActivity(array $events): array
    {
        $out = [];
        foreach ($events as $event) {
            $count = (int)($event['count'] ?? 0);
            $out[] = [
                'icon' => (string)($event['icon'] ?? '🧩'),
                'text' => (string)($event['text'] ?? 'Accion'),
                'url' => (string)($event['url'] ?? ''),
                'actor' => (string)($event['actor'] ?? 'Sistema'),
                'when' => (string)($event['when'] ?? ''),
                'created_at' => (string)($event['created_at'] ?? ''),
                'count' => $count,
                'count_label' => $count > 1 ? ('x' . $count) : '',
            ];
        }
        return $out;
    }

    /**
     * Payload plano para que la vista dashboard/index sea ensambladora.
     *
     * @param array<int,array<string,mixed>> $alerts
     * @param array<string,mixed> $pendingOrdersPanel
     * @param array<int,array<string,mixed>> $kpiCards
     * @param array<int,array<string,mixed>> $featuredRows
     * @param array<int,array<string,mixed>> $topClientsRows
     * @param array<int,array<string,mixed>> $todosCards
     * @param array<int,array<string,mixed>> $lowStockRows
     * @param array<int,array<string,mixed>> $activityEvents
     * @return array<string,mixed>
     */
    public function buildIndexView(
        array $alerts,
        array $pendingOrdersPanel,
        array $kpiCards,
        array $featuredRows,
        array $topClientsRows,
        array $todosCards,
        array $lowStockRows,
        array $activityEvents
    ): array {
        return [
            'alerts' => $alerts,
            'pendingPanel' => $pendingOrdersPanel,
            'kpis' => $kpiCards,
            'featuredRows' => $featuredRows,
            'topClientsRows' => $topClientsRows,
            'todosCards' => $todosCards,
            'lowStockRows' => $lowStockRows,
            'activityEvents' => $activityEvents,
        ];
    }

    private function alertBadgeClass(string $level): string
    {
        return match ($level) {
            'danger' => 'bg-red-50 text-red-700 ring-1 ring-red-200',
            'warning' => 'bg-amber-50 text-amber-800 ring-1 ring-amber-200',
            'info' => 'bg-blue-50 text-blue-700 ring-1 ring-blue-200',
            default => 'bg-[color:var(--soft)] text-[color:var(--fg)] ring-1 ring-[color:var(--line-soft)]',
        };
    }

    private function importBadgeClass(string $status): string
    {
        $badge = 'badge';
        if ($status === 'finished' || $status === 'done') {
            return $badge . ' badge--ok';
        }
        if ($status === 'processing' || $status === 'running') {
            return $badge . ' badge--warn';
        }
        if ($status === 'failed' || $status === 'error') {
            return $badge . ' badge--err';
        }
        return $badge;
    }

    private function dateSlice(string $value): string
    {
        return $value !== '' ? substr($value, 0, 16) : '-';
    }

    private function toDateOnly(string $value): string
    {
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return $value;
        }
        return date('d/m/Y', $ts);
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function preparePathRows(array $rows, string $metricKey): array
    {
        $out = [];
        foreach ($rows as $row) {
            $path = (string)($row['path'] ?? '');
            $label = (string)($row['label'] ?? $path);
            $out[] = [
                'label' => $label !== '' ? $label : '(sin path)',
                'href' => $path !== '' ? url($path) : '',
                'metric' => (int)($row[$metricKey] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function prepareProductRows(array $rows, string $metricKey): array
    {
        $out = [];
        foreach ($rows as $row) {
            $slug = (string)($row['product_slug'] ?? '');
            $name = (string)($row['product_name'] ?? '');
            $out[] = [
                'label' => $name !== '' ? $name : ($slug !== '' ? $slug : 'Producto'),
                'href' => $slug !== '' ? (url('/producto') . '?slug=' . rawurlencode($slug)) : '',
                'metric' => (int)($row[$metricKey] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function prepareVariantRows(array $rows, string $metricKey): array
    {
        $out = [];
        foreach ($rows as $row) {
            $slug = (string)($row['product_slug'] ?? '');
            $name = (string)($row['product_name'] ?? '');
            $out[] = [
                'label' => $name !== '' ? $name : ($slug !== '' ? $slug : 'Producto'),
                'href' => $slug !== '' ? (url('/producto') . '?slug=' . rawurlencode($slug)) : '',
                'variant_title' => trim((string)($row['variant_title'] ?? '')),
                'metric' => (int)($row[$metricKey] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function prepareVariantRowsWithConversion(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $slug = (string)($row['product_slug'] ?? '');
            $name = (string)($row['product_name'] ?? '');
            $out[] = [
                'label' => $name !== '' ? $name : ($slug !== '' ? $slug : 'Producto'),
                'href' => $slug !== '' ? (url('/producto') . '?slug=' . rawurlencode($slug)) : '',
                'variant_title' => trim((string)($row['variant_title'] ?? '')),
                'views' => (int)($row['views'] ?? 0),
                'adds' => (int)($row['adds'] ?? 0),
                'conversion' => number_format((float)($row['conv'] ?? 0.0), 1, ',', '.') . '%',
            ];
        }
        return $out;
    }

    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    private function prepareSources(array $rows): array
    {
        $out = [];
        foreach ($rows as $row) {
            $source = trim((string)($row['source'] ?? ''));
            $out[] = [
                'source' => $source !== '' ? $source : 'Directo',
                'medium' => trim((string)($row['medium'] ?? '')),
                'campaign' => trim((string)($row['campaign'] ?? '')),
                'sessions' => (int)($row['sessions'] ?? 0),
            ];
        }
        return $out;
    }
}
