<?php
namespace App\Services\Shipping\CorreoArgentino;

final class CorreoTrackingEventNormalizer
{
    /**
     * Flatten de payload de /tracking a eventos normalizados.
     *
     * Esperamos un array de entradas, donde cada entrada puede tener:
     * - events: array de { event, date, location?, ... }
     *
     * @param array<int,mixed> $trackingPayload
     * @return array<int,array<string,mixed>>
     */
    public static function normalize(array $trackingPayload): array
    {
        $out = [];

        // La API puede devolver:
        // A) lista de entradas (array indexado)
        // B) objeto/array asociativo con events directo
        // C) wrappers (data/result/response) con events adentro
        $eventLists = self::extractEventLists($trackingPayload);

        foreach ($eventLists as $events) {
            if (!is_array($events)) continue;

            foreach ($events as $ev) {
                if (!is_array($ev)) continue;

                // keys típicas: event/date, code/datetime, status/fecha
                $code = strtoupper(trim((string)($ev['event'] ?? $ev['code'] ?? $ev['status'] ?? '')));
                $dateRaw = trim((string)($ev['date'] ?? $ev['datetime'] ?? $ev['fecha'] ?? $ev['fechahora'] ?? ''));

                if ($code === '' && $dateRaw === '') continue;

                $label = self::labelFromCode($code);

                // Convertimos a DATETIME si se puede
                $eventAt = self::parseDateToMysql($dateRaw);

                $location = '';
                foreach (['location','place','office','branch','sucursal','oficina'] as $k) {
                    $v = trim((string)($ev[$k] ?? ''));
                    if ($v !== '') { $location = $v; break; }
                }

                $out[] = [
                    'event_code' => $code,
                    'event_label' => $label,
                    'event_date_raw' => $dateRaw,
                    'event_at' => $eventAt,
                    'location' => $location,
                    'raw_json' => $ev,
                ];
            }
        }

        // Orden por fecha (si hay) y como fallback por date_raw
        usort($out, function ($a, $b) {
            $da = (string)($a['event_at'] ?? '');
            $db = (string)($b['event_at'] ?? '');
            if ($da !== '' && $db !== '') return strcmp($da, $db);
            return strcmp((string)($a['event_date_raw'] ?? ''), (string)($b['event_date_raw'] ?? ''));
        });

        return $out;
    }

    /**
     * Busca listas de eventos en cualquier profundidad razonable.
     * @return array<int,array<int,mixed>>
     */
    private static function extractEventLists(array $payload): array
    {
        $lists = [];

        // Caso: payload ya es una lista de eventos
        if (self::looksLikeEventList($payload)) {
            $lists[] = $payload;
            return $lists;
        }

        // Caso: payload tiene events directo
        if (isset($payload['events']) && is_array($payload['events'])) {
            $lists[] = $payload['events'];
        }

        // Caso: lista de entradas
        foreach ($payload as $k => $v) {
            if (is_int($k) && is_array($v)) {
                if (isset($v['events']) && is_array($v['events'])) {
                    $lists[] = $v['events'];
                }
                // trackingEvents / movimientos / history
                foreach (['trackingEvents','movements','movement','history','historial'] as $kk) {
                    if (isset($v[$kk]) && is_array($v[$kk])) {
                        $lists[] = $v[$kk];
                    }
                }
            }
        }

        // Búsqueda recursiva (profundidad limitada)
        self::walk($payload, $lists, 0);

        return $lists;
    }

    /** @param array<int,array<int,mixed>> $lists */
    private static function walk(mixed $node, array &$lists, int $depth): void
    {
        if ($depth >= 4) return;
        if (!is_array($node)) return;

        if (isset($node['events']) && is_array($node['events'])) {
            $lists[] = $node['events'];
        }
        foreach (['trackingEvents','movements','history','historial'] as $kk) {
            if (isset($node[$kk]) && is_array($node[$kk])) {
                $lists[] = $node[$kk];
            }
        }

        foreach ($node as $v) {
            if (is_array($v)) self::walk($v, $lists, $depth + 1);
        }
    }

    private static function looksLikeEventList(array $payload): bool
    {
        // heurística: lista indexada con al menos un elemento array que tenga event/date
        $i = 0;
        foreach ($payload as $k => $v) {
            if (!is_int($k)) return false;
            if (!is_array($v)) continue;
            $has = isset($v['event']) || isset($v['code']) || isset($v['status']);
            $has2 = isset($v['date']) || isset($v['datetime']) || isset($v['fecha']) || isset($v['fechahora']);
            if ($has && $has2) return true;
            $i++;
            if ($i > 10) break;
        }
        return false;
    }

    private static function labelFromCode(string $code): string
    {
        if ($code === '') return '';
        if (str_contains($code, 'ENTREG')) return 'Entregado';
        if (str_contains($code, 'SUCURS') || str_contains($code, 'EN_SUCURSAL')) return 'En sucursal';
        if (str_contains($code, 'TRANSITO') || str_contains($code, 'TRASLADO') || str_contains($code, 'EN_CAMINO')) return 'En tránsito';
        if (str_contains($code, 'ADMIS') || str_contains($code, 'IMPOS') || str_contains($code, 'CLASIF') || str_contains($code, 'DESPACH')) return 'Procesado por Correo';
        if (str_contains($code, 'DEVUEL') || str_contains($code, 'CADUC') || str_contains($code, 'RECHAZ')) return 'Fallido / Devuelto';
        return $code;
    }

    private static function parseDateToMysql(string $dateRaw): ?string
    {
        $dateRaw = trim($dateRaw);
        if ($dateRaw === '') return null;

        // Formato usual: DD-MM-YYYY HH:MM (o HH:MM:SS)
        if (preg_match('/^(\d{2})-(\d{2})-(\d{4})\s+(\d{2}):(\d{2})(?::(\d{2}))?$/', $dateRaw, $m)) {
            $dd = $m[1]; $mm = $m[2]; $yyyy = $m[3];
            $hh = $m[4]; $ii = $m[5]; $ss = $m[6] ?? '00';
            return sprintf('%s-%s-%s %s:%s:%s', $yyyy, $mm, $dd, $hh, $ii, $ss);
        }

        // Fallback strtotime
        $ts = strtotime($dateRaw);
        if ($ts === false) return null;
        return date('Y-m-d H:i:s', $ts);
    }
}
