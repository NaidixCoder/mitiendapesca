<?php
namespace App\Services\Catalog;

final class FilterAsideViewModelService
{
    /**
     * @param array<string,mixed> $filters
     * @param array<string,mixed> $facets
     * @param array<int,array{label:string,href:string}> $chips
     * @return array<string,mixed>
     */
    public function build(array $filters, array $facets, array $chips, ?string $clearAll, string $basePath): array
    {
        $tagFacets = $this->flatTagFacets((array)($facets['tags'] ?? []));
        $hasTagSelection = !empty($filters['tags'] ?? []);

        return [
            'active_summary' => [
                'count' => count($chips),
                'chips' => $chips,
                'clear_all' => $clearAll,
                'has_active' => !empty($chips),
            ],
            'show_brands' => $basePath !== '/marca',
            'more_filters' => [
                'label' => 'Mas filtros',
                'is_open' => $hasTagSelection,
                'has_groups' => !empty($tagFacets),
                'use_flat_facets' => true,
                'flat_facets' => $tagFacets,
            ],
            'tag_groups' => [],
        ];
    }

    /**
     * @param array<int,array<string,mixed>> $attributeFacets
     * @return array<int,array<string,mixed>>
     */
    private function flatTagFacets(array $attributeFacets): array
    {
        $facets = [];

        foreach ($attributeFacets as $facet) {
            $slug = trim((string)($facet['slug'] ?? ''));
            $name = trim((string)($facet['name'] ?? ''));
            $options = is_array($facet['options'] ?? null) ? $facet['options'] : [];
            if ($slug === '' || $name === '' || $options === []) {
                continue;
            }

            $visibleOptions = [];
            $overflowOptions = [];
            $selectedCount = (int)($facet['selected_count'] ?? 0);
            $hasSelectedInOverflow = false;

            foreach ($options as $index => $option) {
                $optionVm = [
                    'slug' => trim((string)($option['slug'] ?? '')),
                    'name' => trim((string)($option['name'] ?? '')),
                    'count' => (int)($option['count'] ?? 0),
                    'selected' => !empty($option['selected']),
                ];
                if ($optionVm['slug'] === '' || $optionVm['name'] === '') {
                    continue;
                }
                if ($index < 5) {
                    $visibleOptions[] = $optionVm;
                    continue;
                }
                if ($optionVm['selected']) {
                    $hasSelectedInOverflow = true;
                }
                $overflowOptions[] = $optionVm;
            }

            $facets[] = [
                'slug' => $slug,
                'name' => $name,
                'selected_count' => $selectedCount,
                'is_open' => $selectedCount > 0,
                'visible_options' => $visibleOptions,
                'overflow_options' => $overflowOptions,
                'has_overflow' => $overflowOptions !== [],
                'overflow_open' => $hasSelectedInOverflow,
            ];
        }

        return $facets;
    }

    private function normalizeGroupLabel(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return 'Otros filtros';
        }
        return mb_convert_case($value, MB_CASE_TITLE, 'UTF-8');
    }

    private function groupKey(string $label): string
    {
        $tmp = mb_strtolower($label, 'UTF-8');
        $tmp = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $tmp) ?: $tmp;
        $tmp = preg_replace('/[^a-z0-9]+/i', '-', $tmp) ?? $tmp;
        $tmp = trim($tmp, '-');
        return $tmp !== '' ? $tmp : 'otros-filtros';
    }
}
