<?php
namespace App\Services\Shipping\CorreoArgentino;

use App\Repositories\Settings\AppSettingsRepository;
use App\Repositories\Checkout\CheckoutIntentRepository;
use App\Repositories\Orders\OrderShipmentRepository;
use App\Services\Audit\Audit;
use App\Services\Shipping\Support\ProvinceCodeMapper;
use PDO;
use Throwable;

/**
 * CorreoImportService
 * - Importa un envío en MiCorreo (/shipping/import) usando datos del pedido + remitente fijo (Ajustes).
 * - Persiste en order_shipments: external_shipment_id + tracking_number (si viene).
 */
final class CorreoImportService
{
    public function __construct(private PDO $db) {}

    /**
     * @return array{0:bool,1:string,2:array<string,mixed>}
     */
    public function importForOrder(int $adminUserId, int $orderId): array
    {
        if ($orderId <= 0) return [false, 'params', ['reason' => 'order_id']];

        try {
            $st = $this->db->prepare(
                "SELECT o.*, u.email AS user_email
                   FROM orders o
                   LEFT JOIN users u ON u.id=o.user_id
                  WHERE o.id = ?");
            $st->execute([$orderId]);
            $order = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$order) return [false, 'not_found', []];

            // Si ya está importado, no repetimos.
            $shipRepo = new OrderShipmentRepository($this->db);
            $existing = $shipRepo->findByOrderId($orderId);
            if ($existing && trim((string)($existing['external_shipment_id'] ?? '')) !== '') {
                return [false, 'already_imported', ['external_shipment_id' => (string)$existing['external_shipment_id']]];
            }

            $settings = new AppSettingsRepository($this->db);

            // Gating por ambiente: en QA sin customerId no se puede.
            $cfg = (new CorreoConfig($this->db))->get();
            $mode = (string)($cfg['mode'] ?? 'test');

            // QA/Dev: mock import (solo TEST)
            $mockImport = (bool)($cfg['mock_import'] ?? false);
            $customerId = trim((string)($cfg['customer_id'] ?? ''));
            if ($customerId === '') {
                try { $customerId = trim((string)($settings->get('correo_customer_id','') ?? '')); } catch (Throwable $e) {}
            }

            // Intent snapshot (carrier/mode/agency) vive en checkout_intents.totals_json
            $token = (string)($order['checkout_token'] ?? '');
            $snapshot = null;
            if ($token !== '') {
                $intent = (new CheckoutIntentRepository($this->db))->findByToken($token);
                if ($intent && !empty($intent['totals_json'])) {
                    $tj = json_decode((string)$intent['totals_json'], true);
                    if (is_array($tj) && isset($tj['shipping_snapshot']) && is_array($tj['shipping_snapshot'])) {
                        $snapshot = $tj['shipping_snapshot'];
                    }
                }
            }

            $deliveryType = strtoupper(trim((string)($snapshot['mode'] ?? 'D')));
            if ($deliveryType === 'L') {
                return [false, 'local_pickup', []];
            }
            if (!in_array($deliveryType, ['D','S'], true)) $deliveryType = 'D';
            $agency = trim((string)($snapshot['agency_id'] ?? ''));
            if ($deliveryType === 'S' && $agency === '') {
                return [false, 'missing_agency', []];
            }

            // Remitente fijo desde Ajustes
            $sender = [
                'name' => trim((string)$settings->get('shipping_sender_name','')),
                'phone' => trim((string)$settings->get('shipping_sender_phone','')),
                'cellPhone' => trim((string)$settings->get('shipping_sender_cellphone','')),
                'email' => trim((string)$settings->get('shipping_sender_email','')),
                'originAddress' => [
                    'streetName' => trim((string)$settings->get('shipping_sender_street','')),
                    'streetNumber' => trim((string)$settings->get('shipping_sender_number','')),
                    'floor' => trim((string)$settings->get('shipping_sender_floor','')),
                    'apartment' => trim((string)$settings->get('shipping_sender_apartment','')),
                    'city' => trim((string)$settings->get('shipping_sender_city','')),
                    'provinceCode' => ProvinceCodeMapper::fromState((string)$settings->get('shipping_sender_state','')),
                    'postalCode' => trim((string)$settings->get('shipping_sender_zip','')),
                ],
            ];

            // Validaciones mínimas para evitar 402 de Correo.
            if ($sender['email'] === '' || $sender['name'] === '') {
                return [false, 'missing_sender', ['missing' => 'name/email']];
            }
            if ($sender['originAddress']['postalCode'] === '' || $sender['originAddress']['provinceCode'] === '') {
                return [false, 'missing_sender', ['missing' => 'postalCode/provinceCode']];
            }

            $recipientEmail = (string)($order['user_email'] ?? '');
            if ($recipientEmail === '') {
                // fallback: si el user no tiene email (no debería), usamos el del remitente para no bloquear
                $recipientEmail = $sender['email'];
            }

            $recipient = [
                'name' => (string)($order['shipping_full_name'] ?? ''),
                'phone' => (string)($order['shipping_phone'] ?? ''),
                'cellPhone' => (string)($order['shipping_phone'] ?? ''),
                'email' => $recipientEmail,
            ];
            if (trim($recipient['name']) === '') {
                return [false, 'missing_recipient', ['missing' => 'name']];
            }

            $provinceCode = ProvinceCodeMapper::fromState((string)($order['shipping_state'] ?? ''));
            if ($provinceCode === '') {
                return [false, 'missing_shipping_address', ['missing' => 'provinceCode']];
            }
            if (trim((string)($order['shipping_zip'] ?? '')) === '') {
                return [false, 'missing_shipping_address', ['missing' => 'postalCode']];
            }

            // Si estamos en TEST con mock *y no hay customerId*, simulamos aquí (no pega a Correo)
            if ($customerId === '' && $mode === 'test' && $mockImport) {
                $externalId = 'TEST-' . $orderId . '-' . date('YmdHis');
                $trackingNumber = 'TEST-TRACK-' . substr(bin2hex(random_bytes(6)), 0, 12);

                $shipRepo->upsert($orderId, [
                    'carrier' => 'correo_argentino',
                    'service' => (string)($snapshot['service_code'] ?? ''),
                    'shipping_status' => 'imported',
                    'tracking_number' => $trackingNumber,
                    'external_shipment_id' => $externalId,
                    'meta_json' => json_encode([
                        'mock' => true,
                        'deliveryType' => $deliveryType,
                        'agency' => ($deliveryType === 'S' ? $agency : null),
                    ], JSON_UNESCAPED_UNICODE),
                ]);

                \App\Services\Audit\Audit::log('shipping_correo_import_mock', 'order', $orderId, [
                    'admin_user_id' => $adminUserId,
                    'deliveryType' => $deliveryType,
                    'agency' => ($deliveryType === 'S' ? $agency : null),
                    'external_shipment_id' => $externalId,
                ]);

                return [true, '', [
                    'external_shipment_id' => $externalId,
                    'tracking_number' => $trackingNumber,
                    'mock' => true,
                ]];
            }

            // En flujo real, customerId es obligatorio.
            if ($customerId === '') {
                return [false, 'no_customer_id', ['mode' => $mode]];
            }

            // Dimensiones default (hasta que FIX519 obligue por producto/variante)
            $weight = (int)$settings->get('shipping_default_weight', 1000);
            $height = (int)$settings->get('shipping_default_height', 20);
            $width  = (int)$settings->get('shipping_default_width', 20);
            $length = (int)$settings->get('shipping_default_length', 20);
            $weight = max(1, min(25000, $weight));
            $height = max(1, min(150, $height));
            $width  = max(1, min(150, $width));
            $length = max(1, min(150, $length));

            $declared = (float)max(1, (int)($order['subtotal'] ?? 0));

            $payload = [
                'customerId' => $customerId,
                'extOrderId' => (string)$orderId,
                'orderNumber' => (string)$orderId,
                'sender' => $sender,
                'recipient' => $recipient,
                'shipping' => [
                    'deliveryType' => $deliveryType,
                    'agency' => ($deliveryType === 'S' ? $agency : null),
                    'address' => [
                        'streetName' => (string)($order['shipping_street'] ?? ''),
                        'streetNumber' => (string)($order['shipping_number'] ?? ''),
                        'floor' => (string)($order['shipping_floor'] ?? ''),
                        'apartment' => (string)($order['shipping_apartment'] ?? ''),
                        'city' => (string)($order['shipping_city'] ?? ''),
                        'provinceCode' => $provinceCode,
                        'postalCode' => (string)($order['shipping_zip'] ?? ''),
                    ],
                    'productType' => 'CP',
                    'weight' => $weight,
                    'declaredValue' => $declared,
                    'height' => $height,
                    'length' => $length,
                    'width' => $width,
                ],
            ];

            $client = new CorreoClient($this->db);
            [$ok, $res] = $client->shippingImport($payload);

            // Persistimos lo que venga (best-effort)
            $externalId = '';
            foreach (['shippingId','ShippingId','shipmentId','id'] as $k) {
                if (isset($res[$k]) && is_string($res[$k]) && trim($res[$k]) !== '') { $externalId = trim($res[$k]); break; }
            }
            $trackingNumber = '';
            foreach (['trackingNumber','tracking_number','trackNumber','track_number'] as $k) {
                if (isset($res[$k]) && is_string($res[$k]) && trim($res[$k]) !== '') { $trackingNumber = trim($res[$k]); break; }
            }

            if ($ok) {
                $shipRepo->upsert($orderId, [
                    'carrier' => 'correo_argentino',
                    'service' => (string)($snapshot['service_code'] ?? ''),
                    'shipping_status' => 'imported',
                    'tracking_number' => $trackingNumber,
                    'external_shipment_id' => $externalId,
                    'meta_json' => json_encode([
                        'deliveryType' => $deliveryType,
                        'agency' => ($deliveryType === 'S' ? $agency : null),
                    ], JSON_UNESCAPED_UNICODE),
                ]);

                \App\Services\Audit\Audit::log('shipping_correo_import_success', 'order', $orderId, [
                    'admin_user_id' => $adminUserId,
                    'deliveryType' => $deliveryType,
                    'agency' => ($deliveryType === 'S' ? $agency : null),
                    'external_shipment_id' => $externalId,
                ]);

                return [true, '', [
                    'external_shipment_id' => $externalId,
                    'tracking_number' => $trackingNumber,
                    'raw' => $res,
                ]];
            }

                \App\Services\Audit\Audit::log('shipping_correo_import_error', 'order', $orderId, [
                    'admin_user_id' => $adminUserId,
                'deliveryType' => $deliveryType,
                'agency' => ($deliveryType === 'S' ? $agency : null),
                'error' => $res,
            ]);

            return [false, 'api_error', ['error' => $res]];
        } catch (Throwable $e) {
            return [false, 'exception', ['msg' => $e->getMessage()]];
        }
    }
}
