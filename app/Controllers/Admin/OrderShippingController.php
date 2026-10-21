<?php
namespace App\Controllers\Admin;

use App\Services\Shipping\OrderShipmentService;
use PDO;

final class OrderShippingController
{
    private function db(): PDO { return \db(); }

    // POST /admin/orders/shipping
    public function update(): void
    {
        admin_required('/admin/orders');
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $isJson = (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
               || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
               || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

        $input = $isJson ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;

        $orderId = (int)($input['order_id'] ?? $input['id'] ?? 0);
        $data = [
            // el repo espera 'status'
            'status' => (string)($input['status'] ?? $input['shipping_status'] ?? 'preparando'),
            'carrier' => (string)($input['carrier'] ?? 'Correo Argentino'),
            'service' => (string)($input['service'] ?? ''),
            'tracking_number' => (string)($input['tracking_number'] ?? ''),
            'tracking_url' => (string)($input['tracking_url'] ?? ''),
            'external_shipment_id' => (string)($input['external_shipment_id'] ?? ''),
            'shipped_at' => $input['shipped_at'] ?? null,
        ];

        $svc = new OrderShipmentService($this->db());
        [$ok, $err, $row] = $svc->updateShipment((int)($_SESSION['uid'] ?? 0), $orderId, $data);

        if ($isJson) {
            if (!$ok) {
                $msg = match ($err) {
                    'not_found' => 'Pedido no encontrado.',
                    'params' => 'Datos inválidos.',
                    'requires_paid' => 'No podés marcar el envío como "Despachado" si el pedido está pendiente de pago.',
                    default => 'No se pudo guardar el envío.',
                };
                json(['ok' => false, 'error' => $err, 'message' => $msg], 422);
                return;
            }
            $warn = ($err === 'mail_failed');
            $msg = $warn ? 'Envío guardado, pero no se pudo enviar el email de despacho.' : 'Envío actualizado.';
            json(['ok' => true, 'warning' => $warn, 'message' => $msg, 'shipment' => $row]);
            return;
        }

        if (!$ok) {
            $msg = match ($err) {
                'not_found' => 'Pedido no encontrado.',
                'params' => 'Datos inválidos.',
                'requires_paid' => 'No podés marcar el envío como "Despachado" si el pedido está pendiente de pago.',
                default => 'No se pudo guardar el envío.',
            };
            flash('error', $msg);
        } else {
            flash(($err === 'mail_failed') ? 'warn' : 'ok', ($err === 'mail_failed') ? 'Envío guardado, pero no se pudo enviar el email de despacho.' : 'Envío actualizado.');
        }

        redirect('/admin/orders/show?id=' . $orderId);
    }
}
