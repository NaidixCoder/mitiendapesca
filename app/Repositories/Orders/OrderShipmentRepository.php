<?php
namespace App\Repositories\Orders;

use PDO;

final class OrderShipmentRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Alias por compatibilidad.
     * En otras capas usamos convención findByOrderId().
     * @return array<string,mixed>|null
     */
    public function findByOrderId(int $orderId): ?array
    {
        return $this->getByOrderId($orderId);
    }

    /** @return array<string,mixed>|null */
    public function getByOrderId(int $orderId): ?array
    {
        if ($orderId <= 0) return null;
        $st = $this->db->prepare("SELECT * FROM order_shipments WHERE order_id = ? LIMIT 1");
        $st->execute([$orderId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ?: null;
    }

    /**
     * Upsert de envío para un pedido.
     * @param array<string,mixed> $data
     * @return array{0:bool,1:string,2:array<string,mixed>|null}
     */
    public function upsert(int $orderId, array $data): array
    {
        if ($orderId <= 0) return [false, 'params', null];

        $carrier = trim((string)($data['carrier'] ?? 'Correo Argentino'));
        if ($carrier === '') $carrier = 'Correo Argentino';

        $service = trim((string)($data['service'] ?? ''));
        $trackingNumber = trim((string)($data['tracking_number'] ?? ''));
        $trackingUrl = trim((string)($data['tracking_url'] ?? ''));

        // Estado operativo interno del envío.
        // Los estados de tracking (en tránsito, en sucursal, etc.) vienen de la API y se guardan aparte.
        $status = trim((string)($data['status'] ?? 'preparando'));
        $allowed = ['preparando','despachado'];
        if (!in_array($status, $allowed, true)) $status = 'preparando';

        $shippedAt = $data['shipped_at'] ?? null; // string|nil
        $shippedAt = is_string($shippedAt) ? trim($shippedAt) : null;
        if ($shippedAt === '') $shippedAt = null;
        if (is_string($shippedAt) && strpos($shippedAt, 'T') !== false) {
            // input type=datetime-local -> 'YYYY-MM-DDTHH:MM'
            $shippedAt = str_replace('T', ' ', $shippedAt);
            if (strlen($shippedAt) === 16) $shippedAt .= ':00';
        }

        // Campos nuevos (si existen en el schema)
        $externalShipmentId = trim((string)($data['external_shipment_id'] ?? ''));
        if ($externalShipmentId === '') $externalShipmentId = null;

        $sqlNew = "INSERT INTO order_shipments (order_id, carrier, service, tracking_number, tracking_url, external_shipment_id, status, shipped_at)
                VALUES (:order_id, :carrier, :service, :tracking_number, :tracking_url, :external_shipment_id, :status, :shipped_at)
                ON DUPLICATE KEY UPDATE
                  carrier = VALUES(carrier),
                  service = VALUES(service),
                  tracking_number = VALUES(tracking_number),
                  tracking_url = VALUES(tracking_url),
                  external_shipment_id = COALESCE(VALUES(external_shipment_id), external_shipment_id),
                  status = VALUES(status),
                  shipped_at = VALUES(shipped_at),
                  updated_at = CURRENT_TIMESTAMP";

        $sqlOld = "INSERT INTO order_shipments (order_id, carrier, service, tracking_number, tracking_url, status, shipped_at)
                VALUES (:order_id, :carrier, :service, :tracking_number, :tracking_url, :status, :shipped_at)
                ON DUPLICATE KEY UPDATE
                  carrier = VALUES(carrier),
                  service = VALUES(service),
                  tracking_number = VALUES(tracking_number),
                  tracking_url = VALUES(tracking_url),
                  status = VALUES(status),
                  shipped_at = VALUES(shipped_at),
                  updated_at = CURRENT_TIMESTAMP";

        try {
            $st = $this->db->prepare($sqlNew);
            $ok = $st->execute([
                ':order_id' => $orderId,
                ':carrier' => $carrier,
                ':service' => ($service !== '' ? $service : null),
                ':tracking_number' => ($trackingNumber !== '' ? $trackingNumber : null),
                ':tracking_url' => ($trackingUrl !== '' ? $trackingUrl : null),
                ':external_shipment_id' => $externalShipmentId,
                ':status' => $status,
                ':shipped_at' => $shippedAt,
            ]);
            if (!$ok) return [false, 'db', null];
            return [true, '', $this->getByOrderId($orderId)];
        } catch (\PDOException $e) {
            // Compatibilidad: si el schema todavía no tiene external_shipment_id, usamos SQL viejo.
            try {
                $st = $this->db->prepare($sqlOld);
                $ok = $st->execute([
                    ':order_id' => $orderId,
                    ':carrier' => $carrier,
                    ':service' => ($service !== '' ? $service : null),
                    ':tracking_number' => ($trackingNumber !== '' ? $trackingNumber : null),
                    ':tracking_url' => ($trackingUrl !== '' ? $trackingUrl : null),
                    ':status' => $status,
                    ':shipped_at' => $shippedAt,
                ]);
                if (!$ok) return [false, 'db', null];
                return [true, '', $this->getByOrderId($orderId)];
            } catch (\Throwable $e2) {
                return [false, 'db', null];
            }
        }
    }

    /**
     * Actualiza tracking (eventos) para un envío si el schema soporta campos.
     * @param array<int,array<string,mixed>> $events
     */
    public function updateTracking(int $orderId, ?string $externalShipmentId, ?string $trackingNumber, ?string $status, array $events, ?string $lastError = null): bool
    {
        if ($orderId <= 0) return false;
        $eventsJson = json_encode($events, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);

        // Nuevo esquema (recomendado): separamos estado operativo (status) vs estado de tracking (tracking_status)
        // para que el admin solo maneje Preparando/Despachado y el resto lo alimente la API.
        try {
            $sqlNew = "UPDATE order_shipments
                    SET external_shipment_id = COALESCE(:external_shipment_id, external_shipment_id),
                        tracking_number = COALESCE(:tracking_number, tracking_number),
                        tracking_status = COALESCE(:tracking_status, tracking_status),
                        tracking_events_json = :events_json,
                        last_tracked_at = CURRENT_TIMESTAMP,
                        last_error = :last_error,
                        updated_at = CURRENT_TIMESTAMP
                    WHERE order_id = :order_id";
            $st = $this->db->prepare($sqlNew);
            return (bool)$st->execute([
                ':external_shipment_id' => $externalShipmentId,
                ':tracking_number' => $trackingNumber,
                ':tracking_status' => $status,
                ':events_json' => $eventsJson,
                ':last_error' => $lastError,
                ':order_id' => $orderId,
            ]);
        } catch (\Throwable $e) {
            // Fallback legacy: si el schema todavía no tiene tracking_status, usamos status como antes.
            try {
                $sqlOld = "UPDATE order_shipments
                        SET external_shipment_id = COALESCE(:external_shipment_id, external_shipment_id),
                            tracking_number = COALESCE(:tracking_number, tracking_number),
                            status = COALESCE(:status, status),
                            tracking_events_json = :events_json,
                            last_tracked_at = CURRENT_TIMESTAMP,
                            last_error = :last_error,
                            updated_at = CURRENT_TIMESTAMP
                        WHERE order_id = :order_id";
                $st = $this->db->prepare($sqlOld);
                return (bool)$st->execute([
                    ':external_shipment_id' => $externalShipmentId,
                    ':tracking_number' => $trackingNumber,
                    ':status' => $status,
                    ':events_json' => $eventsJson,
                    ':last_error' => $lastError,
                    ':order_id' => $orderId,
                ]);
            } catch (\Throwable $e2) {
                return false;
            }
        }
    }
}

