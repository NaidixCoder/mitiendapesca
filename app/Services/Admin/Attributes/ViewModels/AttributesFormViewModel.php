<?php
namespace App\Services\Admin\Attributes\ViewModels;

final class AttributesFormViewModel
{
    /**
     * @param array<string,mixed> $row
     * @param array<int,array<string,mixed>> $values
     * @return array<string,mixed>
     */
    public function build(array $row, array $values): array
    {
        $id = (int)($row['id'] ?? 0);
        $name = (string)($row['name'] ?? '');
        $code = (string)($row['code'] ?? '');
        $slug = (string)($row['slug'] ?? '');
        $type = (string)($row['type'] ?? 'select');
        $isVariantAxis = !empty($row['is_variant_axis']);
        $unit = (string)($row['unit'] ?? '');

        $types = [
            ['v' => 'select', 'l' => 'Seleccion'],
            ['v' => 'text', 'l' => 'Texto'],
            ['v' => 'number', 'l' => 'Numero'],
        ];

        $preparedValues = [];
        foreach ($values as $v) {
            if (!is_array($v)) {
                continue;
            }
            $selectedInProducts = (int)($v['selected_in_products'] ?? 0);
            $usedInVariants = (int)($v['used_in_variants'] ?? 0);
            $usedInSets = (int)($v['used_in_sets'] ?? 0);
            $inUseHard = ($usedInVariants > 0 || $usedInSets > 0);

            $preparedValues[] = [
                'id' => (int)($v['id'] ?? 0),
                'value' => (string)($v['value'] ?? ''),
                'sort' => (int)($v['sort'] ?? 0),
                'is_active' => !empty($v['is_active']),
                'selected_in_products' => $selectedInProducts,
                'used_in_variants' => $usedInVariants,
                'used_in_sets' => $usedInSets,
                'in_use_hard' => $inUseHard,
                'delete_title' => $inUseHard ? 'No se puede eliminar: esta en uso por variantes o sets.' : '',
            ];
        }

        return [
            'attributes_form_view' => [
                'id' => $id,
                'name' => $name,
                'code' => $code,
                'slug' => $slug,
                'type' => $type,
                'is_variant_axis' => $isVariantAxis,
                'unit' => $unit,
                'types' => $types,
                'values' => $preparedValues,
                'title' => $id > 0 ? 'Editar atributo' : 'Nuevo atributo',
            ],
        ];
    }
}
