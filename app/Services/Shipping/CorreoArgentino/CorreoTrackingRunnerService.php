<?php
namespace App\Services\Shipping\CorreoArgentino;

use App\Repositories\Orders\OrderShipmentRepository;
use App\Repositories\Orders\OrderShipmentEventRepository;
use App\Services\Audit\Audit;
use PDO;

final class CorreoTrackingRunnerService
{
    public function __construct(private PDO $db) {}

    /**
     * Ejecuta tracking masivo (best-effort).
     * @return array<string,mixed> resumen
     */
    public function run(int $limit = 50, int $minMinutesBetweenRuns = 10): array
    {
        $limit = max(1, min(500, (int)$limit));
        $minMinutesBetweenRuns = max(0, (int)$minMinutesBetweenRuns);

        $items = $this->listShipmentsForTracking($limit, $minMinutesBetweenRuns);

        $client = new CorreoClient($this->db);
        $shipRepo = new OrderShipmentRepository($this->db);
        $evRepo = new OrderShipmentEventRepository($this->db);

        $ok = 0;
        $fail = 0;
        $eventsInserted = 0;
        $updatedStatuses = [
            'preparando' => 0,
            'despachado' => 0,
            'en_transito' => 0,
            'en_sucursal' => 0,
            'entregado' => 0,
            'fallido' => 0,
            'otro' => 0,
        ];

        $errors = [];

        foreach ($items as $it) {
            $orderId = (int)($it['order_id'] ?? 0);
            $shippingId = trim((string)($it['external_shipment_id'] ?? ''));
            if ($orderId <= 0 || $shippingId === '') continue;

            try {
                $payload = $client->track($shippingId);

                $trackingStatus = CorreoStatusMapper::mapStatus($payload);

                $trackingNumber = null;
                foreach ($payload as $entry) {
                    if (!is_array($entry)) continue;
                    $tn = trim((string)($entry['trackingNumber'] ?? ''));
                    if ($tn !== '') { $trackingNumber = $tn; break; }
                }

                $shipRepo->updateTracking(
                    $orderId,
                    $shippingId,
                    $trackingNumber,
                    $trackingStatus,
                    $payload,
                    null
                );

                // Persistimos eventos normalizados (si existe la tabla; best-effort)
                try {
                    $events = CorreoTrackingEventNormalizer::normalize($payload);
                    // En QA (y a veces en prod) puede no venir timeline. Igual creamos un evento sintético
                    // por cambio/estado para que el cliente pueda ver “último evento / última actualización”.
                    if (empty($events)) {
                        $events = [[
                            'event_code' => 'STATUS',
                            'event_label' => self::labelFromTrackingStatus($trackingStatus),
                            // dedup: UNIQUE(order_id,event_code,event_date_raw)
                            'event_date_raw' => $trackingStatus,
                            'event_at' => date('Y-m-d H:i:s'),
                            'location' => '',
                            'raw_json' => [
                                'synthetic' => true,
                                'tracking_status' => $trackingStatus,
                            ],
                        ]];
                    }
                    $eventsInserted += $evRepo->insertMany($orderId, $shippingId, $trackingNumber, $events);
                } catch (\Throwable $e) {
                    // best-effort
                }

                if (isset($updatedStatuses[$trackingStatus])) $updatedStatuses[$trackingStatus] += 1;
                else $updatedStatuses['otro'] += 1;

                $ok += 1;

                Audit::log('shipping_correo_tracking_cron_ok', 'order', $orderId, [
                    'tracking_status' => $trackingStatus,
                ]);
            } catch (\Throwable $e) {
                $fail += 1;
                $errors[] = [
                    'order_id' => $orderId,
                    'error' => $e->getMessage(),
                ];

                // Best-effort: guardar error en order_shipments si existe
                try {
                    $shipRepo->updateTracking($orderId, null, null, null, [], $e->getMessage());
                } catch (\Throwable $e2) {}

                Audit::log('shipping_correo_tracking_cron_error', 'order', $orderId, [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $summary = [
            'ok' => true,
            'fetched' => count($items),
            'tracked_ok' => $ok,
            'tracked_fail' => $fail,
            'events_inserted' => $eventsInserted,
            'status_counts' => $updatedStatuses,
            'errors' => $errors,
        ];

        Audit::log('shipping_correo_tracking_cron_run', 'system', null, [
            'fetched' => count($items),
            'tracked_ok' => $ok,
            'tracked_fail' => $fail,
            'events_inserted' => $eventsInserted,
        ]);

        return $summary;
    }

    private static function labelFromTrackingStatus(string $trackingStatus): string
    {
        $s = strtolower(trim($trackingStatus));
        return match ($s) {
            'preparando' => 'Preparando el envío',
            'despachado' => 'Despachado',
            'en_transito' => 'En tránsito',
            'en_sucursal' => 'En sucursal',
            'entregado' => 'Entregado',
            'fallido' => 'Entrega fallida',
            default => 'Estado actualizado',
        };
    }

    /**
     * @return array<int,array<string,mixed>>
     */
    private function listShipmentsForTracking(int $limit, int $minMinutesBetweenRuns): array
    {
        // Si existe tracking_status filtramos por no-finalizados
        $sql = "SELECT order_id, external_shipment_id, tracking_status, last_tracked_at
                FROM order_shipments
                WHERE carrier = 'correo_argentino'
                  AND external_shipment_id IS NOT NULL
                  AND external_shipment_id <> ''
                  AND (last_tracked_at IS NULL OR last_tracked_at < (NOW() - INTERVAL :minm MINUTE))
                  AND (tracking_status IS NULL OR tracking_status NOT IN ('entregado','fallido'))
                ORDER BY COALESCE(last_tracked_at, '1970-01-01 00:00:00') ASC
                LIMIT :lim";

        try {
            $st = $this->db->prepare($sql);
            $st->bindValue(':minm', $minMinutesBetweenRuns, PDO::PARAM_INT);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return is_array($rows) ? $rows : [];
        } catch (\Throwable $e) {
            // Compatibilidad: si no existe tracking_status, hacemos query sin ese filtro
            $sql2 = "SELECT order_id, external_shipment_id, last_tracked_at
                    FROM order_shipments
                    WHERE carrier = 'correo_argentino'
                      AND external_shipment_id IS NOT NULL
                      AND external_shipment_id <> ''
                      AND (last_tracked_at IS NULL OR last_tracked_at < (NOW() - INTERVAL :minm MINUTE))
                    ORDER BY COALESCE(last_tracked_at, '1970-01-01 00:00:00') ASC
                    LIMIT :lim";
            $st = $this->db->prepare($sql2);
            $st->bindValue(':minm', $minMinutesBetweenRuns, PDO::PARAM_INT);
            $st->bindValue(':lim', $limit, PDO::PARAM_INT);
            $st->execute();
            $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
            return is_array($rows) ? $rows : [];
        }
    }
}

