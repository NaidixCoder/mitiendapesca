<?php
namespace App\Services\Admin\Variants;

use App\Repositories\Catalog\VariantAttributesRepository;
use PDO;
use Throwable;

/**
 * Eliminar un valor maestro nunca puede borrar indirectamente selecciones
 * de productos, variantes o plantillas (las FK existentes son CASCADE).
 */
final class VariantAttributeValueDeleteService
{
    public function __construct(private PDO $db) {}

    /** Limpieza explícita desde Productos: solo el maestro realmente huérfano. */
    public function pruneAttributeValueIfUnused(int $valueId): bool
    {
        if ($valueId <= 0) return false;
        return !empty($this->deleteAttributeValue($valueId)['ok']);
    }

    /**
     * @return array{ok:bool,code:string,message:string,attribute_id?:int,value_id?:int,value?:string,usage?:array<string,int>}
     */
    public function deleteAttributeValue(int $valueId, ?int $attributeId = null): array
    {
        if ($valueId <= 0 || ($attributeId !== null && $attributeId <= 0)) {
            return ['ok'=>false, 'code'=>'INVALID', 'message'=>'Datos inválidos.'];
        }

        $repo = new VariantAttributesRepository($this->db);
        $owns = !$this->db->inTransaction();
        $savepoint = 'attributes_delete_value_guard';
        $active = false;
        try {
            if ($owns) $this->db->beginTransaction();
            else $this->db->exec('SAVEPOINT ' . $savepoint);
            $active = true;

            // InnoDB: FOR UPDATE bloquea la fila padre. Las FK hijas requieren
            // un lock compatible de esa misma fila al insertar referencias.
            $row = $repo->findAttributeValue($valueId, true);
            if (!$row) {
                $this->finish($owns, $savepoint);
                return ['ok'=>false,'code'=>'NOT_FOUND','message'=>'Valor no encontrado.'];
            }
            $actualId = (int)$row['attribute_id'];
            $label = (string)$row['value'];
            if ($attributeId !== null && $actualId !== $attributeId) {
                $this->finish($owns, $savepoint);
                return [
                    'ok'=>false,'code'=>'MISMATCH',
                    'message'=>'El valor no corresponde al atributo indicado.',
                    'attribute_id'=>$actualId,'value_id'=>$valueId,'value'=>$label
                ];
            }

            // Sin catch silenciosos: ausencia de tabla, timeout o error de SQL
            // impiden borrar y producen rollback. Consultar después del lock.
            $usage = [
                'selected_in_products'=>$repo->countProductSelections($valueId),
                'used_in_variants'=>$repo->countVariantUsage($valueId),
                'used_in_sets'=>$repo->countSetUsage($valueId),
            ];
            if (array_sum($usage) > 0) {
                $this->finish($owns, $savepoint);
                return [
                    'ok'=>false,'code'=>'VALUE_IN_USE',
                    'message'=>'No se puede eliminar: el valor está vinculado a productos, variantes o plantillas.',
                    'attribute_id'=>$actualId,'value_id'=>$valueId,'value'=>$label,'usage'=>$usage
                ];
            }

            // Nunca DELETE de product_attribute_values ni de set_items.
            // FK restrictiva o de cascada queda sin efecto al no existir hijos.
            if (!$repo->deleteAttributeValue($valueId, $actualId)) {
                $this->undo($owns, $savepoint);
                $active = false;
                return [
                    'ok'=>false,'code'=>'NOT_DELETED','message'=>'No se pudo eliminar el valor.',
                    'attribute_id'=>$actualId,'value_id'=>$valueId,'value'=>$label,'usage'=>$usage
                ];
            }

            $this->finish($owns, $savepoint);
            $active = false;
            return [
                'ok'=>true,'code'=>'DELETED','message'=>'Valor eliminado del catálogo.',
                'attribute_id'=>$actualId,'value_id'=>$valueId,'value'=>$label,'usage'=>$usage
            ];
        } catch (Throwable $e) {
            if ($active) {
                try { $this->undo($owns, $savepoint); } catch (Throwable $_) {}
            }
            if (function_exists('log_error')) {
                log_error('VariantAttributeValueDeleteService: '.$e->getMessage());
            }
            return ['ok'=>false,'code'=>'EXCEPTION','message'=>'No se pudo comprobar o eliminar el valor de forma segura.'];
        }
    }

    private function finish(bool $owns, string $savepoint): void
    {
        if ($owns) $this->db->commit();
        else $this->db->exec('RELEASE SAVEPOINT ' . $savepoint);
    }

    private function undo(bool $owns, string $savepoint): void
    {
        if ($owns && $this->db->inTransaction()) $this->db->rollBack();
        elseif (!$owns) {
            $this->db->exec('ROLLBACK TO SAVEPOINT ' . $savepoint);
            $this->db->exec('RELEASE SAVEPOINT ' . $savepoint);
        }
    }
}
