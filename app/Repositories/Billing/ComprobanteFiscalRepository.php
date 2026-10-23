<?php
namespace App\Repositories\Billing;

use PDO;
use Throwable;

final class ComprobanteFiscalRepository
{
    public function __construct(private PDO $db) {}

    private static ?bool $tableExists = null;
    private static ?bool $schemaOk = null;
    private static ?bool $schemaHasKind = null;

    public function tableExists(): bool
    {
        if (self::$tableExists !== null) return self::$tableExists;
        $st = $this->db->prepare("SELECT COUNT(*) FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'comprobantes_fiscales'");
        $st->execute();
        self::$tableExists = ((int)$st->fetchColumn() > 0);
        return self::$tableExists;
    }

    /**
     * Este repo espera el schema nuevo (ARCA WSFE): status/arca_env/pto_vta/cbte_tipo/doc_tipo/etc.
     * Si el usuario tiene una tabla vieja (pre-ARCA), evitamos crashear y hacemos fail-closed.
     */
    public function schemaOk(): bool
    {
        if (self::$schemaOk !== null) return self::$schemaOk;
        if (!$this->tableExists()) return self::$schemaOk = false;

        $required = [
            'status','arca_env','pto_vta','cbte_tipo','doc_tipo','doc_nro',
            'imp_total','imp_neto','imp_iva','request_json','created_at','updated_at'
        ];

        $st = $this->db->query("SHOW COLUMNS FROM comprobantes_fiscales");
        $cols = $st ? $st->fetchAll(PDO::FETCH_COLUMN) : [];
        $have = array_fill_keys(array_map('strval', $cols), true);

        foreach ($required as $c) {
            if (!isset($have[$c])) {
                return self::$schemaOk = false;
            }
        }
        return self::$schemaOk = true;
    }

    
    public function schemaHasIssuedAt(): bool
    {
        try {
            $st = $this->db->query("SHOW COLUMNS FROM comprobantes_fiscales");
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                if (strtolower((string)($r['Field'] ?? '')) === 'issued_at') return true;
            }
        } catch (\Throwable $e) {}
        return false;
    }

public function schemaHasEmailTracking(): bool
    {
        // Columnas agregadas en 20260128_01_comprobantes_fiscales_email.sql
        try {
            $st = $this->db->query("SHOW COLUMNS FROM comprobantes_fiscales");
            $cols = [];
            while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
                $cols[strtolower((string)($r['Field'] ?? ''))] = true;
            }
            return isset($cols['email_sent_at']);
        } catch (Throwable $e) {
            return false;
        }
    }

    public function markEmailSent(int $id, string $to): void
    {
        if (!$this->schemaHasEmailTracking()) return;
        $st = $this->db->prepare("UPDATE comprobantes_fiscales SET email_to=:to, email_sent_at=NOW(), email_error=NULL WHERE id=:id");
        $st->execute([':to' => $to, ':id' => $id]);
    }

    public function markEmailError(int $id, string $error): void
    {
        if (!$this->schemaHasEmailTracking()) return;
        $msg = mb_substr($error, 0, 255);
        $st = $this->db->prepare("UPDATE comprobantes_fiscales SET email_error=:e WHERE id=:id");
        $st->execute([':e' => $msg, ':id' => $id]);
    }

    public function updateRequestJson(int $id, array $request): void
    {
        if ($id <= 0 || !$this->schemaOk()) return;
        $json = json_encode($request, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $st = $this->db->prepare("UPDATE comprobantes_fiscales SET request_json=:r WHERE id=:id");
        $st->execute([':r' => $json, ':id' => $id]);
    }

    public function schemaHasKind(): bool
    {
        if (self::$schemaHasKind !== null) return self::$schemaHasKind;
        if (!$this->schemaOk()) return self::$schemaHasKind = false;

        $st = $this->db->query("SHOW COLUMNS FROM comprobantes_fiscales");
        $cols = $st ? $st->fetchAll(PDO::FETCH_COLUMN) : [];
        $have = array_fill_keys(array_map('strval', $cols), true);
        return self::$schemaHasKind = (isset($have['kind']) && isset($have['related_comprobante_id']));
    }

    /** @return array<string,mixed>|null */
    public function findByOrderId(int $orderId): ?array
    {
        if ($orderId <= 0 || !$this->schemaOk()) return null;
        if ($this->schemaHasKind()) {
            $st = $this->db->prepare("SELECT * FROM comprobantes_fiscales WHERE order_id = ? AND kind='invoice' ORDER BY id DESC LIMIT 1");
            $st->execute([$orderId]);
            $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            return $row ?: null;
        }

        // Schema legacy (una fila por pedido)
        $st = $this->db->prepare("SELECT * FROM comprobantes_fiscales WHERE order_id = ? ORDER BY id DESC LIMIT 1");
        $st->execute([$orderId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ?: null;
    }

/**
 * Devuelve la "factura a descargar" para un pedido.
 * Regla: si existe Factura A emitida => A; si no => Factura B emitida; si no => última invoice emitida.
 * Nota: en schema legacy (una fila por pedido) devuelve lo que haya.
 */
public function findPreferredInvoiceForOrder(int $orderId): ?array
{
    if ($orderId <= 0 || !$this->schemaOk()) return null;

    // Schema moderno con múltiples comprobantes por pedido
    if ($this->schemaHasKind()) {
        // Preferir A (cbte_tipo=1)
        $st = $this->db->prepare("SELECT * FROM comprobantes_fiscales WHERE order_id = ? AND kind='invoice' AND status='issued' AND cbte_tipo = 1 ORDER BY id DESC LIMIT 1");
        $st->execute([$orderId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($row) return $row;

        // Luego B (cbte_tipo=6)
        $st = $this->db->prepare("SELECT * FROM comprobantes_fiscales WHERE order_id = ? AND kind='invoice' AND status='issued' AND cbte_tipo = 6 ORDER BY id DESC LIMIT 1");
        $st->execute([$orderId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($row) return $row;

        // Fallback: última invoice emitida
        $st = $this->db->prepare("SELECT * FROM comprobantes_fiscales WHERE order_id = ? AND kind='invoice' AND status='issued' ORDER BY id DESC LIMIT 1");
        $st->execute([$orderId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ?: null;
    }

    // Legacy: una sola fila por pedido
    $row = $this->findByOrderId($orderId);
    if (!$row) return null;
    if ((string)($row['status'] ?? '') !== 'issued') return null;
    return $row;
}

    /** @return array<int,array<string,mixed>> */
    public function listByOrderId(int $orderId): array
    {
        if ($orderId <= 0 || !$this->schemaOk()) return [];

        if (!$this->schemaHasKind()) {
            $one = $this->findByOrderId($orderId);
            return $one ? [$one] : [];
        }

        $st = $this->db->prepare("SELECT * FROM comprobantes_fiscales WHERE order_id = ? ORDER BY id DESC");
        $st->execute([$orderId]);
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /** @param array<string,mixed> $data */
    public function upsertPendingForOrder(int $orderId, array $data): int
    {
        if ($orderId <= 0 || !$this->schemaOk()) return 0;

        // Si existe factura (kind=invoice), la actualizamos (y reseteamos estado si estaba error)
        $existing = $this->findByOrderId($orderId);
        if ($existing) {
            if ((string)($existing['status'] ?? '') === 'issued') {
                throw new \RuntimeException('La factura ya está emitida y no puede volver a estado pendiente.');
            }
            $id = (int)$existing['id'];
            $st = $this->db->prepare("UPDATE comprobantes_fiscales
                SET status='pending', arca_env=:arca_env, pto_vta=:pto_vta, cbte_tipo=:cbte_tipo, concepto=:concepto,
                    doc_tipo=:doc_tipo, doc_nro=:doc_nro, imp_total=:imp_total, imp_neto=:imp_neto, imp_iva=:imp_iva,
                    moneda_id=:moneda_id, moneda_ctz=:moneda_ctz,
                    request_json=:request_json, response_json=NULL,
                    error_code=NULL, error_message=NULL, issued_at=NULL
                WHERE id=:id AND status <> 'issued'");
            $st->execute([
                ':id' => $id,
                ':arca_env' => $data['arca_env'],
                ':pto_vta' => (int)$data['pto_vta'],
                ':cbte_tipo' => (int)$data['cbte_tipo'],
                ':concepto' => (int)($data['concepto'] ?? 1),
                ':doc_tipo' => (int)$data['doc_tipo'],
                ':doc_nro' => (int)$data['doc_nro'],
                ':imp_total' => (int)$data['imp_total'],
                ':imp_neto' => (int)$data['imp_neto'],
                ':imp_iva' => (int)$data['imp_iva'],
                ':moneda_id' => (string)($data['moneda_id'] ?? 'PES'),
                ':moneda_ctz' => (string)($data['moneda_ctz'] ?? '1.0000'),
                ':request_json' => $data['request_json'] ?? null,
            ]);
            if ($st->rowCount() === 0) {
                $latest = $this->findByOrderId($orderId);
                if ((string)($latest['status'] ?? '') === 'issued') {
                    throw new \RuntimeException('La factura fue emitida por otra operación concurrente.');
                }
            }
            return $id;
        }

        $st = $this->db->prepare("INSERT INTO comprobantes_fiscales
            (order_id" . ($this->schemaHasKind() ? ",kind,related_comprobante_id" : "") . ",status,arca_env,pto_vta,cbte_tipo,concepto,doc_tipo,doc_nro,imp_total,imp_neto,imp_iva,moneda_id,moneda_ctz,request_json,created_at,updated_at)
            VALUES
            (:order_id" . ($this->schemaHasKind() ? ",:kind,:related" : "") . ",'pending',:arca_env,:pto_vta,:cbte_tipo,:concepto,:doc_tipo,:doc_nro,:imp_total,:imp_neto,:imp_iva,:moneda_id,:moneda_ctz,:request_json,NOW(),NOW())");
        $params = [
            ':order_id' => $orderId,
            ':arca_env' => $data['arca_env'],
            ':pto_vta' => (int)$data['pto_vta'],
            ':cbte_tipo' => (int)$data['cbte_tipo'],
            ':concepto' => (int)($data['concepto'] ?? 1),
            ':doc_tipo' => (int)$data['doc_tipo'],
            ':doc_nro' => (int)$data['doc_nro'],
            ':imp_total' => (int)$data['imp_total'],
            ':imp_neto' => (int)$data['imp_neto'],
            ':imp_iva' => (int)$data['imp_iva'],
            ':moneda_id' => (string)($data['moneda_id'] ?? 'PES'),
            ':moneda_ctz' => (string)($data['moneda_ctz'] ?? '1.0000'),
            ':request_json' => $data['request_json'] ?? null,
        ];
        if ($this->schemaHasKind()) {
            $params[':kind'] = 'invoice';
            $params[':related'] = null;
        }
        $st->execute($params);

        return (int)$this->db->lastInsertId();
    }

    /**
     * Inserta una nueva factura (kind=invoice) sin pisar facturas previas.
     *
     * Se usa cuando el pedido ya tiene una Factura B emitida y, por solicitud post-compra,
     * se debe emitir una Factura A (manteniendo trazabilidad).
     *
     * @param array<string,mixed> $data
     */
    public function insertPendingInvoiceForOrder(int $orderId, ?int $relatedComprobanteId, array $data): int
    {
        if ($orderId <= 0 || !$this->schemaOk() || !$this->schemaHasKind()) return 0;

        $st = $this->db->prepare("INSERT INTO comprobantes_fiscales
            (order_id,kind,related_comprobante_id,status,arca_env,pto_vta,cbte_tipo,concepto,doc_tipo,doc_nro,imp_total,imp_neto,imp_iva,moneda_id,moneda_ctz,request_json,created_at,updated_at)
            VALUES
            (:order_id,'invoice',:related,'pending',:arca_env,:pto_vta,:cbte_tipo,:concepto,:doc_tipo,:doc_nro,:imp_total,:imp_neto,:imp_iva,:moneda_id,:moneda_ctz,:request_json,NOW(),NOW())");

        $st->execute([
            ':order_id' => $orderId,
            ':related' => $relatedComprobanteId,
            ':arca_env' => $data['arca_env'],
            ':pto_vta' => (int)$data['pto_vta'],
            ':cbte_tipo' => (int)$data['cbte_tipo'],
            ':concepto' => (int)($data['concepto'] ?? 1),
            ':doc_tipo' => (int)$data['doc_tipo'],
            ':doc_nro' => (int)$data['doc_nro'],
            ':imp_total' => (int)$data['imp_total'],
            ':imp_neto' => (int)$data['imp_neto'],
            ':imp_iva' => (int)$data['imp_iva'],
            ':moneda_id' => (string)($data['moneda_id'] ?? 'PES'),
            ':moneda_ctz' => (string)($data['moneda_ctz'] ?? '1.0000'),
            ':request_json' => $data['request_json'] ?? null,
        ]);

        return (int)$this->db->lastInsertId();
    }

    /** @param array<string,mixed> $data */
    public function insertPendingCreditNoteForOrder(int $orderId, int $relatedComprobanteId, array $data): int
    {
        if ($orderId <= 0 || !$this->schemaOk() || !$this->schemaHasKind()) return 0;

        // Idempotencia: si ya existe una NC para este pedido, devolvemos ese id.
        $q = $this->db->prepare("SELECT id FROM comprobantes_fiscales WHERE order_id = ? AND kind = 'credit_note' LIMIT 1");
        $q->execute([$orderId]);
        $existing = (int)($q->fetchColumn() ?: 0);
        if ($existing > 0) return $existing;

        $st = $this->db->prepare("INSERT INTO comprobantes_fiscales
            (order_id,kind,related_comprobante_id,status,arca_env,pto_vta,cbte_tipo,concepto,doc_tipo,doc_nro,imp_total,imp_neto,imp_iva,moneda_id,moneda_ctz,request_json,created_at,updated_at)
            VALUES
            (:order_id,'credit_note',:related,'pending',:arca_env,:pto_vta,:cbte_tipo,:concepto,:doc_tipo,:doc_nro,:imp_total,:imp_neto,:imp_iva,:moneda_id,:moneda_ctz,:request_json,NOW(),NOW())");
        $st->execute([
            ':order_id' => $orderId,
            ':related' => $relatedComprobanteId,
            ':arca_env' => $data['arca_env'],
            ':pto_vta' => (int)$data['pto_vta'],
            ':cbte_tipo' => (int)$data['cbte_tipo'],
            ':concepto' => (int)($data['concepto'] ?? 1),
            ':doc_tipo' => (int)$data['doc_tipo'],
            ':doc_nro' => (int)$data['doc_nro'],
            ':imp_total' => (int)$data['imp_total'],
            ':imp_neto' => (int)$data['imp_neto'],
            ':imp_iva' => (int)$data['imp_iva'],
            ':moneda_id' => (string)($data['moneda_id'] ?? 'PES'),
            ':moneda_ctz' => (int)($data['moneda_ctz'] ?? 1),
            ':request_json' => (string)($data['request_json'] ?? '{}'),
        ]);

        return (int)$this->db->lastInsertId();
    }

    /** @param array<string,mixed> $resp */
    public function markIssued(int $id, array $resp): void
    {
        if ($id <= 0 || !$this->schemaOk()) return;
        $st = $this->db->prepare("UPDATE comprobantes_fiscales
            SET status='issued', cbte_nro=:cbte_nro, cae=:cae, cae_vto=:cae_vto, resultado=:resultado,
                observaciones_json=:observaciones_json, response_json=:response_json,
                error_code=NULL, error_message=NULL, issued_at=NOW()
            WHERE id=:id");
        $st->execute([
            ':id' => $id,
            ':cbte_nro' => isset($resp['cbte_nro']) ? (int)$resp['cbte_nro'] : null,
            ':cae' => $resp['cae'] ?? null,
            ':cae_vto' => $resp['cae_vto'] ?? null,
            ':resultado' => $resp['resultado'] ?? null,
            ':observaciones_json' => $resp['observaciones_json'] ?? null,
            ':response_json' => $resp['response_json'] ?? null,
        ]);
    }

    public function markError(int $id, string $code, string $message, ?string $responseJson = null): void
    {
        if ($id <= 0 || !$this->schemaOk()) return;
        $message = substr($message, 0, 255);
        $st = $this->db->prepare("UPDATE comprobantes_fiscales
            SET status='error', error_code=:c, error_message=:m, response_json=:r
            WHERE id=:id");
        $st->execute([':id'=>$id,':c'=>$code,':m'=>$message,':r'=>$responseJson]);
    }

    public function findById(int $id): ?array
    {
        if ($id <= 0 || !$this->schemaOk()) return null;
        $st = $this->db->prepare('SELECT * FROM comprobantes_fiscales WHERE id=:id LIMIT 1');
        $st->execute([':id' => $id]);
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function list(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        if (!$this->schemaOk()) return [];
        $limit = max(1, min(200, $limit));
        $offset = max(0, $offset);

        $where = [];
        $params = [];

        if (!empty($filters['order_id'])) {
            $where[] = 'order_id = :order_id';
            $params[':order_id'] = (int)$filters['order_id'];
        }

        // Búsqueda simple: pedido o número de comprobante
        if (!empty($filters['q'])) {
            $qRaw = (string)$filters['q'];
            $qDigits = preg_replace('/\D+/', '', $qRaw);
            $qNum = (int)($qDigits ?: 0);
            if ($qNum > 0) {
                $where[] = '(order_id = :q_order OR cbte_nro = :q_cbte)';
                $params[':q_order'] = $qNum;
                $params[':q_cbte']  = $qNum;
            }
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = (string)$filters['status'];
        }
        if (!empty($filters['arca_env'])) {
            $where[] = 'arca_env = :arca_env';
            $params[':arca_env'] = (string)$filters['arca_env'];
        }

        if (!empty($filters['pto_vta'])) {
            $where[] = 'pto_vta = :pto_vta';
            $params[':pto_vta'] = (int)$filters['pto_vta'];
        }
        if (!empty($filters['cbte_tipo'])) {
            $where[] = 'cbte_tipo = :cbte_tipo';
            $params[':cbte_tipo'] = (int)$filters['cbte_tipo'];
        }
        if (!empty($filters['cbte_nro'])) {
            $where[] = 'cbte_nro = :cbte_nro';
            $params[':cbte_nro'] = (int)$filters['cbte_nro'];
        }


        // Filtros por rango (drill-down)
        if (!empty($filters['issued_from']) || !empty($filters['issued_to'])) {
            if ($this->schemaHasIssuedAt()) {
                $fromD = !empty($filters['issued_from']) ? ((string)$filters['issued_from'] . ' 00:00:00') : '1970-01-01 00:00:00';
                $toD   = !empty($filters['issued_to'])   ? ((string)$filters['issued_to'] . ' 23:59:59')   : '2999-12-31 23:59:59';
                $where[] = 'issued_at BETWEEN :issued_from AND :issued_to';
                $params[':issued_from'] = $fromD;
                $params[':issued_to']   = $toD;
            }
        }

        if (!empty($filters['created_from']) || !empty($filters['created_to'])) {
            $fromD = !empty($filters['created_from']) ? ((string)$filters['created_from'] . ' 00:00:00') : '1970-01-01 00:00:00';
            $toD   = !empty($filters['created_to'])   ? ((string)$filters['created_to'] . ' 23:59:59')   : '2999-12-31 23:59:59';
            $where[] = 'created_at BETWEEN :created_from AND :created_to';
            $params[':created_from'] = $fromD;
            $params[':created_to']   = $toD;
        }


        if (!empty($filters['kind']) && $this->schemaHasKind()) {
            $where[] = 'kind = :kind';
            $params[':kind'] = (string)$filters['kind'];
        }

        $sql = 'SELECT * FROM comprobantes_fiscales';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $sql .= ' ORDER BY id DESC LIMIT :lim OFFSET :off';

        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) $st->bindValue($k, $v);
        $st->bindValue(':lim', $limit, PDO::PARAM_INT);
        $st->bindValue(':off', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function count(array $filters = []): int
    {
        if (!$this->schemaOk()) return 0;
        $where = [];
        $params = [];
        if (!empty($filters['order_id'])) {
            $where[] = 'order_id = :order_id';
            $params[':order_id'] = (int)$filters['order_id'];
        }

        // Búsqueda simple: pedido o número de comprobante
        if (!empty($filters['q'])) {
            $qRaw = (string)$filters['q'];
            $qDigits = preg_replace('/\D+/', '', $qRaw);
            $qNum = (int)($qDigits ?: 0);
            if ($qNum > 0) {
                $where[] = '(order_id = :q_order OR cbte_nro = :q_cbte)';
                $params[':q_order'] = $qNum;
                $params[':q_cbte']  = $qNum;
            }
        }
        if (!empty($filters['status'])) {
            $where[] = 'status = :status';
            $params[':status'] = (string)$filters['status'];
        }
        if (!empty($filters['arca_env'])) {
            $where[] = 'arca_env = :arca_env';
            $params[':arca_env'] = (string)$filters['arca_env'];
        }

        if (!empty($filters['pto_vta'])) {
            $where[] = 'pto_vta = :pto_vta';
            $params[':pto_vta'] = (int)$filters['pto_vta'];
        }
        if (!empty($filters['cbte_tipo'])) {
            $where[] = 'cbte_tipo = :cbte_tipo';
            $params[':cbte_tipo'] = (int)$filters['cbte_tipo'];
        }
        if (!empty($filters['cbte_nro'])) {
            $where[] = 'cbte_nro = :cbte_nro';
            $params[':cbte_nro'] = (int)$filters['cbte_nro'];
        }


        // Filtros por rango (drill-down)
        if (!empty($filters['issued_from']) || !empty($filters['issued_to'])) {
            if ($this->schemaHasIssuedAt()) {
                $fromD = !empty($filters['issued_from']) ? ((string)$filters['issued_from'] . ' 00:00:00') : '1970-01-01 00:00:00';
                $toD   = !empty($filters['issued_to'])   ? ((string)$filters['issued_to'] . ' 23:59:59')   : '2999-12-31 23:59:59';
                $where[] = 'issued_at BETWEEN :issued_from AND :issued_to';
                $params[':issued_from'] = $fromD;
                $params[':issued_to']   = $toD;
            }
        }

        if (!empty($filters['created_from']) || !empty($filters['created_to'])) {
            $fromD = !empty($filters['created_from']) ? ((string)$filters['created_from'] . ' 00:00:00') : '1970-01-01 00:00:00';
            $toD   = !empty($filters['created_to'])   ? ((string)$filters['created_to'] . ' 23:59:59')   : '2999-12-31 23:59:59';
            $where[] = 'created_at BETWEEN :created_from AND :created_to';
            $params[':created_from'] = $fromD;
            $params[':created_to']   = $toD;
        }

        if (!empty($filters['kind']) && $this->schemaHasKind()) {
            $where[] = 'kind = :kind';
            $params[':kind'] = (string)$filters['kind'];
        }


        $sql = 'SELECT COUNT(*) FROM comprobantes_fiscales';
        if ($where) $sql .= ' WHERE ' . implode(' AND ', $where);
        $st = $this->db->prepare($sql);
        $st->execute($params);
        return (int)$st->fetchColumn();
    }

    /**
     * Alias retrocompatible: algunos Controllers llaman countList().
     * @param array<string,mixed> $filters
     */
    public function countList(array $filters = []): int
    {
        return $this->count($filters);
    }

    public function appendConsulta(int $id, array $consultaData): void
    {
        if ($id <= 0 || !$this->schemaOk()) return;
        $row = $this->findById($id);
        if (!$row) return;
        $existing = [];
        if (!empty($row['response_json'])) {
            $decoded = json_decode((string)$row['response_json'], true);
            if (is_array($decoded)) $existing = $decoded;
        }
        $existing['consulta'] = $consultaData;
        $st = $this->db->prepare("UPDATE comprobantes_fiscales SET response_json=:r WHERE id=:id");
        $st->execute([':id' => $id, ':r' => json_encode($existing, JSON_UNESCAPED_UNICODE)]);
    }

    /**
     * 6.3.B: Backfill de importes (ImpTotal/ImpNeto/ImpIVA) desde request_json para comprobantes emitidos.
     * Se usa cuando registros legacy quedaron con imp_* en 0 pero el request original tiene los importes correctos.
     */
    public function backfillAmountsFromRequestJsonForIssued(string $from, string $to, string $env = ''): int
    {
        if (!$this->schemaOk()) return 0;

        // Normaliza (YYYY-MM-DD) y bounds inclusivos
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $from)) $from = date('Y-m-01');
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $to))   $to   = date('Y-m-d');
        if ($env !== 'homologacion' && $env !== 'produccion') $env = '';

        $where = "status='issued' AND DATE(issued_at) BETWEEN :from AND :to";
        if ($env !== '') $where .= " AND arca_env=:env";
        // Solo donde haya chances de backfill
        $where .= " AND (imp_total=0 OR imp_neto=0 OR imp_iva=0) AND request_json IS NOT NULL AND request_json <> ''";

        $sql = "SELECT id, imp_total, imp_neto, imp_iva, request_json FROM comprobantes_fiscales WHERE {$where} ORDER BY id ASC";
        $st = $this->db->prepare($sql);
        $params = [':from' => $from, ':to' => $to];
        if ($env !== '') $params[':env'] = $env;
        $st->execute($params);
        $rows = $st->fetchAll(\PDO::FETCH_ASSOC);

        $updated = 0;
        foreach ($rows as $r) {
            $id = (int)($r['id'] ?? 0);
            if ($id <= 0) continue;

            $req = (string)($r['request_json'] ?? '');
            $j = json_decode($req, true);
            if (!is_array($j)) continue;

            // Path esperado: FeDetReq.FECAEDetRequest.ImpTotal / ImpNeto / ImpIVA
            $det = $j['FeDetReq']['FECAEDetRequest'] ?? null;
            if (!is_array($det)) continue;

            $impTotal = $det['ImpTotal'] ?? null;
            $impNeto  = $det['ImpNeto'] ?? null;
            $impIva   = $det['ImpIVA'] ?? null;

            // Acepta int/float/string numérico
            $impTotal = is_numeric($impTotal) ? (int)round((float)$impTotal) : null;
            $impNeto  = is_numeric($impNeto)  ? (int)round((float)$impNeto)  : null;
            $impIva   = is_numeric($impIva)   ? (int)round((float)$impIva)   : null;

            // Si no hay nada útil, sigue
            if ($impTotal === null && $impNeto === null && $impIva === null) continue;

            // No pisar valores ya distintos de 0 (salvo que sean 0)
            $newTotal = ((int)($r['imp_total'] ?? 0) === 0 && $impTotal !== null) ? $impTotal : (int)($r['imp_total'] ?? 0);
            $newNeto  = ((int)($r['imp_neto'] ?? 0) === 0 && $impNeto !== null) ? $impNeto : (int)($r['imp_neto'] ?? 0);
            $newIva   = ((int)($r['imp_iva'] ?? 0) === 0 && $impIva !== null) ? $impIva : (int)($r['imp_iva'] ?? 0);

            // Evita updates vacíos
            if ($newTotal === (int)($r['imp_total'] ?? 0) && $newNeto === (int)($r['imp_neto'] ?? 0) && $newIva === (int)($r['imp_iva'] ?? 0)) {
                continue;
            }

            $u = $this->db->prepare('UPDATE comprobantes_fiscales SET imp_total=:t, imp_neto=:n, imp_iva=:i WHERE id=:id');
            $u->execute([':t' => $newTotal, ':n' => $newNeto, ':i' => $newIva, ':id' => $id]);
            $updated += (int)$u->rowCount();
        }

        return $updated;
    }
}
