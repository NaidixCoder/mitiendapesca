<?php
namespace App\Services\Admin\Settings\ViewModels;

final class SettingsHomeHeroViewModel
{
    /**
     * @param array<int,array<string,mixed>> $rows
     * @return array<int,array<string,mixed>>
     */
    public function buildRows(array $rows, int $min = 4): array
    {
        $rows = array_values(array_filter($rows, static fn($r): bool => is_array($r)));
        if (count($rows) < $min) {
            $rows = array_merge(
                $rows,
                array_fill(0, $min - count($rows), [
                    'is_active' => 1,
                    'title' => '',
                    'alt_text' => '',
                    'image_desktop' => '',
                    'image_mobile' => '',
                    'target_url' => '',
                    'sort_order' => 0,
                    'starts_at' => null,
                    'ends_at' => null,
                ])
            );
        }

        foreach ($rows as $i => &$row) {
            $starts = (string)($row['starts_at'] ?? '');
            $ends = (string)($row['ends_at'] ?? '');
            $row['sort_order'] = (int)($row['sort_order'] ?? ($i + 1));
            $row['starts_at_local'] = $starts !== '' ? str_replace(' ', 'T', substr($starts, 0, 16)) : '';
            $row['ends_at_local'] = $ends !== '' ? str_replace(' ', 'T', substr($ends, 0, 16)) : '';
        }
        unset($row);

        return $rows;
    }
}

