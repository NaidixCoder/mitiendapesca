<?php
namespace App\Services\Admin\Audit;

use PDO;
use App\Repositories\Admin\AuditSearchRepository;

/**
 * Caso de uso /admin/audit.
 *
 * Responsabilidad:
 * - Normalizar filtros
 * - Consultar repositorio
 * - Aplicar enriquecimiento + agrupación UX
 * - Entregar payload listo para render_page('admin/audit/index')
 */
final class AuditIndexService
{
    public function __construct(
        private AuditSearchRepository $repo,
        private AuditEnrichmentService $enricher,
        private ProductBusinessGroupingService $grouping
    ) {}

    /**
     * @return array View data para render_page('admin/audit/index')
     */
    public function buildViewData(PDO $db, array $query): array
    {
        $hasRequestId = $this->repo->hasRequestIdColumn();

        // Vista de grupo (request_id)
        $rid = substr(trim((string)($query['rid'] ?? '')), 0, 128);
        if ($rid !== '' && !preg_match('/^[a-zA-Z0-9_-]+$/D', $rid)) $rid = '';
        if ($rid !== '') {
            return $this->buildGroupViewData($db, $rid, $hasRequestId, (int)($query['open'] ?? 0));
        }

        $pageNum = min(100000, max(1, (int)($query['page'] ?? 1)));
        $perPage = (int)($query['per_page'] ?? $query['perPage'] ?? 25);
        if (!in_array($perPage, [25, 50, 100], true)) $perPage = 25;
        $offset = ($pageNum - 1) * $perPage;

        $tipo   = (string)($query['tipo'] ?? $query['type'] ?? 'todos');
        $action = substr((string)($query['action'] ?? $query['accion'] ?? ''), 0, 100);
        $range  = (string)($query['range'] ?? $query['rango'] ?? '7');
        $q      = substr(trim((string)($query['q'] ?? $query['buscar'] ?? '')), 0, 150);
        $actor  = substr(trim((string)($query['actor'] ?? $query['actor_q'] ?? '')), 0, 150);
        $entityIdRaw = trim((string)($query['entity_id'] ?? $query['entidad_id'] ?? ''));
        $dateFromRaw = trim((string)($query['from'] ?? $query['desde'] ?? ''));
        if ($dateFromRaw !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $dateFromRaw)) $dateFromRaw = '';
        $dateToRaw   = trim((string)($query['to'] ?? $query['hasta'] ?? ''));
        if ($dateToRaw !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/D', $dateToRaw)) $dateToRaw = '';

        if ($q === '' && isset($query['usuario'])) {
            $q = trim((string)$query['usuario']);
        }

        $where = [];
        $params = [];

        $allowedTypes = ['order','product','category','brand','user','attribute','attribute_value'];
        if ($tipo !== 'todos' && in_array($tipo, $allowedTypes, true)) {
            $where[] = 'a.entity_type = :tipo';
            $params[':tipo'] = $tipo;
        } else {
            $tipo = 'todos';
        }

        if ($action !== '') {
            $where[] = 'a.action = :action';
            $params[':action'] = $action;
        }

        // Actor
        if ($actor !== '') {
            $actorNorm = $actor;
            if (isset($actorNorm[0]) && $actorNorm[0] === '#') {
                $actorNorm = substr($actorNorm, 1);
            }
            if ($actorNorm !== '' && ctype_digit($actorNorm)) {
                $where[] = 'a.user_id = :actor_id';
                $params[':actor_id'] = (int)$actorNorm;
            } else {
                $likeActor = '%' . $actor . '%';
                $where[] = "(COALESCE(u.name, '') LIKE :actor_q1 OR COALESCE(u.email, '') LIKE :actor_q2)";
                $params[':actor_q1'] = $likeActor;
                $params[':actor_q2'] = $likeActor;
            }
        }

        // Entidad ID
        if ($entityIdRaw !== '') {
            $eid = $entityIdRaw;
            if (isset($eid[0]) && $eid[0] === '#') {
                $eid = substr($eid, 1);
            }
            if ($eid !== '' && ctype_digit($eid)) {
                $where[] = 'a.entity_id = :entity_id';
                $params[':entity_id'] = (int)$eid;
            }
        }

        // Rango fechas
        $dateFrom = null;
        $dateTo   = null;

        if ($dateFromRaw !== '' || $dateToRaw !== '') {
            $range = 'custom';
            $fromTs = $dateFromRaw !== '' ? strtotime($dateFromRaw) : false;
            $toTs   = $dateToRaw !== '' ? strtotime($dateToRaw) : false;

            if ($fromTs !== false) {
                $dateFrom = date('Y-m-d 00:00:00', $fromTs);
            }
            if ($toTs !== false) {
                $dateTo = date('Y-m-d 23:59:59', $toTs);
            }
            if ($dateFrom && !$dateTo) {
                $dateTo = date('Y-m-d 23:59:59');
            }
            if ($dateTo && !$dateFrom) {
                $dateFrom = date('Y-m-d 00:00:00', strtotime('-7 days', strtotime($dateTo)));
            }
        } else {
            if ($range === 'today') {
                $where[] = '(a.created_at >= CURDATE() AND a.created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY))';
            } elseif ($range === '30') {
                $where[] = '(a.created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY) AND a.created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY))';
            } elseif ($range === 'all') {
                // sin filtro
            } else {
                $range = '7';
                $where[] = '(a.created_at >= DATE_SUB(CURDATE(), INTERVAL 7 DAY) AND a.created_at < DATE_ADD(CURDATE(), INTERVAL 1 DAY))';
            }
        }

        if ($dateFrom && $dateTo) {
            $where[] = '(a.created_at BETWEEN :from AND :to)';
            $params[':from'] = $dateFrom;
            $params[':to']   = $dateTo;
        }

        // Búsqueda global (q)
        if ($q !== '') {
            $qSmart = trim($q);
            $smartMap = [
                'pedido'   => 'order',
                'pedidos'  => 'order',
                'order'    => 'order',
                'producto' => 'product',
                'productos'=> 'product',
                'product'  => 'product',
                'usuario'  => 'user',
                'usuarios' => 'user',
                'user'     => 'user',
                'categoría'=> 'category',
                'categoria'=> 'category',
                'category' => 'category',
                'marca'    => 'brand',
                'brand'    => 'brand',
                'atributo' => 'attribute',
                'attribute'=> 'attribute',
            ];

            if (preg_match('/\b([\p{L}_]+)\s*#?\s*(\d+)\b/iu', $qSmart, $m)) {
                $word = mb_strtolower($m[1]);
                $id   = (int)$m[2];
                if (isset($smartMap[$word])) {
                    $where[] = '(a.entity_type = :qsmart_type AND a.entity_id = :qsmart_id)';
                    $params[':qsmart_type'] = $smartMap[$word];
                    $params[':qsmart_id']   = $id;
                    $q = '';
                }
            }

            if ($q !== '') {
                $qNorm = $q;
                if (isset($qNorm[0]) && $qNorm[0] === '#') {
                    $qNorm = substr($qNorm, 1);
                }

                if ($qNorm !== '' && ctype_digit($qNorm)) {
                    $where[] = '(a.entity_id = :qid1)';
                    $params[':qid1'] = (int)$qNorm;
                } else {
                    $like = '%' . $q . '%';
                    $where[] = '('
                        . "COALESCE(u.name, '') LIKE :q1 OR COALESCE(u.email, '') LIKE :q2 OR "
                        . "a.action LIKE :q3 OR a.entity_type LIKE :q4 OR "
                        . "CAST(a.entity_id AS CHAR) LIKE :q5 OR "
                        . "COALESCE(p.name, '') LIKE :q6 OR COALESCE(o.status, '') LIKE :q7 OR "
                        . "COALESCE(c.name, '') LIKE :q8 OR COALESCE(b.name, '') LIKE :q9 OR "
                        . "COALESCE(att.name, '') LIKE :q10 OR COALESCE(av.value, '') LIKE :q11 OR "
                        . "COALESCE(ue.email, '') LIKE :q12 OR COALESCE(ue.name, '') LIKE :q13 OR "
                        . "COALESCE(a.metadata, '') LIKE :q14"
                        . ')';
                    $params[':q1'] = $like;
                    $params[':q2'] = $like;
                    $params[':q3'] = $like;
                    $params[':q4'] = $like;
                    $params[':q5'] = $like;
                    $params[':q6'] = $like;
                    $params[':q7'] = $like;
                    $params[':q8'] = $like;
                    $params[':q9'] = $like;
                    $params[':q10'] = $like;
                    $params[':q11'] = $like;
                    $params[':q12'] = $like;
                    $params[':q13'] = $like;
                    $params[':q14'] = $like;
                }
            }
        }

        $whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';
        $whereSqlAnd = $whereSql !== '' ? $whereSql : 'WHERE 1=1';

        $enableGrouping = ($hasRequestId && $action === '');

        if (!$enableGrouping) {
            $total = $this->repo->countClassic($whereSql, $params);
            $items = $this->repo->fetchClassic($whereSql, $params, $perPage, $offset, $hasRequestId);
        } else {
            $total = $this->repo->countGrouped($whereSqlAnd, $params);
            $items = $this->repo->fetchGrouped($whereSqlAnd, $params, $perPage, $offset);

            $rids = [];
            foreach ($items as $it) {
                if (($it['row_type'] ?? '') === 'group') {
                    $r = (string)($it['request_id'] ?? $it['a_request_id'] ?? '');
                    if ($r !== '') $rids[] = $r;
                }
            }
            $byRid = $this->repo->getGroupActionCounts($rids);

            foreach ($items as &$it) {
                if (($it['row_type'] ?? '') !== 'group') continue;
                $ridKey = (string)($it['request_id'] ?? $it['a_request_id'] ?? '');
                if ($ridKey === '' || !isset($byRid[$ridKey])) continue;

                $actions = $byRid[$ridKey]['actions'] ?? [];
                $canonical = $this->pickCanonicalRequestAction($actions);

                $meta = [
                    'group' => [
                        'request_id' => $ridKey,
                        'count' => (int)($byRid[$ridKey]['total'] ?? 0),
                        'canonical' => $canonical,
                        'actions' => $actions,
                    ]
                ];

                $it['action'] = $canonical;
                $it['metadata'] = json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            unset($it);
        }

        $this->enricher->enrichItems($db, $items);

        if ($action === '') {
            $items = $this->grouping->groupBusinessProductEvents($items);
        }

        $openId = (int)($query['open'] ?? 0);

        return [
            'items'    => $items,
            'page'     => $pageNum,
            'pageNum'  => $pageNum,
            'perPage'  => $perPage,
            'total'    => $total,
            'tipo'     => $tipo,
            'action'   => $action,
            'range'    => $range,
            'q'        => $q,
            'actor'    => $actor,
            'entity_id'=> $entityIdRaw,
            'from'     => $dateFromRaw,
            'to'       => $dateToRaw,
            'openId'   => $openId,
        ];
    }

    private function buildGroupViewData(PDO $db, string $rid, bool $hasRequestId, int $openId): array
    {
        if (!$hasRequestId) {
            return [
                'items' => [],
                'pageNum' => 1,
                'perPage' => 25,
                'total' => 0,
                'tipo' => 'todos',
                'action' => '',
                'range' => 'all',
                'q' => '',
                'actor' => '',
                'entity_id' => '',
                'from' => '',
                'to' => '',
                'groupRid' => $rid,
                'groupError' => "Tu base aún no tiene request_id. Ejecutá la migración 20260116_01_admin_audit_request_id.sql.",
                'openId' => $openId,
            ];
        }

        $items = $this->repo->listRequestEvents($rid, 250);
        $this->enricher->enrichItems($db, $items);

        return [
            'items' => $items,
            'pageNum' => 1,
            'perPage' => 25,
            'total' => count($items),
            'tipo' => 'todos',
            'action' => '',
            'range' => 'all',
            'q' => '',
            'actor' => '',
            'entity_id' => '',
            'from' => '',
            'to' => '',
            'groupRid' => $rid,
            'groupError' => '',
            'openId' => $openId,
        ];
    }

    private function pickCanonicalRequestAction(array $actions): string
    {
        $prio = [
            'product_created',
            'order_created_transfer',
            'order_mark_paid_mp','order_mark_paid_transfer',
            'order_status_changed',
            'product_images_bulk_assign','product_images_bulk_delete',
            'product.image_upload','product.image_delete','product.image_cover','product.image_assign',
            'product_updated',
        ];
        foreach ($prio as $p) {
            if (isset($actions[$p])) return $p;
        }
        arsort($actions);
        return (string)array_key_first($actions);
    }
}
