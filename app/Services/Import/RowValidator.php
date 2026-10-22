<?php
namespace App\Services\Import;

final class RowValidator
{
    /** Devuelve mensaje de error o null si todo OK */
    public function validate(array $d, string $type): ?string
    {
        if (!empty($d['_attr_error'])) return (string)$d['_attr_error'];
        if (!empty($d['_csv_parse_error'])) {
            return (string)$d['_csv_parse_error'];
        }

        $tipo = strtolower(trim((string)($d['tipo_fila'] ?? 'producto')));
        if (!in_array($tipo, ['producto','variante'], true)) {
            return "tipo_fila inválido (usa 'producto' o 'variante')";
        }

        if (!empty($d['_forbidden'])) {
            return 'CSV inválido: este sistema usa costo final (columna costo). Quitá costo_neto/costo_final.';
        }

        $sku = trim((string)($d['sku'] ?? ''));
        $internal = trim((string)($d['internal_code'] ?? ''));
        if ($sku === '' && $internal === '') return 'Falta sku (o internal_code)';

        if ($tipo === 'variante') {
            $parent = trim((string)($d['sku_padre'] ?? ''));
            if ($parent === '') return "Falta sku_padre para la fila de variante";
        }

        // Si viene stock, debe venir modo_stock. Internamente soportamos:
        // - suma: sumar al stock actual
        // - fija: reemplazar (alias de "set" para compatibilidad)
        if (array_key_exists('stock', $d) && $d['stock'] !== null && $d['stock'] !== '') {
            $mode = strtolower(trim((string)($d['modo_stock'] ?? '')));
            if (!in_array($mode, ['suma', 'fija', 'set'], true)) return "modo_stock inválido (suma|fija)";
        }


        // Envíos: ship_* (int >= 0)
        foreach (['ship_weight','ship_height','ship_width','ship_length'] as $k) {
            if (!array_key_exists($k, $d)) continue;
            $v = $d[$k];
            if ($v === null || $v === '') continue;
            if (!is_numeric($v)) return "{$k} inválido (debe ser numérico)";
            $iv = (int)$v;
            if ($iv < 0) return "{$k} inválido (no puede ser negativo)";
        }

        $m = $d['margin_pct'] ?? null;
        if ($m !== null && $m !== '') {
            $mf = (float)$m;
            if ($mf < 0 || $mf > 1.0) return 'margen_pct fuera de rango (usa 0.35 / 0,35 / 35 / 35%)';
        }

        $iva = $d['iva_rate'] ?? null;
        if ($iva !== null && $iva !== '') {
            $iv = (float)$iva;
            // iva_rate: aceptamos 0.21, 0,21, 21, 21% (se normaliza a rate decimal).
            if ($iv < 0 || $iv > 1.5) return 'iva_rate fuera de rango (usa 0.21 / 0,21 / 21)';
        }

        return null;
    }
}
