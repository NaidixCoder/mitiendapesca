<?php
namespace App\Services\Shipping\CorreoArgentino;

final class CorreoStatusMapper
{
    /**
     * Mapea eventos de Correo Argentino a nuestros estados internos de envío.
     * @param array<int,array<string,mixed>> $trackingPayload
     */
    public static function mapStatus(array $trackingPayload): string
    {
        // buscamos el último evento con fecha (si existe)
        $lastEvent = '';
        $events = [];
        foreach ($trackingPayload as $entry) {
            if (!is_array($entry)) continue;
            $es = $entry['events'] ?? [];
            if (is_array($es)) {
                foreach ($es as $ev) {
                    if (!is_array($ev)) continue;
                    $code = strtoupper(trim((string)($ev['event'] ?? '')));
                    $date = trim((string)($ev['date'] ?? ''));
                    if ($code === '') continue;
                    $events[] = ['code' => $code, 'date' => $date];
                }
            }
        }

        if (!empty($events)) {
            // Ordenar por date si tiene formato DD-MM-YYYY HH:MM
            usort($events, function ($a, $b) {
                return strcmp((string)($a['date'] ?? ''), (string)($b['date'] ?? ''));
            });
            $lastEvent = (string)($events[count($events)-1]['code'] ?? '');
        }

        if ($lastEvent === '') return 'preparando';

        // Normalización por prefijos / keywords
        if (str_contains($lastEvent, 'ENTREG')) return 'entregado';
        if (str_contains($lastEvent, 'SUCURS') || str_contains($lastEvent, 'EN_SUCURSAL')) return 'en_sucursal';
        if (str_contains($lastEvent, 'TRANSITO') || str_contains($lastEvent, 'TRASLADO') || str_contains($lastEvent, 'EN_CAMINO')) return 'en_transito';
        if (str_contains($lastEvent, 'ADMIS') || str_contains($lastEvent, 'IMPOS') || str_contains($lastEvent, 'DESPACH') || str_contains($lastEvent, 'CLASIF')) return 'despachado';
        if (str_contains($lastEvent, 'PREIMPOS') || str_contains($lastEvent, 'PRE-IMPOS')) return 'preparando';
        if (str_contains($lastEvent, 'DEVUEL') || str_contains($lastEvent, 'CADUC') || str_contains($lastEvent, 'RECHAZ')) return 'fallido';

        return 'en_transito';
    }
}
