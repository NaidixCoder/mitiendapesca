<?php
namespace App\Services\Loyalty;

use App\Repositories\Account\LoyaltyEventsRepository;
use App\Services\Admin\CustomerStatsService;
use PDO;

/**
 * LoyaltyBackfillService
 * - Backfill histórico: genera eventos order_paid por cada pedido ya pagado.
 * - Caso típico: tenías puntos en customer_stats, se creó un snapshot inicial,
 *   pero aún no hay eventos por pedido.
 *
 * Estrategia segura:
 * - Para un usuario con snapshot:user:{id} y 0 eventos de pedido:
 *   1) crea eventos order_paid por cada pedido pagado (idempotente por dedupe_key)
 *   2) elimina el snapshot para evitar doble conteo.
 * - Si el usuario ya tiene eventos de pedido, NO toca el snapshot (es válido).
 */
final class LoyaltyBackfillService
{
    private array $paidStatuses = ['paid','processing','packed','shipped','delivered'];

    public function __construct(private PDO $db) {}

    private function pointsFromTotal(int $total): int
    {
        if ($total <= 0) return 0;
        return (int)floor($total / 1000);
    }

    /**
     * Delta coherente con la regla global (puntos = floor(total_pagado_acumulado/1000)).
     */
    private function deltaFromCumulative(int $beforeTotal, int $orderTotal): int
    {
        $beforeTotal = max(0, (int)$beforeTotal);
        $orderTotal  = max(0, (int)$orderTotal);
        $afterTotal  = $beforeTotal + $orderTotal;
        return $this->pointsFromTotal($afterTotal) - $this->pointsFromTotal($beforeTotal);
    }

    /**
     * Backfill para un usuario.
     * @return array{user_id:int, created:int, deleted_snapshot:int, skipped:bool}
     */
    public function backfillUser(int $userId, bool $dryRun = true): array
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return ['user_id' => $userId, 'created' => 0, 'deleted_snapshot' => 0, 'skipped' => true];
        }

        $eventsRepo = new LoyaltyEventsRepository($this->db);
        if (!$eventsRepo->tableExists()) {
            return ['user_id' => $userId, 'created' => 0, 'deleted_snapshot' => 0, 'skipped' => true];
        }

        // Si ya hay eventos por pedido, no backfilleamos (evitamos duplicados y preservamos snapshot válido).
        $orderEvtCount = $eventsRepo->countOrderEventsByUser($userId);
        $snapshotKey = 'snapshot:user:' . $userId;
        $hasSnapshot = $eventsRepo->existsDedupeKey($snapshotKey);
        if ($orderEvtCount > 0) {
            return ['user_id' => $userId, 'created' => 0, 'deleted_snapshot' => 0, 'skipped' => true];
        }

        // Traer TODOS los pedidos en estado pagado del usuario.
        $in = implode(',', array_fill(0, count($this->paidStatuses), '?'));
        $sql = "SELECT id, total FROM orders WHERE user_id = ? AND status IN ($in) ORDER BY id ASC";
        $st = $this->db->prepare($sql);
        $params = array_merge([$userId], $this->paidStatuses);
        $st->execute($params);
        $orders = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $created = 0;
        $sumDeltas = 0;
        $cumulativePaid = 0;

        $snapshotPoints = null;
        if ($hasSnapshot) {
            try {
                $snap = $eventsRepo->findByDedupeKey($snapshotKey);
                $snapshotPoints = $snap ? (int)($snap['points_delta'] ?? 0) : null;
            } catch (\Throwable $e) {
                $snapshotPoints = null;
            }
        }
        foreach ($orders as $o) {
            $orderId = (int)($o['id'] ?? 0);
            $total   = (int)($o['total'] ?? 0);
            if ($orderId <= 0) continue;

            $delta = $this->deltaFromCumulative($cumulativePaid, $total);
            $cumulativePaid += max(0, $total);
            if ($delta <= 0) {
                // Con redondeo global, un pedido puede no sumar puntos.
                continue;
            }
            $sumDeltas += $delta;

            $dedupe = 'order:' . $orderId . ':paid';
            $note = 'Pedido #' . $orderId . ' confirmado (histórico)';
            $meta = json_encode(['source' => 'backfill'], JSON_UNESCAPED_UNICODE);

            // Evitar contar duplicados (idempotencia real).
            $already = $eventsRepo->existsDedupeKey($dedupe);

            if ($dryRun) {
                // Contamos como "creable" si aún no existe.
                if (!$already) {
                    $created++;
                }
                continue;
            }

            if ($already) {
                continue;
            }

            try {
                $ok = $eventsRepo->insertEvent($userId, $dedupe, 'order_paid', $delta, $orderId, $total, $note, $meta);
                if ($ok) $created++;
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        $deletedSnapshot = 0;
        // Seguridad: solo eliminar snapshot si realmente pudimos reconstruir puntos desde pedidos.
        $expectedPoints = $this->pointsFromTotal($cumulativePaid);
        $canDeleteSnapshot = $hasSnapshot && $snapshotPoints !== null && $snapshotPoints === $expectedPoints;

        if ($canDeleteSnapshot) {
            if ($dryRun) {
                $deletedSnapshot = 1;
            } else {
                try {
                    $deletedSnapshot = $eventsRepo->deleteByDedupeKey($snapshotKey);
                } catch (\Throwable $e) {
                    $deletedSnapshot = 0;
                }
            }
        }

        if (!$dryRun) {
            try {
                (new CustomerStatsService($this->db))->rebuildForUser($userId);
            } catch (\Throwable $e) {
                // best-effort
            }
        }

        return ['user_id' => $userId, 'created' => $created, 'deleted_snapshot' => $deletedSnapshot, 'skipped' => false];
    }
}
