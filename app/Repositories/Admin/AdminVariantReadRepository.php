<?php
namespace App\Repositories\Admin;

use App\Repositories\Admin\CatalogTagsRepository;
use PDO;
use Throwable;

final class AdminVariantReadRepository
{
    public function __construct(private PDO $db) {}

    public function fetchForProduct(int $productId, array $opts = []): array
    {
        // global attributes (para selector)
        try {
            $attrs = $this->db->query("SELECT id, name, code, slug, type, is_variant_axis, unit FROM attributes ORDER BY name ASC")
                ->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            // Compat: esquemas viejos pueden no tener slug/type.
            $attrs = $this->db->query("SELECT id, name FROM attributes ORDER BY name ASC")
                ->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($attrs as &$a) {
                $a['code'] = (string)($a['code'] ?? '');
                $a['slug'] = (string)($a['slug'] ?? '');
                $a['type'] = (string)($a['type'] ?? 'select');
                $a['is_variant_axis'] = (int)($a['is_variant_axis'] ?? 0);
                $a['unit'] = (string)($a['unit'] ?? '');
            }
            unset($a);
        }

        // attributes del producto
        try {
            $st = $this->db->prepare("SELECT pa.attribute_id, pa.sort, pa.is_required, a.name, a.code, a.slug, a.type, a.is_variant_axis, a.unit
                                      FROM product_attributes pa
                                      JOIN attributes a ON a.id = pa.attribute_id
                                      WHERE pa.product_id = :pid
                                      ORDER BY pa.sort ASC, a.name ASC");
            $st->execute([':pid'=>$productId]);
            $pattrs = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            // Compat: faltan columnas pa.sort / pa.is_required o a.slug / a.type.
            $st = $this->db->prepare("SELECT pa.attribute_id, a.name
                                      FROM product_attributes pa
                                      JOIN attributes a ON a.id = pa.attribute_id
                                      WHERE pa.product_id = :pid
                                      ORDER BY a.name ASC");
            $st->execute([':pid'=>$productId]);
            $pattrs = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($pattrs as &$pa) {
                $pa['sort'] = (int)($pa['sort'] ?? 0);
                $pa['is_required'] = (int)($pa['is_required'] ?? 1);
                $pa['code'] = (string)($pa['code'] ?? '');
                $pa['slug'] = (string)($pa['slug'] ?? '');
                $pa['type'] = (string)($pa['type'] ?? 'select');
                $pa['is_variant_axis'] = (int)($pa['is_variant_axis'] ?? 0);
                $pa['unit'] = (string)($pa['unit'] ?? '');
            }
            unset($pa);
        }

        // Meta del producto (categoría/marca) para plantillas y sugerencias
        $productMeta = ['category_id' => 0, 'brand_id' => 0, 'iva_rate' => 0.0];
        try {
            $spm = $this->db->prepare("SELECT category_id, brand_id, iva_rate, margin_pct FROM products WHERE id=? LIMIT 1");
            $spm->execute([$productId]);
            $pm = $spm->fetch(PDO::FETCH_ASSOC) ?: [];
            $productMeta['category_id'] = (int)($pm['category_id'] ?? 0);
            $productMeta['brand_id'] = (int)($pm['brand_id'] ?? 0);
            $productMeta['iva_rate'] = (float)($pm['iva_rate'] ?? 0);
        } catch (Throwable $e) {
            // defensivo: si no existe tabla/columna en algún entorno viejo, ignoramos
        }

        // Detectar columnas reales en attribute_values (compat: value vs name, sort opcional)
        $valueCol = 'value';
        try { $this->db->query("SELECT `value` FROM attribute_values LIMIT 0"); } catch (Throwable $e) { $valueCol = 'name'; }

        $hasSort = true;
        try { $this->db->query("SELECT sort FROM attribute_values LIMIT 0"); } catch (Throwable $e) { $hasSort = false; }


        // values de esos attributes
        $valuesByAttr = [];
        // Valores "locales" por producto+atributo (evita contaminación entre rubros)
        if ($pattrs) {
            $attrIds = array_values(array_unique(array_map(fn($r)=>(int)$r['attribute_id'], $pattrs)));
            $in  = implode(',', array_fill(0, count($attrIds), '?'));

            // Si existe product_attribute_values, usamos ese vínculo para listar SOLO lo del producto.
            // (Compat: si no existe, caemos al comportamiento viejo)
            try {
                $sql = "SELECT av.id, av.attribute_id, av.{$valueCol} AS value"
                     . ($hasSort ? ", av.sort AS sort" : ", 0 AS sort")
                     . " FROM product_attribute_values pav"
                     . " JOIN attribute_values av ON av.id = pav.value_id"
                     . " WHERE pav.product_id = ? AND pav.attribute_id IN ($in)"
                     . " ORDER BY av.attribute_id ASC, " . ($hasSort ? "av.sort ASC," : "") . " av.{$valueCol} ASC, av.id ASC";

                $sv = $this->db->prepare($sql);
                $sv->execute(array_merge([$productId], $attrIds));
                $rows = $sv->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $aid = (int)($r['attribute_id'] ?? 0);
                    if (!$aid) continue;
                    $valuesByAttr[$aid] ??= [];
                    $valuesByAttr[$aid][] = $r;
                }
            } catch (Throwable $e) {
                // Fallback a global (entorno viejo)
                $sql = "SELECT id, attribute_id, {$valueCol} AS value"
                     . ($hasSort ? ", sort AS sort" : ", 0 AS sort")
                     . " FROM attribute_values WHERE attribute_id IN ($in)"
                     . " ORDER BY attribute_id ASC, " . ($hasSort ? "sort ASC," : "") . " {$valueCol} ASC, id ASC";
                $sv = $this->db->prepare($sql);
                $sv->execute($attrIds);
                $rows = $sv->fetchAll(PDO::FETCH_ASSOC) ?: [];
                foreach ($rows as $r) {
                    $aid = (int)($r['attribute_id'] ?? 0);
                    if (!$aid) continue;
                    $valuesByAttr[$aid] ??= [];
                    $valuesByAttr[$aid][] = $r;
                }
            }
        }

// valores seleccionados por producto+atributo (si existe)
        $selectedByAttr = [];
        try {
            $sp = $this->db->prepare("SELECT attribute_id, value_id FROM product_attribute_values WHERE product_id = :pid");
            $sp->execute([':pid'=>$productId]);
            $pav = $sp->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($pav as $r) {
                $aid = (int)$r['attribute_id'];
                $selectedByAttr[$aid] ??= [];
                $selectedByAttr[$aid][] = (int)$r['value_id'];
            }
        } catch (Throwable $e) {
            // Compat: en entornos viejos puede no existir product_attribute_values.
            $selectedByAttr = [];
        }


// variants + inventory
$variants = [];
$attempts = [];

$hasPvCol = function (string $col): bool {
    try {
        $this->db->query("SELECT {$col} FROM product_variants LIMIT 0");
        return true;
    } catch (Throwable $e) {
        return false;
    }
};

$pvListPrice = $hasPvCol('list_price') ? 'pv.list_price AS list_price' : 'NULL AS list_price';
$pvMargin = $hasPvCol('margin_pct') ? 'pv.margin_pct AS margin_pct' : 'NULL AS margin_pct';
$pvShipWeight = $hasPvCol('ship_weight') ? 'pv.ship_weight AS ship_weight' : 'NULL AS ship_weight';
$pvShipHeight = $hasPvCol('ship_height') ? 'pv.ship_height AS ship_height' : 'NULL AS ship_height';
$pvShipWidth = $hasPvCol('ship_width') ? 'pv.ship_width AS ship_width' : 'NULL AS ship_width';
$pvShipLength = $hasPvCol('ship_length') ? 'pv.ship_length AS ship_length' : 'NULL AS ship_length';

// Preferimos leer stock por variante desde inventory_variants.
// Pero si la DB del entorno quedó desfasada (tabla/columna distinta), degradamos sin romper la pantalla.
$attempts[] = [
    'sql' => "SELECT pv.id, pv.product_id, pv.sku, pv.title, pv.price, {$pvListPrice}, pv.cost, {$pvMargin}, pv.is_active,
                     {$pvShipWeight}, {$pvShipHeight}, {$pvShipWidth}, {$pvShipLength},
                     iv.stock, iv.low_stock_threshold
              FROM product_variants pv
              LEFT JOIN inventory_variants iv ON iv.variant_id = pv.id
              WHERE pv.product_id = :pid
              ORDER BY pv.is_active DESC, pv.id ASC",
    'has_inventory'  => true,
];

// Compat: algunas DB viejas usaban product_variant_id en vez de variant_id
$attempts[] = [
    'sql' => "SELECT pv.id, pv.product_id, pv.sku, pv.title, pv.price, {$pvListPrice}, pv.cost, {$pvMargin}, pv.is_active,
                     {$pvShipWeight}, {$pvShipHeight}, {$pvShipWidth}, {$pvShipLength},
                     iv.stock, iv.low_stock_threshold
              FROM product_variants pv
              LEFT JOIN inventory_variants iv ON iv.product_variant_id = pv.id
              WHERE pv.product_id = :pid
              ORDER BY pv.is_active DESC, pv.id ASC",
    'has_inventory'  => true,
];

// Último recurso: sin inventory_variants (pantalla igual carga, pero stock/umbral quedan vacíos)
$attempts[] = [
    'sql' => "SELECT pv.id, pv.product_id, pv.sku, pv.title, pv.price, {$pvListPrice}, pv.cost, {$pvMargin}, pv.is_active,
                     {$pvShipWeight}, {$pvShipHeight}, {$pvShipWidth}, {$pvShipLength}
              FROM product_variants pv
              WHERE pv.product_id = :pid
              ORDER BY pv.is_active DESC, pv.id ASC",
    'has_inventory'  => false,
];

$lastErr = null;
foreach ($attempts as $att) {
    try {
        $v = $this->db->prepare($att['sql']);
        $v->execute([':pid'=>$productId]);
        $variants = $v->fetchAll(PDO::FETCH_ASSOC) ?: [];

        if (!$att['has_inventory']) {
            foreach ($variants as &$tmp) { $tmp['stock'] = null; $tmp['low_stock_threshold'] = null; }
            unset($tmp);
        }
        $lastErr = null;
        break;
    } catch (Throwable $e) {
        $lastErr = $e;
        $variants = [];
        continue;
    }
}
if ($lastErr) { throw $lastErr; }// Si el schema obliga sku NOT NULL, al generar variantes usamos un placeholder interno.
        // Para el admin, ese placeholder se muestra como vacío para forzar cargar el SKU real del proveedor.
        foreach ($variants as &$vr) {
            $sku = (string)($vr['sku'] ?? '');
            if ($this->isTmpSku($sku)) {
                $vr['sku'] = '';
            }
        }
        unset($vr);

        // Ganancia (%) por variante (margin_pct) (compat: si la columna no existe, no rompe)
        try {
            $mm = $this->db->prepare("SELECT id, margin_pct FROM product_variants WHERE product_id = :pid");
            $mm->execute([':pid' => $productId]);
            $mrows = $mm->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            $mById = [];
            foreach ($mrows as $r) {
                $vid = (int)($r['id'] ?? 0);
                if (!$vid) continue;
                $mById[$vid] = $r['margin_pct'] ?? null;
            }
            foreach ($variants as &$vr) {
                $vid = (int)($vr['id'] ?? 0);
                if (!$vid) continue;
                if (array_key_exists($vid, $mById)) {
                    $vr['margin_pct'] = $mById[$vid];
                } else {
                    $vr['margin_pct'] = $vr['margin_pct'] ?? null;
                }
            }
            unset($vr);
        } catch (\Throwable $_) {
            foreach ($variants as &$vr) { $vr['margin_pct'] = $vr['margin_pct'] ?? null; }
            unset($vr);
        }

        // orders count per variant (para reglas de eliminación / pausado)
        $ordersCount = [];
        try {
            $oc = $this->db->prepare("SELECT variant_id, COUNT(*) AS c FROM order_items WHERE product_id = :pid AND variant_id IS NOT NULL GROUP BY variant_id");
            $oc->execute([':pid'=>$productId]);
            $rows = $oc->fetchAll(PDO::FETCH_ASSOC) ?: [];
            foreach ($rows as $r) { $ordersCount[(int)($r['variant_id'] ?? 0)] = (int)($r['c'] ?? 0); }
        } catch (Throwable $_) { /* noop */ }

        foreach ($variants as &$vr) {
            $vr['orders_count'] = (int)($ordersCount[(int)($vr['id'] ?? 0)] ?? 0);
        }
        unset($vr);

        // variant values (para mostrar)
        $vvrows = [];
        try {
            $vv = $this->db->prepare("SELECT pvv.variant_id, pvv.attribute_id, pvv.value_id, a.name AS attribute_name, av.{$valueCol} AS value_text
                                      FROM product_variant_values pvv
                                      JOIN attributes a ON a.id = pvv.attribute_id
                                      JOIN attribute_values av ON av.id = pvv.value_id
                                      JOIN product_variants pv ON pv.id = pvv.variant_id
                                      WHERE pv.product_id = :pid
                                      ORDER BY pvv.variant_id ASC, a.name ASC");
            $vv->execute([':pid'=>$productId]);
            $vvrows = $vv->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (Throwable $e) {
            // Compat: algunas instalaciones usan attribute_value_id.
            try {
                $vv = $this->db->prepare("SELECT pvv.variant_id, pvv.attribute_id, pvv.attribute_value_id AS value_id, a.name AS attribute_name, av.{$valueCol} AS value_text
                                          FROM product_variant_values pvv
                                          JOIN attributes a ON a.id = pvv.attribute_id
                                          JOIN attribute_values av ON av.id = pvv.attribute_value_id
                                          JOIN product_variants pv ON pv.id = pvv.variant_id
                                          WHERE pv.product_id = :pid
                                          ORDER BY pvv.variant_id ASC, a.name ASC");
                $vv->execute([':pid'=>$productId]);
                $vvrows = $vv->fetchAll(PDO::FETCH_ASSOC) ?: [];
            } catch (Throwable $e2) {
                // Último fallback: seguimos sin valores asociados (no rompemos fragmento).
                $vvrows = [];
            }
        }
        $valuesByVariant = [];
        // Fallback UX: si ya existen variantes pero no hay selección guardada en product_attribute_values,
        // inferimos los valores usados a partir de product_variant_values.
        // Esto evita que el usuario vea checkboxes vacíos (genera dudas) cuando las variantes ya están creadas.
        $selectionDerived = false;
        $derivedSelected = [];
        foreach ($vvrows as $r) {
            $vid = (int)$r['variant_id'];
            $aid = (int)$r['attribute_id'];
            $valId = (int)$r['value_id'];

            if ($aid > 0 && $valId > 0) {
                $derivedSelected[$aid] ??= [];
                $derivedSelected[$aid][$valId] = true;
            }

            $valuesByVariant[$vid] ??= [];
            $valuesByVariant[$vid][] = [
                'attribute_id' => $aid,
                'attribute_name' => $r['attribute_name'],
                'value_id' => $valId,
                'value' => $r['value_text'],
            ];
        }

        // Si no había selección guardada, la inferimos; si había, unificamos (defensivo).
        if (!empty($derivedSelected)) {
            $hasAnySavedSelection = false;
            foreach ($selectedByAttr as $arr) {
                if (!empty($arr)) { $hasAnySavedSelection = true; break; }
            }

            // UX fallback: solo inferimos/precargamos selección desde variantes cuando todavía no hay
            // selección guardada en product_attribute_values. Si ya existe selección, la respetamos
            // (caso contrario no se podría "quitar" un valor individual porque volvería a marcarse).
            if (!$hasAnySavedSelection) {
                $selectionDerived = true;
                foreach ($derivedSelected as $aid => $set) {
                    $derived = array_map('intval', array_keys($set));
                    $current = array_values(array_unique(array_map('intval', $selectedByAttr[$aid] ?? [])));
                    $selectedByAttr[$aid] = array_values(array_unique(array_merge($current, $derived)));
                }
            
                // Asegurar que los valores derivados existan en el listado (para no mostrar "vacío")
                // cuando ya hay variantes creadas pero aún no hay selección guardada en product_attribute_values.
                $needIds = [];
                foreach ($derivedSelected as $aid => $set) {
                    foreach (array_keys($set ?: []) as $vid) {
                        $vid = (int)$vid;
                        if ($vid > 0) $needIds[$vid] = true;
                    }
                }

                if ($needIds) {
                    // Ya presentes en valuesByAttr
                    $present = [];
                    foreach ($valuesByAttr as $aid => $arr) {
                        foreach (($arr ?: []) as $r) {
                            $idv = (int)($r['id'] ?? 0);
                            if ($idv > 0) $present[$idv] = true;
                        }
                    }

                    $missing = array_values(array_diff(array_keys($needIds), array_keys($present)));
                    if ($missing) {
                        $inM = implode(',', array_fill(0, count($missing), '?'));
                        $sqlM = "SELECT id, attribute_id, {$valueCol} AS value"
                              . ($hasSort ? ", sort AS sort" : ", 0 AS sort")
                              . " FROM attribute_values WHERE id IN ($inM)";
                        $stM = $this->db->prepare($sqlM);
                        $stM->execute(array_values($missing));
                        $rowsM = $stM->fetchAll(PDO::FETCH_ASSOC) ?: [];
                        foreach ($rowsM as $r) {
                            $aid = (int)($r['attribute_id'] ?? 0);
                            if (!$aid) continue;
                            $valuesByAttr[$aid] ??= [];
                            $valuesByAttr[$aid][] = $r;
                        }

                        // Orden por sort/value para consistencia
                        foreach ($valuesByAttr as $aid => $arr) {
                            usort($arr, function($a, $b) use ($hasSort) {
                                $sa = (int)($a['sort'] ?? 0);
                                $sb = (int)($b['sort'] ?? 0);
                                if ($hasSort && $sa !== $sb) return $sa <=> $sb;
                                $va = (string)($a['value'] ?? '');
                                $vb = (string)($b['value'] ?? '');
                                $c = strcasecmp($va, $vb);
                                if ($c !== 0) return $c;
                                return ((int)($a['id'] ?? 0)) <=> ((int)($b['id'] ?? 0));
                            });
                            $valuesByAttr[$aid] = $arr;
                        }
                    }
                }
}
        }

// Sobrantes (obsoletas): aquellas que ya no corresponden a la selección actual de valores.
// Regla: si la variante usa un valor no seleccionado (o un atributo que ya no está en el producto),
// la consideramos "sobrante". Si faltan atributos requeridos, eso es "incompleta" (otra regla).
$allowedAttrIds = [];
foreach (($pattrs ?: []) as $pa) {
    $aid = (int)($pa['attribute_id'] ?? $pa['id'] ?? 0);
    if ($aid) { $allowedAttrIds[$aid] = true; }
}

$selectedSets = [];
foreach (($selectedByAttr ?: []) as $aid => $arr) {
    $aid = (int)$aid;
    if ($aid <= 0) continue;
    $vals = array_values(array_unique(array_map('intval', (array)$arr)));
    $selectedSets[$aid] = $vals ? array_flip($vals) : [];
}

$obsoleteIds = [];
$obsoleteMap = [];
$obsoleteWithOrders = 0;

if (!empty($valuesByVariant)) {
    foreach ($valuesByVariant as $vid => $vals) {
        $vid = (int)$vid;
        if ($vid <= 0) continue;

        $isObsolete = false;
        foreach (($vals ?: []) as $it) {
            $aid = (int)($it['attribute_id'] ?? 0);
            $valId = (int)($it['value_id'] ?? 0);
            if (!$aid || !$valId) continue;

            // atributo ya no pertenece al producto
            if (empty($allowedAttrIds[$aid])) { $isObsolete = true; break; }

            // no hay selección vigente para este atributo
            if (!array_key_exists($aid, $selectedSets) || empty($selectedSets[$aid])) { $isObsolete = true; break; }

            // valor ya no está seleccionado
            if (!isset($selectedSets[$aid][$valId])) { $isObsolete = true; break; }
        }

        if ($isObsolete) {
            $obsoleteIds[] = $vid;
            $obsoleteMap[$vid] = true;
        }
    }
}

foreach ($obsoleteIds as $vid) {
    if ((int)($ordersCount[$vid] ?? 0) > 0) $obsoleteWithOrders++;
}

foreach ($variants as &$vr) {
    $vid = (int)($vr['id'] ?? 0);
    $vr['is_obsolete'] = !empty($obsoleteMap[$vid]) ? 1 : 0;
}
unset($vr);


        // Plantillas (sets) por categoría + marca (para importar rápido y evitar contaminación)
        $setsSupported = false;
        $valueSets = [];
        try {
            $info = $this->listValueSetsForProduct((int)($productMeta['category_id'] ?? 0), (int)($productMeta['brand_id'] ?? 0));
            $setsSupported = (bool)($info['supported'] ?? false);
            $valueSets = $info['sets'] ?? [];
        } catch (Throwable $e) {
            $setsSupported = false;
            $valueSets = [];
        }


        // Ganancia (%) default: preferimos la del producto; si está vacía, tomamos la primera variante que tenga.
        $productMargin = $productMeta['margin_pct'] ?? null;
        if ($productMargin === null || $productMargin === '') {
            foreach (($variants ?: []) as $vr) {
                if (array_key_exists('margin_pct', $vr) && $vr['margin_pct'] !== null && $vr['margin_pct'] !== '') {
                    $productMargin = $vr['margin_pct'];
                    break;
                }
            }
        }


        $catalogVariantAttrs = [];
        foreach ($attrs as $a) {
            $isVariantAxis = (int)($a['is_variant_axis'] ?? 0) === 1;
            if ($isVariantAxis) {
                $catalogVariantAttrs[] = $a;
            }
        }

        $variantPattrs = [];
        foreach ($pattrs as $pa) {
            $isVariantAxis = (int)($pa['is_variant_axis'] ?? 0) === 1;
            if ($isVariantAxis) {
                $variantPattrs[] = $pa;
            }
        }

        $tagsRepo = new CatalogTagsRepository($this->db);
        $variantTagCatalog = [];
        $variantTagsByVariant = [];
        try {
            $variantTagCatalog = $tagsRepo->listActiveGroupsWithTags();
            $variantTagsByVariant = $tagsRepo->findVariantTagIdsByProduct($productId);
        } catch (Throwable $e) {
            $variantTagCatalog = [];
            $variantTagsByVariant = [];
        }

        return [
            'product_iva_rate' => (float)($productMeta['iva_rate'] ?? 0),
            'product_margin_pct' => $productMargin,
            'attributes' => $attrs,
            'attributes_catalog_variant' => $catalogVariantAttrs,
            'product_attributes' => $pattrs,
            'variant_attributes' => $variantPattrs,
            'values_by_attribute' => $valuesByAttr,
            'selected_by_attribute' => $selectedByAttr,
            'selection_derived' => $selectionDerived,
            'sets_supported' => $setsSupported,
            'value_sets' => $valueSets,
            'obsolete_ids' => $obsoleteIds,
            'obsolete_with_orders' => $obsoleteWithOrders,
            'variants' => $variants,
            'values_by_variant' => $valuesByVariant,
            'variant_tag_catalog' => $variantTagCatalog,
            'variant_tags_by_variant' => $variantTagsByVariant,
        ];
    }

    private function isTmpSku(string $sku): bool
    {
        return str_starts_with($sku, '__TMP__');
    }

    private function listValueSetsForProduct(int $categoryId, int $brandId): array
    {
        if ($categoryId <= 0) return ['supported'=>false,'sets'=>[]];

        try {
            $sql = "SELECT s.id, s.name, s.category_id, s.brand_id,
                           (SELECT COUNT(DISTINCT attribute_id) FROM variant_value_set_items i WHERE i.set_id = s.id) AS attributes_count,
                           (SELECT COUNT(*) FROM variant_value_set_items i WHERE i.set_id = s.id) AS items_count
                    FROM variant_value_sets s
                    WHERE s.category_id = :cat
                      AND (s.brand_id IS NULL OR s.brand_id = :brand)
                    ORDER BY (s.brand_id IS NULL) ASC, s.name ASC, s.id ASC";
            $st = $this->db->prepare($sql);
            $st->execute([':cat'=>$categoryId, ':brand'=>$brandId]);
            $sets = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return ['supported'=>true,'sets'=>$sets];
        } catch (Throwable $e) {
            return ['supported'=>false,'sets'=>[]];
        }
    }
}
