<?php
namespace App\Services\Admin\Attributes\ViewModels;

final class AttributeValueSetEditViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $set = is_array($input['set'] ?? null) ? $input['set'] : [];
        $categories = is_array($input['categories'] ?? null) ? $input['categories'] : [];
        $brands = is_array($input['brands'] ?? null) ? $input['brands'] : [];
        $groups = is_array($input['groups'] ?? null) ? $input['groups'] : [];
        $allAttributes = is_array($input['all_attributes'] ?? null) ? $input['all_attributes'] : [];

        $setId = (int)($set['id'] ?? 0);
        $selectedCategoryId = (int)($set['category_id'] ?? 0);
        $selectedBrandId = (int)($set['brand_id'] ?? 0);

        $categoryOptions = [];
        foreach ($categories as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int)($row['id'] ?? 0);
            $categoryOptions[] = [
                'id' => $id,
                'name' => (string)($row['name'] ?? ''),
                'selected' => $id === $selectedCategoryId,
            ];
        }

        $brandOptions = [];
        foreach ($brands as $row) {
            if (!is_array($row)) {
                continue;
            }
            $id = (int)($row['id'] ?? 0);
            $brandOptions[] = [
                'id' => $id,
                'name' => (string)($row['name'] ?? ''),
                'selected' => $id === $selectedBrandId,
            ];
        }

        $attributeOptions = [];
        foreach ($allAttributes as $row) {
            if (!is_array($row)) {
                continue;
            }
            $attributeOptions[] = [
                'id' => (int)($row['id'] ?? 0),
                'name' => (string)($row['name'] ?? ''),
            ];
        }

        $groupsPrepared = [];
        foreach ($groups as $group) {
            if (!is_array($group)) {
                continue;
            }
            $attributeId = (int)($group['attribute_id'] ?? 0);
            $selectedIds = array_map('intval', is_array($group['selected_ids'] ?? null) ? $group['selected_ids'] : []);
            $selectedMap = array_fill_keys($selectedIds, true);

            $values = [];
            $allValues = is_array($group['values_all'] ?? null) ? $group['values_all'] : [];
            foreach ($allValues as $valueRow) {
                if (!is_array($valueRow)) {
                    continue;
                }
                $valueId = (int)($valueRow['id'] ?? 0);
                $values[] = [
                    'id' => $valueId,
                    'value' => (string)($valueRow['value'] ?? ''),
                    'checked' => isset($selectedMap[$valueId]),
                ];
            }

            $groupsPrepared[] = [
                'attribute_id' => $attributeId,
                'attribute_name' => (string)($group['attribute_name'] ?? ''),
                'values' => $values,
            ];
        }

        return [
            'set_id' => $setId,
            'set_name' => (string)($set['name'] ?? ''),
            'csrf_token' => csrf_token(),
            'category_options' => $categoryOptions,
            'brand_options' => $brandOptions,
            'attribute_options' => $attributeOptions,
            'groups_prepared' => $groupsPrepared,
        ];
    }
}
