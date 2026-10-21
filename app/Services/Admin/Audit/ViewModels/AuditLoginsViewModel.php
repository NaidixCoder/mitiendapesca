<?php
namespace App\Services\Admin\Audit\ViewModels;

final class AuditLoginsViewModel
{
    /**
     * @param array<string,mixed> $view
     * @return array{audit_logins_view: array<string,mixed>}
     */
    public function build(array $view): array
    {
        $itemsPrepared = is_array($view['items_prepared'] ?? null) ? $view['items_prepared'] : [];
        $page = max(1, (int)($view['page'] ?? $view['pageNum'] ?? 1));
        $perPage = (int)($view['perPage'] ?? 25);
        $total = (int)($view['total'] ?? 0);
        $pages = max(1, (int)($view['pages'] ?? 1));
        $event = (string)($view['event'] ?? 'todos');
        $method = (string)($view['method'] ?? 'todos');
        $range = (string)($view['range'] ?? '7');
        $q = (string)($view['q'] ?? '');
        $retSuccess = (int)($view['ret_success'] ?? 365);
        $retFailed = (int)($view['ret_failed'] ?? 180);
        $retLogout = (int)($view['ret_logout'] ?? 90);
        $links = is_array($view['links'] ?? null) ? $view['links'] : [];
        $pager = is_array($view['pager'] ?? null) ? $view['pager'] : [];
        $currentUri = (string)($view['current_uri'] ?? ($_SERVER['REQUEST_URI'] ?? '/admin/audit/logins'));

        return [
            'audit_logins_view' => [
                'itemsPrepared' => $itemsPrepared,
                'page' => $page,
                'perPage' => $perPage,
                'total' => $total,
                'pages' => $pages,
                'event' => $event,
                'method' => $method,
                'range' => $range,
                'q' => $q,
                'retSuccess' => $retSuccess,
                'retFailed' => $retFailed,
                'retLogout' => $retLogout,
                'links' => $links,
                'pager' => $pager,
                'currentUri' => $currentUri,
            ],
        ];
    }
}

