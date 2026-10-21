<?php
namespace App\Services\Admin\Proveedores\ViewModels;

final class ProveedoresFormViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $item = is_array($input['item'] ?? null) ? $input['item'] : null;
        $errors = is_array($input['errors'] ?? null) ? $input['errors'] : [];
        $old = is_array($input['old'] ?? null) ? $input['old'] : [];

        $isEdit = is_array($item) && !empty($item['id']);
        $itemId = $isEdit ? (int)($item['id'] ?? 0) : 0;

        $fields = [
            'nombre' => $this->pick('nombre', $old, $item, ''),
            'cuit' => $this->pick('cuit', $old, $item, ''),
            'email' => $this->pick('email', $old, $item, ''),
            'telefono' => $this->pick('telefono', $old, $item, ''),
            'condicion_iva' => $this->pick('condicion_iva', $old, $item, ''),
            'is_active' => $this->pick('is_active', $old, $item, '1'),
        ];

        $condicionIvaOptions = $this->buildOptions(
            [
                '' => '—',
                'responsable_inscripto' => 'Responsable Inscripto',
                'monotributo' => 'Monotributo',
                'exento' => 'Exento',
                'consumidor_final' => 'Consumidor final',
            ],
            (string)$fields['condicion_iva']
        );

        $active = (int)$fields['is_active'];
        $activeOptions = [
            ['value' => '1', 'label' => 'Activo', 'selected' => ($active === 1)],
            ['value' => '0', 'label' => 'Inactivo', 'selected' => ($active === 0)],
        ];

        $formView = [
            'is_edit' => $isEdit,
            'item_id' => $itemId,
            'fields' => $fields,
            'errors' => $errors,
            'condicion_iva_options' => $condicionIvaOptions,
            'active_options' => $activeOptions,
        ];

        return array_merge($formView, [
            'proveedores_form_view' => $formView,
        ]);
    }

    /**
     * @param array<string,mixed> $old
     * @param array<string,mixed>|null $item
     */
    private function pick(string $key, array $old, ?array $item, string $fallback): string
    {
        if (array_key_exists($key, $old)) {
            return (string)$old[$key];
        }
        if (is_array($item) && array_key_exists($key, $item)) {
            return (string)$item[$key];
        }
        return $fallback;
    }

    /**
     * @param array<string,string> $map
     * @return array<int,array<string,mixed>>
     */
    private function buildOptions(array $map, string $current): array
    {
        $out = [];
        foreach ($map as $value => $label) {
            $out[] = [
                'value' => $value,
                'label' => $label,
                'selected' => ($current === $value),
            ];
        }
        return $out;
    }
}




