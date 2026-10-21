<?php
namespace App\Repositories\Account;

use PDO;

/**
 * LoyaltyRepository
 * - Lecturas específicas para la vista de fidelidad (detalle)
 * - SQL solamente (sin reglas de negocio)
 */
final class LoyaltyRepository
{
    public function __construct(private PDO $db) {}

    public function ordersTableExists(): bool
    {
        try {
            $this->db->query('SELECT 1 FROM `orders` LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Totales por estado.
     * @return array{paid_total:int, pending_total:int, paid_orders:int, pending_orders:int}
     */
    public function totalsByStatuses(int $userId, array $paidStatuses, array $pendingStatuses): array
    {
        if ($userId <= 0 || !$this->ordersTableExists()) {
            return ['paid_total'=>0,'pending_total'=>0,'paid_orders'=>0,'pending_orders'=>0];
        }

        $paidStatuses = array_values(array_filter($paidStatuses, fn($s) => is_string($s) && $s !== ''));
        $pendingStatuses = array_values(array_filter($pendingStatuses, fn($s) => is_string($s) && $s !== ''));

        $paidIn = count($paidStatuses) ? implode(',', array_fill(0, count($paidStatuses), '?')) : "''";
        $pendingIn = count($pendingStatuses) ? implode(',', array_fill(0, count($pendingStatuses), '?')) : "''";

        $sql = "
            SELECT
              SUM(CASE WHEN o.status IN ($paidIn) THEN o.total ELSE 0 END) AS paid_total,
              SUM(CASE WHEN o.status IN ($pendingIn) THEN o.total ELSE 0 END) AS pending_total,
              SUM(CASE WHEN o.status IN ($paidIn) THEN 1 ELSE 0 END) AS paid_orders,
              SUM(CASE WHEN o.status IN ($pendingIn) THEN 1 ELSE 0 END) AS pending_orders
            FROM orders o
            WHERE o.user_id = ?
        ";

        // OJO: $paidIn y $pendingIn se reutilizan 2 veces cada uno en el SQL (SUM y COUNT).
        // En PDO, cada '?' cuenta como parámetro independiente, aunque el texto sea el mismo.
        // Por eso debemos pasar los estados duplicados en el mismo orden en que aparecen.
        $params = [];
        foreach ($paidStatuses as $s) { $params[] = $s; }      // paid_total
        foreach ($pendingStatuses as $s) { $params[] = $s; }   // pending_total
        foreach ($paidStatuses as $s) { $params[] = $s; }      // paid_orders
        foreach ($pendingStatuses as $s) { $params[] = $s; }   // pending_orders
        $params[] = $userId;

        $st = $this->db->prepare($sql);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'paid_total'    => (int)($row['paid_total'] ?? 0),
            'pending_total' => (int)($row['pending_total'] ?? 0),
            'paid_orders'   => (int)($row['paid_orders'] ?? 0),
            'pending_orders'=> (int)($row['pending_orders'] ?? 0),
        ];
    }

    /**
     * Lista de pedidos para mostrar puntos ganados (ordenados por fecha desc).
     * @return array<int, array{id:int, status:string, total:int, created_at:?string}>
     */
    public function listOrdersByStatuses(int $userId, array $statuses, int $limit = 25): array
    {
        if ($userId <= 0 || !$this->ordersTableExists()) return [];
        $limit = max(1, min(100, $limit));

        $statuses = array_values(array_filter($statuses, fn($s) => is_string($s) && $s !== ''));
        if (count($statuses) === 0) return [];

        $in = implode(',', array_fill(0, count($statuses), '?'));

        $sql = "
            SELECT o.id, o.status, o.total, o.created_at
            FROM orders o
            WHERE o.user_id = ?
              AND o.status IN ($in)
            ORDER BY o.created_at DESC
            LIMIT $limit
        ";

        $st = $this->db->prepare($sql);
        $params = array_merge([$userId], $statuses);
        $st->execute($params);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int)($r['id'] ?? 0),
                'status' => (string)($r['status'] ?? ''),
                'total' => (int)($r['total'] ?? 0),
                'created_at' => $r['created_at'] ?? null,
            ];
        }
        return $out;
    }
}
