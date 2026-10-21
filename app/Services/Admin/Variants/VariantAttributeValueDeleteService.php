<?php
namespace App\Services\Admin\Variants;

use App\Repositories\Catalog\VariantAttributesRepository;
use PDO;
use Throwable;

final class VariantAttributeValueDeleteService
{
    public function __construct(private PDO $db)
    {
    }

    /**
     * Limpieza segura: elimina un attribute_values.id si ya no esta referenciado.
     */
    public function pruneAttributeValueIfUnused(int $valueId): bool
    {
        if ($valueId <= 0) return false;

        $st = $this->db->prepare("SELECT id FROM attribute_values WHERE id = ? LIMIT 1");
        $st->execute([$valueId]);
        $exists = (int)($st->fetchColumn() ?: 0);
        if ($exists <= 0) return false;

        $checks = [
            ['table' => 'product_attribute_values', 'col' => 'value_id'],
            ['table' => 'product_variant_values',   'col' => 'value_id'],
            ['table' => 'variant_value_set_items',  'col' => 'value_id'],
        ];

        foreach ($checks as $c) {
            try {
                $q = $this->db->prepare("SELECT 1 FROM {$c['table']} WHERE {$c['col']} = ? LIMIT 1");
                $q->execute([$valueId]);
                if ($q->fetchColumn()) return false;
            } catch (Throwable $e) {
                continue;
            }
        }

        $del = $this->db->prepare("DELETE FROM attribute_values WHERE id = ? LIMIT 1");
        $del->execute([$valueId]);
        return $del->rowCount() > 0;
    }

    /**
     * @return array{ok:bool,code:string,message:string,attribute_id?:int,value_id?:int,value?:string,usage?:array<string,int>}
     */
    public function deleteAttributeValue(int $valueId, ?int $attributeId = null): array
    {
        $valueId = (int)$valueId;
        $attributeId = $attributeId !== null ? (int)$attributeId : null;
        if ($valueId <= 0) {
            return ['ok' => false, 'code' => 'INVALID', 'message' => 'Datos invalidos.'];
        }

        $repo = new VariantAttributesRepository($this->db);
        $row = $repo->findAttributeValue($valueId);
        if (!$row) {
            return ['ok' => false, 'code' => 'NOT_FOUND', 'message' => 'Valor no encontrado.'];
        }

        $realAttributeId = (int)($row['attribute_id'] ?? 0);
        $valueLabel = (string)($row['value'] ?? '');
        if ($attributeId !== null && $attributeId > 0 && $realAttributeId !== $attributeId) {
            return ['ok' => false, 'code' => 'MISMATCH', 'message' => 'El valor no corresponde al atributo indicado.'];
        }

        $selectedInProducts = $repo->countProductSelections($valueId);
        $usedInVariants = $repo->countVariantUsage($valueId);
        $usedInSets = $repo->countSetUsage($valueId);
        $usage = [
            'selected_in_products' => $selectedInProducts,
            'used_in_variants' => $usedInVariants,
            'used_in_sets' => $usedInSets,
        ];

        if ($usedInVariants > 0) {
            return [
                'ok' => false,
                'code' => 'VALUE_IN_USE',
                'message' => 'No se puede eliminar: este valor esta usado en variantes. Recomendado: renombrar o desactivar.',
                'attribute_id' => $realAttributeId,
                'value_id' => $valueId,
                'value' => $valueLabel,
                'usage' => $usage,
            ];
        }

        if ($usedInSets > 0) {
            return [
                'ok' => false,
                'code' => 'VALUE_IN_SET',
                'message' => 'No se puede eliminar: este valor esta usado en plantillas.',
                'attribute_id' => $realAttributeId,
                'value_id' => $valueId,
                'value' => $valueLabel,
                'usage' => $usage,
            ];
        }

        $startedTx = false;
        try {
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $startedTx = true;
            }

            if ($selectedInProducts > 0) {
                $repo->deleteProductSelections($valueId);
            }
            $repo->deleteSetItems($valueId);
            $deleted = $repo->deleteAttributeValue($valueId, $realAttributeId);

            if ($startedTx) {
                $this->db->commit();
            }

            if (!$deleted) {
                return [
                    'ok' => false,
                    'code' => 'NOT_DELETED',
                    'message' => 'No se pudo eliminar el valor.',
                    'attribute_id' => $realAttributeId,
                    'value_id' => $valueId,
                    'value' => $valueLabel,
                    'usage' => $usage,
                ];
            }

            return [
                'ok' => true,
                'code' => 'DELETED',
                'message' => 'Valor eliminado del catalogo.',
                'attribute_id' => $realAttributeId,
                'value_id' => $valueId,
                'value' => $valueLabel,
                'usage' => $usage,
            ];
        } catch (Throwable $e) {
            if ($startedTx) {
                try { $this->db->rollBack(); } catch (Throwable $_) {}
            }
            return [
                'ok' => false,
                'code' => 'EXCEPTION',
                'message' => 'No se pudo eliminar el valor.',
                'attribute_id' => $realAttributeId,
                'value_id' => $valueId,
                'value' => $valueLabel,
                'usage' => $usage,
            ];
        }
    }
}

