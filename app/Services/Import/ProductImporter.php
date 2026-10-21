<?php
namespace App\Services\Import;

use App\Repositories\Catalog\ProductRepository;
use App\Repositories\Catalog\BrandRepository;
use App\Repositories\Catalog\CategoryRepository;
use App\Repositories\Catalog\SupplierRepository;
use App\Support\Format\Slug;

final class ProductImporter
{
    public function __construct(
        private ProductRepository $products,
        private BrandRepository $brands,
        private CategoryRepository $categories,
        private SupplierRepository $suppliers
    ) {}

    /**
     * Upsert de producto (sin variantes).
     *
     * @param array<string,mixed> $d
     * @param array<string,mixed> $opt
     * @return array{ok:bool,error?:string,action?:string,product_id?:int,would_create_brand?:bool,would_create_category?:bool,would_create_supplier?:bool,planned_brand_name?:string,planned_category_name?:string,planned_supplier_name?:string,changes?:array<int,string>,rollback?:array<string,mixed>}
     */
    public function upsert(array $d, bool $dry, array $opt = []): array
    {
        $sku = trim((string)($d['sku'] ?? ''));
        $internal = trim((string)($d['internal_code'] ?? ''));
        $name = trim((string)($d['name'] ?? ($d['nombre'] ?? '')));

        $catIn = $d['category'] ?? ($d['category_slug'] ?? null);
        $brandIn = $d['brand'] ?? ($d['brand_slug'] ?? null);

        $supplierName = trim((string)($d['supplier_name'] ?? ($d['proveedor'] ?? '')));
        $supplierCuit = isset($d['supplier_cuit']) ? preg_replace('/\D+/', '', (string)$d['supplier_cuit']) : '';

        $short = $d['short_desc'] ?? null;
        $long  = $d['long_desc'] ?? null;
        $price = $d['price'] ?? null;
        $listp = $d['list_price'] ?? null;
        $cost  = $d['cost'] ?? null;

        $costCurrencyId = $d['cost_currency_id'] ?? null;
        $marginPct = array_key_exists('margin_pct', $d) ? $d['margin_pct'] : null;

        $iva_rate = array_key_exists('iva_rate', $d) ? $d['iva_rate'] : null;

        $slug = trim((string)($d['slug'] ?? ''));
        $is_active = array_key_exists('is_active', $d) ? (int)$d['is_active'] : null;

        $ship_weight = $d['ship_weight'] ?? null;
        $ship_height = $d['ship_height'] ?? null;
        $ship_width  = $d['ship_width'] ?? null;
        $ship_length = $d['ship_length'] ?? null;

        $updateName = !empty($opt['update_name']);
        $updateCategory = !empty($opt['update_category']);
        $updateBrand = !empty($opt['update_brand']);
        $updateSupplier = !empty($opt['update_supplier']);
        $updateDesc = !empty($opt['update_descriptions']);
        $updateCost = !empty($opt['update_cost']);
        $updatePrice = !empty($opt['update_price']);
        $updateListPrice = !empty($opt['update_list_price']);
        $updateWholesale = !empty($opt['update_wholesale_overrides']);
        $updateIva = !empty($opt['update_iva']);
        $updatePublish = !empty($opt['update_publish']);
        $updateSlug = !empty($opt['update_slug']);
        $updateInternal = !empty($opt['update_internal_code']);
        $updateShipDims = !empty($opt['update_ship_dims']);

        $row = $this->products->findBySkuOrInternal($sku, $internal);

        
        // Regla UX/Import (Etapa 1): una fila no puede "desaparecer".
        // - Si se está creando un producto, nombre/título es obligatorio.
        // - Si se marcó "Nombre / título" para actualizar, y el CSV no trae nombre, es error.
        if (!$row) {
            if ($name === '') {
                return ['ok'=>false,'error'=>"Nombre / título requerido para crear producto ($sku)"];
            }
        } else {
            if ($updateName && $name === '') {
                return ['ok'=>false,'error'=>"Marcaste 'Nombre / título' pero el CSV no trae nombre para $sku"];
            }
        }
// Si no existe el producto y el usuario no habilitó creación automática, abortamos.
        if (!$row && empty($opt['create_missing_products'])) {
            return ['ok'=>false,'error'=>"Producto no existe: $sku (activá 'Crear producto si no existe el código')"];
        }

        // Resolver IDs (marca/categoría)
        $createBrands = !empty($opt['create_missing_brands']) || !empty($opt['create_missing_catalog']);
        $createCategories = !empty($opt['create_missing_categories']) || !empty($opt['create_missing_catalog']);
        $createSuppliers = !empty($opt['create_missing_suppliers']);

        $brand_would_create = false;
        $category_would_create = false;
        $supplier_would_create = false;

        $brand_planned_name = '';
        $category_planned_name = '';
        $supplier_planned_name = '';
        $rollbackOps = [];

        $brand_id = null;
        $category_id = null;
        $supplier_id = null;

        // Brand
        if ($brandIn !== null && trim((string)$brandIn) !== '') {
            $brandVal = trim((string)$brandIn);
            $existingId = $this->brands->resolveId($brandVal, false);
            if ($existingId) {
                $brand_id = (int)$existingId;
            } else {
                if ($createBrands) {
                    $brand_would_create = true;
                    $brand_planned_name = $brandVal;
                    if (!$dry) {
                        $newId = $this->brands->resolveId($brandVal, true);
                        $brand_id = $newId ? (int)$newId : null;
                        if ($newId) {
                            $rollbackOps[] = ['entity'=>'brand','action'=>'create','id'=>(int)$newId];
                        }
                    }
                }
            }
        }

        // Category (obligatoria para crear)
        if ($catIn !== null && trim((string)$catIn) !== '') {
            $catVal = trim((string)$catIn);
            $existingId = $this->categories->resolveId($catVal, false);
            if ($existingId) {
                $category_id = (int)$existingId;
            } else {
                if ($createCategories) {
                    $category_would_create = true;
                    $category_planned_name = $catVal;
                    if (!$dry) {
                        $newId = $this->categories->resolveId($catVal, true);
                        $category_id = $newId ? (int)$newId : null;
                        if ($newId) {
                            $rollbackOps[] = ['entity'=>'category','action'=>'create','id'=>(int)$newId];
                        }
                    }
                }
            }
        }

        // Supplier: resolver por CUIT (si viene) o por nombre.
        // Supplier: resolver por CUIT (si viene) o por nombre.
        if ($supplierCuit !== '' && strlen($supplierCuit) === 11) {
            $found = $this->suppliers->findByCuit($supplierCuit);
            if ($found) {
                $supplier_id = (int)$found['id'];
            } elseif ($supplierName !== '') {
                // Si CUIT no existe y hay nombre, podríamos crear.
                $supplier_would_create = true;
                $supplier_planned_name = $supplierName;
                if ($createSuppliers && !$dry) {
                    $supplier_id = $this->suppliers->create($supplierName, $supplierCuit);

                    if ($supplier_id) { $rollbackOps[] = ['entity'=>'supplier','action'=>'create','id'=>(int)$supplier_id]; }
                }
            }
        } elseif ($supplierName !== '') {
            $found = $this->suppliers->findByNombre($supplierName);
            if ($found) {
                $supplier_id = (int)$found['id'];
            } else {
                $supplier_would_create = true;
                $supplier_planned_name = $supplierName;
                if ($createSuppliers && !$dry) {
                    $supplier_id = $this->suppliers->create($supplierName, ($supplierCuit !== '' ? $supplierCuit : null));

                    if ($supplier_id) { $rollbackOps[] = ['entity'=>'supplier','action'=>'create','id'=>(int)$supplier_id]; }
                }
            }
        }

        // Si el CSV trae marca/categoría/supplier inexistente y NO se permite crear, dejamos id null.
        // En create, si categoría queda null, será error por obligatorios.

        // Update
        if ($row) {
            $fields = [];

            if ($updateInternal && $internal !== '') {
                $fields['internal_code'] = $internal;
            }
            if ($updateName && $name !== '') {
                $fields['name'] = $name;
            }
            if ($updateBrand && $brand_id !== null) {
                $fields['brand_id'] = $brand_id;
            }
            if ($updateCategory && $category_id !== null) {
                $fields['category_id'] = $category_id;
            }
            if ($updateSupplier && $supplier_id !== null) {
                $fields['supplier_id'] = $supplier_id;
            }
            if ($updateDesc) {
                if ($short !== null) $fields['short_desc'] = $short;
                if ($long !== null) $fields['long_desc'] = $long;
            }
            if ($updateCost) {
                if ($cost !== null && $cost !== '') $fields['cost'] = (int)$cost;
                if ($costCurrencyId !== null && $costCurrencyId !== '') $fields['cost_currency_id'] = (int)$costCurrencyId;
                if ($marginPct !== null && $marginPct !== '') $fields['margin_pct'] = (float)$marginPct;
            }
            if ($updatePrice && $price !== null && $price !== '') {
                $fields['price'] = (int)$price;
            }
            if ($updateListPrice && $listp !== null && $listp !== '') {
                $fields['list_price'] = (int)$listp;
            }

if ($updateWholesale) {
    if (array_key_exists('price_wh1_override', $d) && $d['price_wh1_override'] !== null && $d['price_wh1_override'] !== '') {
        $fields['price_wh1_override'] = (int)$d['price_wh1_override'];
    }
    if (array_key_exists('price_wh2_override', $d) && $d['price_wh2_override'] !== null && $d['price_wh2_override'] !== '') {
        $fields['price_wh2_override'] = (int)$d['price_wh2_override'];
    }
}

            if ($updateIva && $iva_rate !== null && $iva_rate !== '') {
                $fields['iva_rate'] = (float)$iva_rate;
            }
            if ($updatePublish && $is_active !== null) {
                $fields['is_active'] = (int)$is_active;
            }
            if ($updateSlug && ($slug !== '' || $name !== '')) {
                $fields['slug'] = ($slug !== '' ? $slug : Slug::slugify($name));
            }
            if ($updateShipDims) {
                if ($ship_weight !== null && $ship_weight !== '') $fields['ship_weight'] = (int)$ship_weight;
                if ($ship_height !== null && $ship_height !== '') $fields['ship_height'] = (int)$ship_height;
                if ($ship_width  !== null && $ship_width  !== '') $fields['ship_width']  = (int)$ship_width;
                if ($ship_length !== null && $ship_length !== '') $fields['ship_length'] = (int)$ship_length;
            }

            // Detectar cambios (para mostrar en el detalle)
            $changes = [];
            $before = $row;

            foreach ($fields as $k => $v) {
                $old = $row[$k] ?? null;
                $oldNorm = is_numeric($old) ? (string)(0 + $old) : (string)($old ?? '');
                $newNorm = is_numeric($v) ? (string)(0 + $v) : (string)($v ?? '');
                if ($oldNorm === $newNorm) continue;

                $label = match ($k) {
                    'sku' => 'sku',
                    'internal_code' => 'código interno',
                    'name' => 'nombre',
                    'brand_id' => 'marca',
                    'category_id' => 'categoría',
                    'supplier_id' => 'proveedor',
                    'short_desc' => 'descripción',
                    'long_desc' => 'descripción larga',
                    'cost' => 'costo',
                    'cost_currency_id' => 'moneda costo',
                    'margin_pct' => 'ganancia',
                    'price' => 'precio',
                    'list_price' => 'precio lista',
                    'iva_rate' => 'iva',
                    'is_active' => 'publicación',
                    'slug' => 'slug',
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

            if (!$fields) {
                return [
                    'ok'=>true,'action'=>'noop','product_id'=>(int)$row['id'],
                    'would_create_brand'=>$brand_would_create,'would_create_category'=>$category_would_create,'would_create_supplier'=>$supplier_would_create,
                    'planned_brand_name'=>$brand_planned_name,'planned_category_name'=>$category_planned_name,'planned_supplier_name'=>$supplier_planned_name,
                    'changes'=>[],'rollback_ops'=>$rollbackOps,'rollback'=>null
                ];
            }

            if ($dry) {
                return [
                    'ok'=>true,'action'=>'update','product_id'=>(int)$row['id'],
                    'would_create_brand'=>$brand_would_create,'would_create_category'=>$category_would_create,'would_create_supplier'=>$supplier_would_create,
                    'planned_brand_name'=>$brand_planned_name,'planned_category_name'=>$category_planned_name,'planned_supplier_name'=>$supplier_planned_name,
                    'changes'=>$changes,'rollback_ops'=>$rollbackOps,'rollback'=>['entity'=>'product','action'=>'update','id'=>(int)$row['id'],'before'=>$before]
                ];
            }

            $ok = $this->products->updateById((int)$row['id'], $fields);

            return [
                'ok'=>$ok,'action'=>'update','product_id'=>(int)$row['id'],
                'would_create_brand'=>$brand_would_create,'would_create_category'=>$category_would_create,'would_create_supplier'=>$supplier_would_create,
                'planned_brand_name'=>$brand_planned_name,'planned_category_name'=>$category_planned_name,'planned_supplier_name'=>$supplier_planned_name,
                'changes'=>$changes,'rollback_ops'=>$rollbackOps,'rollback'=>['entity'=>'product','action'=>'update','id'=>(int)$row['id'],'before'=>$before]
            ];
        }

        // Alta
        // Alta
        // En preview (dry-run), si la categoría no existe pero está permitido crearla,
        // no debe marcarse como error: la categoría se creará al aplicar.
        if ($name === '' || ($category_id === null && !($dry && $createCategories && $category_would_create))) {
            return ['ok'=>false,'error'=>'Faltan obligatorios para alta (nombre, categoría)'];
        }
        if ($sku === '' && $internal === '') {
            return ['ok'=>false,'error'=>'Falta identificador para alta (sku o internal_code)'];
        }
        if ($dry) {
            return [
                'ok'=>true,'action'=>'create','product_id'=>0,
                'would_create_brand'=>$brand_would_create,'would_create_category'=>$category_would_create,'would_create_supplier'=>$supplier_would_create,
                'planned_brand_name'=>$brand_planned_name,'planned_category_name'=>$category_planned_name,'planned_supplier_name'=>$supplier_planned_name,
                'changes'=>['alta: — -> creado'],'rollback_ops'=>$rollbackOps,'rollback'=>null
            ];
        }

        $data = [
            'sku' => $sku,
            'internal_code' => ($internal !== '' ? $internal : null),
            'iva_rate' => ($iva_rate !== null && $iva_rate !== '' ? (float)$iva_rate : null),
            'ship_weight' => ($ship_weight !== null && $ship_weight !== '' ? (int)$ship_weight : null),
            'ship_height' => ($ship_height !== null && $ship_height !== '' ? (int)$ship_height : null),
            'ship_width'  => ($ship_width !== null && $ship_width !== '' ? (int)$ship_width : null),
            'ship_length' => ($ship_length !== null && $ship_length !== '' ? (int)$ship_length : null),
            'name' => $name,
            'slug' => ($slug !== '' ? $slug : Slug::slugify($name)),
            'brand_id' => $brand_id,
            'category_id' => $category_id,
            // En alta, si viene proveedor lo seteamos (no depende del checkbox update_supplier).
            'supplier_id' => $supplier_id,
            'short_desc' => $short,
            'long_desc' => $long,
            'price' => ($price !== null && $price !== '' ? (int)$price : null),
            'list_price' => ($listp !== null && $listp !== '' ? (int)$listp : null),
            'cost_currency_id' => ($costCurrencyId !== null && $costCurrencyId !== '' ? (int)$costCurrencyId : null),
            'margin_pct' => ($marginPct !== null && $marginPct !== '' ? (float)$marginPct : null),
            'cost' => ($cost !== null && $cost !== '' ? (int)$cost : null),
            'is_active' => $is_active ?? 1,
        ];

        $newId = $this->products->insertGetId($data);
        $ok = $newId > 0;

        return [
            'ok'=>$ok,'action'=>'create','product_id'=>$newId,
            'would_create_brand'=>$brand_would_create,'would_create_category'=>$category_would_create,'would_create_supplier'=>$supplier_would_create,
            'planned_brand_name'=>$brand_planned_name,'planned_category_name'=>$category_planned_name,'planned_supplier_name'=>$supplier_planned_name,
            'changes'=>['alta: — -> creado'],'rollback_ops'=>$rollbackOps,'rollback'=>['entity'=>'product','action'=>'create','id'=>$newId]
        ];
    }
}
