<?php
declare(strict_types=1);

namespace App\Services\Orders;

use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Admin\AdminOrderService;
use PDO;

final class ExpirePendingTransferOrdersService
{
    public function __construct(private PDO $db)
    {
    }

    public function reservationHours(): int
    {
        $settings = new AppSettingsRepository($this->db);
        $hours = (int)($settings->get('payments_transfer_reservation_hours', '24') ?? '24');
        if ($hours < 1) $hours = 24;
        if ($hours > 720) $hours = 720;
        return $hours;
    }

    /**
     * @return array{reservation_hours:int,cutoff:string,scanned:int,expired:int,errors:int,error_orders:array<int,array<string,mixed>>}
     */
    public function run(int $limit = 200): array
    {
        $limit = max(1, min(1000, $limit));
        $hours = $this->reservationHours();
        $cutoff = date('Y-m-d H:i:s', time() - ($hours * 3600));

        $sql = "
            SELECT id
            FROM orders
            WHERE status = 'pending_payment'
              AND payment_method IN ('transferencia','transfer','transfer_bank','bank_transfer')
              AND created_at <= ?
            ORDER BY created_at ASC
            LIMIT {$limit}
        ";

        $st = $this->db->prepare($sql);
        $st->execute([$cutoff]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $ids = array_map('intval', array_column($rows, 'id'));

        $svc = new AdminOrderService($this->db);
        $expired = 0;
        $errors = 0;
        $errorOrders = [];

        foreach ($ids as $orderId) {
            [$ok, $err, $result] = $svc->changeStatus(0, $orderId, 'payment_expired');
            if ($ok) {
                $expired++;
                continue;
            }

            $errors++;
            $errorOrders[] = [
                'order_id' => $orderId,
                'error' => $err,
                'result' => $result,
            ];
        }

        return [
            'reservation_hours' => $hours,
            'cutoff' => $cutoff,
            'scanned' => count($ids),
            'expired' => $expired,
            'errors' => $errors,
            'error_orders' => $errorOrders,
        ];
    }
}
