<?php
namespace App\Services\Account;

use PDO;
use App\Repositories\Account\CustomerStatsRepository;
use App\Repositories\Account\LoyaltyRepository;
use App\Repositories\Account\LoyaltyEventsRepository;

/**
 * LoyaltyService (Cuenta)
 * - Calcula puntos y tier a partir de órdenes "pagadas".
 * - Persiste en customer_stats para performance.
 *
 * Reglas actuales (simples y transparentes):
 * - 1 punto cada $1.000 gastados (redondeo hacia abajo)
 * - Tiers por gasto total:
 *   Bronce < 100k, Plata >= 100k, Oro >= 250k, Platino >= 500k
 */
class LoyaltyService
{
    private CustomerStatsRepository $repo;
    private LoyaltyRepository $orders;
    private LoyaltyEventsRepository $events;

    /** @var string[] */
    private array $paidStatuses = ['paid','processing','packed','shipped','delivered'];

    public function __construct(PDO $db)
    {
        $this->repo = new CustomerStatsRepository($db);
        $this->orders = new LoyaltyRepository($db);
        $this->events = new LoyaltyEventsRepository($db);
    }

    /**
     * Vista detalle del programa (para /cuenta/lealtad)
     * - Incluye totales pagados + en proceso, y un listado de pedidos que sumaron (o pueden sumar) puntos.
     */
    public function getDetailForUser(int $userId, int $limit = 25): array
    {
        $base = $this->getProgressForUser($userId);

        // Estados "en proceso" (no suman puntos aún, pero sirven para motivar).
        // Importante: NO incluir estados ya pagados (processing/packed/etc.) para no duplicar totales.
        $pendingStatuses = ['pending','pending_payment','payment_failed','payment_expired'];

        $totals = $this->orders->totalsByStatuses($userId, $this->paidStatuses, $pendingStatuses);
        $paidTotal = (int)($totals['paid_total'] ?? 0);
        $pendingTotal = (int)($totals['pending_total'] ?? 0);

        // Listas (pagados primero, luego en proceso)
        $paidOrders = $this->orders->listOrdersByStatuses($userId, $this->paidStatuses, $limit);
        $pendingOrders = $this->orders->listOrdersByStatuses($userId, $pendingStatuses, (int)max(10, min(50, $limit)));

        // Puntos por pedido: usamos el ledger (order_paid) para evitar discrepancias
        // y reflejar la regla global (puntos = floor(total_pagado_acumulado/1000)).
        $paidOrderIds = array_map(fn($o) => (int)($o['id'] ?? 0), $paidOrders);
        $paidPointsMap = $this->events->sumPaidPointsByOrderIds($userId, $paidOrderIds);

        $paidRows = [];
        foreach ($paidOrders as $o) {
            $oid = (int)($o['id'] ?? 0);
            $total = (int)($o['total'] ?? 0);
            $paidRows[] = [
                'id' => $oid,
                'created_at' => $o['created_at'] ?? null,
                'status' => (string)($o['status'] ?? ''),
                'total' => $total,
                'points' => (int)($paidPointsMap[$oid] ?? 0),
            ];
        }

        $pendingRows = [];
        foreach ($pendingOrders as $o) {
            $total = (int)($o['total'] ?? 0);
            $pendingRows[] = [
                'id' => (int)($o['id'] ?? 0),
                'created_at' => $o['created_at'] ?? null,
                'status' => (string)($o['status'] ?? ''),
                'total' => $total,
                'potential_points' => (int)floor($total / 1000),
            ];
        }

        // Totales de puntos
        $base['paid_total'] = $paidTotal; // por las dudas, alineamos con agregados
        $base['pending_total'] = $pendingTotal;
        $base['pending_points'] = (int)floor($pendingTotal / 1000);
        $base['orders_paid_rows'] = $paidRows;
        $base['orders_pending_rows'] = $pendingRows;

        // Ledger (movimientos de puntos) — sin cambiar reglas actuales.
        // Fuente de verdad de puntos sigue siendo customer_stats; el ledger es complementario.
        $ledger = $this->buildLedgerForUser($userId, (int)($base['points'] ?? 0), $paidTotal, (int)max(10, min(50, $limit)));
        $base['ledger_enabled'] = (bool)($ledger['enabled'] ?? false);
        $base['ledger_rows'] = $ledger['rows'] ?? [];
        $base['ledger_points'] = (int)($ledger['points'] ?? 0);
        $base['ledger_mismatch'] = (bool)($ledger['mismatch'] ?? false);
        $base['ledger_note'] = (string)($ledger['note'] ?? '');

        return $base;
    }

    /**
     * Construye el ledger para mostrar "movimientos" sin forzar un cambio de reglas.
     * - Si no hay eventos, crea 1 snapshot idempotente con el saldo actual (customer_stats.loyalty_points)
     * - Devuelve rows con balance "después del movimiento" (más recientes primero)
     *
     * @return array{enabled:bool, points:int, mismatch:bool, note:string, rows:array<int, array<string,mixed>>}
     */
    private function buildLedgerForUser(int $userId, int $pointsSnapshot, int $paidTotalSnapshot, int $limit = 25): array
    {
        $out = ['enabled' => false, 'points' => 0, 'mismatch' => false, 'note' => '', 'rows' => []];
        if ($userId <= 0) return $out;
        if (!$this->events->tableExists()) return $out;

        // Inicialización: si el usuario no tiene eventos, creamos un snapshot (idempotente)
        // para que el usuario vea "movimientos" desde ya, sin recalcular nada.
        $count = $this->events->countByUser($userId);
        if ($count === 0) {
            $dedupe = 'snapshot:user:' . $userId;
            $note = 'Inicialización del programa';
            $meta = 'paid_total=' . $paidTotalSnapshot;
            $this->events->insertEvent($userId, $dedupe, 'snapshot', (int)$pointsSnapshot, null, $paidTotalSnapshot, $note, $meta);
        }

        $pointsLedger = $this->events->sumPointsByUser($userId);
        $mismatch = ($pointsSnapshot !== $pointsLedger);
        $note = $mismatch
            ? 'Estamos sincronizando tus movimientos. Tu saldo visible se basa en tu historial confirmado.'
            : '';

        $events = $this->events->listByUser($userId, $limit, 0);

        // Balance "después del movimiento" para la UI (trabajamos del más reciente al más antiguo).
        $balanceAfter = $pointsLedger;
        $rows = [];
        foreach ($events as $ev) {
            $delta = (int)($ev['points_delta'] ?? 0);
            $eventKey = (string)($ev['event_key'] ?? '');
            $orderId = isset($ev['order_id']) ? (int)$ev['order_id'] : null;

            $rows[] = [
                'id' => (int)($ev['id'] ?? 0),
                'created_at' => $ev['created_at'] ?? null,
                'event_key' => $eventKey,
                'label' => $this->eventLabel($eventKey, $orderId, (string)($ev['note'] ?? '')),
                'points_delta' => $delta,
                'balance_after' => $balanceAfter,
                'order_id' => $orderId,
                'amount_total' => isset($ev['amount_total']) ? (int)$ev['amount_total'] : null,
            ];

            $balanceAfter -= $delta;
        }

        return [
            'enabled' => true,
            'points' => $pointsLedger,
            'mismatch' => $mismatch,
            'note' => $note,
            'rows' => $rows,
        ];
    }

    private function eventLabel(string $eventKey, ?int $orderId, string $note = ''): string
    {
        // Nota explícita tiene prioridad (sirve para ajustes manuales futuro).
        $note = trim($note);
        if ($note !== '') return $note;

        if ($eventKey === 'snapshot') return 'Inicio / saldo inicial';
        if ($eventKey === 'order_paid' && $orderId) return 'Pedido #' . $orderId . ' confirmado';
        if (($eventKey === 'order_refund' || $eventKey === 'order_reverted') && $orderId) return 'Reversión del pedido #' . $orderId;
        if ($eventKey === 'adjustment') return 'Ajuste de puntos';

        return 'Movimiento de puntos';
    }

    /**
     * Devuelve un payload listo para la vista (sin lógica en la view).
     */
    public function getProgressForUser(int $userId): array
    {
        $base = [
            'enabled' => false,
            'points' => 0,
            'tier' => 'Bronce',
            'paid_total' => 0,
            'paid_orders_count' => 0,
            'rate_label' => '1 punto cada $ 1.000',
            'progress_pct' => 0,
            'next_tier' => 'Plata',
            'next_target' => 100000,
            'to_next_tier' => 100000,
            'pill_class' => '',
            'benefits' => $this->benefitsForTier('Bronce'),
            'next_benefits' => $this->benefitsForTier('Plata'),
            'benefits_note' => $this->benefitsNote(),
        ];

        if ($userId <= 0) return $base;
        if (!$this->repo->tableExists('customer_stats')) return $base;

        // ✅ Fuente de verdad: customer_stats.
        // Motivo: evita recalcular (y peor aún, pisar) stats si los estados de órdenes cambian.
        // Los stats se reconstruyen desde Admin/Order transitions (CustomerStatsService) o bajo demanda.
        $row = null;
        try {
            $row = $this->repo->findByUser($userId);
        } catch (\Throwable $e) {
            $row = null;
        }

        // Si existen stats pero quedaron en cero por un bug/regresión, intentamos
        // reconstruirlos desde orders SIN pisar valores válidos.
        if ($row && (int)($row['paid_total'] ?? 0) === 0) {
            $agg = $this->repo->aggregatePaidOrders($userId, $this->paidStatuses);
            $aggTotal = (int)($agg['paid_total'] ?? 0);
            $aggCount = (int)($agg['paid_orders_count'] ?? 0);

            // Solo upsert si encontramos compras pagadas reales.
            if ($aggTotal > 0 || $aggCount > 0) {
                $avg = ($aggCount > 0) ? (int)floor($aggTotal / $aggCount) : 0;
                $points = (int)floor($aggTotal / 1000);
                $tier = $this->tierForTotal($aggTotal);

                $this->repo->upsertForUser($userId, [
                    'paid_orders_count' => $aggCount,
                    'paid_total' => $aggTotal,
                    'first_paid_at' => $agg['first_paid_at'] ?? null,
                    'last_paid_at'  => $agg['last_paid_at'] ?? null,
                    'avg_paid_total' => $avg,
                    'loyalty_points' => $points,
                    'loyalty_tier' => $tier,
                ]);

                $row = $this->repo->findByUser($userId);
            }
        }

        // Si no existen stats aún, hacemos un cálculo inicial best-effort desde orders.
        if (!$row) {
            $agg = $this->repo->aggregatePaidOrders($userId, $this->paidStatuses);
            $paidOrders = (int)($agg['paid_orders_count'] ?? 0);
            $paidTotal  = (int)($agg['paid_total'] ?? 0);
            $avg = ($paidOrders > 0) ? (int)floor($paidTotal / $paidOrders) : 0;
            $points = (int)floor($paidTotal / 1000);
            $tier = $this->tierForTotal($paidTotal);

            $this->repo->upsertForUser($userId, [
                'paid_orders_count' => $paidOrders,
                'paid_total' => $paidTotal,
                'first_paid_at' => $agg['first_paid_at'] ?? null,
                'last_paid_at'  => $agg['last_paid_at'] ?? null,
                'avg_paid_total' => $avg,
                'loyalty_points' => $points,
                'loyalty_tier' => $tier,
            ]);

            $row = $this->repo->findByUser($userId);
        }

        $paidTotal  = (int)($row['paid_total'] ?? 0);
        $paidOrders = (int)($row['paid_orders_count'] ?? 0);
        $points     = (int)($row['loyalty_points'] ?? 0);
        $tier       = (string)($row['loyalty_tier'] ?? $this->tierForTotal($paidTotal));

        $progress = $this->progressForTotal($paidTotal);

        $nextTierName = $progress['next_tier'] ?? null;
        $benefits = $this->benefitsForTier($tier);
        $nextBenefits = $nextTierName ? $this->benefitsForTier((string)$nextTierName) : [];

        return [
            'enabled' => true,
            'points' => $points,
            'tier' => $tier,
            'paid_total' => $paidTotal,
            'paid_orders_count' => $paidOrders,
            'rate_label' => $base['rate_label'],
            'progress_pct' => $progress['pct'],
            'next_tier' => $progress['next_tier'],
            'next_target' => $progress['next_target'],
            'to_next_tier' => $progress['to_next'],
            'pill_class' => $this->pillClassForTier($tier),
            'benefits' => $benefits,
            'next_benefits' => $nextBenefits,
            'benefits_note' => $this->benefitsNote(),
        ];
    }

    /**
     * Beneficios por nivel (copy para UI).
     * Nota: son informativos; si en el futuro se automatizan descuentos/envíos,
     * se conecta aquí con reglas reales.
     *
     * @return string[]
     */
    private function benefitsForTier(string $tier): array
    {
        // Mantener mensajes cortos y orientados a compra.
        $common = [
            'Sumás 1 punto cada $1.000 en compras pagadas.',
            'Tu nivel se actualiza automáticamente según tu historial.',
        ];

        if ($tier === 'Plata') {
            return array_merge($common, [
                'Acceso anticipado a promos seleccionadas.',
                'Beneficios exclusivos del programa (en activación).',
            ]);
        }
        if ($tier === 'Oro') {
            return array_merge($common, [
                'Promos exclusivas de temporada.',
                'Atención prioritaria (en activación).',
            ]);
        }
        if ($tier === 'Platino') {
            return array_merge($common, [
                'Beneficios VIP y preventas (en activación).',
                'Sorpresas y beneficios especiales (en activación).',
            ]);
        }

        // Bronce (default)
        return array_merge($common, [
            'Acceso al programa desde tu primera compra.',
        ]);
    }

    private function benefitsNote(): string
    {
        return 'Algunos beneficios se están activando por etapas. Tu progreso y puntos ya se calculan en tiempo real.';
    }

    private function tierForTotal(int $paidTotal): string
    {
        if ($paidTotal >= 500000) return 'Platino';
        if ($paidTotal >= 250000) return 'Oro';
        if ($paidTotal >= 100000) return 'Plata';
        return 'Bronce';
    }

    /**
     * @return array{pct:int, next_tier:?string, next_target:?int, to_next:?int}
     */
    private function progressForTotal(int $paidTotal): array
    {
        $tier = $this->tierForTotal($paidTotal);

        // Rango actual y próximo objetivo
        $currentMin = 0;
        $nextTier = 'Plata';
        $nextTarget = 100000;

        if ($tier === 'Plata') {
            $currentMin = 100000;
            $nextTier = 'Oro';
            $nextTarget = 250000;
        } elseif ($tier === 'Oro') {
            $currentMin = 250000;
            $nextTier = 'Platino';
            $nextTarget = 500000;
        } elseif ($tier === 'Platino') {
            return ['pct' => 100, 'next_tier' => null, 'next_target' => null, 'to_next' => null];
        }

        $span = max(1, $nextTarget - $currentMin);
        $pos  = max(0, min($span, $paidTotal - $currentMin));
        $pct  = (int)round(($pos / $span) * 100);

        $toNext = max(0, $nextTarget - $paidTotal);

        return [
            'pct' => max(0, min(100, $pct)),
            'next_tier' => $nextTier,
            'next_target' => $nextTarget,
            'to_next' => $toNext,
        ];
    }

    private function pillClassForTier(string $tier): string
    {
        if ($tier === 'Platino') return 'pill--ok';
        if ($tier === 'Oro') return 'pill--warn';
        if ($tier === 'Plata') return 'pill--info';
        return '';
    }
}
