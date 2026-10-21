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
        $variants = new VariantImporter($productsRepo, new ProductVariantImportRepository($db), $inventoryRepo, $product);
        $attributes = new AttributeImporter(
            new AttributeRepository($db),
            new AttributeValueRepository($db),
	        new ProductVariantValueRepository($db),
	        new ProductAttributeRepository($db),
	        new ProductAttributeValueRepository($db)
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

                    // Cambios detallados (campo: viejo -> nuevo)
                    // Rollback: guardar snapshot previo (solo en ejecución real)
                    if (!$dry && !empty($r['rollback']) && is_array($r['rollback'])) {
                        $rollbackOps[] = $r['rollback'];
                    }
                    if (!$dry && !empty($r['rollback_ops']) && is_array($r['rollback_ops'])) {
                        foreach ($r['rollback_ops'] as $opx) {
                            if (is_array($opx)) $rollbackOps[] = $opx;
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
$rowMeta['level'] = 'skipped';

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
                    
                        // Rollback inventario producto
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

                    // Rollback: snapshot variante (solo en ejecución real)
                    if (!$dry && !empty($r['rollback']) && is_array($r['rollback'])) {
                        $rollbackOps[] = $r['rollback'];
                    }
                    if (!$dry && !empty($r['rollback_ops']) && is_array($r['rollback_ops'])) {
                        foreach ($r['rollback_ops'] as $opx) {
                            if (is_array($opx)) $rollbackOps[] = $opx;
                        }
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

            if ($inTx) {
                $this->db->commit();
            }

            // Guardar snapshot para rollback (solo ejecución real)
            if ($inTx && !$dry && !empty($opt['job_id'])) {
                try {
                    $rbFile = preg_replace('/\.csv$/','_rollback.json',$path);
                    if ($rbFile) {
                        // Rollback v2: formato único y reversible.
                        $opsV2 = [];
                        foreach ($rollbackOps as $op) {
                            if (!is_array($op)) continue;
                            $entity = (string)($op['entity'] ?? '');
                            $action = (string)($op['action'] ?? '');
                            $id = isset($op['id']) ? (int)$op['id'] : 0;
                            if ($entity === '' || $action === '' || $id <= 0) continue;

                            $x = [
                                'entity' => $entity,
                                'action' => $action,
                                'pk' => ['id' => $id],
                            ];
                            if ($action === 'update' && !empty($op['before']) && is_array($op['before'])) {
                                // before = solo columnas tocadas (mínimo suficiente)
                                $x['before'] = (array)$op['before'];
                            }
                            $opsV2[] = $x;
                        }

                        $payload = [
                            'version' => 2,
                            'job_id' => (int)$opt['job_id'],
                            'created_at_utc' => gmdate('Y-m-d H:i:s'),
                            'ops' => $opsV2,
                        ];
                        @file_put_contents($rbFile, json_encode($payload, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE));
                    }
                } catch (\Throwable $e) {
                    // No frenamos la importación por falla de snapshot.
                }
            }

        } catch (\Throwable $e) {
            if ($inTx && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            // Importante: los ParseError suelen mostrar solo el token inesperado.
            // Agregamos archivo/línea para debug rápido.
            $res->addError(
                0,
                'Excepción: ' . get_class($e) . ': ' . $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine()
            );
        }

        // Contadores derivados (marcas/categorías únicas)
        $res->finalizePlan();

        return $res;
    }
}
