<?php
namespace App\Services\Admin\Attributes\ViewModels;

final class AttributesIndexViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array{attributes_index_view: array<string,mixed>}
     */
    public function build(array $input): array
    {
        $rows = is_array($input['rows'] ?? null) ? $input['rows'] : [];
        $q = trim((string)($input['q'] ?? ''));
        $stats = is_array($input['stats'] ?? null) ? $input['stats'] : ['attributes' => 0, 'values' => 0];
        $setsTablesOk = !empty($input['setsTablesOk']);
        $sets = is_array($input['sets'] ?? null) ? $input['sets'] : [];
        $setsQ = trim((string)($input['sets_q'] ?? ''));
        $setsCategoryId = (int)($input['sets_category_id'] ?? 0);
        $setsBrandId = (int)($input['sets_brand_id'] ?? 0);
        $categories = is_array($input['categories'] ?? null) ? $input['categories'] : [];
        $brands = is_array($input['brands'] ?? null) ? $input['brands'] : [];

        return [
            'attributes_index_view' => [
                'rows' => $rows,
                'q' => $q,
                'stats' => $stats,
                'setsTablesOk' => $setsTablesOk,
                'sets' => $sets,
                'sets_q' => $setsQ,
                'sets_category_id' => $setsCategoryId,
                'sets_brand_id' => $setsBrandId,
                'categories' => $categories,
                'brands' => $brands,
            ],
        ];
    }
}

