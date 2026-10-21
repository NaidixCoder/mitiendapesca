<?php
namespace App\Controllers\Admin;

use PDO;
use App\Services\Admin\Dashboard\DashboardViewModelService;
use App\Services\Admin\Dashboard\DashboardPartialsViewModelService;
use App\Services\Admin\Dashboard\DashboardNavigationAnalyticsService;


class DashboardController extends BaseController
{
    public function index(): void
    {
        $db = db();

        // KPIs (catálogo / crecimiento)
        $prodActivos   = (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active=1")->fetchColumn();
        $prodInactivos = (int)$db->query("SELECT COUNT(*) FROM products WHERE is_active=0")->fetchColumn();
        $users30d      = (int)$db->query("SELECT COUNT(*) FROM users WHERE created_at >= (NOW() - INTERVAL 30 DAY)")->fetchColumn();

        // KPIs operativos
        $usersTotal   = (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn();

        $ordersToday  = (int)$db->query(
            "SELECT COUNT(*) FROM orders
             WHERE DATE(created_at)=CURDATE()
               AND status IN ('paid','processing','packed','shipped','delivered')"
        )->fetchColumn();

        $revenueToday = (int)$db->query(
            "SELECT COALESCE(SUM(total),0) FROM orders
             WHERE DATE(created_at)=CURDATE()
               AND status IN ('paid','processing','packed','shipped','delivered')"
        )->fetchColumn();

        $pendingCount = (int)$db->query(
            "SELECT COUNT(*) FROM orders
             WHERE status IN ('paid','processing','packed')"
        )->fetchColumn();

        // Low stock (simple + variantes) — sólo items con stock <= umbral
        // IMPORTANTE: forzamos COLLATION en strings para evitar "Illegal mix of collations" en UNION.
        $lowStockSql = "
          SELECT *
          FROM (
            SELECT
              p.id,
              CONVERT(p.sku USING utf8mb4) COLLATE utf8mb4_unicode_ci AS sku,
              CONVERT(p.name USING utf8mb4) COLLATE utf8mb4_unicode_ci AS name,
              NULL AS variant_title,
              COALESCE(i.stock,0) AS stock,
              COALESCE(i.low_stock_threshold,3) AS thr
            FROM products p
            LEFT JOIN inventory i ON i.product_id = p.id
            WHERE p.is_active=1 AND p.sale_mode='simple'

            UNION ALL

            SELECT
              p.id,
              CONVERT(pv.sku USING utf8mb4) COLLATE utf8mb4_unicode_ci AS sku,
              CONVERT(p.name USING utf8mb4) COLLATE utf8mb4_unicode_ci AS name,
              CONVERT(pv.title USING utf8mb4) COLLATE utf8mb4_unicode_ci AS variant_title,
              COALESCE(iv.stock,0) AS stock,
              COALESCE(iv.low_stock_threshold,3) AS thr
            FROM products p
            JOIN product_variants pv ON pv.product_id = p.id AND pv.is_active=1
            LEFT JOIN inventory_variants iv ON iv.variant_id = pv.id
            WHERE p.is_active=1 AND p.sale_mode='variants'
          ) x
          WHERE x.stock <= x.thr
          ORDER BY (x.stock <= 0) DESC, x.stock ASC
          LIMIT 50
        ";
        $lowStock = $db->query($lowStockSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];


        // Destacados (ventas últimos 30 días)
        $destacadosSql = "
          SELECT
            oi.product_id AS id,
            oi.variant_id,
            COALESCE(oi.variant_sku, oi.product_sku) AS sku,
            COALESCE(oi.product_name, p.name) AS product_name,
            NULLIF(oi.variant_title,'') AS variant_title,
            SUM(oi.qty) AS qty,
            SUM(COALESCE(oi.price,0) * oi.qty) AS amount
          FROM order_items oi
          JOIN orders o ON o.id = oi.order_id
          LEFT JOIN products p ON p.id = oi.product_id
          WHERE o.created_at >= (NOW() - INTERVAL 30 DAY)
            AND o.status IN ('paid','processing','packed','shipped','delivered')
          GROUP BY oi.product_id, oi.variant_id, sku, product_name, variant_title
          ORDER BY qty DESC
          LIMIT 8
        ";
        $destacados = $db->query($destacadosSql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Top clientes (últimos 30 días)
        // Importante: el schema usa `orders.total` (no total_amount).
        // Solo estados de venta (evitamos cancelados/reembolsados).
        $topClients30Sql = "
            SELECT
              u.id,
              u.name,
              u.email,
              COUNT(*) AS orders_count,
              SUM(o.total) AS total_spent,
              MAX(o.created_at) AS last_order_at
            FROM orders o
            INNER JOIN users u ON u.id = o.user_id
            WHERE o.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
              AND o.status IN ('paid','processing','packed','shipped','delivered')
            GROUP BY u.id, u.name, u.email
            ORDER BY total_spent DESC
            LIMIT 8
        ";
        $topClients30 = $db->query($topClients30Sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        // Último import
        $import = $db->query(
            "SELECT id, job_type, status, file_name, total_rows, ok_rows, err_rows, created_at, finished_at
             FROM import_jobs
             ORDER BY id DESC
             LIMIT 1"
        )->fetch(PDO::FETCH_ASSOC) ?: null;

        // Actividad reciente (misma lógica que /admin/audit):
        // - Si existe request_id, agrupamos por request (excepto stock_updated)
        // - stock_updated queda como evento independiente
        // - Usamos age_seconds (TIMESTAMPDIFF) para evitar desfasajes de timezone
        $hasRequestId = false;
        try {
            $stCol = $db->prepare("SHOW COLUMNS FROM admin_audit_log LIKE 'request_id'");
            $stCol->execute();
            $hasRequestId = (bool)($stCol->fetch(PDO::FETCH_ASSOC));
        } catch (\Throwable $e) {
            $hasRequestId = false;
        }

        $actividadRaw = [];
        if ($hasRequestId) {
            // Traemos un poco más y luego limitamos a 10 en PHP.
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
                COALESCE(u2.name, u2.email) AS actor,
                o2.status AS order_status,
                p2.name   AS product_name
              FROM (
                SELECT 'group' AS row_type, MAX(a.id) AS sort_id, a.request_id AS request_id
                FROM admin_audit_log a
                WHERE a.action <> 'stock_updated'
                  AND a.request_id IS NOT NULL
                GROUP BY a.request_id

                UNION ALL

                SELECT 'event' AS row_type, a.id AS sort_id, a.request_id AS request_id
                FROM admin_audit_log a
                WHERE a.action = 'stock_updated'
              ) t
              INNER JOIN admin_audit_log a2 ON a2.id = t.sort_id
              LEFT JOIN users u2 ON u2.id = a2.user_id
              LEFT JOIN orders o2 ON (a2.entity_type='order' AND o2.id = a2.entity_id)
              LEFT JOIN products p2 ON (a2.entity_type='product' AND p2.id = a2.entity_id)
              ORDER BY t.sort_id DESC
              LIMIT 60
            ";
            $actividadRaw = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

            // Enriquecer filas de grupo con count + canonical (idéntico a audit, pero solo lo necesario para dashboard)
            $rids = [];
            foreach ($actividadRaw as $it) {
                if (($it['row_type'] ?? '') === 'group') {
                    $r = (string)($it['request_id'] ?? $it['a_request_id'] ?? '');
                    if ($r !== '') $rids[] = $r;
                }
            }
            $rids = array_values(array_unique($rids));
            if (!empty($rids)) {
                $in = implode(',', array_fill(0, count($rids), '?'));
                $stG = $db->prepare("SELECT request_id, action, COUNT(*) AS c
                                     FROM admin_audit_log
                                     WHERE request_id IN ($in)
                                       AND action <> 'stock_updated'
                                     GROUP BY request_id, action");
                $stG->execute($rids);
                $rows = $stG->fetchAll(PDO::FETCH_ASSOC) ?: [];

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

                $pickCanonical = function(array $actions): string {
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
                };

                foreach ($actividadRaw as &$it) {
                    if (($it['row_type'] ?? '') !== 'group') continue;
                    $ridKey = (string)($it['request_id'] ?? $it['a_request_id'] ?? '');
                    if ($ridKey === '' || !isset($byRid[$ridKey])) continue;
                    $actions = $byRid[$ridKey]['actions'] ?? [];
                    $canonical = $pickCanonical($actions);

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
        } else {
            // Fallback (sin request_id): últimos eventos tal cual, pero con age_seconds
            $sql = "
              SELECT
                'event' AS row_type,
                a.id AS sort_id,
                NULL AS request_id,
                a.id,
                a.user_id,
                NULL AS a_request_id,
                a.action,
                a.entity_type,
                a.entity_id,
                a.metadata,
                a.created_at,
                TIMESTAMPDIFF(SECOND, a.created_at, NOW()) AS age_seconds,
                COALESCE(u.name, u.email) AS actor,
                o.status AS order_status,
                p.name   AS product_name
              FROM admin_audit_log a
              LEFT JOIN users u ON u.id = a.user_id
              LEFT JOIN orders o ON (a.entity_type='order' AND o.id = a.entity_id)
              LEFT JOIN products p ON (a.entity_type='product' AND p.id = a.entity_id)
              ORDER BY a.id DESC
              LIMIT 60
            ";
            $actividadRaw = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];
        }

        // Reducir "ruido" de catálogo exactamente como /admin/audit:
        // agrupa eventos de productos (variantes/imágenes/edición) en acciones de negocio.
        // Esto es clave porque un "crear producto con variantes" puede disparar varios requests.
        $pickCanonicalProductAction = function(array $events): string {
            $prio = ['product_created','product_updated','product.image_upload','product.image_delete','product.image_cover','product.image_assign'];
            $actions = [];
            foreach ($events as $it) {
                $a = (string)($it['action'] ?? '');
                if ($a !== '') $actions[$a] = true;
            }
            foreach ($prio as $p) {
                if (isset($actions[$p])) return $p;
            }
            foreach (array_keys($actions) as $a) return $a;
            return 'product_updated';
        };

        $packBizEvents = function(array $events): array {
            $out = [];
            foreach ($events as $it) {
                $meta = [];
                if (!empty($it['metadata'])) {
                    $d = json_decode((string)$it['metadata'], true);
                    if (is_array($d)) $meta = $d;
                }
                $out[] = [
                    'id' => (int)($it['id'] ?? 0),
                    'action' => (string)($it['action'] ?? ''),
                    'created_at' => (string)($it['created_at'] ?? ''),
                    'metadata' => $meta,
                ];
            }
            return $out;
        };

        $groupBusinessProductEvents = function(array $items) use ($pickCanonicalProductAction, $packBizEvents): array {
            if (empty($items)) return $items;

            $creationWindowSeconds = 120;
            $bucketSeconds = 25;

            $out = [];
            $groups = [];
            $order = [];

            $createdIndex = [];
            $createdItemsById = [];
            foreach ($items as $it0) {
                if ((string)($it0['entity_type'] ?? '') !== 'product') continue;
                if ((string)($it0['action'] ?? '') !== 'product_created') continue;
                $uid0 = (int)($it0['user_id'] ?? 0);
                $pid0 = (int)($it0['entity_id'] ?? 0);
                $dt0  = (string)($it0['created_at'] ?? '');
                $ts0  = $dt0 !== '' ? strtotime($dt0) : false;
                if ($uid0 <= 0 || $pid0 <= 0 || $ts0 === false) continue;
                $k0 = $uid0 . '|' . $pid0;
                $createdIndex[$k0][] = [
                    'ts' => (int)$ts0,
                    'id' => (int)($it0['id'] ?? 0),
                    'dt' => $dt0,
                ];
                $cid = (int)($it0['id'] ?? 0);
                if ($cid > 0) $createdItemsById[$cid] = $it0;
            }
            foreach ($createdIndex as $k0 => $list) {
                usort($list, fn($a,$b) => (int)$b['ts'] <=> (int)$a['ts']);
                $createdIndex[$k0] = $list;
            }

            foreach ($items as $it) {
                $etype = (string)($it['entity_type'] ?? '');
                if ($etype !== 'product') {
                    $out[] = $it;
                    continue;
                }

                $uid = (int)($it['user_id'] ?? 0);
                $pid = (int)($it['entity_id'] ?? 0);
                $createdAt = (string)($it['created_at'] ?? '');
                $ts = $createdAt !== '' ? strtotime($createdAt) : false;
                if ($uid <= 0 || $pid <= 0 || $ts === false) {
                    $out[] = $it;
                    continue;
                }

                $createdKey = $uid . '|' . $pid;
                $createdMatch = null;
                if (isset($createdIndex[$createdKey])) {
                    foreach ($createdIndex[$createdKey] as $c) {
                        $cts = (int)($c['ts'] ?? 0);
                        if ($cts <= 0) continue;
                        if ($ts >= $cts && $ts <= ($cts + $creationWindowSeconds)) {
                            $createdMatch = $c;
                            break;
                        }
                    }
                }

                if ($createdMatch) {
                    $cid = (int)($createdMatch['id'] ?? 0);
                    $gKey = 'created|' . $uid . '|' . $pid . '|' . $cid;
                } else {
                    $bucketTs = (int)(floor($ts / $bucketSeconds) * $bucketSeconds);
                    $gKey = $uid . '|' . $pid . '|' . $bucketTs;
                }

                if (!isset($groups[$gKey])) {
                    $base = $it;
                    $hasCreated = false;
                    if (str_starts_with($gKey, 'created|')) {
                        $parts = explode('|', $gKey);
                        $cid = (int)($parts[3] ?? 0);
                        if ($cid > 0 && isset($createdItemsById[$cid])) {
                            $base = $createdItemsById[$cid];
                            $hasCreated = true;
                        }
                    }
                    $baseCreatedAt = (string)($base['created_at'] ?? $createdAt);
                    $groups[$gKey] = [
                        'sort_id' => (int)($it['id'] ?? 0),
                        'created_at' => $baseCreatedAt,
                        'base' => $base,
                        'has_created' => $hasCreated,
                        'buckets' => [
                            'main' => [],
                            'stock' => [],
                            'sku' => [],
                            'variant_name' => [],
                        ],
                    ];
                    $order[] = $gKey;
                }

                $a = (string)($it['action'] ?? '');
                if ($a === 'product_created') {
                    $groups[$gKey]['has_created'] = true;
                }

                $meta = [];
                if (!empty($it['metadata'])) {
                    $d = json_decode((string)$it['metadata'], true);
                    if (is_array($d)) $meta = $d;
                }

                $bucket = 'main';
                if ($a === 'stock_updated') {
                    $bucket = 'stock';
                }

                if ($a === 'product_updated') {
                    $changes = [];
                    if (isset($meta['changes']) && is_array($meta['changes'])) $changes = $meta['changes'];
                    if (isset($meta['from'], $meta['to']) && is_array($meta['from']) && is_array($meta['to'])) {
                        foreach ($meta['to'] as $k => $v) {
                            $before = $meta['from'][$k] ?? null;
                            if ($before !== $v) {
                                $changes[(string)$k] = ['before' => $before, 'after' => $v];
                            }
                        }
                    }
                    if (isset($changes['sku'])) $bucket = 'sku';
                    if (isset($changes['title']) || isset($changes['name'])) {
                        $vid = isset($meta['variant_id']) ? (int)$meta['variant_id'] : 0;
                        if ($vid > 0) $bucket = 'variant_name';
                    }
                }

                $groups[$gKey]['buckets'][$bucket][] = $it;

                $id = (int)($it['id'] ?? 0);
                if ($id > (int)$groups[$gKey]['sort_id']) {
                    $groups[$gKey]['sort_id'] = $id;
                    $groups[$gKey]['base'] = $it;
                    $groups[$gKey]['created_at'] = $createdAt;
                }
            }

            $final = [];
            foreach ($out as $it) { $final[] = $it; }

            $productGroups = [];
            foreach ($order as $gKey) { $productGroups[] = $groups[$gKey]; }
            usort($productGroups, fn($a,$b) => (int)$b['sort_id'] <=> (int)$a['sort_id']);

            foreach ($productGroups as $g) {
                $base = $g['base'];
                $b = $g['buckets'];

                if (!empty($g['has_created'])) {
                    $all = array_merge($b['main'], $b['stock'], $b['sku'], $b['variant_name']);
                    $meta = ['biz_group' => ['bucket' => 'created', 'count' => count($all), 'events' => $packBizEvents($all)]];
                    $row = $base;
                    $row['row_type'] = 'event';
                    $row['action'] = 'product_created';
                    $row['metadata'] = json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    $final[] = $row;
                    continue;
                }

                if (!empty($b['main'])) {
                    $meta = ['biz_group' => ['bucket' => 'main', 'count' => count($b['main']), 'events' => $packBizEvents($b['main'])]];
                    $row = $base;
                    $row['row_type'] = 'event';
                    $row['action'] = $pickCanonicalProductAction($b['main']);
                    $row['metadata'] = json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    $final[] = $row;
                }

                if (!empty($b['stock'])) {
                    $meta = ['biz_group' => ['bucket' => 'stock', 'count' => count($b['stock']), 'events' => $packBizEvents($b['stock'])]];
                    $row = $base;
                    $row['row_type'] = 'event';
                    $row['action'] = 'stock_updated';
                    $row['metadata'] = json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    $final[] = $row;
                }

                if (!empty($b['sku'])) {
                    $meta = ['biz_group' => ['bucket' => 'sku', 'count' => count($b['sku']), 'events' => $packBizEvents($b['sku'])]];
                    $row = $base;
                    $row['row_type'] = 'event';
                    $row['action'] = 'product_updated';
                    $row['metadata'] = json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    $final[] = $row;
                }

                if (!empty($b['variant_name'])) {
                    $meta = ['biz_group' => ['bucket' => 'variant_name', 'count' => count($b['variant_name']), 'events' => $packBizEvents($b['variant_name'])]];
                    $row = $base;
                    $row['row_type'] = 'event';
                    $row['action'] = 'product_updated';
                    $row['metadata'] = json_encode($meta, JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
                    $final[] = $row;
                }
            }

            usort($final, fn($a,$b) => (int)($b['id'] ?? 0) <=> (int)($a['id'] ?? 0));
            return $final;
        };

        $actividadRaw = $groupBusinessProductEvents($actividadRaw);

        $statusOpts = function_exists('order_status_options') ? order_status_options() : [];

        $statusFallback = [
            'pending_payment' => 'Pendiente de pago',
            'payment_failed'  => 'Pago fallido',
            'payment_expired' => 'Pago expirado',
            'paid'            => 'Pagado',
            'processing'      => 'Preparando pedido',
            'packed'          => 'Empaquetado',
            'shipped'         => 'Enviado',
            'delivered'       => 'Entregado',
            'cancelled'       => 'Cancelado',
            'refunded'        => 'Reembolsado',
            'chargeback'      => 'Contracargo',
        ];

        $statusLabel = function(string $st) use ($statusOpts, $statusFallback): string {
            foreach ($statusOpts as $op) {
                if (($op['value'] ?? '') === $st) return (string)($op['label'] ?? $st);
            }
            return $statusFallback[$st] ?? $st;
        };

        $statusIcon = function(string $st): string {
            return match ($st) {
                'paid'            => '💳',
                'processing'      => '🧑‍🍳',
                'packed'          => '📦',
                'shipped'         => '🚚',
                'delivered'       => '✅',
                'cancelled'       => '❌',
                'refunded'        => '💸',
                'chargeback'      => '⚠️',
                'pending_payment' => '🕒',
                'payment_failed'  => '⚠️',
                'payment_expired' => '⏳',
                default           => '🔄',
            };
        };

        $humanizeAction = function(string $action): string {
            $pretty = str_replace('_', ' ', $action);
            $pretty = mb_strtolower($pretty);
            return mb_convert_case($pretty, MB_CASE_TITLE, 'UTF-8');
        };

        
        $metaStatus = function(array $meta, string $key, string $fallback=''): string {
            $candidates = [$key, $key.'_status', $key.'_state', $key.'Status', $key.'State'];
            foreach ($candidates as $k) {
                if (isset($meta[$k]) && is_string($meta[$k]) && $meta[$k] !== '') return $meta[$k];
            }
            // alias comunes
            $alias = [
              'from' => ['old','old_status','previous','prev','before','from_status','status_from'],
              'to'   => ['new','new_status','after','after_status','to_status','status_to','status'],
            ];
            if (isset($alias[$key])) {
                foreach ($alias[$key] as $k) {
                    if (isset($meta[$k]) && is_string($meta[$k]) && $meta[$k] !== '') return $meta[$k];
                }
            }
            return $fallback;
        };

$timeAgoFromSeconds = function($diff): string {
            if ($diff === null) return '';
            $d = (int)$diff;
            if ($d < 0) $d = 0;
            if ($d < 60)  return 'hace ' . max(1, $d) . ' s';
            if ($d < 3600) return 'hace ' . max(1, (int)floor($d/60)) . ' min';
            if ($d < 86400) return 'hace ' . max(1, (int)floor($d/3600)) . ' h';
            if ($d < 172800) return 'ayer';
            if ($d < 604800) return 'hace ' . max(2, (int)floor($d/86400)) . ' días';
            return '';
        };

$map = function(array $a) use ($statusLabel, $statusIcon, $humanizeAction, $metaStatus, $timeAgoFromSeconds): array {
            $action = (string)($a['action'] ?? '');
            $type   = (string)($a['entity_type'] ?? '');
            $id     = (int)($a['entity_id'] ?? 0);
            $actor  = (string)($a['actor'] ?? 'Sistema');
            $created= (string)($a['created_at'] ?? '');
            $ageSeconds = isset($a['age_seconds']) && is_numeric($a['age_seconds']) ? (int)$a['age_seconds'] : null;
            $when = $timeAgoFromSeconds($ageSeconds);
            $ts = 0;
            if ($created) {
                $tmp = strtotime($created);
                $ts = $tmp ? (int)$tmp : 0;
            }

            $meta = [];
            if (!empty($a['metadata'])) {
                if (is_string($a['metadata'])) {
                    $meta = json_decode($a['metadata'], true) ?: [];
                } elseif (is_array($a['metadata'])) {
                    $meta = $a['metadata'];
                }
            }

            $icon = '🧩';
            $text = 'Acción registrada';
            $url  = null;
            $groupKey = $action.'|'.$type.'|'.$id;

            if ($type === 'order') {
                $url = $id ? url('/admin/orders/show?id='.$id) : null;
                $st  = (string)($a['order_status'] ?? '');
                $stLabel = $st ? $statusLabel($st) : '—';

                if ($action === 'order_created') {
                    $icon='🧾'; $text="Pedido #$id creado";
                } elseif ($action === 'order_paid') {
                    $icon='💳'; $text="Pedido #$id pagado";
                } elseif ($action === 'order_cancelled') {
                    $icon='❌'; $text="Pedido #$id cancelado";
                } elseif ($action === 'order_status_changed') {
                    $from = $metaStatus($meta, 'from', '');
                    $to   = $metaStatus($meta, 'to', '');
                    // fallback extremo: si no hay metadata, caemos al status actual del pedido (puede ser inexacto)
                     $usedFallback = false;
                    if ($to === '') { $to = (string)($a['order_status'] ?? ''); $usedFallback = true; }

                    $fromLabel = $from !== '' ? $statusLabel($from) : '';
                    $toLabel   = $to !== '' ? $statusLabel($to) : '—';

                    $icon = $to !== '' ? $statusIcon($to) : '🔄';

                    if ($fromLabel !== '') {
                        $text = "Pedido #$id: $fromLabel → $toLabel";
                        $groupKey = $action.'|'.$type.'|'.$id.'|'.$from.'|'.$to;
                    } else {
                        $text = "Pedido #$id → $toLabel";
                        $groupKey = $action.'|'.$type.'|'.$id.'|'.$to;
                        if ($usedFallback) { $groupKey .= '|'.$a['id']; }
                    }
                } else {
                    $icon='🧾'; $text="Pedido #$id actualizado";
                }
            } elseif ($type === 'product') {
                // Nota: evitar warnings por variables no definidas y tolerar producto sin nombre
                $productName = isset($a['product_name']) ? (string)$a['product_name'] : '';
                $label = $productName !== '' ? '“'.$productName.'”' : "#$id";
                $url = $id ? url('/admin/products/edit?id='.$id) : null;

                if ($action === 'product_created') {
                    $icon='🧰'; $text="Producto $label creado";
                } elseif ($action === 'product_updated') {
                    $icon='✏️'; $text="Producto $label actualizado";
                } elseif ($action === 'stock_updated') {
                    $icon='📦'; $text="Stock actualizado — $label";
                } else {
                    $icon='🧰'; $text="Producto $label — cambio registrado";
                }
            } elseif ($type === 'import') {
                $icon='⬆️'; $text='Importación registrada';
            } elseif ($action) {
                $icon='🧩';
                $suffix = $type && $id ? " ($type #$id)" : ($type ? " ($type)" : "");
                $text = $humanizeAction($action) . $suffix;
            }

            // Si el evento es un "group" (request_id), usamos el contador calculado
            $count = 1;
            if (($a['row_type'] ?? '') === 'group' && isset($meta['group']['count'])) {
                $count = max(1, (int)$meta['group']['count']);
            }

            return [
                'icon' => $icon,
                'text' => $text,
                'actor' => $actor,
                'when' => $when,
                'created_at' => $created,
                'ts' => $ts,
                'url' => $url,
                'group_key' => $groupKey,
                'count' => $count,
            ];
        };

        $mapped = array_map($map, $actividadRaw);

        // La lista ya viene agrupada como auditoría (por request_id). Solo limitamos.
        $actividad = array_slice($mapped, 0, 10);
// Pedidos por gestionar
        $sql = "SELECT o.id, o.created_at, o.status, o.total,
                      COALESCE(u.name, u.email) AS customer
                FROM orders o
                LEFT JOIN users u ON u.id = o.user_id
                WHERE o.status IN ('pending_payment','payment_failed','payment_expired','paid','processing','packed')
                ORDER BY o.created_at DESC
                LIMIT 10";
        $raw = $db->query($sql)->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $pendingOrders = array_map(function(array $o){
            $id = (int)($o['id'] ?? 0);
            $st = (string)($o['status'] ?? 'pending_payment');

            $opts = function_exists('order_status_options')
                ? order_status_options()
                : [
                    ['value'=>'pending_payment','label'=>'Pendiente de pago'],
                    ['value'=>'payment_failed','label'=>'Pago fallido'],
                    ['value'=>'payment_expired','label'=>'Pago expirado'],
                    ['value'=>'paid','label'=>'Pagado'],
                    ['value'=>'processing','label'=>'Preparando pedido'],
                    ['value'=>'packed','label'=>'Empaquetado'],
                    ['value'=>'shipped','label'=>'Enviado'],
                    ['value'=>'delivered','label'=>'Entregado'],
                    ['value'=>'cancelled','label'=>'Cancelado'],
                    ['value'=>'refunded','label'=>'Reembolsado'],
                    ['value'=>'chargeback','label'=>'Contracargo'],
                  ];

            $label = $st;
            foreach ($opts as $op) { if (($op['value'] ?? '') === $st) { $label = (string)($op['label'] ?? $st); break; } }

            // timestamp simple para prioridades/urgente
            $createdAt = (string)($o['created_at'] ?? '');
            $ts = $createdAt ? strtotime($createdAt) : 0;

            return [
                'id'         => $id,
                'created_at' => $createdAt,
                'created_at_ts' => $ts,
                'status'     => $st,
                'status_label' => $label,
                'customer'   => (string)($o['customer'] ?? ''),
                'total'      => (int)($o['total'] ?? 0),
            ];
        }, $raw);

        // ===== Alertas (operativas) =====
        $alerts = [];
        $pluralize = static function (int $count, string $singular, string $plural): string {
            return $count === 1 ? $singular : $plural;
        };

        // Pedidos por gestionar con +24hs (mismo set de estados que el panel)
        $stOver24 = $db->query(
            "SELECT COUNT(*) FROM orders
             WHERE status IN ('pending_payment','payment_failed','payment_expired','paid','processing','packed')
               AND created_at < (NOW() - INTERVAL 24 HOUR)"
        );
        $over24 = (int)$stOver24->fetchColumn();
        if ($over24 > 0) {
            $alerts[] = [
                'level' => 'danger',
                'text'  => sprintf('%d %s %s hace más de 24 h', $over24, $pluralize($over24, 'pedido', 'pedidos'), $pluralize($over24, 'pendiente', 'pendientes')),
                'href'  => url('/admin/orders'),
            ];
        }

        // Pagos pendientes (pendiente/fallido/expirado)
        $stPayPend = $db->query(
            "SELECT COUNT(*) FROM orders
             WHERE status IN ('pending_payment','payment_failed','payment_expired')"
        );
        $payPend = (int)$stPayPend->fetchColumn();
        if ($payPend > 0) {
            $alerts[] = [
                'level' => 'warning',
                'text'  => sprintf('%d %s %s / %s', $payPend, $pluralize($payPend, 'pago', 'pagos'), $pluralize($payPend, 'pendiente', 'pendientes'), $pluralize($payPend, 'fallido', 'fallidos')),
                'href'  => url('/admin/orders?status=pending_payment'),
            ];
        }

        // Stock crítico (en 0) usando el dataset ya calculado (simple + variantes)
        $stockZero = 0;
        foreach ($lowStock as $ls) {
            if ((int)($ls['stock'] ?? 0) <= 0) $stockZero++;
        }
        if ($stockZero > 0) {
            $alerts[] = [
                'level' => 'danger',
                'text'  => sprintf('%d %s con stock en 0', $stockZero, $pluralize($stockZero, 'item', 'items')),
                'href'  => url('/admin/products'),
            ];
        }

        // Productos inactivos con stock (posible pérdida de venta)
        $inactiveWithStock = 0;
        try {
            $inactiveWithStock = (int)$db->query(
                "SELECT COUNT(DISTINCT p.id)
                 FROM products p
                 LEFT JOIN inventory i ON i.product_id = p.id
                 LEFT JOIN product_variants pv ON pv.product_id = p.id AND pv.is_active=1
                 LEFT JOIN inventory_variants iv ON iv.variant_id = pv.id
                 WHERE p.is_active=0
                   AND (
                        (p.sale_mode='simple' AND COALESCE(i.stock,0) > 0)
                     OR (p.sale_mode='variants' AND COALESCE(iv.stock,0) > 0)
                   )"
            )->fetchColumn();
        } catch (\Throwable $e) {
            $inactiveWithStock = 0;
        }
        if ($inactiveWithStock > 0) {
            $alerts[] = [
                'level' => 'warning',
                'text'  => sprintf('%d %s %s con stock (posible pérdida de venta)', $inactiveWithStock, $pluralize($inactiveWithStock, 'producto', 'productos'), $pluralize($inactiveWithStock, 'inactivo', 'inactivos')),
                'href'  => url('/admin/products'),
            ];
        }

        // ===== Pendientes de hoy (resumen accionable) =====
        $todos = [
            'to_prepare' => 0,
            'payments_pending' => $payPend,
            'stock_zero' => $stockZero,
            'low_stock' => count($lowStock),
            'orders_over24' => $over24,
        ];
        try {
            $todos['to_prepare'] = (int)$db->query(
                "SELECT COUNT(*) FROM orders
                 WHERE status IN ('paid','processing')"
            )->fetchColumn();
        } catch (\Throwable $e) {
            $todos['to_prepare'] = 0;
        }

        // Analiticas: se mantienen disponibles en pagina dedicada /admin/analytics.
        $navigationAnalyticsData = (new DashboardNavigationAnalyticsService())->collect($db, 30);

        // Datos para vista
        $kpi = [
            'prod_activos'   => $prodActivos,
            'prod_inactivos' => $prodInactivos,
            'users_30d'      => $users30d,
        ];

        $opsKpi = [
            'orders_today'   => $ordersToday,
            'revenue_today'  => $revenueToday,
            'pending_orders' => $pendingCount,
            'users_total'    => $usersTotal,
        ];

        $viewModel = new DashboardViewModelService();
        $partialsViewModel = new DashboardPartialsViewModelService();
        $cards = $viewModel->buildDashboardCards($kpi, $opsKpi, $lowStock);
        $kpiCards = $partialsViewModel->prepareCombinedKpis(
            is_array($cards['catalogCards'] ?? null) ? $cards['catalogCards'] : [],
            is_array($cards['opsCards'] ?? null) ? $cards['opsCards'] : []
        );
        $pendingOrders = $viewModel->preparePendingOrders($pendingOrders);
        $pendingOrdersPanel = $partialsViewModel->preparePendingOrdersPanel(
            $pendingOrders,
            url('/admin/orders'),
            url('/admin/orders/show'),
            url('/admin/orders/status')
        );
        $alerts = $partialsViewModel->prepareAlerts($alerts);
        $todosCards = $partialsViewModel->prepareTodosCards($todos);
        $lowStock = $partialsViewModel->prepareLowStock($lowStock);
        $destacados = $partialsViewModel->prepareFeaturedProducts($destacados);
        $topClients30 = $partialsViewModel->prepareTopClients($topClients30);
        $import = $partialsViewModel->prepareLastImport($import);
        $actividad = $partialsViewModel->prepareActivity($actividad);
        $dashboardView = $partialsViewModel->buildIndexView(
            $alerts,
            $pendingOrdersPanel,
            $kpiCards,
            $destacados,
            $topClients30,
            $todosCards,
            $lowStock,
            $actividad
        );
        render_page('admin/dashboard/index', [
            'kpi'          => $kpi,
            'opsKpi'       => $opsKpi,
            'kpiCards'     => $kpiCards,
            'ordersTodayHref' => $cards['ordersTodayHref'],
            'lowStock'     => $lowStock,
            'destacados'   => $destacados,
            'navigationAnalytics' => $navigationAnalyticsData['navigationAnalytics'],

            'topClients30' => $topClients30,
            'import'       => $import,
            'actividad'    => $actividad,
            'pendingOrders'=> $pendingOrders,
            'pendingOrdersPanel' => $pendingOrdersPanel,
            'dashboardAlerts' => $alerts,
            'dashboardTodos'  => $todos,
            'dashboardTodosCards' => $todosCards,
            'dashboard_view' => $dashboardView,
            'pageId'       => 'admin-dashboard',
        ]);
    }

    public function trend(): void
    {
        $days = isset($_GET['days']) ? (int)$_GET['days'] : 30;
        if (!in_array($days, [7, 30, 90], true)) { $days = 30; }

        $start = date('Y-m-d', strtotime('-'.($days-1).' day'));

        $db = db();
        $stmt = $db->prepare(
            "SELECT DATE(created_at) AS d,
                    COALESCE(SUM(total),0) AS total,
                    COUNT(*) AS orders
             FROM orders
             WHERE created_at >= :start
               AND status IN ('paid','processing','packed','shipped','delivered')
             GROUP BY DATE(created_at)
             ORDER BY d ASC"
        );
        $stmt->bindValue(':start', $start);
        $stmt->execute();
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $map = [];
        foreach ($rows as $r) {
            $map[$r['d']] = [
                'd'      => $r['d'],
                'total'  => (int)($r['total'] ?? 0),
                'orders' => (int)($r['orders'] ?? 0),
            ];
        }

        $out = [];
        for ($i=$days-1; $i>=0; $i--) {
            $dkey = date('Y-m-d', strtotime("-$i day"));
            $out[] = $map[$dkey] ?? ['d'=>$dkey, 'total'=>0, 'orders'=>0];
        }

        json(['ok'=>true, 'data'=>$out, 'days'=>$days]);
    }
}



