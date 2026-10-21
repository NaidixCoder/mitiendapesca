<?php
namespace App\Services\Admin\Users\ViewModels;

final class UsersShowViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $user = is_array($input['user'] ?? null) ? $input['user'] : [];
        $stats = is_array($input['stats'] ?? null) ? $input['stats'] : [];
        $orders = is_array($input['orders'] ?? null) ? $input['orders'] : [];
        $userProfile = is_array($input['userProfile'] ?? null) ? $input['userProfile'] : [];
        $addresses = is_array($input['addresses'] ?? null) ? $input['addresses'] : [];
        $page = (int)($input['page'] ?? 1);
        $pages = (int)($input['pages'] ?? 1);
        $totalOrders = (int)($input['totalOrders'] ?? 0);

        $uid = (int)($user['id'] ?? 0);
        $name = trim((string)($user['name'] ?? ''));
        $email = (string)($user['email'] ?? '');
        $createdAt = (string)($user['created_at'] ?? '');
        $createdAtShort = $this->formatDate($createdAt, 'd/m/Y');

        $tier = (string)($stats['loyalty_tier'] ?? 'Bronce');
        $badgeTone = 'neutral';
        if ($tier === 'Plata') {
            $badgeTone = 'info';
        } elseif ($tier === 'Oro') {
            $badgeTone = 'warn';
        } elseif ($tier === 'Platino') {
            $badgeTone = 'success';
        }

        $ordersPrepared = [];
        foreach ($orders as $o) {
            if (!is_array($o)) {
                continue;
            }
            $oid = (int)($o['id'] ?? 0);
            $status = (string)($o['status'] ?? '');
            $dt = (string)($o['created_at'] ?? '');
            $ordersPrepared[] = [
                'id' => $oid,
                'href' => url('/admin/orders/show?id=' . $oid),
                'date' => $this->formatDate($dt, 'd/m/Y H:i'),
                'status_label' => function_exists('order_status_label_admin')
                    ? (string)order_status_label_admin($status)
                    : $status,
                'payment_method' => (string)($o['payment_method'] ?? ''),
                'total_fmt' => money((int)($o['total'] ?? 0)),
            ];
        }

        $page = max(1, $page);
        $pages = max(1, $pages);
        $prev = max(1, $page - 1);
        $next = min($pages, $page + 1);

        return [
            'show_payload' => [
                'user' => [
                    'id' => $uid,
                    'name' => $name,
                    'email' => $email,
                    'created_at_short' => $createdAtShort,
                ],
                'stats' => [
                    'paid_total_fmt' => money((int)($stats['paid_total'] ?? 0)),
                    'paid_orders_count' => (int)($stats['paid_orders_count'] ?? 0),
                    'avg_paid_total_fmt' => money((int)($stats['avg_paid_total'] ?? 0)),
                    'first_paid_at' => $this->formatDate((string)($stats['first_paid_at'] ?? ''), 'd/m/Y H:i'),
                    'last_paid_at' => $this->formatDate((string)($stats['last_paid_at'] ?? ''), 'd/m/Y H:i'),
                    'loyalty_points' => (int)($stats['loyalty_points'] ?? 0),
                    'tier' => $tier,
                    'tier_badge_tone' => $badgeTone,
                ],
                'profile' => [
                    'has_profile' => !empty($userProfile),
                    'phone' => (string)($userProfile['phone'] ?? ''),
                    'birth_date' => (string)($userProfile['birth_date'] ?? ''),
                    'marketing_email_opt_in' => !empty($userProfile['marketing_email_opt_in']),
                    'marketing_whatsapp_opt_in' => !empty($userProfile['marketing_whatsapp_opt_in']),
                ],
                'addresses' => [
                    'rows' => $addresses,
                ],
                'orders' => [
                    'rows' => $ordersPrepared,
                ],
                'pagination' => [
                    'page' => $page,
                    'pages' => $pages,
                    'total' => $totalOrders,
                    'prev_href' => url('/admin/users/show?id=' . $uid . '&p=' . $prev),
                    'next_href' => url('/admin/users/show?id=' . $uid . '&p=' . $next),
                    'prev_disabled' => ($page <= 1),
                    'next_disabled' => ($page >= $pages),
                ],
                'links' => [
                    'users_index' => url('/admin/users'),
                    'rebuild_stats' => url('/admin/users/show?id=' . $uid . '&rebuild=1'),
                    'orders_by_user' => url('/admin/orders?user_id=' . $uid),
                    'audit_by_user' => url('/admin/audit?tipo=user&q=%23' . $uid),
                ],
            ],
        ];
    }

    private function formatDate(string $value, string $format): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }
        $ts = strtotime($value);
        if ($ts === false) {
            return '';
        }
        return date($format, $ts);
    }
}

