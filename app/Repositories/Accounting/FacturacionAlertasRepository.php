<?php
namespace App\Repositories\Accounting;

use PDO;
use Throwable;

/**
 * Queries simples para alertas operativas (sin lógica contable).
 */
final class FacturacionAlertasRepository
{
    public function __construct(private PDO $db) {}

    /**
     * Compras por estado (loaded/validated). Excluye void.
     * @return array<int, array<string,mixed>>
     */
    public function listComprasByStatus(string $status, int $limit = 200): array
    {
        $limit = max(1, min(500, $limit));
        $st = $this->db->prepare(
            "SELECT pi.*, s.nombre AS supplier_nombre, s.cuit AS supplier_cuit
             FROM purchase_invoices pi
             INNER JOIN suppliers s ON s.id = pi.supplier_id
             WHERE pi.status = :status
             ORDER BY pi.issued_at DESC, pi.id DESC
             LIMIT {$limit}"
        );
        $st->execute([':status' => $status]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Últimos meses con movimientos (ventas+compras) en formato YYYY-MM.
     * @return string[]
     */
    public function listPeriodsWithMovements(int $limit = 36): array
    {
        $limit = max(1, min(120, $limit));

        // Ventas: comprobantes emitidos
        $sql = "(
            SELECT DISTINCT DATE_FORMAT(issued_at, '%Y-%m') AS period
            FROM comprobantes_fiscales
            WHERE status = 'issued' AND issued_at IS NOT NULL
        )
        UNION
        (
            SELECT DISTINCT DATE_FORMAT(issued_at, '%Y-%m') AS period
            FROM purchase_invoices
            WHERE status <> 'void' AND issued_at IS NOT NULL
        )
        ORDER BY period DESC
        LIMIT {$limit}";

        $st = $this->db->query($sql);
        $rows = $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        $out = [];
        foreach ($rows as $r) {
            $p = (string)($r['period'] ?? '');
            if ($p !== '') $out[] = $p;
        }
        return $out;
    }

    /** @return string[] */
    public function listClosedPeriods(int $limit = 120): array
    {
        $limit = max(1, min(500, $limit));
        try {
            $st = $this->db->query("SELECT period FROM accounting_closures ORDER BY period DESC LIMIT {$limit}");
            $rows = $st ? ($st->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];
        } catch (Throwable $e) {
            // Compat: en entornos sin migración de cierres todavía aplicada.
            $rows = [];
        }
        $out = [];
        foreach ($rows as $r) {
            $p = (string)($r['period'] ?? '');
            if ($p !== '') $out[] = $p;
        }
        return $out;
    }
}
