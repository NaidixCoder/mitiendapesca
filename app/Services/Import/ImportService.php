<?php
namespace App\Services\Import;

use App\Repositories\Catalog\ProductRepository;
use App\Repositories\Catalog\InventoryRepository;
use App\Repositories\Catalog\BrandRepository;
use App\Repositories\Catalog\CategoryRepository;
use App\Repositories\Catalog\SupplierRepository;
use App\Repositories\Imports\ProductVariantImportRepository;
use App\Repositories\Catalog\AttributeRepository;
use App\Repositories\Catalog\AttributeValueRepository;
use App\Repositories\Catalog\ProductVariantValueRepository;
use App\Repositories\Catalog\ProductAttributeRepository;
use App\Repositories\Catalog\ProductAttributeValueRepository;
use App\Repositories\Catalog\CurrencyRepository;
use App\Repositories\Settings\AppSettingsRepository;
use PDO;

final class ImportService
{
    private PDO $db;
    private CsvReader $reader;
    private RowNormalizer $normalizer;
    private RowValidator $validator;
    private ProductImporter $product;
    private InventoryImporter $inventory;
    private VariantImporter $variants;
    private AttributeImporter $attributes;
    private CurrencyRepository $currencies;
    /** Optional test fixture; normal requests create their own store. */
    private ?ImportSnapshotStore $snapshotStore = null;

        public function __construct(
        PDO $db,
        CsvReader $reader,
        RowNormalizer $normalizer,
        RowValidator $validator,
        ProductImporter $product,
        InventoryImporter $inventory,
        VariantImporter $variants,
        AttributeImporter $attributes,
        CurrencyRepository $currencies
    ) {
        $this->db = $db;
        $this->reader = $reader;
        $this->normalizer = $normalizer;
        $this->validator = $validator;
        $this->product = $product;
        $this->inventory = $inventory;
        $this->variants = $variants;
        $this->attributes = $attributes;
        $this->currencies = $currencies;
    }

    public static function fromDb(PDO $db): self
    {
        $reader = new CsvReader();
        $normalizer = new RowNormalizer();
        $validator = new RowValidator();

        $productsRepo = new ProductRepository($db);
        $inventoryRepo = new InventoryRepository($db);

        $product = new ProductImporter($productsRepo, new BrandRepository($db), new CategoryRepository($db), new SupplierRepository($db));
        $inventory = new InventoryImporter($inventoryRepo, $productsRepo);
        $variants = new VariantImporter($productsRepo, new ProductVariantImportRepository($db), $inventoryRepo, $product,
            new \App\Repositories\Imports\ImportProductStateRepository($db));
        $attributes = new AttributeImporter(
            new AttributeRepository($db),
            new AttributeValueRepository($db),
	        new ProductVariantValueRepository($db),
	        new ProductAttributeRepository($db),
	        new ProductAttributeValueRepository($db),
            new \App\Repositories\Imports\ImportAttributeRelationsRepository($db)
        );

        return new self($db, $reader, $normalizer, $validator, $product, $inventory, $variants, $attributes, new CurrencyRepository($db));
    }

    private function applyDefaultStockMode(array &$data, array $opt): void
    {
        if (empty($opt['update_stock'])) {
            return;
        }
        if (!array_key_exists('stock', $data) || $data['stock'] === null || $data['stock'] === '') {
            return;
        }
        // Si el CSV ya trae modo_stock, respetarlo (compatibilidad).
        if (!empty($data['modo_stock'])) {
            return;
        }
        // Por defecto: suma. Si en UI eligieron "fija", viene default_stock_mode='fija'.
        $data['modo_stock'] = $opt['default_stock_mode'] ?? 'suma';
    }

    /**
     * REGLA DE ORO IVA (Importaciones / Costos):
     * - El CSV trae `costo` SIN IVA (costo neto) en la moneda del proveedor.
     * - `iva_rate` indica la alícuota (0,21 / 0,105 / 21 / 10,5 se normaliza a rate decimal).
     * - Internamente guardamos `cost` como costo neto (sin IVA) y `cost_currency_id` según moneda.
     * - El costo final (con IVA) se deriva cuando se necesita (UI / cálculo de precio).
     */
    private function applyCostNormalization(array &$data, array $opt): void
    {
        $hasCost = array_key_exists('cost', $data) && $data['cost'] !== null && $data['cost'] !== '';
        if (!$hasCost) {
            return;
        }

        $currencyCode = isset($data['cost_currency_code']) && trim((string) $data['cost_currency_code']) !== ''
            ? strtoupper(trim((string) $data['cost_currency_code']))
            : ($opt['_currency_default_code'] ?? 'ARS');

        $cmap = $opt['_currency_map'] ?? [];
        $currencyId = isset($cmap[$currencyCode]) ? (int) $cmap[$currencyCode]['id'] : null;

        if ($currencyId !== null) {
            $data['cost_currency_id'] = $currencyId;
        }

        // CSV ya viene SIN IVA => no dividir ni "normalizar" por IVA.
        $data['cost'] = (int) round((float) $data['cost']);
    }

    /**
     * Deriva precio final ARS si:
     * - el usuario activó la derivación, o
     * - el usuario marcó "Actualizar precio/lista" y en el CSV el campo viene vacío.
     *
     * Fórmula (precio final al cliente):
     *   cost_net_csv (SIN IVA, moneda proveedor)  [applyCostNormalization]
     *   cost_ars_net = cost_net * rate_to_ars
     *   cost_ars_final = cost_ars_net * (1 + iva_rate)
     *   price_final_ars = cost_ars_final * (1 + margin_pct)
     */
    private function applyPriceDerivation(array &$data, array $opt): void
    {
        $explicit = !empty($opt['derive_prices']);

        $needsPrice = !empty($opt['update_price']) && (!array_key_exists('price', $data) || $data['price'] === null || $data['price'] === '');
        $needsList  = !empty($opt['update_list_price']) && (!array_key_exists('list_price', $data) || $data['list_price'] === null || $data['list_price'] === '');

        $implicit = ($needsPrice || $needsList);

        if (!$explicit && !$implicit) {
            return;
        }

        $hasCost = array_key_exists('cost', $data) && $data['cost'] !== null && $data['cost'] !== '';
        $hasMargin = array_key_exists('margin_pct', $data) && $data['margin_pct'] !== null && $data['margin_pct'] !== '';
        if (!$hasCost || !$hasMargin) {
            return;
        }

        $costNetInCostCurrency = (float) $data['cost']; // ya normalizado a neto
        $marginRate = (float) $data['margin_pct'];      // rate 0..1
        $ivaRate = array_key_exists('iva_rate', $data) && $data['iva_rate'] !== null && $data['iva_rate'] !== ''
            ? (float) $data['iva_rate']
            : 0.0;

        $currencyCode = isset($data['cost_currency_code']) && trim((string) $data['cost_currency_code']) !== ''
            ? strtoupper(trim((string) $data['cost_currency_code']))
            : ($opt['_currency_default_code'] ?? 'ARS');

        $cmap = $opt['_currency_map'] ?? [];
        $rateToArs = isset($cmap[$currencyCode]) ? (float) $cmap[$currencyCode]['rate_to_ars'] : 1.0;

        $costArsNet = $costNetInCostCurrency * $rateToArs;
        $costArsFinal = $costArsNet * (1.0 + $ivaRate);

        // Precio final al cliente (ARS) = costo final (con IVA) + ganancia.
        $calcPrice = (int) round($costArsFinal * (1.0 + $marginRate));

        if ($needsPrice) {
            $data['price'] = $calcPrice;
        }
        if ($needsList) {
            $data['list_price'] = $calcPrice;
        }
    }
    public function run(string $type, string $path, bool $dry, array $opt = []): ImportResult
    {
        $res = new ImportResult();
        $plannedProducts = [];
        $rollbackOps = [];
        $snapshotWriter = null;
        $commitAttempted = false;
        $commitConfirmed = false;
        $atomicityRejected = false;

        $didWarnIvaVariant = false;

        try {
            $this->reader->open($path, $opt['delimiter'] ?? null);
        } catch (\Throwable $e) {
            $res->addError(0, $e->getMessage());
            return $res;
        }

        // Monedas: mapa activo con cotización vigente (rate_to_ars). Base ARS => 1.
        $currencyMap = $this->currencies->mapActiveWithCurrentRateByCode();
        $opt['_currency_map'] = $currencyMap;
        $opt['_currency_default_code'] = 'ARS';


// Mayoristas: si está OFF en Ajustes, ignoramos columnas/updates de overrides.
if (!empty($opt['update_wholesale_overrides'])) {
    try {
        $settingsRepo = new AppSettingsRepository($this->db);
        $enabled = ((string)($settingsRepo->get('wh_enabled', '0') ?? '0')) === '1';
        if (!$enabled) {
            $opt['update_wholesale_overrides'] = false;
        }
    } catch (\Throwable $e) {
        // best-effort: si falla lectura de settings, por seguridad no aplicamos overrides
        $opt['update_wholesale_overrides'] = false;
    }
}



        $inTx = false;
        if (!$dry) {
            $this->db->beginTransaction();
            $inTx = true;
        }

        try {
            foreach ($this->reader as $rownum => $row) {
                $data = $this->normalizer->fromSpanish($row, $type, $opt);

                if (!isset($data['tipo_fila']) || trim((string)$data['tipo_fila']) === '') {
                    $data['tipo_fila'] = 'producto';
                }

                $tipo = strtolower(trim((string)($data['tipo_fila'] ?? 'producto')));

                $this->applyDefaultStockMode($data, $opt);
                $this->applyCostNormalization($data, $opt);

                // Derivación: necesita costo normalizado + mapa de monedas.
                $this->applyPriceDerivation($data, $opt);

                $err = $this->validator->validate($data, $type);
                if ($err) { $res->addError($rownum, $err); $res->addRow(['row'=>(int)$rownum,'tipo_fila'=>$tipo,'sku'=>trim((string)($data['sku'] ?? '')),'sku_padre'=>trim((string)($data['sku_padre'] ?? '')),'entidad'=>($tipo==='producto'?'Producto':'Variante'),'accion'=>'Error','notas'=>[$err],'level'=>'error']); continue; }


                $rowMeta = [
                    'row' => (int)$rownum,
                    'tipo_fila' => $tipo,
                    'sku' => trim((string)($data['sku'] ?? '')),
                    'sku_padre' => trim((string)($data['sku_padre'] ?? '')),
                    'entidad' => ($tipo === 'producto' ? 'Producto' : 'Variante'),
                    'accion' => '',
                    'notas' => [],
                    'level' => 'ok',
                ];

                // Warnings informativos (no bloquean)
                if ($tipo !== 'producto' && array_key_exists('iva_rate', $data) && $data['iva_rate'] !== null && $data['iva_rate'] !== '') {
                    if (!$didWarnIvaVariant) {
                        $res->addWarning((int)$rownum, 'IVA en variante se ignora (hereda del producto padre).');
                        $didWarnIvaVariant = true;
                    }
                    $rowMeta['notas'][] = 'IVA en variante se ignora (hereda del padre)';
                    $rowMeta['level'] = 'warning';
                }

                // UX: despiece de costo/IVA/precio (Regla de Oro IVA: costo SIN IVA).
                // Esto ayuda a validar rápidamente importaciones y evita confusiones tipo "10000 -> 8264".
                $hasCostForNote = array_key_exists('cost', $data) && $data['cost'] !== null && $data['cost'] !== '';
                if ($hasCostForNote && (!empty($opt['update_cost']) || !empty($opt['update_iva']) || !empty($opt['update_price']) || !empty($opt['update_list_price']) || !empty($opt['derive_prices']))) {
                    $currencyCode = isset($data['cost_currency_code']) && trim((string)$data['cost_currency_code']) !== ''
                        ? strtoupper(trim((string)$data['cost_currency_code']))
                        : ($opt['_currency_default_code'] ?? 'ARS');
                    $cmap = $opt['_currency_map'] ?? [];
                    $rateToArs = isset($cmap[$currencyCode]) ? (float)$cmap[$currencyCode]['rate_to_ars'] : 1.0;

                    $costNet = (float)$data['cost'];
                    $ivaRate = array_key_exists('iva_rate', $data) && $data['iva_rate'] !== null && $data['iva_rate'] !== '' ? (float)$data['iva_rate'] : 0.0;
                    $marginRate = array_key_exists('margin_pct', $data) && $data['margin_pct'] !== null && $data['margin_pct'] !== '' ? (float)$data['margin_pct'] : null;

                    $costArsNet = $costNet * $rateToArs;
                    $ivaArs = $costArsNet * $ivaRate;
                    $costArsFinal = $costArsNet * (1.0 + $ivaRate);

                    // Nota compacta (en ARS) para lectura humana.
                    $noteBits = [];
                    $noteBits[] = 'costo_sin_iva: ' . (int)round($costNet) . ' ' . $currencyCode;
                    if ($currencyCode !== 'ARS') {
                        $noteBits[] = 'cotiz: ' . rtrim(rtrim(number_format($rateToArs, 4, '.', ''), '0'), '.') . ' ARS';
                    }
                    if ($ivaRate > 0) {
                        $noteBits[] = 'iva: ' . rtrim(rtrim(number_format($ivaRate * 100, 2, '.', ''), '0'), '.') . '%';
                        $noteBits[] = 'costo_final_ars: ' . (int)round($costArsFinal);
                    } else {
                        $noteBits[] = 'iva: 0%';
                        $noteBits[] = 'costo_final_ars: ' . (int)round($costArsNet);
                    }

                    if ($marginRate !== null) {
                        $noteBits[] = 'ganancia: ' . rtrim(rtrim(number_format($marginRate * 100, 2, '.', ''), '0'), '.') . '%';
                    }

                    // Si precio/lista viene en data (sea por CSV o por derivación) lo mostramos.
                    if (array_key_exists('price', $data) && $data['price'] !== null && $data['price'] !== '') {
                        $noteBits[] = 'precio_ars: ' . (int)round((float)$data['price']);
                    }
                    if (array_key_exists('list_price', $data) && $data['list_price'] !== null && $data['list_price'] !== '') {
                        $noteBits[] = 'precio_lista_ars: ' . (int)round((float)$data['list_price']);
                    }

                    $rowMeta['notas'][] = implode(' · ', $noteBits);
                }

if ($tipo === 'producto') {
                    // En dry-run, usamos esta lista para permitir variantes/stock que dependan de un padre creado en el mismo archivo.
                    $pkey = trim((string)($data['sku'] ?? ''));
                    if ($pkey !== '') $plannedProducts[] = $pkey;
                    $pint = trim((string)($data['internal_code'] ?? ''));
                    if ($pint !== '') $plannedProducts[] = $pint;
                    $r = $this->product->upsert($data, $dry, $opt);
                    if (!$r['ok']) {
                        $msg = (string)($r['error'] ?? 'Error');
                        $rowMeta['accion'] = 'Error';
                        $rowMeta['level'] = 'error';
                        $rowMeta['notas'][] = $msg;
                        $res->addRow($rowMeta);
                        $res->addError($rownum, $msg);
                        continue;
                    }

                    // Altas auxiliares ANTES del producto: rollback borra
                    // inversamente (inventario/variantes -> producto -> catálogos).
                    if (!$dry) {
                        foreach ((array)($r['rollback_ops'] ?? []) as $opx) {
                            if (!is_array($opx) || empty($opx['entity'])
                                || empty($opx['action']) || (int)($opx['id'] ?? 0) <= 0) {
                                throw new \RuntimeException('Operación de catálogo no verificable.');
                            }
                            $rollbackOps[] = $opx;
                        }
                        if (!empty($r['rollback']) && is_array($r['rollback'])) {
                            $rollbackOps[] = $r['rollback'];
                        } elseif (($r['action'] ?? '') !== 'noop') {
                            throw new \RuntimeException('Producto modificado sin operación de rollback.');
                        }
                    }

                    if (!empty($r['changes']) && is_array($r['changes'])) {
                        foreach ($r['changes'] as $c) {
                            if (is_string($c) && trim($c) !== '') $rowMeta['notas'][] = trim($c);
                        }
                    }


$action = (string)($r['action'] ?? '');
if ($action === 'create') { $res->inc('create_products'); $rowMeta['accion'] = 'Crear'; }
elseif ($action === 'update') { $res->inc('update_products'); $rowMeta['accion'] = 'Actualizar'; }
else { $res->inc('noop_products'); $rowMeta['accion'] = 'Ignorar'; }
$rowMeta['level'] = in_array($action, ['create', 'update'], true) ? 'ok' : 'skipped';

        // Catálogo: guardar nombres planeados (únicos) y calcular contadores al final
        if (!empty($r['would_create_brand'])) {
            $rowMeta['notas'][] = 'Creará marca (auto)';
            $res->addPlanned('brands', (string)($r['planned_brand_name'] ?? ''));
        }
        if (!empty($r['would_create_category'])) {
            $rowMeta['notas'][] = 'Creará categoría (auto)';
            $res->addPlanned('categories', (string)($r['planned_category_name'] ?? ''));
        }
        if (!empty($r['would_create_supplier'])) {
            $rowMeta['notas'][] = 'Creará proveedor (auto)';
            $res->addPlanned('suppliers', (string)($r['planned_supplier_name'] ?? ''));
        }

                    if (!empty($opt['update_stock']) && array_key_exists('stock', $data)) {
                        $ir = $this->inventory->apply($data, $dry);
                        if (!$ir['ok']) {
                            $msg = (string)($ir['error'] ?? 'Error');
                            $rowMeta['accion'] = 'Error';
                            $rowMeta['level'] = 'error';
                            $rowMeta['notas'][] = $msg;
                            $res->addRow($rowMeta);
                            $res->addError($rownum, $msg);
                            continue;
                        }

                        if (!$dry && !empty($ir['rollback']) && is_array($ir['rollback'])) {
                            $rollbackOps[] = $ir['rollback'];
                        }
                        if (!$dry && !empty($ir['rollback_ops']) && is_array($ir['rollback_ops'])) {
                            foreach ($ir['rollback_ops'] as $opx) {
                                if (is_array($opx)) $rollbackOps[] = $opx;
                            }
                        }
                    
                        if (!empty($ir['changes']) && is_array($ir['changes'])) {
                            foreach ($ir['changes'] as $c) {
                                if (is_string($c) && trim($c) !== '') $rowMeta['notas'][] = trim($c);
                            }
                        }
}
                } else {
                    if (!empty($opt['update_attributes']) && !empty($data['attr_pairs'])) {
                        $labels = [];
                        foreach ((array)$data['attr_pairs'] as $pair) {
                            $labels[] = trim((string)($pair['name'] ?? '')).': '.trim((string)($pair['value'] ?? ''));
                        }
                        $rowMeta['notas'][] = 'Atributos: '.implode(' · ', $labels);
                    }
                    $opt2 = $opt;
                    $opt2['planned_products'] = $plannedProducts;
                    $r = $this->variants->upsert($data, $dry, $opt2);
                    if (!$r['ok']) {
                        $msg = (string)($r['error'] ?? 'Error');
                        $rowMeta['accion'] = 'Error';
                        $rowMeta['level'] = 'error';
                        $rowMeta['notas'][] = $msg;
                        $res->addRow($rowMeta);
                        $res->addError($rownum, $msg);
                        continue;
                    }

                    // Registrar primero altas del padre implícito y catálogos
                    // (marca/categoría/proveedor -> padre), luego la variante.
                    // El rollback consume CREATEs en orden INVERSO para respetar FK.
                    if (!$dry && !empty($r['parent_created'])) {
                        $parentOps = $r['rollback_ops'] ?? null;
                        $parentId = (int)($r['parent_product_id'] ?? 0);
                        $variantOp = $r['rollback'] ?? null;
                        if (($r['parent_snapshot_complete'] ?? false) !== true
                            || $parentId <= 0 || (int)($r['product_id'] ?? 0) !== $parentId
                            || !is_array($parentOps) || !$parentOps
                            || !is_array($variantOp) || ($variantOp['entity'] ?? '') !== 'variant'
                            || !in_array((string)($variantOp['action'] ?? ''), ['create','update'], true)
                            || (int)($variantOp['id'] ?? 0) <= 0) {
                            throw new \RuntimeException('Snapshot del padre implícito incompleto: importación cancelada.');
                        }
                        $seenParentOps = [];
                        $foundParentCreate = false;
                        foreach ($parentOps as $parentOp) {
                            $entity = is_array($parentOp) ? (string)($parentOp['entity'] ?? '') : '';
                            $action = is_array($parentOp) ? (string)($parentOp['action'] ?? '') : '';
                            $id = is_array($parentOp) ? (int)($parentOp['id'] ?? 0) : 0;
                            $key = $entity.':'.$id;
                            if (!in_array($entity, ['brand','category','supplier','product'], true)
                                || $action !== 'create' || $id <= 0 || isset($seenParentOps[$key])
                                || ($entity === 'product' && ($foundParentCreate || $id !== $parentId))
                                || ($entity !== 'product' && $foundParentCreate)) {
                                throw new \RuntimeException('Orden u operaciones del padre implícito no verificables.');
                            }
                            $seenParentOps[$key] = true;
                            if ($entity === 'product') $foundParentCreate = true;
                        }
                        if (!$foundParentCreate || (string)($parentOps[array_key_last($parentOps)]['entity'] ?? '') !== 'product') {
                            throw new \RuntimeException('Falta la operación CREATE del producto padre implícito.');
                        }
                    }
                    // El retorno de VariantImporter incluye rollback_ops del padre
                    // solo al crearlo. No registrar dos veces y nunca omitir ops malas.
                    if (!$dry && isset($r['rollback_ops'])) {
                        if (!is_array($r['rollback_ops'])) {
                            throw new \RuntimeException('Operaciones de rollback de variante inválidas.');
                        }
                        foreach ($r['rollback_ops'] as $opx) {
                            if (!is_array($opx)) {
                                throw new \RuntimeException('Operación de rollback de variante inválida.');
                            }
                            $rollbackOps[] = $opx;
                        }
                    }
                    if (!$dry && !empty($r['rollback']) && is_array($r['rollback'])) {
                        $rollbackOps[] = $r['rollback'];
                    }

                    // Cambios detallados (campo: viejo -> nuevo)
                    if (!empty($r['changes']) && is_array($r['changes'])) {
                        foreach ($r['changes'] as $c) {
                            if (is_string($c) && trim($c) !== '') $rowMeta['notas'][] = trim($c);
                        }
                    }


$va = (string)($r['action'] ?? '');
if ($va === 'create_parent') {
    $res->inc('create_parents_from_variants');
    $res->inc('create_products');
    $rowMeta['accion'] = 'Crear padre + variante';
    $rowMeta['entidad'] = 'Padre + Variante';
    $rowMeta['notas'][] = 'Padre implícito desde sku_padre';
} elseif ($va === 'create') {
    $res->inc('create_variants');
    $rowMeta['accion'] = 'Crear variante';
} elseif ($va === 'update') {
    $res->inc('update_variants');
    $rowMeta['accion'] = 'Actualizar variante';
} else {
    $res->inc('noop_variants');
    $rowMeta['accion'] = 'Ignorar variante';
    $rowMeta['level'] = 'skipped';
}
                    // Si el import creó un padre implícito desde una fila variante, lo marcamos como planificado para permitir las siguientes filas en dry-run.
                    if (!empty($r['parent_created']) && !empty($r['parent_key'])) {
                        $plannedProducts[] = (string)$r['parent_key'];
                    }

                    $variantId = (int)($r['variant_id'] ?? 0);

                    if (!empty($opt['update_attributes']) && !empty($data['attr_pairs']) && $variantId > 0) {
                        $pid = (int)($r['product_id'] ?? 0);
                        $ar = $this->attributes->applyPairsToVariant($variantId, $pid, (array)$data['attr_pairs'], $dry);
                        if (!$ar['ok']) {
                            $msg = (string)($ar['error'] ?? 'Error');
                            if (!$dry) throw new \RuntimeException('Atributos no reversibles: '.$msg);
                            $rowMeta['accion'] = 'Error';
                            $rowMeta['level'] = 'error';
                            $rowMeta['notas'][] = $msg;
                            $res->addRow($rowMeta);
                            $res->addError($rownum, $msg);
                            continue;
                        }
                        if (!$dry && !empty($ar['rollback_ops']) && is_array($ar['rollback_ops'])) {
                            foreach ($ar['rollback_ops'] as $opx) {
                                if (is_array($opx)) $rollbackOps[] = $opx;
                            }
                        }
                    }

                    if (!empty($opt['update_stock']) && array_key_exists('stock', $data) && $variantId > 0) {
                        $ir = $this->variants->applyInventory($variantId, $data, $dry);
                        if (!$ir['ok']) {
                            $msg = (string)($ir['error'] ?? 'Error');
                            $rowMeta['accion'] = 'Error';
                            $rowMeta['level'] = 'error';
                            $rowMeta['notas'][] = $msg;
                            $res->addRow($rowMeta);
                            $res->addError($rownum, $msg);
                            continue;
                        }

                        if (!$dry && !empty($ir['rollback']) && is_array($ir['rollback'])) {
                            $rollbackOps[] = $ir['rollback'];
                        }
                        if (!$dry && !empty($ir['rollback_ops']) && is_array($ir['rollback_ops'])) {
                            foreach ($ir['rollback_ops'] as $opx) {
                                if (is_array($opx)) $rollbackOps[] = $opx;
                            }
                        }

                        // Mostrar cambio de stock/umbral cuando corresponda
                        $sf = $ir['stock_from'] ?? null;
                        $st = $ir['stock_to'] ?? null;
                        $sm = $ir['stock_mode'] ?? null;
                        if ($sf !== null && $st !== null && (int)$sf !== (int)$st) {
                            $suffix = $sm ? " ({$sm})" : '';
                            $rowMeta['notas'][] = 'stock: ' . (int)$sf . ' -> ' . (int)$st . $suffix;
                        }
                        $tf = $ir['threshold_from'] ?? null;
                        $tt = $ir['threshold_to'] ?? null;
                        if ($tf !== null && $tt !== null && (int)$tf !== (int)$tt) {
                            $rowMeta['notas'][] = 'umbral: ' . (int)$tf . ' -> ' . (int)$tt;
                        }
                    }
                }

                $res->addRow($rowMeta);

                // Etapa 1: conteo consistente por fila (OK / WARNING / SKIPPED)
                $lvl = (string)($rowMeta['level'] ?? 'ok');
                if ($lvl === 'skipped') {
                    $res->addSkipped();
                } elseif ($lvl === 'warning') {
                    $res->addOkWithWarning();
                } else {
                    $res->addOk();
                }
}

            // I05 — política comercial aprobada: ninguna fila fallida puede
            // acompañar un COMMIT. Se evalúa después de recorrer TODO el CSV,
            // para preservar cada diagnóstico, incluso si hubo escrituras previas.
            // Los warnings informativos NO suman a errors.
            if ($inTx && $res->errors > 0) {
                $atomicityRejected = true;
                throw new \RuntimeException('I05: se cancela el CSV completo por errores de fila.');
            }

            // Generar el estado FINAL mientras la transacción del import todavía
            // mantiene sus locks. No modifica datos comerciales.
            $rollbackPayload = null;
            if ($inTx && !$dry && !empty($opt['job_id'])) {
                $opsV3 = [];
                $keys = [];
                $complete = true;
                $reason = '';
                foreach ($rollbackOps as $op) {
                    if (!is_array($op)) { $complete = false; $reason='invalid_op'; break; }
                    $entity = (string)($op['entity'] ?? '');
                    $action = (string)($op['action'] ?? '');
                    $id = (int)($op['id'] ?? 0);
                    if ($entity === '' || $id <= 0 || !in_array($action,['create','update'],true)) {
                        $complete=false; $reason='invalid_entity_action'; break;
                    }
                    $before = (array)($op['before'] ?? []);
                    if ($action==='update' && !$before) {
                        $complete=false; $reason='missing_before'; break;
                    }
                    $key = $entity.':'.$id;
                    if (isset($keys[$key])) {
                        $i=$keys[$key];
                        // Si una fila creó la entidad y otra la actualizó, revertimos
                        // la creación (no la actualización). Para updates, usar
                        // BEFORE original, no el intermedio.
                        if ($action==='create') {
                            $opsV3[$i]=['entity'=>$entity,'action'=>'create','pk'=>['id'=>$id]];
                        }
                        continue;
                    }
                    $x=['entity'=>$entity,'action'=>$action,'pk'=>['id'=>$id]];
                    if ($action==='update')$x['before']=$before;
                    $keys[$key]=count($opsV3);
                    $opsV3[]=$x;
                }

                if (!$opsV3) { $complete=false; $reason='empty_ops'; }
                if ($complete) {
                    try {
                        $stateRepo = new \App\Repositories\Imports\ImportRevertRepository($this->db);
                        foreach ($opsV3 as &$op) {
                            $fingerprint = $stateRepo->fingerprint((string)$op['entity'], (int)$op['pk']['id']);
                            if ($fingerprint === null) {
                                $complete=false; $reason='missing_poststate'; break;
                            }
                            $op['after_sha256']=$fingerprint;
                            if ($op['action'] === 'create') {
                                $op['after_rel_sha256']=$stateRepo->dependentFingerprint((string)$op['entity'], (int)$op['pk']['id']);
                            }
                        }
                        unset($op);
                    } catch (\Throwable $e) {
                        $complete=false;
                        $reason='cannot_read_poststate';
                    }
                }
                $rollbackPayload = [
                    'version'=>3,
                    'job_id'=>(int)$opt['job_id'],
                    'created_at_utc'=>gmdate('Y-m-d H:i:s'),
                    'complete'=>$complete,
                    'reason'=>$complete ? null : $reason,
                    'ops'=>$opsV3,
                ];
            }

            if ($inTx) {
                // No se confirma ninguna ejecución potencialmente mutadora sin
                // snapshot comprobable. Un import solo-noop se rechaza de modo
                // conservador: los importadores podrían tener efectos no declarados.
                if (empty($opt['job_id']) || !$rollbackPayload
                    || empty($rollbackPayload['complete'])
                    || !$rollbackPayload['ops']) {
                    throw new \RuntimeException('La importación no tiene un snapshot v3 completo; cambios SQL cancelados.');
                }

                $snapshotWriter = $this->snapshotStore ?? new ImportSnapshotStore();
                $snapshotWriter->prepare($path, (int)$opt['job_id'], $rollbackPayload);
                $snapshotWriter->publish();
                $snapshotWriter->beforeCommit();

                $commitAttempted = true;
                if (!$this->db->commit()) {
                    throw new \RuntimeException('MySQL no confirmó el commit de la importación.');
                }
                $commitConfirmed = true;
                $res->setApplyOutcome(ImportResult::APPLY_COMMITTED);
                $snapshotWriter->committed();
            }

        } catch (\Throwable $e) {
            $rollbackConfirmed = false;
            if ($inTx && !$commitConfirmed && $this->db->inTransaction()) {
                try {
                    $rollbackConfirmed = $this->db->rollBack();
                } catch (\Throwable $rollbackError) {
                    // Si el estado de MySQL es incierto, preservar el snapshot
                    // publicado como posible evidencia de una transacción confirmada.
                }
            }
            if ($commitConfirmed) {
                $res->setApplyOutcome(ImportResult::APPLY_COMMITTED);
            } elseif ($rollbackConfirmed) {
                $res->setApplyOutcome(ImportResult::APPLY_ROLLED_BACK);
            } elseif ($commitAttempted || ($inTx && !$rollbackConfirmed)) {
                $res->setApplyOutcome(ImportResult::APPLY_COMMIT_UNCERTAIN);
            } else {
                $res->setApplyOutcome(ImportResult::APPLY_NOT_STARTED);
            }
            if ($snapshotWriter !== null) {
                try {
                    if (!$commitAttempted || $rollbackConfirmed) {
                        $snapshotWriter->aborted();
                    } else {
                        // PDO::commit() pudo llegar al servidor y perder su respuesta.
                        // En ese caso NO eliminar el punto publicado.
                        $snapshotWriter->uncertainCommit();
                    }
                } catch (\Throwable $cleanupError) {
                    // Nunca ocultar la causa SQL/FS original.
                }
            }
            // Importante: los ParseError suelen mostrar solo el token inesperado.
            // Agregamos archivo/línea para debug rápido.
            // En I05 los errores individuales ya fueron recogidos en el CSV:
            // no convertir la decisión de rollback en otro error artificial.
            if (!$atomicityRejected) {
                $res->addError(
                    0,
                    'Excepción: ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine()
                );
            }
        }

        // Contadores derivados (marcas/categorías únicas)
        $res->finalizePlan();

        return $res;
    }
}
