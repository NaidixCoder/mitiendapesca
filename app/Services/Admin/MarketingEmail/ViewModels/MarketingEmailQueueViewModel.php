<?php
namespace App\Services\Admin\MarketingEmail\ViewModels;

final class MarketingEmailQueueViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $counts = is_array($input['counts'] ?? null) ? $input['counts'] : [];
        $rows = is_array($input['rows'] ?? null) ? $input['rows'] : [];
        $logs = is_array($input['logs'] ?? null) ? $input['logs'] : [];
        $q = (string)($input['q'] ?? '');
        $status = (string)($input['status'] ?? 'queued');

        $queueRows = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $st = (string)($row['status'] ?? '');
            [$label, $badge] = $this->queueStatusUi($st);
            $queueRows[] = [
                'status_label' => $label,
                'status_tone' => $badge,
                'to_email' => (string)($row['to_email'] ?? ''),
                'subject' => (string)($row['subject'] ?? ''),
                'scheduled_at' => (string)($row['scheduled_at'] ?? ''),
                'attempts' => (int)($row['attempts'] ?? 0),
            ];
        }

        $logRows = [];
        foreach ($logs as $log) {
            if (!is_array($log)) {
                continue;
            }
            $st = (string)($log['status'] ?? '');
            $ok = ($st === 'sent');
            $logRows[] = [
                'created_at' => (string)($log['created_at'] ?? ''),
                'result_label' => $ok ? 'OK' : 'Fallo',
                'result_tone' => $ok ? 'ok' : 'error',
                'to_email' => (string)($log['to_email'] ?? ''),
                'subject' => (string)($log['subject'] ?? ''),
                'error_text' => (string)($log['error_text'] ?? ''),
            ];
        }

        return [
            'q' => $q,
            'status' => $status,
            'counts' => [
                'queued' => (int)($counts['queued'] ?? 0),
                'sending' => (int)($counts['sending'] ?? 0),
                'sent' => (int)($counts['sent'] ?? 0),
                'failed' => (int)($counts['failed'] ?? 0),
                'cancelled' => (int)($counts['cancelled'] ?? 0),
                'total' => (int)($counts['total'] ?? 0),
            ],
            'status_options' => $this->statusOptions($status),
            'rows_prepared' => $queueRows,
            'logs_prepared' => $logRows,
            'links' => [
                'summary' => url('/admin/marketing/email'),
                'automations' => url('/admin/marketing/email/automatizaciones'),
                'settings' => url('/admin/settings?tab=email-marketing'),
                'queue' => url('/admin/marketing/email/cola'),
                'run' => url('/admin/marketing/email/cola/procesar'),
            ],
        ];
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function statusOptions(string $selected): array
    {
        $rows = [
            ['value' => 'queued', 'label' => 'Por enviar'],
            ['value' => 'sending', 'label' => 'En proceso'],
            ['value' => 'sent', 'label' => 'Enviados'],
            ['value' => 'failed', 'label' => 'Fallidos'],
            ['value' => 'cancelled', 'label' => 'Cancelados'],
            ['value' => 'all', 'label' => 'Todos'],
        ];
        foreach ($rows as &$row) {
            $row['selected'] = ($row['value'] === $selected);
        }
        unset($row);
        return $rows;
    }

    /**
     * @return array{0:string,1:string}
     */
    private function queueStatusUi(string $status): array
    {
        return match ($status) {
            'queued' => ['Por enviar', 'warn'],
            'sending' => ['En proceso', 'info'],
            'sent' => ['Enviado', 'ok'],
            'failed' => ['Fallo', 'error'],
            'cancelled' => ['Cancelado', 'neutral'],
            default => [$status, 'neutral'],
        };
    }
}




