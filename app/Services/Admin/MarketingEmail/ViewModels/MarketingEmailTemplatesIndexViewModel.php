<?php
namespace App\Services\Admin\MarketingEmail\ViewModels;

final class MarketingEmailTemplatesIndexViewModel
{
    /**
     * @param array<int,array<string,mixed>> $templates
     * @param ?array<string,mixed> $notice
     * @return array<string,mixed>
     */
    public function build(array $templates, ?array $notice): array
    {
        return [
            'templates_prepared' => $this->prepareTemplates($templates),
            'templates_total' => count($templates),
            'notice' => $this->prepareNotice($notice),
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $templates
     * @return array<int,array<string,mixed>>
     */
    private function prepareTemplates(array $templates): array
    {
        $out = [];
        foreach ($templates as $row) {
            $id = (int)($row['id'] ?? 0);
            $subject = trim((string)($row['subject_default'] ?? ''));
            $createdRaw = trim((string)($row['created_at'] ?? ''));
            $createdLabel = $createdRaw;
            if ($createdRaw !== '') {
                $ts = strtotime($createdRaw);
                if ($ts !== false) {
                    $createdLabel = date('Y-m-d H:i', $ts);
                }
            }

            $mode = (string)($row['editor_mode'] ?? 'visual');
            $out[] = [
                'id' => $id,
                'name' => (string)($row['name'] ?? 'Plantilla'),
                'mode_label' => ($mode === 'html' ? 'HTML' : 'Visual'),
                'subject' => $subject,
                'subject_empty' => ($subject === ''),
                'created_label' => $createdLabel,
                'show_url' => url('/admin/marketing/email/templates/show?id=' . $id),
                'use_url' => url('/admin/marketing/email/campanas/new?template_id=' . $id),
            ];
        }

        return $out;
    }

    /**
     * @param ?array<string,mixed> $notice
     * @return array<string,mixed>
     */
    private function prepareNotice(?array $notice): array
    {
        if (!is_array($notice) || empty($notice['message'])) {
            return ['show' => false];
        }

        $type = (string)($notice['type'] ?? 'info');
        return [
            'show' => true,
            'type' => $type,
            'message' => (string)$notice['message'],
            'type' => $type,
        ];
    }
}

