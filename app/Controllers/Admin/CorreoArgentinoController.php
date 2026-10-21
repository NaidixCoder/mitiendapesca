<?php
namespace App\Controllers\Admin;

use App\Services\Audit\Audit;
use App\Services\Shipping\CorreoArgentino\CorreoClient;
use App\Services\Shipping\CorreoArgentino\CorreoStatusMapper;
use App\Services\Shipping\CorreoArgentino\CorreoImportService;
use App\Repositories\Orders\OrderShipmentRepository;

final class CorreoArgentinoController extends BaseController
{
    public function testConnection(): void
    {
        verify_csrf_or_abort();

        try {
            $db = db();
            $client = new CorreoClient($db);

            // 1) token (solo valida credenciales API)
            $client->authenticate();

            $summary = 'Token OK.';

            // 2) validate user (opcional: solo necesario para sucursales /agencies)
            $customerId = '';
            if ($client->hasUserCredentials()) {
                $info = $client->validateUser();
                $customerId = (string)($info['customerId'] ?? '');
                if ($customerId !== '') {
                    $client->saveCustomerId($customerId);
                    $summary .= ' Usuario validado. CustomerId: ' . $customerId;
                } else {
                    $summary .= ' Usuario validado, pero sin customerId.';
                }
            } else {
                $summary .= ' Faltan credenciales de usuario MiCorreo (CORREO_USER_EMAIL / CORREO_USER_PASSWORD). Esto solo afecta retiro en sucursal.';
            }

            Audit::log('shipping_correo_test_success', 'settings', null, [
                'mode' => (string)($_POST['mode'] ?? $_GET['mode'] ?? ''),
                'has_customer_id' => ($customerId !== ''),
            ]);

            $this->json([
                'ok' => true,
                'customerId' => $customerId,
                'summary' => $summary,
            ]);
        } catch (\Throwable $e) {
            Audit::log('shipping_correo_test_error', 'settings', null, [
                'error' => $e->getMessage(),
            ]);
            $this->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    public function trackOrder(): void
    {
        verify_csrf_or_abort();

        try {
            $payload = json_decode((string)file_get_contents('php://input'), true);
            $orderId = is_array($payload) ? (int)($payload['order_id'] ?? 0) : 0;
            if ($orderId <= 0) {
                $this->json(['ok' => false, 'message' => 'order_id inválido.'], 422);
                return;
            }

            $db = db();
            $repo = new OrderShipmentRepository($db);
            $shipment = $repo->getByOrderId($orderId);
            if (!$shipment) {
                $this->json(['ok' => false, 'message' => 'El pedido no tiene envío registrado.'], 422);
                return;
            }

            $shippingId = trim((string)($shipment['external_shipment_id'] ?? ''));
            if ($shippingId === '') {
                // Fallback: a veces guardan tracking_number como shippingId
                $shippingId = trim((string)($shipment['tracking_number'] ?? ''));
            }
            if ($shippingId === '') {
                $this->json(['ok' => false, 'message' => 'Falta shippingId (external_shipment_id) para consultar tracking.'], 422);
                return;
            }

            $client = new CorreoClient($db);
            $trackingPayload = $client->track($shippingId);

            $newStatus = CorreoStatusMapper::mapStatus($trackingPayload);

            // trackingNumber lo devuelve el payload en varios formatos
            $trackingNumber = null;
            foreach ($trackingPayload as $entry) {
                if (!is_array($entry)) continue;
                $tn = trim((string)($entry['trackingNumber'] ?? ''));
                if ($tn !== '') { $trackingNumber = $tn; break; }
            }

            $repo->updateTracking(
                $orderId,
                $shippingId,
                $trackingNumber,
                $newStatus,
                $trackingPayload,
                null
            );

            Audit::log('shipping_correo_tracking_ok', 'order', $orderId, [
                'status' => $newStatus,
            ]);

            $this->json([
                'ok' => true,
                'order_id' => $orderId,
                'status' => $newStatus,
                'tracking_number' => $trackingNumber,
                'events_count' => self::countEvents($trackingPayload),
            ]);
        } catch (\Throwable $e) {
            // Intentamos guardar el error en order_shipments si existe
            try {
                $payload = json_decode((string)file_get_contents('php://input'), true);
                $orderId = is_array($payload) ? (int)($payload['order_id'] ?? 0) : 0;
                if ($orderId > 0 && function_exists('db')) {
                    (new OrderShipmentRepository(db()))->updateTracking($orderId, null, null, null, [], $e->getMessage());
                }
            } catch (\Throwable $e2) {}

            Audit::log('shipping_correo_tracking_error', 'order', isset($orderId) ? (int)$orderId : null, [
                'error' => $e->getMessage(),
            ]);

            $this->json([
                'ok' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Admin Pedido -> Crear envío (Correo)
     * - POST JSON {order_id}
     * - Gating: si no hay customerId, devuelve 422 (UI debe mostrar razón)
     */
    public function importOrder(): void
    {
        verify_csrf_or_abort();

        try {
            $payload = json_decode((string)file_get_contents('php://input'), true);
            $orderId = is_array($payload) ? (int)($payload['order_id'] ?? 0) : 0;
            if ($orderId <= 0) {
                $this->json(['ok' => false, 'message' => 'order_id inválido.'], 422);
                return;
            }

            $adminUserId = (int)($_SESSION['admin_user_id'] ?? $_SESSION['user_id'] ?? 0);

            $svc = new CorreoImportService(db());
            [$ok, $code, $meta] = $svc->importForOrder($adminUserId, $orderId);

            if (!$ok) {
                $msg = match ($code) {
                    'already_imported' => 'Este pedido ya fue importado a MiCorreo.',
                    'no_customer_id' => 'No hay CustomerId configurado. En QA esto es esperable (users/validate=406).',
                    'missing_sender' => 'Faltan datos del remitente en Ajustes → Envíos.',
                    'missing_recipient' => 'Faltan datos del destinatario en el pedido (nombre).',
                    'missing_shipping_address' => 'Faltan datos de dirección del pedido (CP / provincia).',
                    'missing_agency' => 'Este envío es a sucursal pero falta la sucursal (agency).',
                    'api_error' => 'Correo Argentino rechazó la importación del envío.',
                    default => 'No se pudo crear el envío.',
                };

                $this->json([
                    'ok' => false,
                    'code' => $code,
                    'message' => $msg,
                    'meta' => $meta,
                ], 422);
                return;
            }

            $this->json([
                'ok' => true,
                'order_id' => $orderId,
                'external_shipment_id' => (string)($meta['external_shipment_id'] ?? ''),
                'tracking_number' => (string)($meta['tracking_number'] ?? ''),
            ]);
        } catch (\Throwable $e) {
            Audit::log('shipping_correo_import_exception', 'order', null, ['error' => $e->getMessage()]);
            $this->json(['ok' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /** @param array<int,array<string,mixed>> $payload */
    private static function countEvents(array $payload): int
    {
        $n = 0;
        foreach ($payload as $entry) {
            if (!is_array($entry)) continue;
            $events = $entry['events'] ?? [];
            if (is_array($events)) $n += count($events);
        }
        return $n;
    }
}

