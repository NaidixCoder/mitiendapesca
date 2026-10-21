<?php
namespace App\Repositories\Accounting;

use PDO;

final class AccountingClosureRepository
{
    public function __construct(private PDO $db) {}

    public function findByPeriod(string $period): ?array
    {
        $st = $this->db->prepare("SELECT * FROM accounting_closures WHERE period = :p LIMIT 1");
        $st->execute([':p' => $period]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * @param array<int,string> $periods
     * @return array<string,array<string,mixed>> Map period => row
     */
    public function findByPeriods(array $periods): array
    {
        $periods = array_values(array_unique(array_filter(array_map('strval', $periods))));
        if (!$periods) return [];

        $in = implode(',', array_fill(0, count($periods), '?'));
        $st = $this->db->prepare("SELECT * FROM accounting_closures WHERE period IN ($in)");
        $st->execute($periods);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $out = [];
        foreach ($rows as $r) {
            $p = (string)($r['period'] ?? '');
            if ($p !== '') $out[$p] = $r;
        }
        return $out;
    }

    /**
     * @param array<string,mixed> $data
     */
    public function create(array $data): int
    {
        $st = $this->db->prepare("
            INSERT INTO accounting_closures
            (period, iva_ventas, iva_compras, saldo_iva, ventas_json, compras_json, closed_by_user_id, closed_at, created_at)
            VALUES
            (:period, :iva_ventas, :iva_compras, :saldo_iva, :ventas_json, :compras_json, :closed_by_user_id, :closed_at, :created_at)
        ");
        $st->execute([
            ':period' => (string)($data['period'] ?? ''),
            ':iva_ventas' => (int)($data['iva_ventas'] ?? 0),
            ':iva_compras' => (int)($data['iva_compras'] ?? 0),
            ':saldo_iva' => (int)($data['saldo_iva'] ?? 0),
            ':ventas_json' => $data['ventas_json'] ?? null,
            ':compras_json' => $data['compras_json'] ?? null,
            ':closed_by_user_id' => (int)($data['closed_by_user_id'] ?? 0),
            ':closed_at' => (string)($data['closed_at'] ?? ''),
            ':created_at' => (string)($data['created_at'] ?? null),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function listLatest(int $limit = 36): array
    {
        $limit = max(1, min(120, $limit));
        $st = $this->db->query("SELECT * FROM accounting_closures ORDER BY period DESC LIMIT " . (int)$limit);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
