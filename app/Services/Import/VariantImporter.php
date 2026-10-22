<?php
namespace App\Services\Import;

use App\Repositories\Catalog\ProductRepository;
use App\Repositories\Imports\ProductVariantImportRepository;
use App\Repositories\Imports\ImportProductStateRepository;
use App\Repositories\Catalog\InventoryRepository;

final class VariantImporter
{
    public function __construct(
        private ProductRepository $products,
        private ProductVariantImportRepository $variants,
        private InventoryRepository $inventory,
        private ProductImporter $productImporter,
        private ImportProductStateRepository $productStates
    ) {}

    /**
     * @return array{ok:bool,error?:string,variant_id?:int,product_id?:int,parent_created?:bool,parent_key?:string,action?:string,changes?:array<int,string>}
     */
    /**
     * @param array<string,mixed> $opt
     */
    public function upsert(array $d, bool $dry, array $opt = []): array
    {
        // El esquema real tiene dimensiones en products, NO en product_variants.
        // Rechazar ANTES de consultar/crear padre, cambiar sale_mode o inventario.
        // Con la opción apagada se ignoran los campos de envío, como corresponde.
        if (!empty($opt['update_ship_dims'])) {
            foreach (['ship_weight','ship_height','ship_width','ship_length'] as $shipField) {
                if (!array_key_exists($shipField, $d)) continue;
                $value = $d[$shipField];
                if ($value === null || trim((string)$value) === '') continue;
                return [
                    'ok' => false,
                    'error' => 'Las dimensiones de envío por variante no están disponibles en el esquema actual (' . $shipField . '). Desactivá Dimensiones de envío o dejá vacías esas celdas.',
                ];
            }
        }

        $sku = trim((string)($d['sku'] ?? ''));
        if ($sku === '') return ['ok'=>false,'error'=>'Falta sku de variante'];

        $parentKey = trim((string)($d['sku_padre'] ?? ''));
        if ($parentKey === '') return ['ok'=>false,'error'=>'Falta sku_padre'];

        $parentCreated = false;
        $parentCreatedKey = null;
        $parentRollbackOps = [];
        $parentSnapshotComplete = false;
        $parentExistingRollback = null;

        $rollback = null;

        // sku_padre puede ser sku o internal_code del producto
        $parent = $this->products->findBySku($parentKey) ?? $this->products->findByInternalCode($parentKey);
        if (!$parent) {
            // Modo opcional: crear padre implícito desde filas variante (usa los mismos campos que hoy usa la fila "producto")
            if (!empty($opt['allow_parent_from_variants'])) {
                if (empty($opt['create_missing_products'])) {
                    return ['ok'=>false,'error'=>"Producto padre no existe: $parentKey (activá 'Crear producto si no existe el código')"];
                }
                $parentData = $d;

                // El padre usa sku_padre como sku propio
                $parentData['sku'] = $parentKey;
                $parentData['internal_code'] = $parentData['internal_code'] ?? '';
                // Evitar que una fila de variante pise precios/stock del padre
                foreach (['price','list_price','cost','stock','modo_stock','low_stock_threshold','margin_pct','attr_pairs'] as $k) {
                    if (array_key_exists($k, $parentData)) unset($parentData[$k]);
                }

                // Validación mínima para poder crear el padre
                $pname = trim((string)($parentData['name'] ?? ($parentData['nombre'] ?? '')));
                $pcat = $parentData['category'] ?? ($parentData['category_slug'] ?? null);
                if ($pname === '' || $pcat === null || trim((string)$pcat) === '') {
                    return ['ok'=>false,'error'=>"No existe producto padre: {$parentKey}. Para crearlo desde la fila variante faltan obligatorios (nombre, categoría)."];
                }

                $pr = $this->productImporter->upsert($parentData, $dry, $opt);
                if (!$pr['ok']) {
                    // ProductImporter pudo crear catálogos antes de fallar.
                    // Un error en aplicación real no puede dejar esas altas sin traza.
                    if (!$dry) throw new \RuntimeException('No se pudo crear producto padre: ' . ($pr['error'] ?? 'resultado inválido'));
                    return ['ok'=>false,'error'=>$pr['error'] ?? 'No se pudo crear producto padre'];
                }

                // En dry-run: consideramos OK y dejamos que ImportService marque el padre como "planificado"
                if ($dry) {
                    return ['ok'=>true,'variant_id'=>0,'product_id'=>0,'parent_created'=>true,'parent_key'=>$parentKey,'action'=>'create_parent'];
                }

                // Contrato íntegro del alta implícita: catálogos primero, padre después.
                // No inferir IDs: se usan exclusivamente los retornados por ProductImporter.
                $parentId = (int)($pr['product_id'] ?? 0);
                $parentCreate = $pr['rollback'] ?? null;
                if (($pr['action'] ?? '') !== 'create' || $parentId <= 0
                    || !is_array($parentCreate)
                    || ($parentCreate['entity'] ?? '') !== 'product'
                    || ($parentCreate['action'] ?? '') !== 'create'
                    || (int)($parentCreate['id'] ?? 0) !== $parentId
                    || !isset($pr['rollback_ops']) || !is_array($pr['rollback_ops'])) {
                    throw new \RuntimeException('No puede verificarse el snapshot del producto padre implícito.');
                }
                $seen = ['product:'.$parentId => true];
                foreach ($pr['rollback_ops'] as $op) {
                    $ent = is_array($op) ? (string)($op['entity'] ?? '') : '';
                    $action = is_array($op) ? (string)($op['action'] ?? '') : '';
                    $id = is_array($op) ? (int)($op['id'] ?? 0) : 0;
                    $key = $ent.':'.$id;
                    if (!in_array($ent, ['brand','category','supplier'], true)
                        || $action !== 'create' || $id <= 0 || isset($seen[$key])) {
                        throw new \RuntimeException('Operación de catálogo incompleta o duplicada en el snapshot del padre.');
                    }
                    $seen[$key] = true;
                    $parentRollbackOps[] = $op;
                }
                // ProductImporter informa qué catálogos debió crear. Exigir
                // también esas operaciones: un v3 con el padre pero sin una
                // marca/categoría/proveedor nueva NO es recuperación completa.
                foreach ([
                    'brand' => ['flag'=>'would_create_brand', 'enabled'=>!empty($opt['create_missing_brands']) || !empty($opt['create_missing_catalog'])],
                    'category' => ['flag'=>'would_create_category', 'enabled'=>!empty($opt['create_missing_categories']) || !empty($opt['create_missing_catalog'])],
                    'supplier' => ['flag'=>'would_create_supplier', 'enabled'=>!empty($opt['create_missing_suppliers'])],
                ] as $entity => $condition) {
                    if (!empty($condition['enabled']) && !empty($pr[$condition['flag']])) {
                        $documented = false;
                        foreach ($parentRollbackOps as $op) {
                            if (($op['entity'] ?? '') === $entity && ($op['action'] ?? '') === 'create') {
                                $documented = true;
                                break;
                            }
                        }
                        if (!$documented) {
                            throw new \RuntimeException('Falta la operación de rollback del catálogo ' . $entity . ' del padre implícito.');
                        }
                    }
                }
                $parentRollbackOps[] = $parentCreate;
                $parentSnapshotComplete = true;

                // Releer padre y comprobar que corresponde EXACTAMENTE al alta documentada.
                $parent = $this->products->findBySku($parentKey);
                if (!$parent || (int)($parent['id'] ?? 0) !== $parentId) {
                    throw new \RuntimeException('No se pudo verificar la identidad del padre implícito creado.');
                }
                $parentCreated = true;
                $parentCreatedKey = $parentKey;
            }

            // En dry-run permitimos variantes cuyo padre será creado en el mismo archivo.
            if ($dry && !empty($opt['planned_products']) && in_array($parentKey, (array)$opt['planned_products'], true)) {
                return ['ok'=>true,'variant_id'=>0,'product_id'=>0,'action'=>'create'];
            }

            if (!$parent) {
                if ($parentCreated) throw new \RuntimeException('Padre implícito creado sin registro verificable.');
                return ['ok'=>false,'error'=>"No existe producto padre: {$parentKey}"];
            }
        }
        $productId = (int)($parent['id'] ?? 0);
        if ($productId <= 0) {
            if ($parentCreated) throw new \RuntimeException('Padre implícito con ID inválido.');
            return ['ok'=>false,'error'=>'Producto padre inválido'];
        }

        // Normalizaciones ya aplicadas por ImportService:
        // - cost_currency_id puede venir derivado desde moneda
        // - margin_pct puede venir del CSV (margen_pct) y/o de la derivación de precios
        $costCurrencyId = $d['cost_currency_id'] ?? null;
        $marginPct = array_key_exists('margin_pct', $d) ? $d['margin_pct'] : null;

        // Validar ownership ANTES de escribir cualquier dato del padre.
        $row = $this->variants->findBySku($sku);
        if ($row && (int)($row['product_id'] ?? 0) !== $productId) {
            if ($parentCreated) throw new \RuntimeException('SKU de variante pertenece a otro padre; creación implícita cancelada.');
            return ['ok'=>false,'error'=>"El SKU {$sku} ya existe en otro producto"];
        }

        // Toda operación de variante actualiza el cache de precio Desde, y
        // ocasionalmente cambia sale_mode. Documentar el BEFORE del padre
        // existente incluso cuando su modo ya era variants.
        if (!$dry && !$parentCreated) {
            $parentExistingRollback = $this->productStates->captureVariantParent($productId);
        }
        if (!$dry && (($parent['sale_mode'] ?? '') !== 'variants')) {
            if (!$this->products->updateById($productId, ['sale_mode' => 'variants'])) {
                throw new \RuntimeException('No se pudo activar el modo variantes del producto padre.');
            }
        }

        $fields = [];

        if (!empty($opt['update_internal_code']) && array_key_exists('internal_code', $d)) {
            $fields['internal_code'] = ($d['internal_code'] !== '' ? $d['internal_code'] : null);
        }

        if (!empty($opt['update_name']) && (array_key_exists('name', $d) || array_key_exists('title', $d))) {
            $t = trim((string)($d['title'] ?? $d['name'] ?? ''));
            if ($t !== '') $fields['title'] = $t;
        }

        if (!empty($opt['update_price']) && array_key_exists('price', $d) && $d['price'] !== null && $d['price'] !== '') {
            $fields['price'] = (int)$d['price'];
        }

        if (!empty($opt['update_list_price']) && array_key_exists('list_price', $d) && $d['list_price'] !== null && $d['list_price'] !== '') {
            $fields['list_price'] = (int)$d['list_price'];
        }

if (!empty($opt['update_wholesale_overrides'])) {
    if (array_key_exists('price_wh1_override', $d) && $d['price_wh1_override'] !== null && $d['price_wh1_override'] !== '') {
        $fields['price_wh1_override'] = (int)$d['price_wh1_override'];
    }
    if (array_key_exists('price_wh2_override', $d) && $d['price_wh2_override'] !== null && $d['price_wh2_override'] !== '') {
        $fields['price_wh2_override'] = (int)$d['price_wh2_override'];
    }
}


        if (!empty($opt['update_cost']) && array_key_exists('cost', $d) && $d['cost'] !== null && $d['cost'] !== '') {
            $fields['cost'] = (int)$d['cost'];
        }

        if (!empty($opt['update_cost']) && $costCurrencyId !== null) {
            $fields['cost_currency_id'] = (int)$costCurrencyId;
        }
        $wantsPrice = (!empty($opt['update_price']) || !empty($opt['update_list_price']));
        if (($wantsPrice || !empty($opt['update_cost'])) && $marginPct !== null && $marginPct !== '') {
            $fields['margin_pct'] = (float)$marginPct;
        }

        if (!empty($opt['update_publish']) && array_key_exists('is_active', $d) && $d['is_active'] !== null && $d['is_active'] !== '') {
            $fields['is_active'] = (int)$d['is_active'];
        }

        $variantId = 0;
        $changes = [];
        $before = [];
        if ($row) {
            $variantId = (int)$row['id'];
            // Detectar cambios (para mostrar en el detalle)
            foreach ($fields as $k => $v) {
                $old = $row[$k] ?? null;
                $oldNorm = is_numeric($old) ? (string)(0 + $old) : (string)($old ?? '');
                $newNorm = is_numeric($v) ? (string)(0 + $v) : (string)($v ?? '');
                if ($oldNorm === $newNorm) continue;

                $before[$k] = $old;

                $label = match ($k) {
                    'internal_code' => 'código interno',
                    'title' => 'título',
                    'price' => 'precio',
                    'list_price' => 'precio lista',
                    'cost' => 'costo',
                    'is_active' => 'publicación',
                    'ship_weight' => 'peso envío (g)',
                    'ship_height' => 'alto envío (cm)',
                    'ship_width' => 'ancho envío (cm)',
                    'ship_length' => 'largo envío (cm)',
                    default => $k,
                };

                $oldDisp = ($old === null || $old === '') ? '—' : (string)$old;
                $newDisp = ($v === null || $v === '') ? '—' : (string)$v;
                $changes[] = "{$label}: {$oldDisp} -> {$newDisp}";
            }

            if ($dry) {
                if (!$fields) return ['ok'=>true,'variant_id'=>$variantId,'product_id'=>$productId,'action'=>'noop','changes'=>[],'rollback'=>null];
                return ['ok'=>true,'variant_id'=>$variantId,'product_id'=>$productId,'action'=>'update','changes'=>$changes,'rollback'=>['entity'=>'variant','action'=>'update','id'=>$variantId,'before'=>$before]];
            }
            $ok = $this->variants->updateById($variantId, $fields);
            if (!$ok) {
                throw new \RuntimeException('No se pudo actualizar variante sin riesgo de cambios parciales en el padre.');
            }
            $rollback = ['entity'=>'variant','action'=>'update','id'=>$variantId,'before'=>$before];
        } else {
            $fields['sku'] = $sku;
            if ($dry) return ['ok'=>true,'variant_id'=>0,'product_id'=>0,'action'=>'create','changes'=>['alta: — -> creada'],'rollback'=>null];
            $variantId = $this->variants->insert($productId, $fields);
            if ($variantId <= 0) {
                throw new \RuntimeException('No se pudo crear variante sin riesgo de cambios parciales en el padre.');
            }
            $changes = ['alta: — -> creada'];
            $rollback = ['entity'=>'variant','action'=>'create','id'=>$variantId,'product_id'=>$productId];
        }

        // Actualiza cache 'Desde' del padre (solo aplica a sale_mode=variants)
        $this->products->refreshVariantPriceCache($productId);

        return ['ok'=>true,'variant_id'=>$variantId,'product_id'=>$productId,'action'=>($row ? 'update' : 'create'),'changes'=>$changes,'rollback'=>$rollback,
                'rollback_ops'=>$parentCreated ? $parentRollbackOps : ($parentExistingRollback ? [$parentExistingRollback] : [])]
            + ($parentCreated ? [
                'parent_created'=>true,
                'parent_key'=>(string)$parentCreatedKey,
                'parent_product_id'=>$productId,
                'parent_snapshot_complete'=>$parentSnapshotComplete,
            ] : []);
    }

    /** @return array{ok:bool,error?:string,stock_from?:int,stock_to?:int,stock_mode?:string,threshold_from?:int,threshold_to?:int,rollback?:array<string,mixed>} */
    public function applyInventory(int $variantId, array $d, bool $dry): array
    {
        if ($variantId <= 0) return ['ok'=>false,'error'=>'Variante inválida'];

        if (!array_key_exists('stock', $d)) return ['ok'=>true];

        $stockRaw = $d['stock'] ?? null;
        if ($stockRaw === '' || $stockRaw === null) return ['ok'=>true];
        $stock = (int)$stockRaw;
        $thrRaw = $d['low_stock_threshold'] ?? null;
        $thr   = ($thrRaw === '' || $thrRaw === null) ? 3 : (int)$thrRaw;
        $mode  = strtolower(trim((string)($d['modo_stock'] ?? '')));
        if (!in_array($mode, ['suma','fija'], true)) return ['ok'=>false,'error'=>'modo_stock inválido'];

        $prev = $this->inventory->getByVariantId($variantId);
        $newStock = $prev ? (int)($prev['stock'] ?? 0) : 0;
        if ($mode === 'suma') $newStock = $newStock + $stock;
        if ($mode === 'fija') $newStock = $stock;

        if ($dry) return ['ok'=>true,'stock_from'=>$prev ? (int)($prev['stock'] ?? 0) : 0,'stock_to'=>$newStock,'stock_mode'=>$mode,'threshold_from'=>$prev ? (int)($prev['low_stock_threshold'] ?? 3) : 3,'threshold_to'=>$thr,'rollback'=>null];
        $ok = $prev
            ? $this->inventory->updateByVariantId($variantId, $newStock, $thr)
            : $this->inventory->insertVariant($variantId, $newStock, $thr);
        return ['ok'=>$ok,'stock_from'=>$prev ? (int)($prev['stock'] ?? 0) : 0,'stock_to'=>$newStock,'stock_mode'=>$mode,'threshold_from'=>$prev ? (int)($prev['low_stock_threshold'] ?? 3) : 3,'threshold_to'=>$thr,'rollback'=>['entity'=>'inventory_variant','action'=>($prev?'update':'create'),'id'=>$variantId,'before'=>['stock'=>$prev ? (int)($prev['stock'] ?? 0) : 0,'low_stock_threshold'=>$prev ? (int)($prev['low_stock_threshold'] ?? 3) : 3]]];
    }
}
