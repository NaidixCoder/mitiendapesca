<?php
namespace App\Services\Billing;

use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Validación de rangos y protección de texto libre para exportaciones fiscales.
 * No altera los valores de dominio almacenados en la base.
 */
final class FiscalExportGuard
{
    public static function requireDateRange(string $from, string $to): void
    {
        if (!self::isDate($from) || !self::isDate($to) || $from > $to) {
            throw new InvalidArgumentException('Rango de fechas inválido.');
        }
    }

    public static function safeFilenamePart(string $input): string
    {
        return preg_replace('/[^A-Za-z0-9_-]/', '', $input) ?? '';
    }

    public static function safeCsvText(string $input): string
    {
        // Un valor numérico negativo puro es dato, no fórmula.
        if (preg_match('/^-?[0-9]+(?:\\.[0-9]+)?$/D', $input) === 1) {
            return $input;
        }
        if (preg_match('/^[\x00-\x20]*[=+@-]/', $input) === 1) {
            return "'" . $input;
        }
        return $input;
    }

    /** Evita fórmulas al exportar texto libre, conservando celdas numéricas. */
    public static function writeCsv($stream, array $columns): void
    {
        $safe = array_map(static fn($value) => is_string($value) ? self::safeCsvText($value) : $value, $columns);
        fputcsv($stream, $safe);
    }

    private static function isDate(string $value): bool
    {
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/D', $value)) {
            return false;
        }
        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
        return $date !== false && $date->format('Y-m-d') === $value;
    }
}
