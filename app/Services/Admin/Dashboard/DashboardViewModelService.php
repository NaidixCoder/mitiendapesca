<?php
namespace App\Services\Admin\Dashboard;

final class DashboardViewModelService
{
    /**
     * @param array<int,array<string,mixed>> $orders
     * @return array<int,array<string,mixed>>
     */
    public function preparePendingOrders(array $orders): array
    {
        $nowTs = time();
        $prepared = [];

        foreach ($orders as $order) {
            $createdTs = null;

            if (isset($order['created_at_ts']) && is_numeric($order['created_at_ts'])) {
                $createdTs = (int)$order['created_at_ts'];
            } else {
                $raw = (string)($order['created_at_raw'] ?? $order['created_at'] ?? '');
                $parsed = $raw !== '' ? strtotime($raw) : false;
                if ($parsed !== false) {
                    $createdTs = (int)$parsed;
                }
            }

            $order['_ts'] = $createdTs;
            $order['_urgent'] = $createdTs !== null ? (($nowTs - $createdTs) >= 48 * 3600) : false;
            $prepared[] = $order;
        }

        usort($prepared, static function (array $a, array $b): int {
            $aUrgent = !empty($a['_urgent']) ? 1 : 0;
            $bUrgent = !empty($b['_urgent']) ? 1 : 0;
            if ($aUrgent !== $bUrgent) {
                return $bUrgent <=> $aUrgent;
            }

            $aTs = $a['_ts'] ?? null;
            $bTs = $b['_ts'] ?? null;
            if ($aTs === null && $bTs === null) {
                return 0;
            }
            if ($aTs === null) {
                return 1;
            }
            if ($bTs === null) {
                return -1;
            }

            return $aTs <=> $bTs;
        });

        return $prepared;
    }

    /**
     * @param array<string,mixed> $catalogKpi
     * @param array<string,mixed> $opsKpi
     * @param array<int,mixed> $lowStock
     * @return array<string,mixed>
     */
    public function buildDashboardCards(array $catalogKpi, array $opsKpi, array $lowStock): array
    {
        $today = date('Y-m-d');
        $ordersTodayHref = url('/admin/orders?from=' . $today . '&to=' . $today);

        return [
            'catalogCards' => [
                ['label' => 'PRODUCTOS ACTIVOS', 'value' => (int)($catalogKpi['prod_activos'] ?? 0), 'sub' => '', 'href' => url('/admin/products')],
                ['label' => 'INACTIVOS', 'value' => (int)($catalogKpi['prod_inactivos'] ?? 0), 'sub' => '', 'href' => url('/admin/products')],
                ['label' => 'USUARIOS NUEVOS (30D)', 'value' => (int)($catalogKpi['users_30d'] ?? 0), 'sub' => '', 'href' => url('/admin/users')],
                ['label' => 'BAJO STOCK', 'value' => count($lowStock), 'sub' => '', 'href' => url('/admin/products')],
            ],
            'opsCards' => [
                ['label' => 'PEDIDOS HOY', 'value' => (int)($opsKpi['orders_today'] ?? 0), 'sub' => '', 'href' => $ordersTodayHref],
                ['label' => 'FACTURACION HOY', 'value' => '$ ' . number_format((int)($opsKpi['revenue_today'] ?? 0), 0, ',', '.'), 'sub' => '', 'href' => $ordersTodayHref],
                ['label' => 'PEDIDOS PENDIENTES', 'value' => (int)($opsKpi['pending_orders'] ?? 0), 'sub' => '', 'href' => url('/admin/orders')],
                ['label' => 'USUARIOS REGISTRADOS', 'value' => (int)($opsKpi['users_total'] ?? 0), 'sub' => '', 'href' => url('/admin/users')],
            ],
            'ordersTodayHref' => $ordersTodayHref,
        ];
    }
}

