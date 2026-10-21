<?php
namespace App\Services\Admin;

use App\Repositories\Admin\AdminAuditRepository;
use App\Services\Audit\Audit;
use App\Repositories\Catalog\InventoryRepository;
use App\Repositories\Payments\PaymentRepository;
use App\Services\Loyalty\LoyaltyOrderEventsService;
use PDO;
use Throwable;

/**
 * AdminOrderService
 * - Listado + detalle para admin (sin lógica en vistas)
 * - Máquina de estados (valores internos) con labels ES (helpers)
 * - Cancelación con reposición de stock (transaccional + locks)
 */
final class AdminOrderService
{
    public function __construct(private PDO $db) {}

    /** @return array{0:array<int,array<string,mixed>>,1:array<int,string>} */
    /**
     * Listado operacional paginado. El estado del pedido, pago y envío se obtienen
     * separadamente: nunca se trata payment_method como comprobación de pago.
     * @return array{0:array,1:array,2:array,3:array}
     */
    public function listForAdmin(array $filters): array
    {
        $statusOptions = $this->statusOptions();
        $groups = [
            'all' => [],
            'collect' => ['pending', 'pending_payment', 'payment_failed'],
            'prepare' => ['paid', 'processing', 'packed'],
            'shipping' => ['shipped'],
            'finished' => ['delivered', 'completed', 'cancelled', 'refunded', 'chargeback', 'payment_expired'],
        ];
        $view = (string)($filters['view'] ?? 'all');
        if (!isset($groups[$view])) $view = 'all';
        $q = trim((string)($filters['q'] ?? ''));
        $status = (string)($filters['status'] ?? '');
        $from = (string)($filters['from'] ?? '');
        $to = (string)($filters['to'] ?? '');
        $pm = (string)($filters['payment_method'] ?? '');
        $sort = (string)($filters['sort'] ?? 'newest');
        if (!in_array($sort, ['newest','oldest','total_desc'], true)) $sort = 'newest';
        $perPage = (int)($filters['per_page'] ?? 25);
        if (!in_array($perPage, [25,50], true)) $perPage = 25;
        $page = max(1, min(1000000,(int)($filters['page'] ?? 1)));
        $where = [];
        $args = [];

        if ($view !== 'all') {
            $states = $groups[$view];
            $where[] = 'o.status IN (' . implode(',',array_fill(0,count($states),'?')) . ')';
            foreach ($states as $state) $args[] = $state;
        }
        if ($status !== '' && in_array($status,$statusOptions,true)) {
            $where[]='o.status = ?';
            $args[]=$status;
        }
        if ($pm !== '') {
            $where[]='o.payment_method = ?';
            $args[]=$pm;
        }
        foreach (['from'=>'>=','to'=>'<='] as $key=>$operator) {
            $date=$key==='from' ? $from : $to;
            if ($date !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/',$date)) {
                $valid=\DateTimeImmutable::createFromFormat('!Y-m-d',$date);
                if ($valid && $valid->format('Y-m-d')===$date) {
                    $where[]='o.created_at '.$operator.' ?';
                    $args[]=$date.($key==='from'?' 00:00:00':' 23:59:59');
                }
            }
        }
        if ($q !== '') {
            if (preg_match('/^#?(\d+)$/',$q,$match)) {
                $where[]='o.id = ?'; $args[]=(int)$match[1];
            } else {
                $where[]='(u.email LIKE ? OR u.name LIKE ?)';
                $term='%'.$q.'%';
                $args[]=$term; $args[]=$term;
            }
        }
        $fromSql=' FROM orders o LEFT JOIN users u ON u.id=o.user_id';
        $whereSql=$where ? ' WHERE '.implode(' AND ',$where) : '';
        $countStmt=$this->db->prepare('SELECT COUNT(*)'.$fromSql.$whereSql);
        $countStmt->execute($args);
        $total=(int)$countStmt->fetchColumn();
        $pages=max(1,(int)ceil($total/$perPage));
        $page=min($page,$pages);
        $offset=($page-1)*$perPage;
        $orderBy=match($sort){
            'oldest'=>'o.created_at ASC, o.id ASC',
            'total_desc'=>'o.total DESC, o.id DESC',
            default=>'o.created_at DESC, o.id DESC',
        };
        $sql='SELECT o.id,o.created_at,o.status,o.payment_method,o.total,'
            .'COALESCE(u.name,u.email) AS customer,'
            .'(SELECT p.status FROM payments p WHERE p.order_id=o.id ORDER BY p.updated_at DESC,p.id DESC LIMIT 1) AS payment_status,'
            .'(SELECT os.status FROM order_shipments os WHERE os.order_id=o.id ORDER BY os.updated_at DESC LIMIT 1) AS shipment_status'
            .$fromSql.$whereSql.' ORDER BY '.$orderBy.' LIMIT '.$perPage.' OFFSET '.$offset;
        $stmt=$this->db->prepare($sql);
        $stmt->execute($args);
        $rows=$stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $orders=[];
        foreach($rows as $o) {
            $state=(string)($o['status']??'pending_payment');
            $when=(string)($o['created_at']??'');
            $dt=\DateTimeImmutable::createFromFormat('Y-m-d H:i:s',$when);
            $dateLabel=$dt ? $dt->format('d/m/Y') : substr($when,0,10);
            $timeLabel=$dt ? $dt->format('H:i') : substr($when,11,5);
            $orders[]=[
                'id'=>(int)$o['id'],
                'created_short'=>substr($when,0,16),
                'date_label'=>$dateLabel,
                'time_label'=>$timeLabel,
                'customer'=>trim((string)($o['customer']??''))?:'Cliente sin identificar',
                'status'=>$state,
                'payment_method'=>(string)($o['payment_method']??''),
                'payment_status'=>(string)($o['payment_status']??''),
                'shipment_status'=>(string)($o['shipment_status']??''),
                'status_label'=>function_exists('order_status_label_admin')?order_status_label_admin($state):$state,
                'badge_class'=>$this->badgeClass($state),
                'total_fmt'=>'$ '.number_format((int)($o['total']??0),0,',','.'),
                'href'=>url('/admin/orders/show').'?id='.(int)$o['id'],
            ];
        }
        $pmOptions=[];
        $pmStmt=$this->db->query("SELECT DISTINCT payment_method FROM orders WHERE payment_method IS NOT NULL AND payment_method <> '' ORDER BY payment_method");
        $pmOptions=array_values(array_filter(array_map(static fn($r)=>(string)($r['payment_method']??''),$pmStmt->fetchAll(PDO::FETCH_ASSOC)?:[])));
        $counts=['all'=>0,'collect'=>0,'prepare'=>0,'shipping'=>0,'finished'=>0];
        $countsStmt=$this->db->query('SELECT status,COUNT(*) AS total FROM orders GROUP BY status');
        foreach($countsStmt->fetchAll(PDO::FETCH_ASSOC)?:[] as $row){
            $st=(string)$row['status'];$n=(int)$row['total'];
            $counts['all']+=$n;
            foreach($groups as $key=>$statuses){
                if($key!=='all' && in_array($st,$statuses,true))$counts[$key]+=$n;
            }
        }
        $meta=['page'=>$page,'per_page'=>$perPage,'pages'=>$pages,'total'=>$total,
            'view'=>$view,'sort'=>$sort,'quick_counts'=>$counts];
        return [$orders,$statusOptions,$pmOptions,$meta];
    }

    /**
     * Marcar como pagado (solo Transferencia bancaria)
     * - Requiere status pending_payment
     * - Requiere payment_method transferencia bancaria (transfer_bank/transferencia)
     */
    public function markPaidTransfer(int $adminUserId, int $orderId): array
    {
        if ($orderId <= 0) return [false, 'params', ['reason' => 'order_id']];

        $audit = new AdminAuditRepository($this->db);

        try {
            $this->db->beginTransaction();

            $st = $this->db->prepare("SELECT id, user_id, total, status, payment_method FROM orders WHERE id = ? FOR UPDATE");
            $st->execute([$orderId]);
            $order = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$order) {
                $this->db->rollBack();
                return [false, 'not_found', []];
            }

            $current = (string)($order['status'] ?? 'pending_payment');
            $pm      = (string)($order['payment_method'] ?? '');

            if (!in_array($pm, ['transfer_bank','transferencia','transfer','bank_transfer'], true)) {
                $this->db->rollBack();
                return [false, 'not_transfer_bank', ['payment_method' => $pm]];
            }
            if ($current !== 'pending_payment') {
                $this->db->rollBack();
                return [false, 'invalid_state', ['current' => $current]];
            }

            $paid = 'paid';
            $next = 'processing';
            if (!$this->canTransition($current, $paid) || !$this->canTransition($paid, $next)) {
                $this->db->rollBack();
                return [false, 'invalid_transition', ['current' => $current, 'next' => $next]];
            }

            $u = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $u->execute([$next, $orderId]);

            $audit->log(
                $adminUserId,
                'order_mark_paid_transfer',
                'order',
                $orderId,
                ['from' => $current, 'to' => $next]
            );


            $this->db->commit();

            // Lealtad: registrar evento de puntos (best-effort).
            try {
                $uid = (int)($order['user_id'] ?? 0);
                $total = (int)($order['total'] ?? 0);
                if ($uid > 0 && $total > 0) {
                    (new LoyaltyOrderEventsService($this->db))->onOrderStatusTransition(
                        $orderId,
                        $uid,
                        $total,
                        $current,
                        $next,
                        'admin_mark_paid_transfer'
                    );
                }
            } catch (\Throwable $e) {
                // ignore
            }

            // Mantener stats del cliente (opción B) sincronizados
            if (!empty($order['user_id'])) {
                try {
                    (new CustomerStatsService($this->db))->rebuildForUser((int)$order['user_id']);
                } catch (\Throwable $e) {
                    // no rompemos flujo admin por stats
                }
            }


            return [true, '', ['previous_status' => $current, 'current' => $next, 'next' => $next, 'label' => function_exists('order_status_label_admin') ? order_status_label_admin($next) : $next, 'badge_class' => $this->badgeClass($next)]];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if (function_exists('log_error')) log_error('AdminOrderService@markPaidTransfer: '.$e->getMessage());
            return [false, 'exception', ['msg' => $e->getMessage()]];
        }
    }

    /**
     * Marca como pagado un pedido pendiente confirmado por Mercado Pago.
     *
     * @param array<string,mixed> $context
     * @return array{0:bool,1:string,2:array<string,mixed>}
     */
    public function markPaidMercadoPago(int $orderId, string $paymentId, array $context = []): array
    {
        if ($orderId <= 0) return [false, 'params', ['reason' => 'order_id']];

        try {
            $this->db->beginTransaction();

            $st = $this->db->prepare("SELECT id, user_id, total, status FROM orders WHERE id = ? FOR UPDATE");
            $st->execute([$orderId]);
            $order = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$order) {
                $this->db->rollBack();
                return [false, 'not_found', []];
            }

            $current = (string)($order['status'] ?? 'pending_payment');
            if (in_array($current, ['paid', 'processing', 'packed', 'shipped', 'delivered'], true)) {
                $this->db->commit();
                return [true, '', ['previous_status' => $current, 'current' => $current, 'next' => $current, 'noop' => true]];
            }

            if ($current !== 'pending_payment') {
                $this->db->rollBack();
                return [false, 'invalid_state', ['current' => $current]];
            }

            $next = 'paid';
            if (!$this->canTransition($current, $next)) {
                $this->db->rollBack();
                return [false, 'invalid_transition', ['current' => $current, 'next' => $next]];
            }

            $u = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $u->execute([$next, $orderId]);

            $this->db->commit();

            try {
                $uid = (int)($order['user_id'] ?? 0);
                $total = (int)($order['total'] ?? 0);
                if ($uid > 0 && $total > 0) {
                    (new LoyaltyOrderEventsService($this->db))->onOrderStatusTransition(
                        $orderId,
                        $uid,
                        $total,
                        $current,
                        $next,
                        'mp_webhook_mark_paid'
                    );
                }
            } catch (\Throwable $e) {
                // ignore
            }

            if (!empty($order['user_id'])) {
                try {
                    (new CustomerStatsService($this->db))->rebuildForUser((int)$order['user_id']);
                } catch (\Throwable $e) {
                    // no rompemos webhook por stats
                }
            }

            Audit::log('order_mark_paid_mp', 'order', $orderId, array_merge([
                'provider' => 'mercadopago',
                'payment_id' => $paymentId,
                'from' => $current,
                'to' => $next,
                'paid_transition' => $paid,
                'source' => 'webhook',
                'reconciliation_mode' => 'existing_order',
            ], $context));
            cache_forget('feed:audit_recent8');

            return [true, '', [
                'previous_status' => $current,
                'current' => $next,
                'next' => $next,
                'label' => function_exists('order_status_label_admin') ? order_status_label_admin($next) : $next,
                'badge_class' => $this->badgeClass($next),
            ]];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if (function_exists('log_error')) log_error('AdminOrderService@markPaidMercadoPago: '.$e->getMessage());
            return [false, 'exception', ['msg' => $e->getMessage()]];
        }
    }

/** @return array{0:array<string,mixed>|null,1:array<int,array<string,mixed>>} */
    public function getDetailForAdmin(int $orderId): array
    {
        if ($orderId <= 0) return [null, []];

        $st = $this->db->prepare(
            "SELECT o.*, COALESCE(u.name,u.email) AS customer, u.email
             FROM orders o
             LEFT JOIN users u ON u.id=o.user_id
             WHERE o.id=?"
        );
        $st->execute([$orderId]);
        $order = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$order) return [null, []];

        $si = $this->db->prepare(
            "SELECT 
                    oi.product_id,
                    oi.variant_id,
                    oi.qty,
                    oi.price,
                    COALESCE(oi.product_sku, p.sku)  AS product_sku,
                    COALESCE(oi.product_name, p.name) AS product_name,
                    oi.variant_sku,
                    oi.variant_title,
                    oi.variant_attributes_json
             FROM order_items oi
             LEFT JOIN products p ON p.id = oi.product_id
             WHERE oi.order_id = ?
             ORDER BY oi.id"
        );
        $si->execute([$orderId]);
        $raw = $si->fetchAll(PDO::FETCH_ASSOC) ?: [];

        $items = [];
        $groups = [];
        foreach ($raw as $it) {
            $qty   = (int)($it['qty'] ?? 0);
            $price = (int)($it['price'] ?? 0);

            $variantId = (int)($it['variant_id'] ?? 0);
            $productSku = (string)($it['product_sku'] ?? '—');
            $productName = (string)($it['product_name'] ?? '—');
            $variantSku = (string)($it['variant_sku'] ?? '');
            $variantTitle = (string)($it['variant_title'] ?? '');
            $variantAttrsJson = (string)($it['variant_attributes_json'] ?? '');

            $variantLabel = '';
            if ($variantId > 0) {
                if ($variantTitle !== '') {
                    $variantLabel = $variantTitle;
                } elseif ($variantAttrsJson !== '') {
                    $decoded = json_decode($variantAttrsJson, true);
                    if (is_array($decoded)) {
                        // soporta array de {name,value} o {attribute,value}
                        $vals = [];
                        foreach ($decoded as $row) {
                            if (is_array($row)) {
                                $v = (string)($row['value'] ?? $row['val'] ?? '');
                                if ($v !== '') $vals[] = $v;
                            }
                        }
                        if (!empty($vals)) $variantLabel = implode(' - ', $vals);
                    }
                }
            }

            $skuToShow = ($variantId > 0 && $variantSku !== '') ? $variantSku : $productSku;

            // Agrupamos líneas idénticas (mismo producto+variante+precio+sku)
            $k = implode('|', [
                (string)($it['product_id'] ?? 0),
                (string)$variantId,
                (string)$price,
                $skuToShow,
                $productName,
                $variantLabel
            ]);

            if (!isset($groups[$k])) {
                $groups[$k] = [
                    'product_id'    => (int)($it['product_id'] ?? 0),
                    'variant_id'    => $variantId,
                    'sku'           => $skuToShow,
                    'name'          => $productName,
                    'variant_label' => $variantLabel,
                    'qty'           => 0,
                    'price'         => $price,
                ];
            }

            $groups[$k]['qty'] += $qty;
        }

        foreach ($groups as $g) {
            $qty = (int)$g['qty'];
            $price = (int)$g['price'];
            $sub = $price * $qty;

            $items[] = [
                'product_id'    => (int)$g['product_id'],
                'variant_id'    => (int)$g['variant_id'],
                'sku'           => (string)$g['sku'],
                'name'          => (string)$g['name'],
                'variant_label' => (string)$g['variant_label'],
                'qty'           => $qty,
                'price_fmt'     => '$ '.number_format($price, 0, ',', '.'),
                'sub_fmt'       => '$ '.number_format($sub,   0, ',', '.'),
            ];
        }
$status = (string)($order['status'] ?? 'pending_payment');
        $opts = function_exists('order_status_allowed_next') ? array_merge([$status], order_status_allowed_next($status)) : $this->statusOptions();
        // de-dup
        $uniq = [];
        foreach ($opts as $s) { if (!in_array($s, $uniq, true)) $uniq[] = $s; }

        
        $payRepo = new PaymentRepository($this->db);
        $payment = $payRepo->findLatestByOrderId((int)$order['id']);
$orderView = [
            'id'               => (int)$order['id'],
            'created_short'    => substr((string)($order['created_at'] ?? ''), 0, 16),
            'customer_label'   => (string)($order['customer'] ?? $order['email'] ?? '—'),
            'customer_email'   => (string)($order['email'] ?? ''),
            'payment_method'   => (string)($order['payment_method'] ?? ''),
            'payment'          => $payment,
            'status'           => $status,
            'status_label'     => function_exists('order_status_label_admin') ? order_status_label_admin($status) : $status,
            'status_options'   => $uniq,
            'badge_class'      => $this->badgeClass($status),
            'total_fmt'        => '$ '.number_format((int)($order['total'] ?? 0), 0, ',', '.'),
            'status_endpoint'  => url('/admin/orders/status'),
            'index_url'        => url('/admin/orders'),

            // Snapshot entrega (orders.*)
            'shipping_full_name' => (string)($order['shipping_full_name'] ?? ''),
            'shipping_phone'     => (string)($order['shipping_phone'] ?? ''),
            'shipping_street'    => (string)($order['shipping_street'] ?? ''),
            'shipping_number'    => (string)($order['shipping_number'] ?? ''),
            'shipping_floor'     => (string)($order['shipping_floor'] ?? ''),
            'shipping_apartment' => (string)($order['shipping_apartment'] ?? ''),
            'shipping_city'      => (string)($order['shipping_city'] ?? ''),
            'shipping_state'     => (string)($order['shipping_state'] ?? ''),
            'shipping_zip'       => (string)($order['shipping_zip'] ?? ''),
            'shipping_notes'     => (string)($order['shipping_notes'] ?? ''),
        ];

        return [$orderView, $items];
    }

    /**
     * Cambia estado con validación + side-effects (cancel repone stock).
     * @return array{0:bool,1:string,2:array<string,mixed>}
     */
    public function changeStatus(int $adminUserId, int $orderId, string $next): array
    {
        $next = trim($next);
        if ($orderId <= 0) return [false, 'params', ['reason' => 'order_id']];
        if (!in_array($next, $this->statusOptions(), true)) return [false, 'invalid_status', ['next' => $next]];

        $inv   = new InventoryRepository($this->db);

        try {
            $this->db->beginTransaction();

            $st = $this->db->prepare("SELECT id, user_id, total, status FROM orders WHERE id = ? FOR UPDATE");
            $st->execute([$orderId]);
            $order = $st->fetch(PDO::FETCH_ASSOC) ?: null;
            if (!$order) {
                $this->db->rollBack();
                return [false, 'not_found', []];
            }

            $current = (string)($order['status'] ?? 'pending_payment');
            // Idempotencia: si el estado ya es el solicitado, no hacemos nada (evita doble restock / errores 422)
            if ($current === $next) {
                $this->db->commit();
                return [true, null, ['previous_status' => $current, 'current' => $current, 'next' => $current, 'noop' => true]];
            }

            if (!$this->canTransition($current, $next)) {
                $this->db->rollBack();
                return [false, 'invalid_transition', ['current' => $current, 'next' => $next]];
            }

            $restocked = false;
            $restockItems = [];
            $shouldRestock = false;
            if ($next === 'payment_expired' && in_array($current, ['pending_payment', 'payment_failed'], true)) {
                $shouldRestock = true;
            }
            if ($next === 'cancelled' && in_array($current, ['pending_payment', 'payment_failed', 'paid', 'processing', 'packed'], true)) {
                $shouldRestock = true;
            }

            // Cancelación: repone stock una vez (si corresponde)
            if ($shouldRestock) {
                $cancelable = function_exists('order_status_is_cancelable') ? order_status_is_cancelable($current) : true;
                if ($next === 'cancelled' && !$cancelable) {
                    $this->db->rollBack();
                    return [false, 'cannot_cancel', ['current' => $current]];
                }

                // No asumir columnas: intentamos leer variant_id si existe, con fallback.
                try {
                    $si = $this->db->prepare("SELECT product_id, variant_id, qty FROM order_items WHERE order_id = ? FOR UPDATE");
                    $si->execute([$orderId]);
                    $items = $si->fetchAll(PDO::FETCH_ASSOC) ?: [];
                } catch (\Throwable $e) {
                    $si = $this->db->prepare("SELECT product_id, qty FROM order_items WHERE order_id = ? FOR UPDATE");
                    $si->execute([$orderId]);
                    $items = $si->fetchAll(PDO::FETCH_ASSOC) ?: [];
                }

                $restockItems = [];

                foreach ($items as $it) {
                    $pid = (int)($it['product_id'] ?? 0);
                    $qty = (int)($it['qty'] ?? 0);
                    $vid = (int)($it['variant_id'] ?? 0);
                    if ($pid <= 0 || $qty <= 0) continue;

                    // Reponer stock según tipo de ítem (simple vs variante)
                    if ($vid > 0) {
                        $inv->lockVariantStockForUpdate($vid);
                        $inv->incrementVariantStock($vid, $qty);
                    } else {
                        $inv->lockStockForUpdate($pid);
                        $inv->incrementStock($pid, $qty);
                    }

                    $restockItems[] = [
                        'product_id' => $pid,
                        'variant_id' => $vid,
                        'qty' => $qty,
                    ];
                }
                $restocked = true;
            }

            $u = $this->db->prepare("UPDATE orders SET status = ? WHERE id = ?");
            $u->execute([$next, $orderId]);

            $this->db->commit();

            // Lealtad: registrar eventos si el cambio cruza el umbral de "pagado".
            // Best-effort: nunca debe romper el flujo admin.
            try {
                $uid = (int)($order['user_id'] ?? 0);
                $total = (int)($order['total'] ?? 0);
                if ($uid > 0 && $total > 0) {
                    (new LoyaltyOrderEventsService($this->db))->onOrderStatusTransition(
                        $orderId,
                        $uid,
                        $total,
                        $current,
                        $next,
                        'admin_change_status'
                    );
                }
            } catch (\Throwable $e) {
                // ignore
            }

            // Auditoría: usar el wrapper Audit para asegurar request_id (necesario para la vista agrupada
            // de /admin/audit y la Actividad reciente del dashboard). Si falla, jamás debe romper UX.
            Audit::log(
                'order_status_changed',
                'order',
                $orderId,
                [
                    'from' => $current,
                    'to'   => $next,
                    'restocked' => $restocked,
                    'restock_items' => $restocked ? ($restockItems ?? []) : [],
                ]
            );

            return [true, '', [
                'previous_status' => $current,
                'current' => $current,
                'next' => $next,
                'restocked' => $restocked,
                'next_label' => function_exists('order_status_label_admin') ? order_status_label_admin($next) : $next,
                'badge_class' => $this->badgeClass($next),
            ]];
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            if (function_exists('log_error')) log_error('AdminOrderService@changeStatus: '.$e->getMessage());
            return [false, 'exception', ['msg' => $e->getMessage()]];
        }
    }

    /** @return array<int,string> */
    private function statusOptions(): array
    {
        return function_exists('order_status_all') ? order_status_all() : [
            'pending_payment','payment_failed','payment_expired',
            'paid','processing','packed','shipped','delivered',
            'cancelled','refunded','chargeback',
        ];
    }

    private function badgeClass(string $status): string
    {
        return function_exists('order_status_badge_class_admin') ? order_status_badge_class_admin($status) : 'badge';
    }

    private function canTransition(string $from, string $to): bool
    {
        return function_exists('order_status_can_transition') ? order_status_can_transition($from, $to) : ($from === $to);
    }
}

