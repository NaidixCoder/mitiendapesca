<?php
namespace App\Services\Admin\MarketingEmail\ViewModels;

final class MarketingEmailCampaignShowViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $campaign = is_array($input['campaign'] ?? null) ? $input['campaign'] : [];
        $orders = is_array($input['orders'] ?? null) ? $input['orders'] : [];
        $stats = is_array($input['stats'] ?? null) ? $input['stats'] : [];
        $notice = is_array($input['notice'] ?? null) ? $input['notice'] : [];

        $status = (string)($campaign['status'] ?? 'draft');
        [$statusLabel, $statusBadge] = $this->statusUi($status);

        $audience = (string)($campaign['audience'] ?? 'all');
        $audParams = $this->decodeAudienceParams((string)($campaign['audience_params_json'] ?? ''));

        $preparedOrders = [];
        foreach ($orders as $order) {
            if (!is_array($order)) {
                continue;
            }
            $orderId = (int)($order['id'] ?? 0);
            $preparedOrders[] = [
                'id' => $orderId,
                'user_id' => (int)($order['user_id'] ?? 0),
                'status_label' => $this->orderStatusLabel((string)($order['status'] ?? '')),
                'total_label' => '$ ' . number_format((int)($order['total'] ?? 0), 0, ',', '.'),
                'created_at' => (string)($order['created_at'] ?? ''),
                'order_url' => url('/admin/orders/show?id=' . $orderId),
            ];
        }

        $noticeType = (string)($notice['type'] ?? 'info');
        $noticeTitle = 'Info';
        $noticeTone = 'info';
        if ($noticeType === 'success') {
            $noticeTitle = 'Listo';
            $noticeTone = 'success';
        } elseif ($noticeType === 'error') {
            $noticeTitle = 'No se pudo';
            $noticeTone = 'error';
        }

        return [
            'campaign_view' => [
                'id' => (int)($campaign['id'] ?? 0),
                'name' => (string)($campaign['name'] ?? ''),
                'subject' => (string)($campaign['subject'] ?? ''),
                'status' => $status,
                'status_label' => $statusLabel,
                'status_tone' => $statusBadge,
                'scheduled_at' => (string)($campaign['scheduled_at'] ?? ''),
                'audience_label' => $this->audienceLabel($audience),
                'audience_key' => $audience,
                'audience_params' => $audParams,
                'vip_min_total_label' => isset($audParams['min_total'])
                    ? '$ ' . number_format((int)$audParams['min_total'], 0, ',', '.')
                    : '',
                'cta_label' => (string)($campaign['cta_label'] ?? ''),
                'cta_url' => (string)($campaign['cta_url'] ?? ''),
                'body_html' => (string)($campaign['body_html'] ?? ''),
                'can_edit' => (bool)($input['can_edit'] ?? in_array($status, ['draft', 'scheduled'], true)),
                'can_schedule_now' => $status === 'draft',
                'can_cancel' => in_array($status, ['scheduled', 'sending'], true),
            ],
            'stats_view' => [
                'sent_count' => (int)($stats['sent_count'] ?? 0),
                'open_count' => (int)($stats['open_count'] ?? 0),
                'click_count' => (int)($stats['click_count'] ?? 0),
                'orders_count' => (int)($stats['orders_count'] ?? 0),
                'revenue_total_label' => '$ ' . number_format((int)($stats['revenue_total'] ?? 0), 0, ',', '.'),
            ],
            'orders_view' => $preparedOrders,
            'links' => [
                'back' => url('/admin/marketing/email/campanas'),
                'edit' => url('/admin/marketing/email/campanas/edit?id=' . (int)($campaign['id'] ?? 0)),
                'schedule_now' => url('/admin/marketing/email/campanas/schedule_now'),
                'cancel' => url('/admin/marketing/email/campanas/cancel'),
            ],
            'notice' => [
                'show' => !empty($notice['message']),
                'message' => (string)($notice['message'] ?? ''),
                'title' => $noticeTitle,
                'type' => $noticeTone,
            ],
        ];
    }

    /**
     * @return array<string,mixed>
     */
    private function decodeAudienceParams(string $json): array
    {
        if ($json === '') {
            return [];
        }
        $tmp = json_decode($json, true);
        return is_array($tmp) ? $tmp : [];
    }

    private function orderStatusLabel(string $status): string
    {
        return match ($status) {
            'paid' => 'Pagado',
            'processing' => 'En preparación',
            'packed' => 'Preparado',
            'shipped' => 'Enviado',
            'delivered' => 'Entregado',
            'cancelled' => 'Cancelado',
            default => $status,
        };
    }

    private function audienceLabel(string $audience): string
    {
        return match ($audience) {
            'all' => 'Todos los suscriptores',
            'buyers' => 'Compradores (con pedidos confirmados)',
            'non_buyers' => 'No compradores',
            'inactive_buyers' => 'Compradores inactivos',
            'vip' => 'VIP (por gasto)',
            default => $audience,
        };
    }

    /**
     * @return array{0:string,1:string}
     */
    private function statusUi(string $status): array
    {
        return match ($status) {
            'draft' => ['Borrador', 'neutral'],
            'scheduled' => ['Programada', 'info'],
            'sending' => ['Enviando', 'warn'],
            'sent' => ['Enviada', 'ok'],
            'cancelled' => ['Cancelada', 'error'],
            default => [$status, 'neutral'],
        };
    }
}



