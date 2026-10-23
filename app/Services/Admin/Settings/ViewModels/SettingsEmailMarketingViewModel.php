<?php
namespace App\Services\Admin\Settings\ViewModels;

final class SettingsEmailMarketingViewModel
{
    /**
     * @param array<string,mixed> $cfg
     * @param array<int,array<string,mixed>> $rows
     * @return array<string,mixed>
     */
    public function build(array $cfg, array $rows): array
    {
        $groups = [];
        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }
            $automationCode = (string)($row['automation_code'] ?? '');
            if ($automationCode === '') {
                continue;
            }
            if (!isset($groups[$automationCode])) {
                $groups[$automationCode] = [
                    'name' => (string)($row['automation_name'] ?? $automationCode),
                    'steps' => [],
                ];
            }
            if (!empty($row['step_id'])) {
                $groups[$automationCode]['steps'][] = $row;
            }
        }

        return [
            'cfg' => $cfg,
            'rows' => $rows,
            'groups' => $groups,
            'double_opt' => $this->boolOn($cfg['double_optin'] ?? null),
            'double_opt_checked' => $this->resolveDoubleOptChecked($cfg),
            'open_tracking_checked' => $this->boolOn($cfg['open_tracking'] ?? '0'),
            'web_runner_enabled_checked' => $this->boolOn($cfg['web_runner_enabled'] ?? '0'),
            'run_url' => url('/email/run'), // Ruta pública solamente; el token nunca viaja a la vista.
            'web_runner_configured' => !empty($cfg['web_runner_configured']),
        ];
    }

    /**
     * @param mixed $value
     */
    private function boolOn($value): bool
    {
        return in_array(strtolower(trim((string)$value)), ['1', 'true', 'yes', 'on'], true);
    }

    /**
     * @param array<string,mixed> $cfg
     */
    private function resolveDoubleOptChecked(array $cfg): bool
    {
        $doubleOpt = $cfg['double_optin'] ?? null;
        if ($doubleOpt === null) {
            return $this->boolOn(env('EMAIL_MARKETING_DOUBLE_OPTIN', '0'));
        }
        return $this->boolOn($doubleOpt);
    }
}
