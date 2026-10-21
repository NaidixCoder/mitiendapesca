<?php
namespace App\Support\Format;

/**
 * Utilidades fiscales simples.
 *
 * Convención actual del sitio:
 * - price/list_price guardados son precios finales (IVA incluido) en pesos enteros.
 * - cost guardado es costo sin IVA.
 *
 * Este helper permite generar snapshots contables (neto/IVA/total) para pedidos,
 * incluso si el vendedor es Monotributo (Factura C), donde el IVA no se discrimina.
 */
final class Tax
{
    /**
     * Divide un total (IVA incluido) en neto + IVA según alícuota.
     *
     * @return array{net:int, vat:int}
     */
    public static function splitTotal(int $total, float $vatRate): array
    {
        $total = max(0, (int)$total);
        $vatRate = max(0.0, (float)$vatRate);

        // Clamp de seguridad
        if ($vatRate > 1.0) {
            $vatRate = $vatRate / 100.0;
        }
        if ($vatRate < 0.0) $vatRate = 0.0;
        if ($vatRate > 1.0) $vatRate = 1.0;

        // 0% IVA
        if ($vatRate <= 0.0000001) {
            return ['net' => $total, 'vat' => 0];
        }

        $den = 1.0 + $vatRate;
        // redondeo al peso entero más cercano (misma lógica usada en el admin)
        $net = (int)round($total / $den);
        $net = max(0, min($total, $net));
        $vat = (int)($total - $net);
        if ($vat < 0) $vat = 0;
        return ['net' => $net, 'vat' => $vat];
    }
}

