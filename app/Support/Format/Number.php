<?php
namespace App\Support\Format;

final class Number
{
    private static function contains(string $haystack, string $needle): bool
    {
        return $needle !== '' && strpos($haystack, $needle) !== false;
    }

    /** Convierte una cadena a entero (limpia $/â‚¬ y separadores) */
    public static function toInt($val): int
    {
        $s = trim((string)$val);
        $s = str_replace(['$', 'â‚¬', '£'], '', $s);
        $s = str_replace(['.', ' '], ['', ''], $s);
        $s = str_replace(',', '.', $s);
        if ($s === '' || $s === '.') return 0;
        return (int) round((float)$s);
    }

    /** Convierte a float (acepta coma/punto, limpia símbolos) */
    public static function toFloat($val): float
    {
        $s = trim((string)$val);
        $s = str_replace(['$', 'â‚¬', '£'], '', $s);
        $s = str_replace([' '], [''], $s);
        // Si viene con miles en . y decimales en , lo normalizamos
        if (substr_count($s, ',') === 1 && substr_count($s, '.') >= 1) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
        } else {
            $s = str_replace(',', '.', $s);
        }
        if ($s === '' || $s === '.') return 0.0;
        return (float)$s;
    }

    /** Convierte una alícuota a rate decimal (acepta 0.21, 0,21, 21, 10.5, 10,5, 21%) */
    public static function toRate($val): float
    {
        $s = trim((string)$val);
        if ($s === '') return 0.0;

        // limpiar símbolos comunes
        $s = str_replace(['$', 'â‚¬', '£', ' '], ['', '', '', ''], $s);

        $hasPercent = self::contains($s, '%');
        $s = str_replace('%', '', $s);

        // normalizar coma a punto
        $s = str_replace(',', '.', $s);

        if ($s === '' || $s === '.') return 0.0;

        $v = (float)$s;
        // Si viene en porcentaje (21 o 10.5), pasarlo a rate
        if ($hasPercent || $v > 1.5) {
            // 21 => 0.21, 10.5 => 0.105
            $v = $v / 100.0;
        }
        // clamp leve
        if ($v < 0) $v = 0.0;
        return $v;
    
    }


    /**
     * Convierte un porcentaje a rate (0..1).
     * Acepta: 35, 35%, 0.35, 0,35
     */
    public static function toPctRate($val): float
    {
        $s = trim((string)$val);
        if ($s === '') return 0.0;

        $s = str_replace(['$', 'â‚¬', '£', ' '], ['', '', '', ''], $s);
        $hasPercent = self::contains($s, '%');
        $s = str_replace('%', '', $s);
        $s = str_replace(',', '.', $s);
        if ($s === '' || $s === '.') return 0.0;

        $v = (float)$s;
        if ($hasPercent || $v > 1.0) $v = $v / 100.0;
        if ($v < 0) $v = 0.0;
        return $v;
    }

}


