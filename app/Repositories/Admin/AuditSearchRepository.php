<?php
namespace App\Repositories\Admin;

use PDO;

/**
 * Repositorio de lectura/búsqueda para auditoría (admin_audit_log).
 *
 * Regla: SQL acá, no en Controllers/Services.
 */
final class AuditSearchRepository
{
    public function __construct(private PDO $db) {}

    public function hasRequestIdColumn(): bool
    {
        try {
            $st = $this->db->prepare("SHOW COLUMNS FROM admin_audit_log LIKE 'request_id'");
            $st->execute();
            return (bool)$st->fetch(PDO::FETCH_ASSOC);
        } catch (\Throwable $e) {
            return false;
        }
    }

    private function baseFromJoin(): string
    {
        return "FROM admin_audit_log a
                LEFT JOIN users u ON u.id = a.user_id
                LEFT JOIN orders o ON (a.entity_type='order' AND o.id = a.entity_id)
                LEFT JOIN products p ON (a.entity_type='product' AND p.id = a.entity_id)
                LEFT JOIN categories c ON (a.entity_type='category' AND c.id = a.entity_id)
                LEFT JOIN brands b ON (a.entity_type='brand' AND b.id = a.entity_id)
                LEFT JOIN attributes att ON (a.entity_type='attribute' AND att.id = a.entity_id)
                LEFT JOIN attribute_values av ON (a.entity_type='attribute_value' AND av.id = a.entity_id)
                LEFT JOIN users ue ON (a.entity_type='user' AND ue.id = a.entity_id)";
    }

    /**
     * Total para modo clásico (sin agrupación por request).
     */
    public function countClassic(string $whereSql, array $params): int
    {
        $sql = "SELECT COUNT(*) " . $this->baseFromJoin() . " " . $whereSql;
        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->execute();
        return (int)$st->fetchColumn();
    }

    /**
     * Items para modo clásico.
     */
    public function fetchClassic(string $whereSql, array $params, int $limit, int $offset, bool $hasRequestId): array
    {
        $reqSelectA = $hasRequestId ? 'a.request_id' : 'NULL AS request_id';

        $sql = "
            SELECT
              a.id,
              a.user_id,
              {$reqSelectA},
              a.action,
              a.entity_type,
              a.entity_id,
              a.metadata,
              a.created_at,
              TIMESTAMPDIFF(SECOND, a.created_at, NOW()) AS age_seconds,
              COALESCE(u.name, u.email) AS actor_name,
              o.status AS order_status,
              p.name AS product_name,
              c.name AS category_name,
              b.name AS brand_name,
              att.name AS attribute_name,
              av.value AS attribute_value,
              COALESCE(ue.name, ue.email) AS user_name,
              'event' AS row_type
            " . $this->baseFromJoin() . "
            {$whereSql}
            ORDER BY a.id DESC
            LIMIT :limit OFFSET :offset
        ";

        $st = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            if (in_array($k, [':qid1', ':qsmart_id', ':actor_id', ':entity_id'], true)) {
                $st->bindValue($k, (int)$v, PDO::PARAM_INT);
            } else {
                $st->bindValue($k, $v);
            }
        }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Total para modo agrupado por request_id.
     */
    public function countGrouped(string $whereSqlAnd, array $params): int
    {
        $fromJoin = $this->baseFromJoin();
        [$whereSqlAnd2, $params2] = $this->duplicateWhereParams($whereSqlAnd, $params, '_g2');
        $sql = "
            SELECT COUNT(*) FROM (
              SELECT MAX(a.id) AS sort_id
              {$fromJoin}
              {$whereSqlAnd}
              AND a.action <> 'stock_updated'
              AND a.request_id IS NOT NULL
              GROUP BY a.request_id
              UNION ALL
              SELECT a.id AS sort_id
              {$fromJoin}
              {$whereSqlAnd2}
              AND (a.action = 'stock_updated' OR a.request_id IS NULL)
            ) t
        ";

        $st = $this->db->prepare($sql);
        foreach (array_merge($params, $params2) as $k => $v) {
            $st->bindValue($k, $v);
        }
        $st->execute();
        return (int)$st->fetchColumn();
    }

    /**
     * Items para modo agrupado por request_id.
     */
    public function fetchGrouped(string $whereSqlAnd, array $params, int $limit, int $offset): array
    {
        $fromJoin = $this->baseFromJoin();
        [$whereSqlAnd2, $params2] = $this->duplicateWhereParams($whereSqlAnd, $params, '_g2');

        $sql = "
            SELECT
              t.row_type,
              t.sort_id,
              t.request_id,
              a2.id,
              a2.user_id,
              a2.request_id AS a_request_id,
              a2.action,
              a2.entity_type,
              a2.entity_id,
              a2.metadata,
              a2.created_at,
              TIMESTAMPDIFF(SECOND, a2.created_at, NOW()) AS age_seconds,
              COALESCE(u2.name, u2.email) AS actor_name,
              o2.status AS order_status,
              p2.name AS product_name,
              c2.name AS category_name,
              b2.name AS brand_name,
              att2.name AS attribute_name,
              av2.value AS attribute_value,
              COALESCE(ue2.name, ue2.email) AS user_name
            FROM (
              SELECT 'group' AS row_type, MAX(a.id) AS sort_id, a.request_id AS request_id
              {$fromJoin}
              {$whereSqlAnd}
              AND a.action <> 'stock_updated'
              AND a.request_id IS NOT NULL
              GROUP BY a.request_id
              UNION ALL
              SELECT 'event' AS row_type, a.id AS sort_id, a.request_id AS request_id
              {$fromJoin}
              {$whereSqlAnd2}
              AND (a.action = 'stock_updated' OR a.request_id IS NULL)
            ) t
            INNER JOIN admin_audit_log a2 ON a2.id = t.sort_id
            LEFT JOIN users u2 ON u2.id = a2.user_id
            LEFT JOIN orders o2 ON (a2.entity_type='order' AND o2.id = a2.entity_id)
            LEFT JOIN products p2 ON (a2.entity_type='product' AND p2.id = a2.entity_id)
            LEFT JOIN categories c2 ON (a2.entity_type='category' AND c2.id = a2.entity_id)
            LEFT JOIN brands b2 ON (a2.entity_type='brand' AND b2.id = a2.entity_id)
            LEFT JOIN attributes att2 ON (a2.entity_type='attribute' AND att2.id = a2.entity_id)
            LEFT JOIN attribute_values av2 ON (a2.entity_type='attribute_value' AND av2.id = a2.entity_id)
            LEFT JOIN users ue2 ON (a2.entity_type='user' AND ue2.id = a2.entity_id)
            ORDER BY t.sort_id DESC
            LIMIT :limit OFFSET :offset
        ";

        $st = $this->db->prepare($sql);
        foreach (array_merge($params, $params2) as $k => $v) {
            if (in_array($k, [':qid1', ':qsmart_id', ':actor_id', ':entity_id'], true)) {
                $st->bindValue($k, (int)$v, PDO::PARAM_INT);
            } else {
                $st->bindValue($k, $v);
            }
        }
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->bindValue(':offset', $offset, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * @param array<string,mixed> $params
     * @return array{0:string,1:array<string,mixed>}
     */
    private function duplicateWhereParams(string $whereSqlAnd, array $params, string $suffix): array
    {
        if ($whereSqlAnd === '' || empty($params)) {
            return [$whereSqlAnd, []];
        }

        $replace = [];
        $params2 = [];
        foreach ($params as $k => $v) {
            $old = (string)$k;
            if ($old === '') {
                continue;
            }
            $new = $old . $suffix;
            $replace[$old] = $new;
            $params2[$new] = $v;
        }

        if (empty($replace)) {
            return [$whereSqlAnd, []];
        }

        $where2 = strtr($whereSqlAnd, $replace);
        return [$where2, $params2];
    }

    /**
     * Devuelve para cada request_id: conteo por action (sin stock_updated).
     * @return array rid => ['total' => int, 'actions' => [action => int]]
     */
    public function getGroupActionCounts(array $rids): array
    {
        $rids = array_values(array_filter(array_unique(array_map('strval', $rids))));
        if (empty($rids)) return [];

        $in = implode(',', array_fill(0, count($rids), '?'));
        $st = $this->db->prepare(
            "SELECT request_id, action, COUNT(*) AS c
             FROM admin_audit_log
             WHERE request_id IN ($in)
               AND action <> 'stock_updated'
             GROUP BY request_id, action"
        );
        $st->execute($rids);
        $rows = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $byRid = [];
        foreach ($rows as $r) {
            $ridKey = (string)($r['request_id'] ?? '');
            $aKey = (string)($r['action'] ?? '');
            $cnt = (int)($r['c'] ?? 0);
            if ($ridKey === '' || $aKey === '') continue;
            if (!isset($byRid[$ridKey])) $byRid[$ridKey] = ['total' => 0, 'actions' => []];
            $byRid[$ridKey]['total'] += $cnt;
            $byRid[$ridKey]['actions'][$aKey] = ($byRid[$ridKey]['actions'][$aKey] ?? 0) + $cnt;
        }
        return $byRid;
    }

    /**
     * Lista eventos internos de un request_id (sin stock_updated).
     */
    public function listRequestEvents(string $rid, int $limit = 250): array
    {
        $rid = trim($rid);
        if ($rid === '') return [];

        $sql = "
          SELECT
            a.id,
            a.user_id,
            a.request_id,
            a.action,
            a.entity_type,
            a.entity_id,
            a.metadata,
            a.created_at,
            TIMESTAMPDIFF(SECOND, a.created_at, NOW()) AS age_seconds,
            COALESCE(u.name, u.email) AS actor_name,
            o.status AS order_status,
            p.name AS product_name,
            c.name AS category_name,
            b.name AS brand_name,
            att.name AS attribute_name,
            av.value AS attribute_value,
            COALESCE(ue.name, ue.email) AS user_name,
            'event' AS row_type
          " . $this->baseFromJoin() . "
          WHERE a.request_id = :rid
            AND a.action <> 'stock_updated'
          ORDER BY a.id ASC
          LIMIT :limit
        ";

        $st = $this->db->prepare($sql);
        $st->bindValue(':rid', $rid);
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function findEventById(int $id): ?array
    {
        if ($id <= 0) return null;

        $sql = "
          SELECT
            a.id,
            a.user_id,
            a.action,
            a.entity_type,
            a.entity_id,
            a.metadata,
            a.created_at,
            TIMESTAMPDIFF(SECOND, a.created_at, NOW()) AS age_seconds,
            COALESCE(u.name, u.email) AS actor_name,
            o.status AS order_status,
            p.name AS product_name,
            c.name AS category_name,
            b.name AS brand_name,
            att.name AS attribute_name,
            av.value AS attribute_value,
            COALESCE(ue.name, ue.email) AS user_name,
            'event' AS row_type
          " . $this->baseFromJoin() . "
          WHERE a.id = :id
          LIMIT 1
        ";

        $st = $this->db->prepare($sql);
        $st->bindValue(':id', $id, PDO::PARAM_INT);
        $st->execute();
        $row = $st->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function listProductEventsWindow(int $productId, string $fromDt, string $toDt, int $limit = 250): array
    {
        if ($productId <= 0) return [];

        $sql = "
          SELECT
            a.id,
            a.user_id,
            a.action,
            a.entity_type,
            a.entity_id,
            a.metadata,
            a.created_at,
            TIMESTAMPDIFF(SECOND, a.created_at, NOW()) AS age_seconds,
            COALESCE(u.name, u.email) AS actor_name,
            o.status AS order_status,
            p.name AS product_name,
            c.name AS category_name,
            b.name AS brand_name,
            att.name AS attribute_name,
            av.value AS attribute_value,
            COALESCE(ue.name, ue.email) AS user_name,
            'event' AS row_type
          " . $this->baseFromJoin() . "
          WHERE a.entity_type='product'
            AND a.entity_id = :pid
            AND a.created_at BETWEEN :from_dt AND :to_dt
          ORDER BY a.id DESC
          LIMIT :limit
        ";

        $st = $this->db->prepare($sql);
        $st->bindValue(':pid', $productId, PDO::PARAM_INT);
        $st->bindValue(':from_dt', $fromDt);
        $st->bindValue(':to_dt', $toDt);
        $st->bindValue(':limit', $limit, PDO::PARAM_INT);
        $st->execute();
        return $st->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
    /**
     * Devuelve el nombre (o email) del actor que realizó la última acción para una entidad.
     * Útil para "Modificado por" en pantallas admin.
     */
    public function findLatestActorNameForEntity(string $entityType, int $entityId, ?string $action = null): ?string
    {
        $where = "WHERE a.entity_type = :et AND a.entity_id = :eid";
        if ($action !== null && $action !== '') {
            $where .= " AND a.action = :act";
        }

        $sql = "SELECT COALESCE(u.name, u.email) AS actor_name
                FROM admin_audit_log a
                LEFT JOIN users u ON u.id = a.user_id
                {$where}
                ORDER BY a.id DESC
                LIMIT 1";

        $st = $this->db->prepare($sql);
        $st->bindValue(':et', $entityType);
        $st->bindValue(':eid', (int)$entityId, \PDO::PARAM_INT);
        if ($action !== null && $action !== '') {
            $st->bindValue(':act', $action);
        }
        $st->execute();
        $val = $st->fetchColumn();
        $val = is_string($val) ? trim($val) : '';
        return $val !== '' ? $val : null;
    }

}
