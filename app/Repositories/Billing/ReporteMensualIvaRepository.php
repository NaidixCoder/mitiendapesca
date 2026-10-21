<?php
namespace App\Repositories\Billing;

use PDO;

final class ReporteMensualIvaRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Resumen mensual por fecha de emisión (issued_at).
     *
     * @return array<int,array<string,mixed>>
     */
    public function monthlySummary(string $fromDate, string $toDate, string $arcaEnv = ''): array
    {
        $whereEnv = '';
        if ($arcaEnv !== '') {
            $whereEnv = " AND arca_env = :arca_env ";
        }

        $sql = "
            SELECT
              DATE_FORMAT(issued_at, '%Y-%m') AS ym,
              YEAR(issued_at) AS y,
              MONTH(issued_at) AS m,

              COUNT(*) AS comprobantes_count,

              SUM(CASE WHEN kind = 'invoice' THEN 1 ELSE 0 END) AS facturas_count,
              SUM(CASE WHEN kind = 'credit_note' THEN 1 ELSE 0 END) AS notas_credito_count,

              SUM(CASE WHEN kind = 'credit_note' THEN -imp_neto ELSE imp_neto END) AS neto,
              SUM(CASE WHEN kind = 'credit_note' THEN -imp_iva ELSE imp_iva END) AS iva,
              SUM(CASE WHEN kind = 'credit_note' THEN -imp_total ELSE imp_total END) AS total,

              -- Facturas por tipo (A/B/C)
              SUM(CASE WHEN kind = 'invoice' AND cbte_tipo = 1 THEN imp_neto ELSE 0 END) AS fa_neto,
              SUM(CASE WHEN kind = 'invoice' AND cbte_tipo = 1 THEN imp_iva  ELSE 0 END) AS fa_iva,
              SUM(CASE WHEN kind = 'invoice' AND cbte_tipo = 1 THEN imp_total ELSE 0 END) AS fa_total,
              SUM(CASE WHEN kind = 'invoice' AND cbte_tipo = 6 THEN imp_neto ELSE 0 END) AS fb_neto,
              SUM(CASE WHEN kind = 'invoice' AND cbte_tipo = 6 THEN imp_iva  ELSE 0 END) AS fb_iva,
              SUM(CASE WHEN kind = 'invoice' AND cbte_tipo = 6 THEN imp_total ELSE 0 END) AS fb_total,
              SUM(CASE WHEN kind = 'invoice' AND cbte_tipo = 11 THEN imp_neto ELSE 0 END) AS fc_neto,
              SUM(CASE WHEN kind = 'invoice' AND cbte_tipo = 11 THEN imp_iva  ELSE 0 END) AS fc_iva,
              SUM(CASE WHEN kind = 'invoice' AND cbte_tipo = 11 THEN imp_total ELSE 0 END) AS fc_total,

              -- Notas de crédito por tipo (A/B/C)
              SUM(CASE WHEN kind = 'credit_note' AND cbte_tipo = 3 THEN -imp_neto ELSE 0 END) AS nca_neto,
              SUM(CASE WHEN kind = 'credit_note' AND cbte_tipo = 3 THEN -imp_iva  ELSE 0 END) AS nca_iva,
              SUM(CASE WHEN kind = 'credit_note' AND cbte_tipo = 3 THEN -imp_total ELSE 0 END) AS nca_total,
              SUM(CASE WHEN kind = 'credit_note' AND cbte_tipo = 8 THEN -imp_neto ELSE 0 END) AS ncb_neto,
              SUM(CASE WHEN kind = 'credit_note' AND cbte_tipo = 8 THEN -imp_iva  ELSE 0 END) AS ncb_iva,
              SUM(CASE WHEN kind = 'credit_note' AND cbte_tipo = 8 THEN -imp_total ELSE 0 END) AS ncb_total,
              SUM(CASE WHEN kind = 'credit_note' AND cbte_tipo = 13 THEN -imp_neto ELSE 0 END) AS ncc_neto,
              SUM(CASE WHEN kind = 'credit_note' AND cbte_tipo = 13 THEN -imp_iva  ELSE 0 END) AS ncc_iva,
              SUM(CASE WHEN kind = 'credit_note' AND cbte_tipo = 13 THEN -imp_total ELSE 0 END) AS ncc_total

            FROM comprobantes_fiscales
            WHERE status = 'issued'
              AND issued_at IS NOT NULL
              AND issued_at >= :fromDate
              AND issued_at < :toDateNext
              {$whereEnv}
            GROUP BY ym, y, m
            ORDER BY ym DESC
        ";

        $stmt = $this->db->prepare($sql);

        $from = $fromDate . ' 00:00:00';
        // toDate is inclusive end date; use next day at 00:00 for < comparison
        $toNext = (new \DateTimeImmutable($toDate . ' 00:00:00'))->modify('+1 day')->format('Y-m-d H:i:s');

        $stmt->bindValue(':fromDate', $from);
        $stmt->bindValue(':toDateNext', $toNext);
        if ($arcaEnv !== '') {
            $stmt->bindValue(':arca_env', $arcaEnv);
        }
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
