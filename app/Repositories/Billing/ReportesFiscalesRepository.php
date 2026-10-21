<?php
namespace App\Repositories\Billing;

use PDO;

final class ReportesFiscalesRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Totales globales del período (solo comprobantes emitidos).
     *
     * @return array{invoices: array{count:int,total:int,neto:int,iva:int}, credit_notes: array{count:int,total:int,neto:int,iva:int}}
     */
    public function totalsIssued(string $fromDate, string $toDate, string $arcaEnv = ''): array
    {
        $from = $fromDate . ' 00:00:00';
        $to   = $toDate   . ' 23:59:59';

        // Importante: evitamos reutilizar el mismo placeholder nombrado dos veces en la misma query
        // (PDO puede lanzar "Invalid parameter number" si no está emulando prepares).
        $envSql = ($arcaEnv !== '') ? " AND arca_env = :env" : '';

        $sql = "SELECT kind,
                       COUNT(*) AS c,
                       COALESCE(SUM(imp_total),0) AS total,
                       COALESCE(SUM(imp_neto),0)  AS neto,
                       COALESCE(SUM(imp_iva),0)   AS iva
                FROM comprobantes_fiscales
                WHERE status='issued'
                  AND issued_at IS NOT NULL
                  AND issued_at BETWEEN :from AND :to
                  $envSql
                GROUP BY kind";

        $st = $this->db->prepare($sql);
        $params = [
            ':from' => $from,
            ':to'   => $to,
        ];
        if ($arcaEnv !== '') {
            $params[':env'] = $arcaEnv;
        }
        $st->execute($params);

        $out = [
            'invoices' => ['count' => 0, 'total' => 0, 'neto' => 0, 'iva' => 0],
            'credit_notes' => ['count' => 0, 'total' => 0, 'neto' => 0, 'iva' => 0],
        ];

        foreach (($st->fetchAll(PDO::FETCH_ASSOC) ?: []) as $r) {
            $kind = (string)($r['kind'] ?? '');
            $bucket = ($kind === 'credit_note') ? 'credit_notes' : 'invoices';
            $out[$bucket] = [
                'count' => (int)($r['c'] ?? 0),
                'total' => (int)($r['total'] ?? 0),
                'neto'  => (int)($r['neto'] ?? 0),
                'iva'   => (int)($r['iva'] ?? 0),
            ];
        }

        return $out;
    }

    /**
     * Serie por día o mes (solo emitidos).
     *
     * @return array<int,array{period:string,invoices_count:int,invoices_total:int,credit_notes_count:int,credit_notes_total:int}>
     */
    public function seriesIssued(string $fromDate, string $toDate, string $groupBy, string $arcaEnv = ''): array
    {
        $from = $fromDate . ' 00:00:00';
        $to   = $toDate   . ' 23:59:59';

        $envSql = ($arcaEnv !== '') ? " AND arca_env = :env" : '';

        $groupBy = ($groupBy === 'month') ? 'month' : 'day';
        $expr = ($groupBy === 'month')
            ? "DATE_FORMAT(issued_at, '%Y-%m')"
            : "DATE(issued_at)";

        $sql = "SELECT $expr AS period,
                       SUM(CASE WHEN kind='invoice' THEN 1 ELSE 0 END) AS invoices_count,
                       COALESCE(SUM(CASE WHEN kind='invoice' THEN imp_total ELSE 0 END),0) AS invoices_total,
                       SUM(CASE WHEN kind='credit_note' THEN 1 ELSE 0 END) AS credit_notes_count,
                       COALESCE(SUM(CASE WHEN kind='credit_note' THEN imp_total ELSE 0 END),0) AS credit_notes_total
                FROM comprobantes_fiscales
                WHERE status='issued'
                  AND issued_at IS NOT NULL
                  AND issued_at BETWEEN :from AND :to
                  $envSql
                GROUP BY period
                ORDER BY period ASC";

        $st = $this->db->prepare($sql);
        $params = [
            ':from' => $from,
            ':to'   => $to,
        ];
        if ($arcaEnv !== '') {
            $params[':env'] = $arcaEnv;
        }
        $st->execute($params);

        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'period' => (string)($r['period'] ?? ''),
                'invoices_count' => (int)($r['invoices_count'] ?? 0),
                'invoices_total' => (int)($r['invoices_total'] ?? 0),
                'credit_notes_count' => (int)($r['credit_notes_count'] ?? 0),
                'credit_notes_total' => (int)($r['credit_notes_total'] ?? 0),
            ];
        }
        return $out;
    }

    /**
     * Conciliación: pedidos que deberían tener factura emitida y no la tienen.
     *
     * @return array<int,array<string,mixed>>
     */
    public function ordersMissingInvoice(string $fromDate, string $toDate, string $arcaEnv = '', array $facturableStatuses = []): array
    {
        $from = $fromDate . ' 00:00:00';
        $to   = $toDate   . ' 23:59:59';

        $joinEnvSql = ($arcaEnv !== '') ? " AND cf.arca_env = :env" : '';

        // M3: configurable por Ajustes. Si viene vacío, cae a defaults prudentes.
        if (empty($facturableStatuses)) {
            $facturableStatuses = ['paid','processing','packed','shipped','delivered'];
        }

        // placeholders posicionales para evitar HY093
        $stPlaceholders = [];
        $stParams = [];
        foreach (array_values($facturableStatuses) as $i => $stVal) {
            $ph = ':st'.$i;
            $stPlaceholders[] = $ph;
            $stParams[$ph] = (string)$stVal;
        }

        $sql = "SELECT o.id AS order_id, o.created_at, o.status AS order_status, o.total,
                       o.customer_tax_condition, o.customer_cuit,
                       cf.id AS comprobante_id, cf.status AS cf_status
                FROM orders o
                LEFT JOIN comprobantes_fiscales cf
                  ON cf.order_id = o.id
                 AND cf.kind = 'invoice'
                 $joinEnvSql
                WHERE o.created_at BETWEEN :from AND :to
                  AND o.status IN (".implode(',', $stPlaceholders).")
                  AND (cf.id IS NULL OR cf.status <> 'issued')
                ORDER BY o.created_at DESC";

        $st = $this->db->prepare($sql);
        $params = [
            ':from' => $from,
            ':to'   => $to,
        ];
        $params = array_merge($params, $stParams);
        if ($arcaEnv !== '') {
            $params[':env'] = $arcaEnv;
        }
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Conciliación: comprobantes en pending/error en el período (created_at).
     *
     * @return array<int,array<string,mixed>>
     */
    public function comprobantesNotIssued(string $fromDate, string $toDate, string $arcaEnv = ''): array
    {
        $from = $fromDate . ' 00:00:00';
        $to   = $toDate   . ' 23:59:59';

        $envSql = ($arcaEnv !== '') ? " AND cf.arca_env = :env" : '';

        $sql = "SELECT cf.id, cf.order_id, cf.kind, cf.status, cf.arca_env, cf.pto_vta, cf.cbte_tipo, cf.cbte_nro,
                       cf.imp_total, cf.error_code, cf.error_message, cf.created_at, cf.updated_at
                FROM comprobantes_fiscales cf
                WHERE cf.created_at BETWEEN :from AND :to
                  AND cf.status IN ('pending','error')
                  $envSql
                ORDER BY cf.created_at DESC";

        $st = $this->db->prepare($sql);
        $params = [
            ':from' => $from,
            ':to'   => $to,
        ];
        if ($arcaEnv !== '') {
            $params[':env'] = $arcaEnv;
        }
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }


/**
 * Listado detallado de comprobantes emitidos (para drill-down y export).
 *
 * @return array<int,array<string,mixed>>
 */


    /**
     * Conciliación PRO (M2): desvíos pedidos ↔ comprobantes emitidos.
     * Detecta:
     *  - totales distintos (neto emitido != total pedido)
     *  - duplicados (más de 1 factura o más de 1 NC emitida)
     *  - mezcla de entornos (homo/prod) cuando no se filtra por env
     *
     * @return array<int,array<string,mixed>>
     */
    public function conciliationDeviations(string $fromDate, string $toDate, string $arcaEnv = ''): array
    {
        $from = $fromDate . ' 00:00:00';
        $to   = $toDate   . ' 23:59:59';

        $envSql = ($arcaEnv !== '') ? " AND cf.arca_env = :env" : '';
        $envHaving = ($arcaEnv === '') ? " OR env_min <> env_max" : '';

        // Conciliación PRO: universo por fecha de emisión (issued_at), no por created_at del pedido.
        $sql = "SELECT
                    o.id AS order_id,
                    o.created_at,
                    o.status AS order_status,
                    o.total AS order_total,

                    SUM(CASE WHEN cf.kind='invoice' THEN cf.imp_total ELSE 0 END) AS invoices_total,
                    SUM(CASE WHEN cf.kind='credit_note' THEN cf.imp_total ELSE 0 END) AS credit_total,

                    SUM(CASE WHEN cf.kind='invoice' THEN 1 ELSE 0 END) AS invoices_count,
                    SUM(CASE WHEN cf.kind='credit_note' THEN 1 ELSE 0 END) AS credit_count,

                    MIN(cf.arca_env) AS env_min,
                    MAX(cf.arca_env) AS env_max,

                    (SUM(CASE WHEN cf.kind='invoice' THEN cf.imp_total ELSE 0 END)
                     - SUM(CASE WHEN cf.kind='credit_note' THEN cf.imp_total ELSE 0 END)) AS net_issued,

                    ((SUM(CASE WHEN cf.kind='invoice' THEN cf.imp_total ELSE 0 END)
                     - SUM(CASE WHEN cf.kind='credit_note' THEN cf.imp_total ELSE 0 END)) - o.total) AS diff_total,

                    MAX(cf.issued_at) AS last_issued_at
                FROM comprobantes_fiscales cf
                JOIN orders o ON o.id = cf.order_id
                WHERE cf.status='issued'
                  AND cf.issued_at IS NOT NULL
                  AND cf.issued_at BETWEEN :from AND :to
                  $envSql
                GROUP BY o.id
                HAVING
                    invoices_count > 1
                    OR credit_count > 1
                    OR diff_total <> 0
                    $envHaving
                ORDER BY last_issued_at DESC
                LIMIT 200";

        $st = $this->db->prepare($sql);
        $params = [
            ':from' => $from,
            ':to'   => $to,
        ];
        if ($arcaEnv !== '') {
            $params[':env'] = $arcaEnv;
        }
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Conciliación PRO: duplicados exactos (mismo PV + tipo + nro).
     * @return array<int,array<string,mixed>>
     */
    public function duplicatesExactIssued(string $fromDate, string $toDate, string $arcaEnv = ''): array
    {
        $from = $fromDate . ' 00:00:00';
        $to   = $toDate   . ' 23:59:59';

        $envSql = ($arcaEnv !== '') ? " AND cf.arca_env = :env" : '';

        $sql = "SELECT
                    cf.arca_env,
                    cf.kind,
                    cf.pto_vta,
                    cf.cbte_tipo,
                    cf.cbte_nro,
                    COUNT(*) AS c,
                    MIN(cf.id) AS min_id,
                    MAX(cf.id) AS max_id,
                    MIN(cf.issued_at) AS first_issued_at,
                    MAX(cf.issued_at) AS last_issued_at,
                    COALESCE(SUM(cf.imp_total),0) AS total_sum
                FROM comprobantes_fiscales cf
                WHERE cf.status='issued'
                  AND cf.issued_at IS NOT NULL
                  AND cf.issued_at BETWEEN :from AND :to
                  $envSql
                GROUP BY cf.arca_env, cf.kind, cf.pto_vta, cf.cbte_tipo, cf.cbte_nro
                HAVING c > 1
                ORDER BY last_issued_at DESC
                LIMIT 200";

        $st = $this->db->prepare($sql);
        $params = [
            ':from' => $from,
            ':to'   => $to,
        ];
        if ($arcaEnv !== '') $params[':env'] = $arcaEnv;
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Conciliación PRO: pedidos NO facturables que igual tienen factura emitida en el período.
     * @return array<int,array<string,mixed>>
     */
    public function nonBillableOrdersWithInvoiceIssued(string $fromDate, string $toDate, string $arcaEnv = '', array $nonFacturableStatuses = []): array
    {
        $from = $fromDate . ' 00:00:00';
        $to   = $toDate   . ' 23:59:59';

        if (empty($nonFacturableStatuses)) {
            $nonFacturableStatuses = ['pending_payment','payment_failed','payment_expired','cancelled','chargeback','refunded'];
        }

        $stPlaceholders = [];
        $stParams = [];
        foreach (array_values($nonFacturableStatuses) as $i => $stVal) {
            $ph = ':nst'.$i;
            $stPlaceholders[] = $ph;
            $stParams[$ph] = (string)$stVal;
        }

        $envJoin = ($arcaEnv !== '') ? " AND cf.arca_env = :env" : '';

        $sql = "SELECT
                    o.id AS order_id,
                    o.created_at,
                    o.status AS order_status,
                    o.total AS order_total,
                    cf.id AS comprobante_id,
                    cf.issued_at,
                    cf.arca_env,
                    cf.pto_vta,
                    cf.cbte_tipo,
                    cf.cbte_nro,
                    cf.imp_total
                FROM orders o
                JOIN comprobantes_fiscales cf
                  ON cf.order_id = o.id
                 AND cf.status='issued'
                 AND cf.kind='invoice'
                 AND cf.issued_at IS NOT NULL
                 AND cf.issued_at BETWEEN :from AND :to
                 $envJoin
                WHERE o.status IN (".implode(',', $stPlaceholders).")
                ORDER BY cf.issued_at DESC
                LIMIT 200";

        $st = $this->db->prepare($sql);
        $params = [
            ':from' => $from,
            ':to'   => $to,
        ];
        $params = array_merge($params, $stParams);
        if ($arcaEnv !== '') $params[':env'] = $arcaEnv;
        $st->execute($params);

        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }



public function issuedComprobantesDetailed(string $fromDate, string $toDate, string $arcaEnv = '', string $kind = ''): array
{
    $from = $fromDate . ' 00:00:00';
    $to   = $toDate   . ' 23:59:59';

    $envSql = ($arcaEnv !== '') ? " AND cf.arca_env = :env" : '';
    $kindSql = ($kind !== '') ? " AND cf.kind = :kind" : '';

    $sql = "SELECT cf.id, cf.order_id, cf.kind, cf.status, cf.arca_env,
                   cf.pto_vta, cf.cbte_tipo, cf.cbte_nro,
                   cf.doc_tipo, cf.doc_nro,
                   cf.imp_total, cf.imp_neto, cf.imp_iva,
                   cf.cae, cf.cae_vto,
                   cf.related_comprobante_id,
                   cfo.pto_vta   AS original_pto_vta,
                   cfo.cbte_tipo AS original_cbte_tipo,
                   cfo.cbte_nro  AS original_cbte_nro,
                   cf.issued_at, cf.created_at, cf.updated_at
            FROM comprobantes_fiscales cf
            LEFT JOIN comprobantes_fiscales cfo ON cfo.id = cf.related_comprobante_id
            WHERE cf.status='issued'
              AND cf.issued_at IS NOT NULL
              AND cf.issued_at BETWEEN :from AND :to
              $envSql
              $kindSql
            ORDER BY cf.issued_at ASC, cf.id ASC";

    $st = $this->db->prepare($sql);
    $params = [
        ':from' => $from,
        ':to'   => $to,
    ];
    if ($arcaEnv !== '') $params[':env'] = $arcaEnv;
    if ($kind !== '') $params[':kind'] = $kind;
    $st->execute($params);

    return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
}

}
