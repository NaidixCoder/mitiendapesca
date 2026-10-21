<?php
namespace App\Repositories\Account;

use PDO;

/**
 * CustomerStatsRepository (Cuenta)
 * - Agregados por usuario para fidelidad / performance.
 * - NO contiene lógica de negocio de tiers/puntos (eso vive en el Service).
 */
class CustomerStatsRepository
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function tableExists(string $table): bool
    {
        // Evitamos SHOW TABLES porque puede fallar por permisos (p.ej. hosting
        // restringido) aunque el SELECT funcione. Usamos un SELECT liviano.
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $table)) return false;

        try {
            $this->db->query("SELECT 1 FROM `{$table}` LIMIT 1");
            return true;
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Devuelve agregados de compras "pagadas" para un usuario.
     * @return array{paid_orders_count:int, paid_total:int, first_paid_at:?string, last_paid_at:?string}
     */
    public function aggregatePaidOrders(int $userId, array $paidStatuses): array
    {
        if ($userId <= 0) {
            return ['paid_orders_count'=>0,'paid_total'=>0,'first_paid_at'=>null,'last_paid_at'=>null];
        }

        if (!$this->tableExists('orders')) {
            return ['paid_orders_count'=>0,'paid_total'=>0,'first_paid_at'=>null,'last_paid_at'=>null];
        }

        $paidStatuses = array_values(array_filter($paidStatuses, fn($s) => is_string($s) && $s !== ''));
        if (count($paidStatuses) === 0) {
            return ['paid_orders_count'=>0,'paid_total'=>0,'first_paid_at'=>null,'last_paid_at'=>null];
        }

        $in = implode(',', array_fill(0, count($paidStatuses), '?'));
        $sql = "
            SELECT
              COUNT(*) AS paid_orders_count,
              COALESCE(SUM(o.total), 0) AS paid_total,
              MIN(o.created_at) AS first_paid_at,
              MAX(o.created_at) AS last_paid_at
            FROM orders o
            WHERE o.user_id = ?
              AND o.status IN ($in)
        ";

        $st = $this->db->prepare($sql);
        $params = array_merge([$userId], $paidStatuses);
        $st->execute($params);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: [];

        return [
            'paid_orders_count' => (int)($row['paid_orders_count'] ?? 0),
            'paid_total'        => (int)($row['paid_total'] ?? 0),
            'first_paid_at'     => $row['first_paid_at'] ?? null,
            'last_paid_at'      => $row['last_paid_at'] ?? null,
        ];
    }

    /**
     * Upsert de customer_stats.
     * @param array{paid_orders_count:int, paid_total:int, first_paid_at:?string, last_paid_at:?string, avg_paid_total:int, loyalty_points:int, loyalty_tier:string} $data
     */
    public function upsertForUser(int $userId, array $data): void
    {
        if ($userId <= 0) return;
        if (!$this->tableExists('customer_stats')) return;

        $st = $this->db->prepare("
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

        $st->execute([
            ':uid'  => $userId,
            ':c'    => (int)($data['paid_orders_count'] ?? 0),
            ':t'    => (int)($data['paid_total'] ?? 0),
            ':f'    => $data['first_paid_at'] ?? null,
            ':l'    => $data['last_paid_at'] ?? null,
            ':a'    => (int)($data['avg_paid_total'] ?? 0),
            ':p'    => (int)($data['loyalty_points'] ?? 0),
            ':tier' => (string)($data['loyalty_tier'] ?? 'Bronce'),
        ]);
    }

    /**
     * Lee customer_stats (si existe).
     */
    public function findByUser(int $userId): ?array
    {
        if ($userId <= 0) return null;
        if (!$this->tableExists('customer_stats')) return null;

        $st = $this->db->prepare('SELECT * FROM customer_stats WHERE user_id = ? LIMIT 1');
        $st->execute([$userId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ?: null;
    }
}
