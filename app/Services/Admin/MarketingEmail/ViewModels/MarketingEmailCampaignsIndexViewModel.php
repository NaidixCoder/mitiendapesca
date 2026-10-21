<?php
namespace App\Services\Admin\MarketingEmail\ViewModels;

final class MarketingEmailCampaignsIndexViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $rows = is_array($input['campaigns'] ?? null) ? $input['campaigns'] : [];
        $prepared = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $status = (string)($row['status'] ?? 'draft');
            [$label, $badge] = $this->statusUi($status);

            $sent = (int)($row['sent_count'] ?? 0);
            $opens = (int)($row['open_count'] ?? 0);
            $clicks = (int)($row['click_count'] ?? 0);
            $openRate = $sent > 0 ? round(($opens / $sent) * 100, 1) : 0.0;
            $ctr = $sent > 0 ? round(($clicks / $sent) * 100, 1) : 0.0;
            $id = (int)($row['id'] ?? 0);

            $prepared[] = [
                'id' => $id,
                'name' => (string)($row['name'] ?? ''),
                'subject' => (string)($row['subject'] ?? ''),
                'scheduled_at' => (string)($row['scheduled_at'] ?? ''),
                'status_label' => $label,
                'status_tone' => $badge,
                'sent_count' => $sent,
                'open_count' => $opens,
                'click_count' => $clicks,
                'orders_count' => (int)($row['orders_count'] ?? 0),
                'revenue_total' => (int)($row['revenue_total'] ?? 0),
                'revenue_total_label' => '$ ' . number_format((int)($row['revenue_total'] ?? 0), 0, ',', '.'),
                'open_rate' => $openRate,
                'ctr' => $ctr,
                'show_url' => url('/admin/marketing/email/campanas/show?id=' . $id),
                'edit_url' => url('/admin/marketing/email/campanas/edit?id=' . $id),
            ];
        }

        return [
            'campaigns_prepared' => $prepared,
            'links' => [
                'summary' => url('/admin/marketing/email'),
                'automations' => url('/admin/marketing/email/automatizaciones'),
                'subscribers' => url('/admin/marketing/email/suscriptores'),
                'queue' => url('/admin/marketing/email/cola'),
                'new_campaign' => url('/admin/marketing/email/campanas/new'),
            ],
        ];
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



