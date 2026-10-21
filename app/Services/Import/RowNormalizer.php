<?php
namespace App\Services\Import;

use App\Support\Format\Slug;
use App\Support\Format\Number;

final class RowNormalizer
{
    /** @return array normalizado a claves internas (name, category_slug, price, etc.) */
    public function fromSpanish(array $row, string $type, array $opt = []): array
    {
        // Index auxiliar: lower + sin tildes
        $d = [];
        foreach ($row as $k=>$v) {
            $key = strtolower(trim((string)$k));
            $key2 = strtr($key, ['á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ñ'=>'n']);
            $d[$key] = $v;
            if (!array_key_exists($key2, $d)) $d[$key2] = $v;
        }

        $out = $row;

        // -----------------------------
        // Formato "plantilla v3" (unificado)
        // -----------------------------
        if (isset($d['tipo_fila'])) $out['tipo_fila'] = strtolower(trim((string)$d['tipo_fila']));
        if (isset($d['sku'])) $out['sku'] = trim((string)$d['sku']);
        if (isset($d['sku_padre'])) $out['sku_padre'] = trim((string)$d['sku_padre']);
        if (isset($d['internal_code'])) $out['internal_code'] = trim((string)$d['internal_code']);

        if (isset($d['slug'])) $out['slug'] = trim((string)$d['slug']);
        if (isset($d['nombre'])) $out['name'] = $d['nombre'];
        if (isset($d['name'])) $out['name'] = $d['name'];

        // Categoría/Marca: aceptar slug o nombre (en import resolvemos)
        if (isset($d['categoria'])) $out['category'] = trim((string)$d['categoria']);
        if (isset($d['proveedor'])) $out['supplier_name'] = trim((string)$d['proveedor']);
        if (isset($d['proveedor_cuit'])) $out['supplier_cuit'] = preg_replace('/\D+/', '', (string)$d['proveedor_cuit']);
        if (isset($d['categoría'])) $out['category'] = trim((string)$d['categoría']);
        if (isset($d['marca'])) $out['brand'] = trim((string)$d['marca']);
        if (isset($d['proveedor'])) $out['supplier_name'] = trim((string)$d['proveedor']);

        // Descripciones
        if (isset($d['descripcion_corta'])) $out['short_desc'] = $d['descripcion_corta'];
        if (isset($d['descripcion_larga'])) $out['long_desc']  = $d['descripcion_larga'];

        // IVA (si viene vacío, no lo seteamos)
        if (isset($d['iva_rate'])) {
            $raw = trim((string)$d['iva_rate']);
            if ($raw !== '') $out['iva_rate'] = Number::toRate($raw);
        }

        // Costos y márgenes (si viene vacío, no lo seteamos)
        // Opción A: no se permite costo_neto ni costo_final en el CSV oficial
        if (isset($d['costo_neto']) || isset($d['costo_final'])) {
            $out['_forbidden'] = true;
        }
        // costo (plantilla oficial): se guarda directo en DB (entero, pesos)
        if (isset($d['costo'])) {
            $raw = trim((string)$d['costo']);
            if ($raw !== '') $out['cost'] = Number::toInt($raw);
        }

        // Moneda de costo (opcional): ARS, USD_GOZIO, USD_MARINE
        if (isset($d['moneda'])) {
            $raw = trim((string)$d['moneda']);
            if ($raw !== '') $out['cost_currency_code'] = strtoupper($raw);
        }
        if (isset($d['margen_pct'])) {
            // margen_pct es un porcentaje 0..100 (ej: 35). NO debe normalizarse como rate (0.35).
            // Soportamos coma/punto y luego los validators aseguran el rango.
            $raw = trim((string)$d['margen_pct']);
            if ($raw !== '') $out['margin_pct'] = Number::toPctRate($raw);
        }

        // Precios finales (si el usuario quiere pegar valores directos)
        if (isset($d['precio'])) {
            $raw = trim((string)$d['precio']);
            if ($raw !== '') $out['price'] = Number::toInt($raw);
        }
        if (isset($d['precio_lista'])) {
            $raw = trim((string)$d['precio_lista']);
            if ($raw !== '') $out['list_price'] = Number::toInt($raw);
        }


// Overrides de precio mayorista (opcional)
// Regla: si viene vacío => NO seteamos la clave (para que no pise).
$whMap = [
    'precio_mayorista1' => 'price_wh1_override',
    'precio_wh1' => 'price_wh1_override',
    'wh1_override' => 'price_wh1_override',
    'price_wh1_override' => 'price_wh1_override',
    'precio_mayorista2' => 'price_wh2_override',
    'precio_wh2' => 'price_wh2_override',
    'wh2_override' => 'price_wh2_override',
    'price_wh2_override' => 'price_wh2_override',
];
foreach ($whMap as $inKey => $outKey) {
    if (isset($d[$inKey])) {
        $raw = trim((string)$d[$inKey]);
        if ($raw !== '') {
            $out[$outKey] = Number::toInt($raw);
        }
    }
}
        // Stock/umbral
        // IMPORTANTE: si la celda viene vacía, NO debemos convertirla a 0.
        // Caso típico: fila de producto padre (variantes) sin stock => si hacemos (int)'' queda 0
        // y luego la validación exige modo_stock. Por eso solo seteamos si hay valor real.
        if (isset($d['stock'])) {
            $raw = trim((string)$d['stock']);
            if ($raw !== '') $out['stock'] = (int) Number::toInt($raw);
        }
        if (isset($d['umbral'])) {
            $raw = trim((string)$d['umbral']);
            if ($raw !== '') $out['low_stock_threshold'] = (int) Number::toInt($raw);
        }
        if (isset($d['modo_stock'])) $out['modo_stock'] = strtolower(trim((string)$d['modo_stock']));

        // Publicación
        if (isset($d['publicado'])) {
            $val = strtolower(trim((string)$d['publicado']));
            $out['is_active'] = in_array($val, ['1','true','si','sí','yes'], true) ? 1 : 0;
        }

        
        // Envíos: peso y dimensiones (INT en DB: gramos y centímetros)
        // Backwards compatible: solo seteamos si la celda trae valor real.
        // Soportamos columnas oficiales:
        // - ship_weight_g (gramos)
        // - ship_height_cm, ship_width_cm, ship_length_cm (cm)
        // Alias de compatibilidad:
        // - ship_weight (interpreta gramos)
        // - ship_weight_kg (kg -> gramos)
                if (!empty($opt['update_ship_dims'])) {
$weightKeys = ['peso_envio_g','ship_weight_g','ship_weight','peso_envio_kg','ship_weight_kg'];
        foreach ($weightKeys as $wk) {
            if (isset($d[$wk])) {
                $raw = trim((string)$d[$wk]);
                if ($raw !== '') {
                    // Si viene en kg, convertimos a gramos (redondeo).
                    if ($wk === 'ship_weight_kg' || $wk === 'peso_envio_kg') {
                        $out['ship_weight'] = (int) round(((float) str_replace(',', '.', $raw)) * 1000);
                    } else {
                        $out['ship_weight'] = (int) Number::toInt($raw);
                    }
                }
                break; // la primera columna encontrada gana
            }
        }

        $dimMap = [
            'alto_envio_cm'  => 'ship_height',
            'ship_height_cm' => 'ship_height',
            'ancho_envio_cm' => 'ship_width',
            'ship_width_cm'  => 'ship_width',
            'largo_envio_cm' => 'ship_length',
            'ship_length_cm' => 'ship_length',
            // Alias por si el CSV trae directamente ship_height, etc.
            'ship_height' => 'ship_height',
            'ship_width'  => 'ship_width',
            'ship_length' => 'ship_length',
        ];
        foreach ($dimMap as $inKey => $outKey) {
            if (!isset($d[$inKey])) continue;
            $raw = trim((string)$d[$inKey]);
            if ($raw === '') continue;
            $out[$outKey] = (int) Number::toInt($raw);
        }
        }

// Atributos por columnas (atributo_1/valor_1 ...)
        $pairs = [];
        for ($i=1; $i<=3; $i++) {
            $ak = 'atributo_'.$i;
            $vk = 'valor_'.$i;
            if (!isset($d[$ak]) && !isset($d[$vk])) continue;
            $an = trim((string)($d[$ak] ?? ''));
            $av = trim((string)($d[$vk] ?? ''));
            if ($an !== '' && $av !== '') $pairs[] = ['name'=>$an,'value'=>$av];
        }
        if ($pairs) $out['attr_pairs'] = $pairs;
        // -----------------------------
        // Compatibilidad con formato viejo (MVP simple)
        // -----------------------------
        if (isset($d['nombre'])) $out['name'] = $d['nombre'];
        if (isset($d['categoría']) || isset($d['categoria'])) {
            $cat = $d['categoría'] ?? $d['categoria'];
            $out['category_slug'] = Slug::slugify((string)$cat);
        }
        if (isset($d['marca'])) $out['brand_slug'] = Slug::slugify((string)$d['marca']);
        if (isset($d['precio'])) {
            $raw = trim((string)$d['precio']);
            if ($raw !== '') $out['price'] = Number::toInt($raw);
        }
        if (isset($d['precio_lista'])) {
            $raw = trim((string)$d['precio_lista']);
            if ($raw !== '') $out['list_price'] = Number::toInt($raw);
        }
        if (isset($d['activo'])) {
            $val = strtolower(trim((string)$d['activo']));
            $out['is_active'] = in_array($val, ['1','true','si','sí','yes'], true) ? 1 : 0;
        }
        if (isset($d['umbral_stock'])) {
            $raw = trim((string)$d['umbral_stock']);
            if ($raw !== '') $out['low_stock_threshold'] = (int) Number::toInt($raw);
        }
        if (isset($d['stock'])) {
            $raw = trim((string)$d['stock']);
            if ($raw !== '') $out['stock'] = (int) Number::toInt($raw);
        }
        if (isset($d['modo_stock'])) $out['modo_stock'] = strtolower(trim((string)$d['modo_stock']));
        if (isset($d['descripción_corta'])) $out['short_desc'] = $d['descripción_corta'];
        if (isset($d['descripcion_corta'])) $out['short_desc'] = $d['descripcion_corta'];
        if (isset($d['descripción_larga'])) $out['long_desc']  = $d['descripción_larga'];
        if (isset($d['descripcion_larga'])) $out['long_desc']  = $d['descripcion_larga'];

        return $out;
    }
}

