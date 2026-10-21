<?php
namespace App\Repositories\Catalog;

use PDO;

final class CurrencyRepository
{
    public function __construct(private PDO $db) {}

    /**
     * @return array<string, array{id:int,code:string,name:string,rate_to_ars:float,is_base:int}>
     */
    public function mapActiveWithCurrentRateByCode(): array
    {
        // Latest rate per currency (<= now). We store DB timestamps in UTC.
        $sql = "
            SELECT c.id, c.code, c.name, c.is_base,
                   COALESCE(r.rate_to_ars, 1.0) AS rate_to_ars
            FROM currencies c
            LEFT JOIN (
                SELECT cr.currency_id, cr.rate_to_ars
                FROM currency_rates cr
                JOIN (
                    SELECT currency_id, MAX(effective_from) AS mx
                    FROM currency_rates
                    WHERE effective_from <= UTC_TIMESTAMP()
                    GROUP BY currency_id
                ) t ON t.currency_id = cr.currency_id AND t.mx = cr.effective_from
            ) r ON r.currency_id = c.id
            WHERE c.is_active = 1
        ";
        $st = $this->db->query($sql);
        $rows = $st ? $st->fetchAll(PDO::FETCH_ASSOC) : [];
        $out = [];
        foreach ($rows as $row) {
            $code = (string)$row['code'];
            $out[$code] = [
                'id' => (int)$row['id'],
                'code' => $code,
                'name' => (string)$row['name'],
                'is_base' => (int)$row['is_base'],
                'rate_to_ars' => (float)$row['rate_to_ars'],
            ];
        }
        return $out;
    }

    public function findIdByCode(string $code): ?int
    {
        $code = trim($code);
        if ($code === '') return null;
        $st = $this->db->prepare("SELECT id FROM currencies WHERE code = ? AND is_active = 1 LIMIT 1");
        $st->execute([$code]);
        $id = $st->fetchColumn();
        return $id ? (int)$id : null;
    }
}

