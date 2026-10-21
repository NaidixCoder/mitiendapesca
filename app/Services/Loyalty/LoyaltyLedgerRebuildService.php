<?php
namespace App\Services\Loyalty;

use App\Repositories\Account\LoyaltyEventsRepository;
use PDO;

/**
 * LoyaltyLedgerRebuildService
 * - Reconstruye el ledger de lealtad (snapshot/order_paid/order_reverted) desde pedidos pagados.
 * - Regla global: puntos = floor(total_pagado_acumulado / 1000)
 * - Para cada pedido pagado (orden cronológico) genera un delta incremental:
 *     delta = floor((acum+total)/1000) - floor(acum/1000)
 */
final class LoyaltyLedgerRebuildService
{
    private array $paidStatuses = ['paid','processing','packed','shipped','delivered'];

    public function __construct(private PDO $db) {}

    private function pointsFromTotal(int $total): int
    {
        if ($total <= 0) return 0;
        return (int)floor($total / 1000);
    }

    private function deltaFromCumulative(int $beforeTotal, int $orderTotal): int
    {
        $beforeTotal = max(0, (int)$beforeTotal);
        $orderTotal  = max(0, (int)$orderTotal);
        $afterTotal  = $beforeTotal + $orderTotal;
        return $this->pointsFromTotal($afterTotal) - $this->pointsFromTotal($beforeTotal);
    }

    /**
     * @return array{user_id:int, deleted:int, created:int, paid_total:int, points:int}
     */
    public function rebuildUser(int $userId, bool $dryRun = true): array
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return ['user_id' => $userId, 'deleted' => 0, 'created' => 0, 'paid_total' => 0, 'points' => 0];
        }

        $repo = new LoyaltyEventsRepository($this->db);
        if (!$repo->tableExists()) {
            return ['user_id' => $userId, 'deleted' => 0, 'created' => 0, 'paid_total' => 0, 'points' => 0];
        }

        // 1) Traer pedidos pagados (orden cronológico estable)
        $in = implode(',', array_fill(0, count($this->paidStatuses), '?'));
        $sql = "SELECT id, total, created_at FROM orders WHERE user_id = ? AND status IN ($in) ORDER BY created_at ASC, id ASC";
        $st = $this->db->prepare($sql);
        $st->execute(array_merge([$userId], $this->paidStatuses));
        $orders = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $deleted = 0;
        $created = 0;

        if (!$dryRun) {
            // 2) Borrar eventos reconstruibles (sin tocar futuros ajustes/manuales)
            $deleted = $repo->deleteByUserAndEventKeys($userId, ['snapshot','order_paid','order_reverted']);
        }

        // 3) Reinsertar order_paid incremental
        $cumulative = 0;
        $points = 0;
        foreach ($orders as $o) {
            $orderId = (int)($o['id'] ?? 0);
            $total   = (int)($o['total'] ?? 0);
            if ($orderId <= 0 || $total <= 0) {
                $cumulative += max(0, $total);
                continue;
            }

            $delta = $this->deltaFromCumulative($cumulative, $total);
            $cumulative += $total;
            if ($delta <= 0) {
                continue;
            }

            $points += $delta;
            $dedupe = 'order:' . $orderId . ':paid';
            $note   = 'Pedido #' . $orderId . ' confirmado (rebuild)';
            $meta   = json_encode(['source' => 'rebuild'], JSON_UNESCAPED_UNICODE);

            if ($dryRun) {
                if (!$repo->existsDedupeKey($dedupe)) {
                    $created++;
                }
                continue;
            }

            try {
                $ok = $repo->insertEvent($userId, $dedupe, 'order_paid', $delta, $orderId, $total, $note, $meta);
                if ($ok) $created++;
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        return ['user_id' => $userId, 'deleted' => $deleted, 'created' => $created, 'paid_total' => (int)$cumulative, 'points' => (int)$points];
    }
}
