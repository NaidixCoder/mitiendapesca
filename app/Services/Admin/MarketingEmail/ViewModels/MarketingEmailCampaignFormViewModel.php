<?php
namespace App\Services\Admin\MarketingEmail\ViewModels;

final class MarketingEmailCampaignFormViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $campaign = is_array($input['campaign'] ?? null) ? $input['campaign'] : [];
        $templates = is_array($input['templatesLite'] ?? null) ? $input['templatesLite'] : [];
        $notice = is_array($input['notice'] ?? null) ? $input['notice'] : [];

        $campaignId = (int)($campaign['id'] ?? 0);
        $status = (string)($campaign['status'] ?? 'draft');
        $audience = (string)($campaign['audience'] ?? 'all');

        $audParams = [];
        $raw = (string)($campaign['audience_params_json'] ?? '');
        if ($raw !== '') {
            $tmp = json_decode($raw, true);
            if (is_array($tmp)) {
                $audParams = $tmp;
            }
        }

        $scheduled = (string)($campaign['scheduled_at'] ?? '');
        if ($scheduled !== '') {
            $scheduled = str_replace(' ', 'T', $scheduled);
            $scheduled = substr($scheduled, 0, 16);
        }

        $templateOptions = [];
        foreach ($templates as $tpl) {
            if (!is_array($tpl)) {
                continue;
            }
            $templateOptions[] = [
                'id' => (int)($tpl['id'] ?? 0),
                'name' => (string)($tpl['name'] ?? 'Plantilla'),
            ];
        }

        $noticeType = (string)($notice['type'] ?? 'info');
        if (!in_array($noticeType, ['info', 'success', 'error', 'warning'], true)) {
            $noticeType = 'info';
        }

        $links = [
            'back' => url('/admin/marketing/email/campanas'),
            'save' => url('/admin/marketing/email/campanas/save'),
            'templates_library' => url('/admin/marketing/email/templates'),
            'preview' => url('/admin/marketing/email/campanas/preview'),
            'send_test' => url('/admin/marketing/email/campanas/send_test'),
            'upload_image' => url('/admin/marketing/email/uploads/image'),
            'products_search' => url('/admin/marketing/email/products/search'),
            'apply_template' => url('/admin/marketing/email/templates/apply'),
            'save_as_template' => url('/admin/marketing/email/templates/create'),
            'return_to' => url($campaignId > 0
                ? '/admin/marketing/email/campanas/edit?id=' . $campaignId
                : '/admin/marketing/email/campanas/new'),
        ];

        $linksInput = is_array($input['links'] ?? null) ? $input['links'] : [];
        $links = array_merge($links, $linksInput);

        return [
            'campaign' => $campaign,
            'templatesLite' => $templates,
            'form' => [
                'campaign_id' => $campaignId,
                'is_edit' => ($campaignId > 0),
                'status' => $status,
                'audience' => $audience,
                'aud_days' => (int)($audParams['days'] ?? 60),
                'aud_min_total' => (int)($audParams['min_total'] ?? 200000),
                'scheduled_at_input' => $scheduled,
                'show_scheduled' => ($status === 'scheduled'),
            ],
            'ui' => [
                'status_options' => $this->statusOptions($status),
                'audience_options' => $this->audienceOptions($audience),
                'template_options' => $templateOptions,
            ],
            'links' => $links,
            'notice' => [
                'show' => !empty($notice['message']),
                'message' => (string)($notice['message'] ?? ''),
                'type' => $noticeType,
                'title' => 'Revisá antes de guardar',
            ],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function statusOptions(string $selected): array
    {
        $rows = [
            ['value' => 'draft', 'label' => 'Borrador'],
            ['value' => 'scheduled', 'label' => 'Programada'],
        ];
        foreach ($rows as &$row) {
            $row['selected'] = ($row['value'] === $selected);
        }
        unset($row);
        return $rows;
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function audienceOptions(string $selected): array
    {
        $rows = [
            ['value' => 'all', 'label' => 'Todos los suscriptores'],
            ['value' => 'buyers', 'label' => 'Compradores (con pedidos confirmados)'],
            ['value' => 'non_buyers', 'label' => 'No compradores'],
            ['value' => 'inactive_buyers', 'label' => 'Compradores inactivos'],
            ['value' => 'vip', 'label' => 'VIP (por gasto)'],
        ];
        foreach ($rows as &$row) {
            $row['selected'] = ($row['value'] === $selected);
        }
        unset($row);
        return $rows;
    }
}




