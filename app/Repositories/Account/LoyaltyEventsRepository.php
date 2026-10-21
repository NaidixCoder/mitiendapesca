<?php
namespace App\Repositories\Account;

use PDO;

/**
 * LoyaltyEventsRepository
 * - Ledger de puntos (cuenta corriente)
 * - Solo SQL (sin reglas)
 */
final class LoyaltyEventsRepository
{
    public function __construct(private PDO $db) {}

    public function tableExists(): bool
    {
        try {
            $this->db->query('SELECT 1 FROM `loyalty_events` LIMIT 1');
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * @return int suma de puntos (SUM deltas)
     */
    public function sumPointsByUser(int $userId): int
    {
        if ($userId <= 0 || !$this->tableExists()) return 0;
        $st = $this->db->prepare('SELECT COALESCE(SUM(points_delta),0) AS pts FROM loyalty_events WHERE user_id = ?');
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        return (int)($row['pts'] ?? 0);
    }

    /**
     * @return int cantidad de eventos
     */
    public function countByUser(int $userId): int
    {
        if ($userId <= 0 || !$this->tableExists()) return 0;
        $st = $this->db->prepare('SELECT COUNT(*) AS c FROM loyalty_events WHERE user_id = ?');
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        return (int)($row['c'] ?? 0);
    }

    /**
     * @return int cantidad de eventos asociados a pedidos (order_paid/order_reverted)
     */
    public function countOrderEventsByUser(int $userId): int
    {
        if ($userId <= 0 || !$this->tableExists()) return 0;
        $st = $this->db->prepare(
            "SELECT COUNT(*) AS c FROM loyalty_events WHERE user_id = ? AND event_key IN ('order_paid','order_reverted')"
        );
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];
        return (int)($row['c'] ?? 0);
    }

    public function existsDedupeKey(string $dedupeKey): bool
    {
        if ($dedupeKey === '' || !$this->tableExists()) return false;
        $st = $this->db->prepare('SELECT 1 FROM loyalty_events WHERE dedupe_key = ? LIMIT 1');
        $st->execute([$dedupeKey]);
        return (bool)$st->fetchColumn();
    }

    /**
     * Devuelve un evento por dedupe_key.
     * @return array<string,mixed>|null
     */
    public function findByDedupeKey(string $dedupeKey): ?array
    {
        if ($dedupeKey === '' || !$this->tableExists()) return null;
        $st = $this->db->prepare('SELECT * FROM loyalty_events WHERE dedupe_key = ? LIMIT 1');
        $st->execute([$dedupeKey]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ?: null;
    }

    /**
     * Mapa order_id => SUM(points_delta) para eventos de pedido (order_paid).
     * @param int[] $orderIds
     * @return array<int,int>
     */
    public function sumPaidPointsByOrderIds(int $userId, array $orderIds): array
    {
        if ($userId <= 0 || !$this->tableExists()) return [];
        $ids = array_values(array_filter(array_map('intval', $orderIds), fn($id) => $id > 0));
        $ids = array_values(array_unique($ids));
        if (count($ids) === 0) return [];

        $in = implode(',', array_fill(0, count($ids), '?'));
        $sql = "SELECT order_id, COALESCE(SUM(points_delta),0) AS pts
                FROM loyalty_events
                WHERE user_id = ?
                  AND event_key = 'order_paid'
                  AND order_id IN ($in)
                GROUP BY order_id";

        $st = $this->db->prepare($sql);
        $st->execute(array_merge([$userId], $ids));
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $oid = (int)($r['order_id'] ?? 0);
            if ($oid > 0) $out[$oid] = (int)($r['pts'] ?? 0);
        }
        return $out;
    }

    /**
     * Borra un evento por dedupe_key (p/limpiezas controladas como snapshot).
     * @return int filas borradas
     */
    public function deleteByDedupeKey(string $dedupeKey): int
    {
        if ($dedupeKey === '' || !$this->tableExists()) return 0;
        $st = $this->db->prepare('DELETE FROM loyalty_events WHERE dedupe_key = ?');
        $st->execute([$dedupeKey]);
        return (int)$st->rowCount();
    }

    /**
     * Borra eventos de un usuario para un conjunto de event_key.
     * Útil para reconstrucciones controladas del ledger.
     * @param string[] $eventKeys
     */
    public function deleteByUserAndEventKeys(int $userId, array $eventKeys): int
    {
        if ($userId <= 0 || !$this->tableExists()) return 0;
        $keys = array_values(array_filter($eventKeys, fn($k) => is_string($k) && $k !== ''));
        $keys = array_values(array_unique($keys));
        if (count($keys) === 0) return 0;

        $in = implode(',', array_fill(0, count($keys), '?'));
        $sql = "DELETE FROM loyalty_events WHERE user_id = ? AND event_key IN ($in)";
        $st = $this->db->prepare($sql);
        $st->execute(array_merge([$userId], $keys));
        return (int)$st->rowCount();
    }

    /**
     * Inserta un evento de forma idempotente usando dedupe_key.
     */
    public function insertEvent(
        int $userId,
        string $dedupeKey,
        string $eventKey,
        int $pointsDelta,
        ?int $orderId = null,
        ?int $amountTotal = null,
        ?string $note = null,
        ?string $meta = null
    ): bool {
        if ($userId <= 0 || $dedupeKey === '' || $eventKey === '' || !$this->tableExists()) return false;

        $sql = "
            INSERT INTO loyalty_events (user_id, order_id, dedupe_key, event_key, points_delta, amount_total, note, meta)
            VALUES (:user_id, :order_id, :dedupe_key, :event_key, :points_delta, :amount_total, :note, :meta)
            ON DUPLICATE KEY UPDATE id = id
        ";

        $st = $this->db->prepare($sql);
        return $st->execute([
            ':user_id' => $userId,
            ':order_id' => $orderId,
            ':dedupe_key' => $dedupeKey,
            ':event_key' => $eventKey,
            ':points_delta' => $pointsDelta,
            ':amount_total' => $amountTotal,
            ':note' => $note,
            ':meta' => $meta,
        ]);
    }

    /**
     * Lista eventos (más recientes primero).
     *
     * @return array<int, array{id:int, created_at:?string, event_key:string, points_delta:int, order_id:?int, amount_total:?int, note:?string}>
     */
    public function listByUser(int $userId, int $limit = 25, int $offset = 0): array
    {
        if ($userId <= 0 || !$this->tableExists()) return [];
        $limit = max(1, min(100, $limit));
        $offset = max(0, $offset);

        $sql = "
            SELECT id, created_at, event_key, points_delta, order_id, amount_total, note
            FROM loyalty_events
            WHERE user_id = ?
            ORDER BY created_at DESC, id DESC
            LIMIT $limit OFFSET $offset
        ";

        $st = $this->db->prepare($sql);
        $st->execute([$userId]);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'id' => (int)($r['id'] ?? 0),
                'created_at' => $r['created_at'] ?? null,
                'event_key' => (string)($r['event_key'] ?? ''),
                'points_delta' => (int)($r['points_delta'] ?? 0),
                'order_id' => isset($r['order_id']) ? (int)$r['order_id'] : null,
                'amount_total' => isset($r['amount_total']) ? (int)$r['amount_total'] : null,
                'note' => $r['note'] ?? null,
            ];
        }
        return $out;
    }
}
