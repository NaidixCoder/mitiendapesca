<?php
namespace App\Repositories\Billing;

use PDO;

final class PurchaseInvoiceRepository
{
    public function __construct(private PDO $db) {}

    public function list(array $filters = []): array
    {
        $from = trim((string)($filters['from'] ?? ''));
        $to   = trim((string)($filters['to'] ?? ''));
        $q    = trim((string)($filters['q'] ?? ''));
        $supplierId = (int)($filters['supplier_id'] ?? 0);
        $status = trim((string)($filters['status'] ?? ''));
        $kind = trim((string)($filters['kind'] ?? ''));

        $sql = "SELECT pi.*,
                       s.nombre AS supplier_nombre,
                       s.cuit   AS supplier_cuit
                FROM purchase_invoices pi
                INNER JOIN suppliers s ON s.id = pi.supplier_id
                WHERE 1=1";

        $params = [];

        if ($from !== '') {
            $sql .= " AND pi.issued_at >= :from";
            $params[':from'] = $from;
        }
        if ($to !== '') {
            $sql .= " AND pi.issued_at <= :to";
            $params[':to'] = $to;
        }
        if ($supplierId > 0) {
            $sql .= " AND pi.supplier_id = :supplier_id";
            $params[':supplier_id'] = $supplierId;
        }
        if ($status !== '') {
            $sql .= " AND pi.status = :status";
            $params[':status'] = $status;
        } else {
            // Por defecto no mostramos anuladas (void)
            $sql .= " AND pi.status <> 'void'";
        }
        if ($kind !== '') {
            $sql .= " AND pi.kind = :kind";
            $params[':kind'] = $kind;
        }
        if ($q !== '') {
            $sql .= " AND (s.nombre LIKE :q OR s.cuit LIKE :q OR pi.cbte_nro LIKE :q)";
            $params[':q'] = '%' . $q . '%';
        }

        $sql .= " ORDER BY pi.issued_at DESC, pi.id DESC LIMIT 500";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findById(int $id): ?array
    {
        $st = $this->db->prepare("SELECT pi.*, s.nombre AS supplier_nombre, s.cuit AS supplier_cuit
                                  FROM purchase_invoices pi
                                  INNER JOIN suppliers s ON s.id = pi.supplier_id
                                  WHERE pi.id = :id LIMIT 1");
        $st->execute([':id' => $id]);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ?: null;
    }


    /**
     * Devuelve un registro existente con el mismo documento (proveedor + tipo + pv + nro), si existe.
     * @return array{id:int,status:string}|null
     */
    public function findDuplicateDoc(int $supplierId, int $cbteTipo, int $ptoVta, int $cbteNro, ?int $excludeId = null): ?array
    {
        $sql = "SELECT id, status
                FROM purchase_invoices
                WHERE supplier_id = :supplier_id
                  AND cbte_tipo = :cbte_tipo
                  AND pto_vta = :pto_vta
                  AND cbte_nro = :cbte_nro";
        $params = [
            ':supplier_id' => $supplierId,
            ':cbte_tipo' => $cbteTipo,
            ':pto_vta' => $ptoVta,
            ':cbte_nro' => $cbteNro,
        ];
        if ($excludeId && $excludeId > 0) {
            $sql .= " AND id <> :exclude_id";
            $params[':exclude_id'] = $excludeId;
        }
        $sql .= " LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute($params);
        $r = $st->fetch(PDO::FETCH_ASSOC);
        return $r ? ['id' => (int)$r['id'], 'status' => (string)$r['status']] : null;
    }

    public function setStatusValidated(int $id): void
    {
        $st = $this->db->prepare("UPDATE purchase_invoices SET status='validated', updated_at=NOW() WHERE id=:id AND status <> 'void' LIMIT 1");
        $st->execute([':id' => $id]);
    }


    public function insert(array $data): int
    {
        $sql = "INSERT INTO purchase_invoices
                (supplier_id, kind, cbte_tipo, pto_vta, cbte_nro, issued_at, imp_neto, imp_iva, perc_iva, perc_iibb, ret_ganancias, imp_total, status, notes, created_at, updated_at)
                VALUES
                (:supplier_id, :kind, :cbte_tipo, :pto_vta, :cbte_nro, :issued_at, :imp_neto, :imp_iva, :perc_iva, :perc_iibb, :ret_ganancias, :imp_total, :status, :notes, NOW(), NOW())";
        $st = $this->db->prepare($sql);
        $st->execute([
            ':supplier_id' => (int)$data['supplier_id'],
            ':kind' => (string)$data['kind'],
            ':cbte_tipo' => (int)$data['cbte_tipo'],
            ':pto_vta' => (int)$data['pto_vta'],
            ':cbte_nro' => (int)$data['cbte_nro'],
            ':issued_at' => (string)$data['issued_at'],
            ':imp_neto' => (int)$data['imp_neto'],
            ':imp_iva' => (int)$data['imp_iva'],
            ':perc_iva' => (int)($data['perc_iva'] ?? 0),
            ':perc_iibb' => (int)($data['perc_iibb'] ?? 0),
            ':ret_ganancias' => (int)($data['ret_ganancias'] ?? 0),
            ':imp_total' => (int)$data['imp_total'],
            ':status' => (string)$data['status'],
            ':notes' => (string)($data['notes'] ?? ''),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $sql = "UPDATE purchase_invoices
                SET supplier_id=:supplier_id, kind=:kind, cbte_tipo=:cbte_tipo, pto_vta=:pto_vta, cbte_nro=:cbte_nro,
                    issued_at=:issued_at, imp_neto=:imp_neto, imp_iva=:imp_iva, perc_iva=:perc_iva, perc_iibb=:perc_iibb, ret_ganancias=:ret_ganancias, imp_total=:imp_total, status=:status,
                    notes=:notes, updated_at=NOW()
                WHERE id=:id LIMIT 1";
        $st = $this->db->prepare($sql);
        $st->execute([
            ':id' => $id,
            ':supplier_id' => (int)$data['supplier_id'],
            ':kind' => (string)$data['kind'],
            ':cbte_tipo' => (int)$data['cbte_tipo'],
            ':pto_vta' => (int)$data['pto_vta'],
            ':cbte_nro' => (int)$data['cbte_nro'],
            ':issued_at' => (string)$data['issued_at'],
            ':imp_neto' => (int)$data['imp_neto'],
            ':imp_iva' => (int)$data['imp_iva'],
            ':perc_iva' => (int)($data['perc_iva'] ?? 0),
            ':perc_iibb' => (int)($data['perc_iibb'] ?? 0),
            ':ret_ganancias' => (int)($data['ret_ganancias'] ?? 0),
            ':imp_total' => (int)$data['imp_total'],
            ':status' => (string)$data['status'],
            ':notes' => (string)($data['notes'] ?? ''),
        ]);
    }

    public function void(int $id): void
    {
        $st = $this->db->prepare("UPDATE purchase_invoices SET status='void', voided_at=NOW(), updated_at=NOW() WHERE id=:id LIMIT 1");
        $st->execute([':id' => $id]);
    }

    /** Libro IVA Compras (por emisión). Solo comprobantes validados o cargados (según filtros). */
    public function libroRows(string $from, string $to, array $filters = []): array
    {
        $supplierId = (int)($filters['supplier_id'] ?? 0);
        $status = trim((string)($filters['status'] ?? ''));
        $kind = trim((string)($filters['kind'] ?? ''));

        $sql = "SELECT pi.*,
                       s.nombre AS supplier_nombre,
                       s.cuit AS supplier_cuit
                FROM purchase_invoices pi
                INNER JOIN suppliers s ON s.id = pi.supplier_id
                WHERE pi.issued_at >= :from AND pi.issued_at <= :to";
        $params = [':from' => $from, ':to' => $to];

        if ($supplierId > 0) {
            $sql .= " AND pi.supplier_id = :supplier_id";
            $params[':supplier_id'] = $supplierId;
        }
        if ($status !== '') {
            $sql .= " AND pi.status = :status";
            $params[':status'] = $status;
        } else {
            $sql .= " AND pi.status <> 'void'";
        }
        if ($kind !== '') {
            $sql .= " AND pi.kind = :kind";
            $params[':kind'] = $kind;
        }

        $sql .= " ORDER BY pi.issued_at DESC, pi.id DESC";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function libroTotals(string $from, string $to, array $filters = []): array
    {
        $supplierId = (int)($filters['supplier_id'] ?? 0);
        $status = trim((string)($filters['status'] ?? ''));
        $kind = trim((string)($filters['kind'] ?? ''));

        $sql = "SELECT
                  SUM(CASE WHEN pi.kind='credit_note' THEN -pi.imp_neto ELSE pi.imp_neto END) AS neto,
                  SUM(CASE WHEN pi.kind='credit_note' THEN -pi.imp_iva ELSE pi.imp_iva END) AS iva,
                  SUM(CASE WHEN pi.kind='credit_note' THEN -pi.imp_total ELSE pi.imp_total END) AS total
                FROM purchase_invoices pi
                WHERE pi.issued_at >= :from AND pi.issued_at <= :to";
        $params = [':from' => $from, ':to' => $to];

        if ($supplierId > 0) {
            $sql .= " AND pi.supplier_id = :supplier_id";
            $params[':supplier_id'] = $supplierId;
        }
        if ($status !== '') {
            $sql .= " AND pi.status = :status";
            $params[':status'] = $status;
        } else {
            $sql .= " AND pi.status <> 'void'";
        }
        if ($kind !== '') {
            $sql .= " AND pi.kind = :kind";
            $params[':kind'] = $kind;
        }

        $st = $this->db->prepare($sql);
        $st->execute($params);
        $r = $st->fetch(PDO::FETCH_ASSOC) ?: ['neto'=>0,'iva'=>0,'total'=>0];
        return [
            'neto' => (int)($r['neto'] ?? 0),
            'iva' => (int)($r['iva'] ?? 0),
            'total' => (int)($r['total'] ?? 0),
        ];
    }

    public function libroTotalsByType(string $from, string $to, array $filters = []): array
    {
        $supplierId = (int)($filters['supplier_id'] ?? 0);
        $status = trim((string)($filters['status'] ?? ''));
        $sql = "SELECT pi.cbte_tipo,
                       pi.kind,
                       SUM(CASE WHEN pi.kind='credit_note' THEN -pi.imp_neto ELSE pi.imp_neto END) AS neto,
                       SUM(CASE WHEN pi.kind='credit_note' THEN -pi.imp_iva ELSE pi.imp_iva END) AS iva,
                       SUM(CASE WHEN pi.kind='credit_note' THEN -pi.imp_total ELSE pi.imp_total END) AS total
                FROM purchase_invoices pi
                WHERE pi.issued_at >= :from AND pi.issued_at <= :to";
        $params = [':from'=>$from,':to'=>$to];

        if ($supplierId > 0) { $sql .= " AND pi.supplier_id=:supplier_id"; $params[':supplier_id']=$supplierId; }
        if ($status !== '') { $sql .= " AND pi.status=:status"; $params[':status']=$status; }
        else { $sql .= " AND pi.status<>'void'"; }

        $sql .= " GROUP BY pi.cbte_tipo, pi.kind ORDER BY pi.cbte_tipo ASC";

        $st=$this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Resumen mensual (por issued_at) para IVA compras.
     * Las notas de crédito restan (signo negativo).
     *
     * @return array<int,array<string,mixed>>
     */
    public function monthlySummary(string $fromDate, string $toDate, array $filters = []): array
    {
        $supplierId = (int)($filters['supplier_id'] ?? 0);
        $status = trim((string)($filters['status'] ?? ''));
        $kind = trim((string)($filters['kind'] ?? ''));

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
              SUM(CASE WHEN kind = 'credit_note' THEN -perc_iva ELSE perc_iva END) AS perc_iva,
              SUM(CASE WHEN kind = 'credit_note' THEN -perc_iibb ELSE perc_iibb END) AS perc_iibb,
              SUM(CASE WHEN kind = 'credit_note' THEN -ret_ganancias ELSE ret_ganancias END) AS ret_ganancias,
              SUM(CASE WHEN kind = 'credit_note' THEN -imp_total ELSE imp_total END) AS total
            FROM purchase_invoices
            WHERE issued_at >= :from AND issued_at <= :to
        ";
        $params = [':from' => $fromDate, ':to' => $toDate];

        if ($supplierId > 0) {
            $sql .= " AND supplier_id = :supplier_id";
            $params[':supplier_id'] = $supplierId;
        }
        if ($status !== '') {
            $sql .= " AND status = :status";
            $params[':status'] = $status;
        } else {
            $sql .= " AND status <> 'void'";
        }
        if ($kind !== '') {
            $sql .= " AND kind = :kind";
            $params[':kind'] = $kind;
        }

        $sql .= " GROUP BY ym, y, m ORDER BY y ASC, m ASC";

        $st = $this->db->prepare($sql);
        $st->execute($params);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

}
