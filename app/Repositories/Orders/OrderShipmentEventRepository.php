<?php
namespace App\Repositories\Orders;

use PDO;

final class OrderShipmentEventRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Inserta eventos normalizados (dedup por UNIQUE order_id+event_code+event_date_raw).
     * @param array<int,array<string,mixed>> $events
     * @return int cantidad insertada (best-effort)
     */
    public function insertMany(int $orderId, ?string $externalShipmentId, ?string $trackingNumber, array $events): int
    {
        if ($orderId <= 0) return 0;
        if (empty($events)) return 0;

        $sql = "INSERT IGNORE INTO order_shipment_events
                (order_id, external_shipment_id, tracking_number, event_code, event_label, event_date_raw, event_at, location, raw_json)
                VALUES
                (:order_id, :external_shipment_id, :tracking_number, :event_code, :event_label, :event_date_raw, :event_at, :location, :raw_json)";

        $st = $this->db->prepare($sql);
        $count = 0;

        foreach ($events as $ev) {
            if (!is_array($ev)) continue;

            $code = strtoupper(trim((string)($ev['event_code'] ?? '')));
            if ($code === '') continue;

            $label = trim((string)($ev['event_label'] ?? ''));
            $dateRaw = trim((string)($ev['event_date_raw'] ?? ''));
            $eventAt = $ev['event_at'] ?? null; // string|nil
            $eventAt = is_string($eventAt) ? trim($eventAt) : null;
            if ($eventAt === '') $eventAt = null;

            $location = trim((string)($ev['location'] ?? ''));
            $raw = $ev['raw_json'] ?? null;
            $rawJson = $raw !== null ? json_encode($raw, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES) : null;

            try {
                $ok = $st->execute([
                    ':order_id' => $orderId,
                    ':external_shipment_id' => $externalShipmentId,
                    ':tracking_number' => $trackingNumber,
                    ':event_code' => $code,
                    ':event_label' => ($label !== '' ? $label : null),
                    ':event_date_raw' => ($dateRaw !== '' ? $dateRaw : null),
                    ':event_at' => $eventAt,
                    ':location' => ($location !== '' ? $location : null),
                    ':raw_json' => $rawJson,
                ]);
                if ($ok && $st->rowCount() > 0) $count += (int)$st->rowCount();
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        return $count;
    }
}

