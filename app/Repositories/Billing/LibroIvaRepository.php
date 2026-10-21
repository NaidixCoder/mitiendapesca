<?php
namespace App\Repositories\Billing;

use PDO;

final class LibroIvaRepository
{
    public function __construct(private PDO $db) {}

    /**
     * @return array<int,array<string,mixed>>
     */
    public function listIssuedByEmission(string $fromDate, string $toDate, string $arcaEnv = '', string $kind = ''): array
    {
        $from = $fromDate . ' 00:00:00';
        $to   = $toDate   . ' 23:59:59';

        $envSql  = ($arcaEnv !== '') ? " AND cf.arca_env = :env" : '';
        $kindSql = ($kind !== '' && in_array($kind, ['invoice','credit_note'], true)) ? " AND cf.kind = :kind" : '';

        $sql = "SELECT
                    cf.id,
                    cf.order_id,
                    cf.kind,
                    cf.status,
                    cf.arca_env,
                    cf.pto_vta,
                    cf.cbte_tipo,
                    cf.cbte_nro,
                    cf.doc_tipo,
                    cf.doc_nro,
                    cf.imp_total,
                    cf.imp_neto,
                    cf.imp_iva,
                    cf.cae,
                    cf.cae_vto,
                    cf.issued_at
                FROM comprobantes_fiscales cf
                WHERE cf.status='issued'
                  AND cf.issued_at IS NOT NULL
                  AND cf.issued_at BETWEEN :from AND :to
                  $envSql
                  $kindSql
                ORDER BY cf.issued_at ASC, cf.id ASC";

        $st = $this->db->prepare($sql);
        $params = [':from' => $from, ':to' => $to];
        if ($arcaEnv !== '') $params[':env'] = $arcaEnv;
        if ($kindSql !== '') $params[':kind'] = $kind;
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Totales por tipo de comprobante (cbte_tipo) + kind.
     *
     * @return array<int,array{kind:string,cbte_tipo:int,count:int,total:int,neto:int,iva:int}>
     */
    public function totalsByType(string $fromDate, string $toDate, string $arcaEnv = ''): array
    {
        $from = $fromDate . ' 00:00:00';
        $to   = $toDate   . ' 23:59:59';

        $envSql = ($arcaEnv !== '') ? " AND arca_env = :env" : '';

        $sql = "SELECT
                    kind,
                    cbte_tipo,
                    COUNT(*) AS c,
                    COALESCE(SUM(imp_total),0) AS total,
                    COALESCE(SUM(imp_neto),0)  AS neto,
                    COALESCE(SUM(imp_iva),0)   AS iva
                FROM comprobantes_fiscales
                WHERE status='issued'
                  AND issued_at IS NOT NULL
                  AND issued_at BETWEEN :from AND :to
                  $envSql
                GROUP BY kind, cbte_tipo
                ORDER BY kind ASC, cbte_tipo ASC";

        $st = $this->db->prepare($sql);
        $params = [':from' => $from, ':to' => $to];
        if ($arcaEnv !== '') $params[':env'] = $arcaEnv;
        $st->execute($params);

        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'kind' => (string)($r['kind'] ?? ''),
                'cbte_tipo' => (int)($r['cbte_tipo'] ?? 0),
                'count' => (int)($r['c'] ?? 0),
                'total' => (int)($r['total'] ?? 0),
                'neto' => (int)($r['neto'] ?? 0),
                'iva' => (int)($r['iva'] ?? 0),
            ];
        }
        return $out;
    }
}
