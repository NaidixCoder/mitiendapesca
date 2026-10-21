<?php
namespace App\Controllers\Admin;

use App\Services\Admin\AdminOrderService;
use App\Repositories\Billing\ComprobanteFiscalRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Billing\FacturacionArcaService;
use App\Services\Admin\Orders\ViewModels\OrdersIndexViewModel;
use App\Services\Admin\Orders\ViewModels\OrdersShowViewModel;
use App\Repositories\Orders\OrderShipmentRepository;
use App\Services\Receipts\TransferReceiptService;
use App\Support\Admin\ArcaBoxRenderer;
use PDO;

final class OrdersController
{
    private function db(): PDO { return \db(); }

    /** Renderiza el bloque ARCA (HTML) para refrescar vía AJAX sin F5. */
    private function renderArcaBoxHtml(int $orderId): string
    {
        return (new ArcaBoxRenderer($this->db()))->render($orderId);
    }

    // GET /admin/orders
    public function index(): void
    {
        admin_required('/admin/orders');

        $filters = [
            'q' => (string)($_GET['q'] ?? ''),
            'status' => (string)($_GET['status'] ?? ''),
            'payment_method' => (string)($_GET['payment_method'] ?? ''),
            'from' => (string)($_GET['from'] ?? ''),
            'to' => (string)($_GET['to'] ?? ''),
            'view' => (string)($_GET['view'] ?? 'all'),
            'sort' => (string)($_GET['sort'] ?? 'newest'),
            'per_page' => (int)($_GET['per_page'] ?? 25),
            'page' => (int)($_GET['page'] ?? 1),
        ];

        $svc = new AdminOrderService($this->db());
        [$orders, $statusOptions, $paymentMethodOptions, $listMeta] = $svc->listForAdmin($filters);

        $vm = (new OrdersIndexViewModel())->build([
            'orders' => $orders,
            'filters' => $filters,
            'statusOptions' => $statusOptions,
            'paymentMethodOptions' => $paymentMethodOptions,
            'meta' => $listMeta,
        ]);

        render_page('admin/orders/index', array_merge($vm, [
            'pageId' => 'admin-orders-index',
        ]));
    }

    // GET /admin/orders/show?id=...
    public function show(): void
    {
        admin_required('/admin/orders');

        $id = (int)($_GET['id'] ?? 0);
        $svc = new AdminOrderService($this->db());
        [$orderView, $items] = $svc->getDetailForAdmin($id);

        $shipRepo = new OrderShipmentRepository($this->db());
        $shipment = $shipRepo->getByOrderId($id);

        // Correo Argentino (FIX517) Ã¢â‚¬” gating para botón "Crear envío (Correo)"
        $correo = [
            'can_import' => false,
            'blocked_reason' => '',
            'mode' => 'test',
            'customer_id' => '',
            'mock_import' => false,
        ];
        try {
            $cfg = (new \App\Services\Shipping\CorreoArgentino\CorreoConfig($this->db()))->get();
            $correo['mode'] = (string)($cfg['mode'] ?? 'test');
            $correo['customer_id'] = trim((string)($cfg['customer_id'] ?? ''));
            $correo['mock_import'] = (bool)($cfg['mock_import'] ?? false);
            if ($correo['customer_id'] === '') {
                $correo['customer_id'] = trim((string)(new \App\Repositories\AppSettingsRepository($this->db()))->get('correo_customer_id', ''));
            }

            $already = $shipment && trim((string)($shipment['external_shipment_id'] ?? '')) !== '';
            if ($already) {
                $correo['blocked_reason'] = 'Ya importado.';
            } elseif ($correo['customer_id'] === '') {
                if ($correo['mode'] === 'test' && $correo['mock_import']) {
                    $correo['can_import'] = true;
                    $correo['blocked_reason'] = '';
                } else {
                    $correo['blocked_reason'] = 'Sin CustomerId (QA bloqueado).';
                }
            } else {
                $correo['can_import'] = true;
            }
        } catch (\Throwable $e) {
            $correo['can_import'] = false;
            $correo['blocked_reason'] = 'Config de Correo no disponible.';
        }

        // Solicitud Factura A (Etapa 3) Ã¢â‚¬” datos para banner operativa
        $orderFiscalRequest = null;
        $userFiscalProfile = null;
        try {
            // La solicitud es por pedido (order_id), no depende de que el pedido esté linkeado a user_id.
            $orderFiscalRequest = (new \App\Repositories\Orders\OrderFiscalRequestRepository())->findByOrderId($id);

            // Perfil fiscal es por usuario (si existe).
            $uid = (int)($orderView['user_id'] ?? 0);
            if ($uid > 0) {
                $userFiscalProfile = (new \App\Repositories\Users\UserFiscalProfileRepository())->findByUserId($uid);
            }
        } catch (\Throwable $e) {
            $orderFiscalRequest = null;
            $userFiscalProfile = null;
        }

        $receipts = [];
        try {
            $rsvc = new TransferReceiptService();
            $receipts = $rsvc->listForOrder($id);
        } catch (\Throwable $e) {
            $receipts = [];
        }

        $comprobante = null;
        $comprobantes = [];
        $creditNote = null;
        $comprobantesHasKind = false;
        try {
            $crepo = new ComprobanteFiscalRepository($this->db());
            $comprobante = $crepo->findByOrderId($id); // factura (kind=invoice)
            $comprobantesHasKind = $crepo->schemaHasKind();
            $comprobantes = $crepo->listByOrderId($id);

            // Buscar última Nota de Crédito asociada a la factura (si el schema soporta kind/related)
            if ($comprobante && $comprobantesHasKind) {
                $invoiceId = (int)($comprobante['id'] ?? 0);
                foreach ($comprobantes as $row) {
                    if ((string)($row['kind'] ?? '') !== 'credit_note') continue;
                    if ((int)($row['related_comprobante_id'] ?? 0) !== $invoiceId) continue;
                    $creditNote = $row;
                    break;
                }
            }
        } catch (\Throwable $e) {
            $comprobante = null;
            $comprobantes = [];
            $creditNote = null;
            $comprobantesHasKind = false;
        }

        // Snapshot de régimen fiscal del emisor desde app_settings (fuente de verdad).
        $settingsRepo = new AppSettingsRepository($this->db());
        $orderView['seller_tax_regime'] = (string)$settingsRepo->get('fiscal_seller_tax_regime', 'monotributo');


        // Config ARCA (para UI "inteligente" en Admin)
        try {
            $cfg = (new \App\Services\Billing\Arca\ArcaConfig($this->db()))->get();
            $orderView['arca_auto_emit'] = (int)($cfg['auto_emit'] ?? false) ? 1 : 0;
            $orderView['arca_auto_emit_trigger'] = (string)($cfg['auto_emit_trigger'] ?? 'paid');
        } catch (\Throwable $e) {
            $orderView['arca_auto_emit'] = 0;
            $orderView['arca_auto_emit_trigger'] = 'paid';
        }


        $showVm = (new OrdersShowViewModel())->build([
            'orderView' => $orderView,
            'items' => $items,
            'shipment' => $shipment,
            'receipts' => $receipts,
            'correo' => $correo,
            'orderFiscalRequest' => $orderFiscalRequest,
            'userFiscalProfile' => $userFiscalProfile,
        ]);

        render_page('admin/orders/show', array_merge([
            'orderView' => $orderView,
            'items'     => $items,
            'shipment'  => $shipment,
            'correo'    => $correo,
            'receipts'  => $receipts,
            'orderFiscalRequest' => $orderFiscalRequest,
            'userFiscalProfile' => $userFiscalProfile,
            'comprobante' => $comprobante,
            'comprobantes' => $comprobantes,
            'creditNote' => $creditNote,
            'comprobantesHasKind' => $comprobantesHasKind,
            'pageId'    => 'admin-orders-show',
            'hideHeader' => true,
            'hideFooter' => true,
        ], $showVm));
    }

    // GET /admin/orders/comprobante/descargar?order_id=..&file=..
    public function downloadReceipt(): void
    {
        admin_required('/admin/orders');

        $orderId = (int)($_GET['order_id'] ?? 0);
        $file = (string)($_GET['file'] ?? '');
        if ($orderId <= 0 || trim($file) === '') {
            redirect('/admin/orders?err=receipt');
            return;
        }

        $svc = new TransferReceiptService();
        $path = $svc->resolvePath($orderId, $file);
        if (!$path) {
            redirect('/admin/orders/show?id='.$orderId.'&err=receipt');
            return;
        }

        $name = basename($path);
        $mime = 'application/octet-stream';
        if (class_exists(\finfo::class)) {
            $fi = new \finfo(FILEINFO_MIME_TYPE);
            $mime = $fi->file($path) ?: $mime;
        }

        header('Content-Type: ' . $mime);
        header('Content-Disposition: attachment; filename="' . addslashes($name) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    // GET /admin/orders/comprobante/ver?order_id=..&file=..
    public function viewReceipt(): void
    {
        admin_required('/admin/orders');

        $orderId = (int)($_GET['order_id'] ?? 0);
        $file = (string)($_GET['file'] ?? '');
        if ($orderId <= 0 || trim($file) === '') {
            redirect('/admin/orders?err=receipt');
            return;
        }

        $svc = new TransferReceiptService();
        $path = $svc->resolvePath($orderId, $file);
        if (!$path) {
            redirect('/admin/orders/show?id='.$orderId.'&err=receipt');
            return;
        }

        $name = basename($path);
        $mime = $svc->detectMime($path);
        if (!$svc->isPreviewableMime($mime)) {
            redirect('/admin/orders/show?id='.$orderId.'&err=receipt');
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($name) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }

    
    // POST /admin/orders/mark-paid (form POST: id)
    public function markPaid(): void
    {
        admin_required('/admin/orders');
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $isJson = (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
               || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
               || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

        $input = $isJson ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;

        $id = (int)($input['id'] ?? 0);
        $returnTo = (string)($input['return_to'] ?? '');
if ($id <= 0) {
            redirect('/admin/orders?err=mark_paid');
            return;
        }

        $adminId = (int)($_SESSION['uid'] ?? 0);

        $svc = new AdminOrderService($this->db());
        [$ok, $err, $result] = $svc->markPaidTransfer($adminId, $id);

        // Auto-emisión ARCA (si está habilitada en Ajustes)
        $autoArca = null;
        if ($ok) {
            try {
                $cfg = (new \App\Services\Billing\Arca\ArcaConfig($this->db()))->get();
                if (($cfg['auto_emit_trigger'] ?? 'paid') === 'paid') {
                    $arcaSvc = new FacturacionArcaService($this->db());
                    $autoArca = $arcaSvc->emitirAutoDesdePedido($adminId, $id, 'admin_mark_paid_transfer');
                }
            } catch (\Throwable $e) {
                // no bloqueante
                if (function_exists('log_error')) log_error('arca_auto_emit(markPaid): '.$e->getMessage());
            }
        }


        if ($isJson) {
            if (!$ok) {
                json(['ok' => false, 'error' => $err, 'result' => $result], 422);
                return;
            }
            json(['ok' => true, 'result' => $result, 'arca' => $autoArca]);
            return;
        }

        if (!$ok) {
            redirect('/admin/orders?err='.urlencode($err));
            return;
        }

        // HTML: si la auto-emisión falló, mostramos aviso (sin cortar el flujo)
        if (is_array($autoArca) && (($autoArca['enabled'] ?? false) === true) && (($autoArca['ok'] ?? false) !== true)) {
            flash('error', (string)($autoArca['msg'] ?? 'No se pudo auto-emitir el comprobante.'));
        }

        if ($returnTo === 'show') {
            redirect('/admin/orders/show?id='.$id.'&ok=mark_paid');
            return;
        }
        redirect('/admin/orders?ok=mark_paid');
    }

// POST /admin/orders/status  (JSON: {id, status})
    public function status(): void
    {
        admin_required('/admin/orders');
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $input  = json_decode(file_get_contents('php://input'), true) ?: [];
        $id     = (int)($input['id'] ?? 0);
        $next   = (string)($input['status'] ?? '');
        $skipArcaAuto = !empty($input['skip_arca_auto']);
        $skipArcaNc   = !empty($input['skip_arca_nc']);

        if ($id <= 0 || $next === '') {
            json(['ok' => false, 'error' => 'params'], 422);
            return;
        }

        $svc = new AdminOrderService($this->db());
        [$ok, $err, $result] = $svc->changeStatus((int)($_SESSION['uid'] ?? 0), $id, $next);

        if (!$ok) {
            $msg = match ($err) {
                'invalid_status' => 'Estado inválido.',
                'invalid_transition' => 'Transición de estado inválida.',
                'cannot_cancel' => 'No se puede cancelar desde el estado actual.',
                'not_found' => 'Pedido no encontrado.',
                'exception' => 'Error interno al actualizar el estado.',
                default => 'No se pudo actualizar el estado.',
            };
            // contexto extra para debug (no se muestra si no hay)
            if (!empty($result['msg'])) { $msg .= ' ' . (string)$result['msg']; }
            json(['ok' => false, 'error' => $err, 'message' => $msg, 'result' => $result], 422);
            return;
        }

        // Nota de Crédito automática (best-effort)
        // Reglas:
        // - Si el pedido pasa a refunded/chargeback => intentamos emitir NC TOTAL si hay factura emitida.
        // - Si pasa a cancelled, también intentamos (por si cancelaron post-pago), pero solo actuará si hay factura.
        $arcaNc = null;
        if (!$skipArcaNc && empty($result['noop']) && in_array($next, ['refunded','chargeback','cancelled'], true)) {
            try {
                $arcaSvc = new \App\Services\Billing\FacturacionArcaService($this->db());
                $arcaNc = $arcaSvc->emitirNotaCreditoAutoDesdePedido((int)($_SESSION['uid'] ?? 0), $id, $next, 'admin_change_status');
            } catch (\Throwable $e) {
                $arcaNc = ['ok'=>false,'msg'=>'Error intentando emitir NC: '.$e->getMessage()];
            }
        }

        // Auto-emisión al despachar (best-effort)
        // Solo si el trigger configurado es "shipped" y el pedido ya está pagado.
        $arcaAuto = null;
        if (!$skipArcaAuto && empty($result['noop']) && $next === 'shipped') {
            try {
                $cfg = (new \App\Services\Billing\Arca\ArcaConfig($this->db()))->get();
                if (($cfg['auto_emit_trigger'] ?? 'paid') === 'shipped') {
                    // Condición mínima: no emitir si el pedido viene de estados claramente impagos.
                    $current = (string)($result['previous_status'] ?? '');
                    $paidLike = ['paid','processing','packed','shipped','delivered'];
                    if (in_array($current, $paidLike, true)) {
                        $arcaSvc = new \App\Services\Billing\FacturacionArcaService($this->db());
                        $arcaAuto = $arcaSvc->emitirAutoDesdePedido((int)($_SESSION['uid'] ?? 0), $id, 'admin_change_status_shipped');
                    } else {
                        $arcaAuto = ['ok'=>true,'skipped'=>true,'msg'=>'Auto-emisión: el pedido no figura como pagado (estado previo: '.$current.').'];
                    }
                }
            } catch (\Throwable $e) {
                $arcaAuto = ['ok'=>false,'msg'=>'Error intentando auto-emitir: '.$e->getMessage()];
            }
        }

        // HTML actualizado del bloque ARCA (server-render) para refrescar sin recargar.
        $arcaHtml = '';
        try {
            $arcaHtml = $this->renderArcaBoxHtml($id);
        } catch (\Throwable $e) {
            $arcaHtml = '';
            if (function_exists('log_error')) log_error('renderArcaBoxHtml: '.$e->getMessage());
        }

        $currentStatus = (string)($result['next'] ?? $next);
        $statusOptionsHtml = $this->renderStatusOptionsHtml($currentStatus);

        json([
            'ok' => true,
            'result' => $result,
            'arca_nc' => $arcaNc,
            'arca_auto' => $arcaAuto,
            'arca_html' => $arcaHtml,
            'status_options_html' => $statusOptionsHtml,
        ]);
    }

    // POST /admin/orders/arca/emit  (form POST: order_id)
    public function emitArca(): void
    {
        admin_required('/admin/orders');
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $isJson = (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
              || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
              || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

        $input = $isJson ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;

        $orderId = (int)($input['order_id'] ?? 0);
        if ($orderId <= 0) {
            if ($isJson) {
                json(['ok'=>false,'message'=>'Pedido inválido.'], 422);
                return;
            }
            flash('error', 'Pedido inválido.');
            redirect('/admin/orders');
            return;
        }

        $kind = strtoupper(trim((string)($input['tipo'] ?? ($input['kind'] ?? 'C'))));
        if (!in_array($kind, ['A','B','C'], true)) $kind = 'C';

        // Régimen fiscal del emisor (fuente de verdad: app_settings) para bloquear tipos incompatibles.
        $settingsRepo = new AppSettingsRepository($this->db());
        $sellerRegime = (string)$settingsRepo->get('fiscal_seller_tax_regime', 'monotributo');
        $sellerIsMono = ($sellerRegime === 'monotributo' || $sellerRegime === 'exento');
        $sellerIsRI = ($sellerRegime === 'responsable_inscripto' || $sellerRegime === 'ri');
        if ($sellerIsMono && $kind !== 'C') {
            $msg = 'Tu régimen fiscal está configurado como Monotributo/Exento: corresponde emitir Factura C.';
            if ($isJson) {
                json(['ok'=>false,'message'=>$msg], 422);
                return;
            }
            flash('error', $msg);
            redirect('/admin/orders/show?id='.$orderId);
            return;
        }
        if ($sellerIsRI && $kind === 'C') {
            $msg = 'Tu régimen fiscal está configurado como Responsable Inscripto: no corresponde emitir Factura C. Se emite Factura B.';
            if ($isJson) {
                json(['ok'=>false,'message'=>$msg], 422);
                return;
            }
            flash('error', $msg);
            redirect('/admin/orders/show?id='.$orderId);
            return;
        }

        // RI R1: Factura B únicamente. Si llega "A" desde UI vieja o un request manual, lo forzamos a "B".
        if ($sellerIsRI && $kind === 'A') {
            $kind = 'B';
        }

        $svc = new FacturacionArcaService($this->db());
        $adminId = (int)($_SESSION['uid'] ?? 0);

        if ($kind === 'A') {
            $r = $svc->emitirFacturaADesdePedido($adminId, $orderId);
        } elseif ($kind === 'B') {
            $r = $svc->emitirFacturaBDesdePedido($adminId, $orderId);
        } else {
            $r = $svc->emitirFacturaCDesdePedido($adminId, $orderId);
        }
        $ok = (($r['ok'] ?? false) === true);
        $msg = (string)($r['msg'] ?? ($ok ? 'Factura emitida.' : 'No se pudo emitir.'));

        if ($isJson) {
            $arcaHtml = '';
            try { $arcaHtml = $this->renderArcaBoxHtml($orderId); } catch (\Throwable $e) { $arcaHtml = ''; }
            json(['ok'=>$ok,'message'=>$msg,'arca_html'=>$arcaHtml] + ($ok ? [] : ['error'=>'emit_failed']), $ok ? 200 : 422);
            return;
        }

        flash($ok ? 'ok' : 'error', $msg);
        redirect('/admin/orders/show?id='.$orderId);
    }

    public function emitArcaCreditNote(): void
    {
        admin_required('/admin/orders');
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $isJson = (stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false)
              || (stripos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
              || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

        $input = $isJson ? (json_decode(file_get_contents('php://input'), true) ?: []) : $_POST;

        $orderId = (int)($input['order_id'] ?? 0);
        if ($orderId <= 0) {
            if ($isJson) {
                json(['ok'=>false,'message'=>'Pedido inválido.'], 422);
                return;
            }
            flash('error', 'Pedido inválido.');
            redirect('/admin/orders');
            return;
        }

        $svc = new FacturacionArcaService($this->db());
        $adminId = (int)($_SESSION['uid'] ?? 0);

        $r = $svc->emitirNotaCreditoAutoDesdePedido($adminId, $orderId, 'manual_admin', 'manual_admin');

        $ok = (($r['ok'] ?? false) === true);
        $skipped = !empty($r['skipped']);
        $msg = (string)($r['msg'] ?? ($ok ? ($skipped ? 'No se emitió la Nota de Crédito.' : 'Nota de Crédito emitida.') : 'No se pudo emitir la Nota de Crédito.'));

        if ($isJson) {
            $arcaHtml = '';
            try { $arcaHtml = $this->renderArcaBoxHtml($orderId); } catch (\Throwable $e) { $arcaHtml = ''; }
            json(['ok'=>$ok,'skipped'=>$skipped,'message'=>$msg,'arca_html'=>$arcaHtml] + ($ok ? [] : ['error'=>'emit_nc_failed']), $ok ? 200 : 422);
            return;
        }

        if ($ok) {
            flash($skipped ? 'warn' : 'ok', $msg);
        } else {
            flash('error', $msg);
        }

        redirect('/admin/orders/show?id='.$orderId);
    }



    private function renderStatusOptionsHtml(string $current): string
    {
        $opts = [];
        $opts[] = $current;
        if (function_exists('order_status_allowed_next')) {
            foreach (order_status_allowed_next($current) as $st) {
                if (!in_array($st, $opts, true)) $opts[] = $st;
            }
        }
        $html = '';
        foreach ($opts as $st) {
            $label = function_exists('order_status_label_admin') ? order_status_label_admin($st) : $st;
            $sel = ($st === $current) ? ' selected' : '';
            $html .= '<option value="'.htmlspecialchars($st, ENT_QUOTES, 'UTF-8').'"'.$sel.'>'
                  . htmlspecialchars($label, ENT_QUOTES, 'UTF-8')
                  . '</option>';
        }
        return $html;
    }


}


