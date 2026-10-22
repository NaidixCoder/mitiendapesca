<?php
namespace App\Services\Import;

use App\Repositories\Catalog\AttributeRepository;
use App\Repositories\Catalog\AttributeValueRepository;
use App\Repositories\Catalog\ProductVariantValueRepository;
use App\Repositories\Catalog\ProductAttributeRepository;
use App\Repositories\Catalog\ProductAttributeValueRepository;
use App\Support\Format\Slug;
use App\Repositories\Imports\ImportAttributeRelationsRepository;

final class AttributeImporter
{
    public function __construct(
        private AttributeRepository $attributes,
        private AttributeValueRepository $values,
        private ProductVariantValueRepository $pvv,
        private ProductAttributeRepository $pa,
        private ProductAttributeValueRepository $pav,
        private ImportAttributeRelationsRepository $relations
    ) {}

    /**
     * Aplica atributos de una variante reemplazando el set completo.
     *
     * @param array<int,array{name:string,value:string}> $pairs
     * @return array{ok:bool,error?:string}
     */
    public function applyPairsToVariant(int $variantId, int $productId, array $pairs, bool $dry): array
    {
        if ($variantId <= 0) return ['ok'=>false,'error'=>'Variante inválida'];
        if ($productId <= 0) return ['ok'=>false,'error'=>'Producto inválido'];
        if (!$pairs) return ['ok'=>true,'rollback_ops'=>[]];

        // Primero capturar el BEFORE de los tres scopes. Nunca deducir el
        // estado anterior a partir del resultado del reemplazo de variantes.
        $relationOps = [];
        if (!$dry) {
            foreach (['relation_variant_values'=>$variantId,
                      'relation_product_attrs'=>$productId,
                      'relation_product_attr_values'=>$productId] as $entity=>$scope) {
                $relationOps[] = $this->relations->capture($entity, $scope);
            }
        }
        $resolved = [];
        $rollbackOps = [];
        foreach ($pairs as $p) {
            $attrName = trim((string)($p['name'] ?? ''));
            $value = trim((string)($p['value'] ?? ''));
            if ($attrName === '' || $value === '') continue;

            $attrSlug = Slug::slugify($attrName);
            if ($attrSlug === '') return ['ok'=>false,'error'=>"Atributo inválido: {$attrName}"];

            $attr = $this->attributes->findBySlug($attrSlug) ?? $this->attributes->findByName($attrName);
            $attrId = $attr ? (int)$attr['id'] : 0;
            if (!$attrId) {
                if ($dry) {
                    // En dry-run, asumimos que se crearía
                    $attrId = -1;
                } else {
                    $attrId = $this->attributes->insert($attrName, $attrSlug, 'select');
                    if (!$attrId) return ['ok'=>false,'error'=>"No se pudo crear atributo: {$attrName}"];
                    $rollbackOps[] = ['entity'=>'attribute','action'=>'create','id'=>(int)$attrId];
                }
            }

            // Valor
            $valueId = 0;
            if ($attrId > 0) {
                $v = $this->values->findByAttributeAndValue($attrId, $value);
                $valueId = $v ? (int)$v['id'] : 0;
                if (!$valueId) {
                    if ($dry) {
                        $valueId = -1;
                    } else {
                        $valueId = $this->values->insert($attrId, $value, 0);
                        if ($valueId) { $rollbackOps[] = ['entity'=>'attribute_value','action'=>'create','id'=>(int)$valueId]; }
                        if (!$valueId) return ['ok'=>false,'error'=>"No se pudo crear valor '{$value}' para {$attrName}"];
                    }
                }
            }

            if ($attrId > 0 && $valueId > 0) {
                $resolved[] = ['attribute_id'=>$attrId,'value_id'=>$valueId];
            }
        }

        if ($dry) return ['ok'=>true];

        // 1) La variante reemplaza su conjunto. BEFORE capturado arriba.
        if (!$this->pvv->replaceForVariant($variantId, $resolved)) {
            throw new \RuntimeException('No se pudieron reemplazar relaciones de variante.');
        }

        // 2) A nivel producto, aseguramos:
        // - el atributo como "disponible" (product_attributes)
        // - el valor como "seleccionado" (product_attribute_values)
        foreach ($resolved as $it) {
            $aid = (int)($it['attribute_id'] ?? 0);
            $vid = (int)($it['value_id'] ?? 0);
            if ($aid > 0 && !$this->pa->ensure($productId, $aid, 0, 0)) {
                throw new \RuntimeException('No se pudo guardar el atributo del producto.');
            }
            if ($aid > 0 && $vid > 0 && !$this->pav->ensure($productId, $aid, $vid)) {
                throw new \RuntimeException('No se pudo guardar el valor del producto.');
            }
        }

        // Orden de snapshot: maestros creados -> conjuntos de relaciones.
        // Revert restaura TODOS los BEFORE antes de borrar cualquier CREATE.
        return ['ok'=>true,'rollback_ops'=>array_merge($rollbackOps, $relationOps)];
    }
}
