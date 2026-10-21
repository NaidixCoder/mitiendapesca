<?php
namespace App\Services\Admin;

use App\Repositories\Account\LoyaltyEventsRepository;
use App\Services\Loyalty\LoyaltyLedgerRebuildService;
use PDO;

/**
 * CustomerStatsService
 * - Mantiene agregados por usuario para no recalcular SUM/COUNT en cada view.
 * - Se puede recalcular "on demand" o disparar desde transiciones de pedido.
 */
class CustomerStatsService
{
    private PDO $db;

    private LoyaltyEventsRepository $loyaltyEventsRepo;

    // Estados que cuentan como "venta válida" para historial (coherente con dashboard)
    private array $paidStatuses = ['paid','processing','packed','shipped','delivered'];

    public function __construct(PDO $db)
    {
        $this->db = $db;
        $this->loyaltyEventsRepo = new LoyaltyEventsRepository($db);
    }

    public function rebuildForUser(int $userId): array
    {
        if ($userId <= 0) return ['ok' => false, 'reason' => 'user_id'];

        $in = implode(',', array_fill(0, count($this->paidStatuses), '?'));

        $st = $this->db->prepare("
            SELECT
              COUNT(*) AS paid_orders_count,
              COALESCE(SUM(o.total), 0) AS paid_total,
              MIN(o.created_at) AS first_paid_at,
              MAX(o.created_at) AS last_paid_at
            FROM orders o
            WHERE o.user_id = ?
              AND o.status IN ($in)
        ");

        $params = array_merge([$userId], $this->paidStatuses);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        $paidOrders = (int)($row['paid_orders_count'] ?? 0);
        $paidTotal  = (int)($row['paid_total'] ?? 0);
        $firstPaid  = $row['first_paid_at'] ?? null;
        $lastPaid   = $row['last_paid_at'] ?? null;

        $avg = ($paidOrders > 0) ? (int)floor($paidTotal / $paidOrders) : 0;

        // Fidelidad
        // Regla global: puntos = floor(total_pagado/1000).
        // Ledger existe para explicar movimientos. Si hay mismatch, auto-reconstruimos.
        $spendPoints = (int)floor($paidTotal / 1000);
        $points = $spendPoints;

        if ($this->loyaltyEventsRepo->tableExists()) {
            $eventsCount = $this->loyaltyEventsRepo->countByUser($userId);
            if ($eventsCount > 0) {
                $ledgerPoints = $this->loyaltyEventsRepo->sumPointsByUser($userId);

                // Autocorrección: si el ledger está basado en una regla vieja (puntos por pedido), reconstruimos.
                if ($ledgerPoints !== $spendPoints) {
                    try {
                        (new LoyaltyLedgerRebuildService($this->db))->rebuildUser($userId, false);
                        $ledgerPoints = $this->loyaltyEventsRepo->sumPointsByUser($userId);
                    } catch (\Throwable $e) {
                        // best-effort
                    }
                }

                // Si aún hay mismatch, priorizamos el cálculo por gasto para no sub/infra-contar.
                $points = ($ledgerPoints === $spendPoints) ? $ledgerPoints : $spendPoints;
            } else {
                // Seed inicial (idempotente): el usuario ve "movimientos" desde ya.
                $this->loyaltyEventsRepo->insertEvent(
                    $userId,
                    'snapshot:user:' . $userId,
                    'snapshot',
                    $spendPoints,
                    null,
                    null,
                    'Saldo inicial (seed automático)'
                );
            }
        }

        // Tier por gasto total (ajustable más adelante)
        $tier = 'Bronce';
        if ($paidTotal >= 500000) $tier = 'Platino';
        else if ($paidTotal >= 250000) $tier = 'Oro';
        else if ($paidTotal >= 100000) $tier = 'Plata';

        // Upsert
        $up = $this->db->prepare("
            INSERT INTO customer_stats
              (user_id, paid_orders_count, paid_total, first_paid_at, last_paid_at, avg_paid_total, loyalty_points, loyalty_tier)
            VALUES
              (:uid, :c, :t, :f, :l, :a, :p, :tier)
            ON DUPLICATE KEY UPDATE
              paid_orders_count = VALUES(paid_orders_count),
              paid_total        = VALUES(paid_total),
              first_paid_at     = VALUES(first_paid_at),
              last_paid_at      = VALUES(last_paid_at),
              avg_paid_total    = VALUES(avg_paid_total),
              loyalty_points    = VALUES(loyalty_points),
              loyalty_tier      = VALUES(loyalty_tier),
              updated_at        = CURRENT_TIMESTAMP
        ");

        $up->execute([
            ':uid'  => $userId,
            ':c'    => $paidOrders,
            ':t'    => $paidTotal,
            ':f'    => $firstPaid,
            ':l'    => $lastPaid,
            ':a'    => $avg,
            ':p'    => $points,
            ':tier' => $tier,
        ]);

        return [
            'ok' => true,
            'paid_orders_count' => $paidOrders,
            'paid_total' => $paidTotal,
            'first_paid_at' => $firstPaid,
            'last_paid_at' => $lastPaid,
            'avg_paid_total' => $avg,
            'loyalty_points' => $points,
            'loyalty_tier' => $tier,
        ];
    }
}
