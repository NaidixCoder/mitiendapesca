<?php
namespace App\Services\Admin\Audit;

use PDO;
use App\Repositories\Admin\AuditLoginsRepository;
use App\Services\Admin\AuthLoginEventsMaintenanceService;
use App\Services\Audit\Audit;

final class AuditLoginsService
{
    public function buildViewData(PDO $db, array $query): array
    {
        $pageNum = max(1, (int)($query['page'] ?? 1));
        $perPage = (int)($query['per_page'] ?? $query['perPage'] ?? 25);
        if (!in_array($perPage, [25,50,100], true)) $perPage = 25;
        $offset = ($pageNum - 1) * $perPage;

        $event  = (string)($query['event'] ?? 'todos');
        $method = (string)($query['method'] ?? 'todos');
        $range  = (string)($query['range'] ?? '7');
        $q      = trim((string)($query['q'] ?? ''));

        $where = [];
        $params = [];

        if (in_array($event, ['login_success','login_failed','logout'], true)) {
            $where[] = 'e.event = :event';
            $params[':event'] = $event;
        } else {
            $event = 'todos';
        }

        if (in_array($method, ['password','google'], true)) {
            $where[] = 'e.method = :method';
            $params[':method'] = $method;
        } else {
            $method = 'todos';
        }

        $dateFrom = null;
        $dateTo = null;
        if ($range === '30') {
            $dateFrom = date('Y-m-d 00:00:00', strtotime('-30 days'));
            $dateTo = date('Y-m-d 23:59:59');
        } elseif ($range === '90') {
            $dateFrom = date('Y-m-d 00:00:00', strtotime('-90 days'));
            $dateTo = date('Y-m-d 23:59:59');
        } elseif ($range === 'all') {
            $dateFrom = null;
            $dateTo = null;
        } else {
            $range = '7';
            $dateFrom = date('Y-m-d 00:00:00', strtotime('-7 days'));
            $dateTo = date('Y-m-d 23:59:59');
        }

        if ($dateFrom && $dateTo) {
            $where[] = '(e.created_at BETWEEN :from AND :to)';
            $params[':from'] = $dateFrom;
            $params[':to'] = $dateTo;
        }

        if ($q !== '') {
            $like = '%' . $q . '%';
            $where[] = '(u.email LIKE :q1 OR u.name LIKE :q2 OR e.ip LIKE :q3 OR e.reason LIKE :q4 OR e.event LIKE :q5 OR e.method LIKE :q6)';
            $params[':q1'] = $like;
            $params[':q2'] = $like;
            $params[':q3'] = $like;
            $params[':q4'] = $like;
            $params[':q5'] = $like;
            $params[':q6'] = $like;
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $repo = new AuditLoginsRepository($db);
        $total = $repo->count($whereSql, $params);
        $items = $repo->fetch($whereSql, $params, $perPage, $offset);

        return [
            'items' => $items,
            'pageNum' => $pageNum,
            'perPage' => $perPage,
            'total' => $total,
            'event' => $event,
            'method' => $method,
            'range' => $range,
            'q' => $q,
            'retention' => AuthLoginEventsMaintenanceService::retentionFromEnv(),
        ];
    }

    /**
     * Ejecuta la limpieza real (sin dry run).
     *
     * @return array [ok=>bool, message=>string]
     */
    public function cleanup(PDO $db, array $post): array
    {
        $retention = AuthLoginEventsMaintenanceService::retentionFromEnv();

        $successDays = (int)($post['success_days'] ?? 0);
        $failedDays  = (int)($post['failed_days'] ?? 0);
        $logoutDays  = (int)($post['logout_days'] ?? 0);
        if ($successDays > 0) $retention['login_success'] = max(7, $successDays);
        if ($failedDays  > 0) $retention['login_failed']  = max(7, $failedDays);
        if ($logoutDays  > 0) $retention['logout']        = max(7, $logoutDays);

        $res = AuthLoginEventsMaintenanceService::cleanup($db, $retention, false);

        Audit::log('logins_cleanup_executed', 'system', null, [
            'dry_run' => false,
            'retention_days' => $retention,
            'result' => $res,
        ]);

        $deleted = (int)($res['total_deleted'] ?? 0);
        return ['ok' => true, 'message' => 'Limpieza OK: se borraron ' . $deleted . ' registro(s).'];
    }
}

