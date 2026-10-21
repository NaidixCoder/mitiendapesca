<?php
namespace App\Services\Marketing;

use App\Repositories\Marketing\EmailTemplateRepository;

final class EmailTemplateService
{
    public function __construct(private EmailTemplateRepository $repo) {}

    /** @return array{ok:bool,id?:int,error?:string} */
    public function createTemplate(array $data): array
    {
        $name = trim((string)($data['name'] ?? ''));
        $bodyHtml = (string)($data['body_html'] ?? '');
        $subjectDefault = $data['subject_default'] ?? null;
        $preheaderDefault = $data['preheader_default'] ?? null;
        $editorMode = (string)($data['editor_mode'] ?? 'visual');

        if ($name === '') {
            return ['ok' => false, 'error' => 'Ingresá un nombre para la plantilla'];
        }
        if (mb_strlen($name) > 120) {
            return ['ok' => false, 'error' => 'El nombre es demasiado largo'];
        }

        $clean = $this->sanitizeEmailHtml($bodyHtml);
        if (trim($clean) === '') {
            return ['ok' => false, 'error' => 'El contenido de la plantilla está vacío'];
        }

        if (!in_array($editorMode, ['visual','html'], true)) {
            $editorMode = 'visual';
        }

        $id = $this->repo->create([
            'name' => $name,
            'subject_default' => ($subjectDefault !== null && trim((string)$subjectDefault) !== '' ? (string)$subjectDefault : null),
            'preheader_default' => ($preheaderDefault !== null && trim((string)$preheaderDefault) !== '' ? (string)$preheaderDefault : null),
            'editor_mode' => $editorMode,
            'body_html' => $clean,
        ]);

        return ['ok' => true, 'id' => $id];
    }

    private function sanitizeEmailHtml(string $html): string
    {
        $s = $html;
        // Bloqueo mínimo: scripts
        $s = preg_replace('/<\s*script\b[^>]*>.*?<\s*\/\s*script\s*>/is', '', $s) ?? '';
        // Quita handlers on*=
        $s = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $s) ?? '';
        // Evita javascript: en href/src
        $s = preg_replace('/(href|src)\s*=\s*("|\')\s*javascript:[^"\']*("|\')/i', '$1=$2#$3', $s) ?? '';
        return (string)$s;
    }
}
