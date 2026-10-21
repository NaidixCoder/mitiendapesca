<?php
namespace App\Services\Loyalty;

use App\Repositories\Account\LoyaltyEventsRepository;
use App\Repositories\Account\CustomerStatsRepository;
use App\Services\Admin\CustomerStatsService;
use PDO;

/**
 * LoyaltyOrderEventsService
 * - Registra eventos de puntos (ledger) al cruzar el umbral de "pagado".
 * - Idempotente por order_id usando dedupe_key.
 * - Best-effort: nunca debe romper checkout/admin.
 */
final class LoyaltyOrderEventsService
{
    private array $paidStatuses = ['paid','processing','packed','shipped','delivered'];

    public function __construct(private PDO $db) {}

    private function isPaidStatus(string $status): bool
    {
        return in_array($status, $this->paidStatuses, true);
    }

    private function pointsFromTotal(int $total): int
    {
        if ($total <= 0) return 0;
        return (int)floor($total / 1000);
    }

    /**
     * Calcula el delta de puntos para una transición que cruza el umbral de "pagado",
     * respetando la regla global: puntos = floor(total_pagado_acumulado / 1000).
     *
     * Importante: se calcula con el total pagado "después" del cambio (estado ya aplicado en DB),
     * para no depender de cómo se actualiza el pedido.
     */
    private function pointsDeltaFromCumulative(int $userId, int $orderTotal, bool $isCredit): int
    {
        if ($userId <= 0 || $orderTotal <= 0) return 0;

        // Total pagado actual (POST-TRANSICIÓN) según estados "pagados".
        $agg = (new CustomerStatsRepository($this->db))->aggregatePaidOrders($userId, $this->paidStatuses);
        $paidAfter = (int)($agg['paid_total'] ?? 0);
        if ($paidAfter < 0) $paidAfter = 0;

        if ($isCredit) {
            // No pagado -> pagado: antes = después - total_pedido
            $paidBefore = max(0, $paidAfter - $orderTotal);
        } else {
            // Pagado -> no pagado: después ya no incluye el pedido; antes = después + total_pedido
            $paidBefore = $paidAfter + $orderTotal;
        }

        return $this->pointsFromTotal($paidAfter) - $this->pointsFromTotal($paidBefore);
    }

    /**
     * Registra eventos si el cambio de estado cruza el umbral (no pagado -> pagado) o (pagado -> no pagado).
     * @return array{did_credit:bool,did_debit:bool,points:int}
     */
    public function onOrderStatusTransition(
        int $orderId,
        int $userId,
        int $orderTotal,
        string $from,
        string $to,
        string $source = ''
    ): array {
        $from = trim($from);
        $to   = trim($to);

        $didCredit = false;
        $didDebit  = false;
        $reportedPoints = 0;

        // Nada que hacer sin usuario o sin total.
        if ($orderId <= 0 || $userId <= 0 || $orderTotal <= 0) {
            return ['did_credit' => false, 'did_debit' => false, 'points' => 0];
        }

        $repo = new LoyaltyEventsRepository($this->db);
        if (!$repo->tableExists()) {
            // Si aún no está la migración, no rompemos.
            return ['did_credit' => false, 'did_debit' => false, 'points' => 0];
        }

        $wasPaid = $this->isPaidStatus($from);
        $isPaid  = $this->isPaidStatus($to);

        // 1) Crédito: entra a pagado.
        if (!$wasPaid && $isPaid) {
            $delta = $this->pointsDeltaFromCumulative($userId, $orderTotal, true);
            if ($delta <= 0) {
                // Con redondeo global, este pedido puede no sumar puntos.
                // No registramos evento con delta 0.
                $delta = 0;
            }
            $dedupe = 'order:'.$orderId.':paid';
            $note = 'Pedido #'.$orderId.' confirmado';
            $meta = $source !== '' ? json_encode(['source' => $source, 'from' => $from, 'to' => $to], JSON_UNESCAPED_UNICODE) : null;
            try {
                if ($delta > 0) {
                    $didCredit = $repo->insertEvent($userId, $dedupe, 'order_paid', $delta, $orderId, $orderTotal, $note, $meta);
                    if ($didCredit) $reportedPoints = $delta;
                }
            } catch (\Throwable $e) {
                $didCredit = false;
            }
        }

        // 2) Débito: sale de pagado hacia un estado no pagado (cancel/refund/chargeback/fail/etc).
        if ($wasPaid && !$isPaid) {
            $delta = $this->pointsDeltaFromCumulative($userId, $orderTotal, false); // negativo o 0
            if ($delta >= 0) $delta = 0;
            $dedupe = 'order:'.$orderId.':revert:'.$to;
            $note = 'Reversión de puntos (Pedido #'.$orderId.')';
            $meta = $source !== '' ? json_encode(['source' => $source, 'from' => $from, 'to' => $to], JSON_UNESCAPED_UNICODE) : null;
            try {
                if ($delta < 0) {
                    $didDebit = $repo->insertEvent($userId, $dedupe, 'order_reverted', $delta, $orderId, $orderTotal, $note, $meta);
                    if ($didDebit) $reportedPoints = abs($delta);
                }
            } catch (\Throwable $e) {
                $didDebit = false;
            }
        }

        // Mantener customer_stats coherente (puntos/tier/progreso)
        if ($didCredit || $didDebit) {
            try {
                (new CustomerStatsService($this->db))->rebuildForUser($userId);
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        return ['did_credit' => $didCredit, 'did_debit' => $didDebit, 'points' => $reportedPoints];
    }
}
