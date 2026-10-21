<?php
namespace App\Services\Catalog;

/**
 * Normaliza filtros provenientes del request (GET).
 * Salida estable para services/repos/vistas.
 */
final class FilterService
{
    /** @return array */
    public function fromRequest(array $get): array
    {
        $str = static function($k) use ($get): string {
            return trim((string)($get[$k] ?? ''));
        };

        $out = [
            'q'         => $str('q'),
            'categoria' => $str('categoria'),
            'sub'       => $str('sub'),
            'marca'     => $this->normalizeBrandValues($get['marca'] ?? []),
            'tags'      => $this->normalizeTagFilters($get['tag'] ?? []),
            'min'       => $str('min'),
            'max'       => $str('max'),
            'orden'     => $get['orden'] ?? 'relevancia',
        ];

        $page = (int)($get['page'] ?? 1);
        $out['page'] = max(1, $page);

        $perPage = (int)($get['perPage'] ?? $get['per_page'] ?? 12);
        $allowed = [12, 24, 48];
        if (!in_array($perPage, $allowed, true)) {
            if ($perPage < 12) $perPage = 12;
            elseif ($perPage > 48) $perPage = 48;
            else $perPage = 12;
        }
        $out['perPage']  = $perPage;
        $out['per_page'] = $perPage;

        return $out;
    }

    /** @return array<int,string> */
    private function normalizeBrandValues(mixed $raw): array
    {
        $values = is_array($raw) ? $raw : [$raw];
        $out = [];

        foreach ($values as $value) {
            $slug = trim((string)$value);
            if ($slug === '') {
                continue;
            }
            $out[] = $slug;
        }

        return array_values(array_unique($out));
    }

    /**
     * @return array<string,array<int,int>>
     */
    private function normalizeTagFilters(mixed $raw): array
    {
        if (!is_array($raw)) {
            return [];
        }

        $out = [];
        foreach ($raw as $groupSlug => $values) {
            $slug = trim((string)$groupSlug);
            if ($slug === '') {
                continue;
            }

            $items = is_array($values) ? $values : [$values];
            $normalized = [];
            foreach ($items as $value) {
                $tagSlug = trim((string)$value);
                if ($tagSlug === '') {
                    continue;
                }
                $normalized[] = $tagSlug;
            }

            $normalized = array_values(array_unique($normalized));
            if ($normalized === []) {
                continue;
            }

            $out[$slug] = $normalized;
        }

        ksort($out);
        return $out;
    }
}
