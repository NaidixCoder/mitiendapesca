<?php
namespace App\Services\Shipping;

use App\Mail\Orders\TransactionalMail;
use App\Repositories\Orders\OrderShipmentRepository;
use App\Services\Audit\Audit;
use PDO;
use Throwable;

final class OrderShipmentService
{
    public function __construct(private PDO $db) {}

    /** @return array{0:bool,1:string,2:array<string,mixed>|null} */
    public function updateShipment(int $adminUserId, int $orderId, array $data): array
    {
        if ($orderId <= 0) return [false, 'params', null];

        $repo = new OrderShipmentRepository($this->db);

        try {
            $this->db->beginTransaction();

            $mailFailed = false;

            // Lock pedido + user
            $st = $this->db->prepare("SELECT id, user_id, status FROM orders WHERE id = ? FOR UPDATE");
            $st->execute([$orderId]);
            $order = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$order) { $this->db->rollBack(); return [false, 'not_found', null]; }
            // Coherencia: no permitir despachar si el pedido no está pagado
            $orderStatus = (string)($order['status'] ?? 'pending_payment');
            $desiredShipStatus = trim((string)($data['status'] ?? $data['shipping_status'] ?? 'preparando'));
            $unpaid = in_array($orderStatus, ['pending','pending_payment','payment_failed','payment_expired'], true);
            $isDispatch = ($desiredShipStatus === 'despachado');
            if ($unpaid && $isDispatch) {
                $this->db->rollBack();
                return [false, 'requires_paid', null];
            }

            // Si se marca como despachado y no viene shipped_at, lo seteamos automáticamente.
            // (Evita el picker y mantiene el dato consistente.)
            if ($desiredShipStatus === 'despachado') {
                $sa = $data['shipped_at'] ?? null;
                $sa = is_string($sa) ? trim($sa) : null;
                if ($sa === null || $sa === '') {
                    $data['shipped_at'] = date('Y-m-d H:i:s');
                }
            }


            $before = $repo->getByOrderId($orderId);
            $beforeStatus = (string)($before['status'] ?? '');

            [$ok, $err, $row] = $repo->upsert($orderId, $data);
            if (!$ok) { $this->db->rollBack(); return [false, $err, null]; }

            $afterStatus = (string)($row['status'] ?? '');

            // Si pasa a "despachado" por primera vez -> email
            $userId = (int)($order['user_id'] ?? 0);
            if ($userId > 0 && $afterStatus === 'despachado' && $beforeStatus !== 'despachado') {
                $mail = new TransactionalMail($this->db);

                $tracking = [
                    'carrier' => (string)($row['carrier'] ?? 'Correo Argentino'),
                    'service' => (string)($row['service'] ?? ''),
                    'tracking_number' => (string)($row['tracking_number'] ?? ''),
                    'tracking_url' => (string)($row['tracking_url'] ?? ''),
                ];

                // Envío: si falla NO rompe el guardado, pero lo reportamos
                $sentOk = false;
                try { $sentOk = (bool)$mail->sendOrderShipped($userId, $orderId, $tracking); } catch (Throwable $e) { $sentOk = false; }

                if (!$sentOk) {
                    Audit::log('order_shipped_email_failed', 'order', $orderId, [
                        'user_id' => $userId,
                        'carrier' => $tracking['carrier'] ?? null,
                        'tracking_number' => $tracking['tracking_number'] ?? null,
                    ]);
                    // Devolvemos una señal para que el controller avise
                    $mailFailed = true;
                } else {
                    Audit::log('order_shipped_email_sent', 'order', $orderId, [
                        'user_id' => $userId,
                        'carrier' => $tracking['carrier'] ?? null,
                        'tracking_number' => $tracking['tracking_number'] ?? null,
                    ]);
                }
            }

            Audit::log('order_shipment_updated', 'order', $orderId, [
                'before' => $beforeStatus ?: null,
                'after'  => $afterStatus ?: null,
            ]);

            $this->db->commit();
            return [true, ($mailFailed ? 'mail_failed' : ''), $row];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return [false, 'exception', null];
        }
    }
}

