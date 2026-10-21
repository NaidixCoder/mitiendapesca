<?php
namespace App\Services\Admin\MarketingEmail\ViewModels;

final class MarketingEmailAutomationsViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $rows = is_array($input['rows'] ?? null) ? $input['rows'] : [];
        $grouped = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $automationId = (int)($row['automation_id'] ?? 0);
            if (!isset($grouped[$automationId])) {
                $grouped[$automationId] = [
                    'id' => $automationId,
                    'code' => (string)($row['automation_code'] ?? ''),
                    'name' => (string)($row['automation_name'] ?? ''),
                    'enabled' => ((int)($row['automation_enabled'] ?? 0) === 1),
                    'steps' => [],
                ];
            }

            $stepId = (int)($row['step_id'] ?? 0);
            if ($stepId <= 0) {
                continue;
            }
            $delayMinutes = (int)($row['delay_minutes'] ?? 0);
            $grouped[$automationId]['steps'][] = [
                'id' => $stepId,
                'code' => (string)($row['step_code'] ?? ''),
                'name' => (string)($row['step_name'] ?? ''),
                'delay_minutes' => $delayMinutes,
                'delay_label' => $this->delayLabel($delayMinutes),
                'subject' => (string)($row['subject'] ?? ''),
                'template' => (string)($row['template'] ?? ''),
                'enabled' => ((int)($row['step_enabled'] ?? 0) === 1),
            ];
        }

        return [
            'automations_prepared' => array_values($grouped),
            'links' => [
                'summary' => url('/admin/marketing/email'),
                'queue' => url('/admin/marketing/email/cola'),
                'settings' => url('/admin/settings?tab=email-marketing'),
                'toggle_automation' => url('/admin/marketing/email/automatizaciones/toggle'),
                'toggle_step' => url('/admin/marketing/email/pasos/toggle'),
            ],
        ];
    }

    private function delayLabel(int $minutes): string
    {
        if ($minutes < 60) {
            return $minutes . ' min';
        }
        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours . ' h';
        }
        $days = intdiv($hours, 24);
        return $days . ' d';
    }
}




