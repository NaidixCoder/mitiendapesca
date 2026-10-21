<?php
namespace App\Services\Admin\MarketingEmail\ViewModels;

final class MarketingEmailTemplatesShowViewModel
{
    /**
     * @param array<string,mixed> $template
     * @return array<string,mixed>
     */
    public function build(array $template): array
    {
        $id = (int)($template['id'] ?? 0);
        $name = (string)($template['name'] ?? 'Plantilla');
        $mode = (string)($template['editor_mode'] ?? 'visual');
        $subject = (string)($template['subject_default'] ?? '');
        $preheader = (string)($template['preheader_default'] ?? '');
        $createdAt = (string)($template['created_at'] ?? '');
        $bodyHtml = (string)($template['body_html'] ?? '');

        return [
            'page_title' => 'Plantilla: ' . $name,
            'template_show' => [
                'id' => $id,
                'name' => $name,
                'mode_label' => ($mode === 'html' ? 'HTML' : 'Visual'),
                'subject' => $subject,
                'subject_empty' => ($subject === ''),
                'preheader' => $preheader,
                'preheader_empty' => ($preheader === ''),
                'created_at' => $createdAt,
                'html' => $bodyHtml,
                'back_url' => url('/admin/marketing/email/templates'),
                'use_url' => url('/admin/marketing/email/campanas/new?template_id=' . $id),
                'delete_url' => url('/admin/marketing/email/templates/delete'),
                'tip_text' => 'Antes de programar un envío, revisá el contenido y todos los enlaces.',
                'preview_hint' => 'Solo visualización; no se envía ningún correo.',
            ],
        ];
    }
}
