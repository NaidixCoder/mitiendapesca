<?php
namespace App\Controllers\Account;

use App\Repositories\Account\OrderRepository;
use App\Repositories\Billing\ComprobanteFiscalRepository;
use App\Services\Account\OrderService;
use App\Repositories\Orders\OrderShipmentRepository;
use App\Repositories\Orders\OrderFiscalRequestRepository;
use App\Repositories\Users\UserFiscalProfileRepository;
use App\Services\Fiscal\FiscalRequestService;
use App\Services\Receipts\TransferReceiptService;
use App\Services\Billing\FacturaPdfService;
use App\Services\Cart\CartReadService;
use App\Services\Audit\Audit;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Payments\MercadoPago\MercadoPagoStatusMapper;
use App\Support\Geo\ProvinciasAR;

class OrdersController
{
    // GET /cuenta/pedidos
    public function index(): void
    {
        auth_required('/cuenta');

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        // Si venimos de un pago (Mercado Pago), intentamos resolver el pedido y llevar al detalle (OpciÃ³n 1).
        $pago = (string)($_GET['pago'] ?? '');
        $pago = trim($pago);
        if ($pago !== '') {
            try {
                $payRepo = new \App\Repositories\Payments\PaymentRepository(db());
                $p = $payRepo->findByProviderPaymentId('mercadopago', $pago);
                $oid = (int)($p['order_id'] ?? 0);
                if ($oid > 0) {
                    redirect(url('/cuenta/pedidos/ver').'?id='.$oid.'&ok=1');
                    return;
                }
            } catch (\Throwable $e) {
                // no bloquear
            }
            $this->flashMercadoPagoPaymentFeedback($pago);
        }

        $repo = new OrderRepository(db());
        if (!$repo->tableExists()) {
            // InstalaciÃ³n sin mÃ³dulo de pedidos todavÃ­a
            render_page('cuenta/pedidos/index', [
                'orders' => [],
                'hasOrders' => false,
            ]);
            return;
        }

        $svc = new OrderService($repo);
        $orders = $svc->listForUserView($uid, 50);
        render_page('cuenta/pedidos/index', [
            'orders' => $orders,
            'hasOrders' => count($orders) > 0,
        ]);
    }

    // GET /cuenta/pedidos/ver?id=...
    public function show(): void
    {
        auth_required('/cuenta');

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        // Si venimos de un pago (Mercado Pago), intentamos resolver el pedido y llevar al detalle (OpciÃ³n 1).
        $pago = (string)($_GET['pago'] ?? '');
        $pago = trim($pago);
        if ($pago !== '') {
            try {
                $payRepo = new \App\Repositories\Payments\PaymentRepository(db());
                $p = $payRepo->findByProviderPaymentId('mercadopago', $pago);
                $oid = (int)($p['order_id'] ?? 0);
                if ($oid > 0) {
                    redirect(url('/cuenta/pedidos/ver').'?id='.$oid.'&ok=1');
                    return;
                }
            } catch (\Throwable $e) {
                // no bloquear
            }
            $this->flashMercadoPagoPaymentFeedback($pago);
        }

        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            flash('error', 'Pedido invÃ¡lido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $repo = new OrderRepository(db());
        if (!$repo->tableExists()) {
            flash('error', 'Pedidos no disponibles en este momento.');
            redirect('/cuenta');
            return;
        }

        $order = $repo->findByIdForUser($id, $uid);
        if (!$order) {
            // No revelar existencia de pedidos ajenos
            flash('error', 'No encontramos ese pedido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $svc = new OrderService($repo);
        $orderView = $svc->detailView($order);
        $transferReservationHours = 24;
        try {
            $transferReservationHours = (int)((new AppSettingsRepository(db()))->get('payments_transfer_reservation_hours', '24') ?? '24');
        } catch (\Throwable $e) {
            $transferReservationHours = 24;
        }
        if ($transferReservationHours < 1) $transferReservationHours = 24;
        if ($transferReservationHours > 720) $transferReservationHours = 720;
        $orderView['transfer_reservation_hours'] = $transferReservationHours;
        $createdAt = trim((string)($order['created_at'] ?? ''));
        if ($createdAt !== '') {
            $reserveUntilTs = strtotime($createdAt . ' +' . $transferReservationHours . ' hours');
            if ($reserveUntilTs !== false) {
                $orderView['transfer_reservation_until'] = date('d/m/Y H:i', $reserveUntilTs);
            }
        }
        $items = $svc->itemsView($id);

        $shipRepo = new OrderShipmentRepository(db());
        $shipment = $shipRepo->getByOrderId($id);

        $bank = [
            'name'  => (string)env('BANK_NAME',''),
            'alias' => (string)env('BANK_ALIAS',''),
            'cbu'   => (string)env('BANK_CBU',''),
            'owner' => (string)env('BANK_OWNER',''),
            'cuit'  => (string)env('BANK_CUIT',''),
        ];

        $receipts = [];
        try {
            $rsvc = new TransferReceiptService();
            $receipts = $rsvc->listForOrder($id);
        } catch (\Throwable $e) {
            $receipts = [];
        }

        $sellerTaxRegime = 'monotributo';
        try {
            $sellerTaxRegime = strtolower(trim((string)((new AppSettingsRepository(db()))->get('fiscal_seller_tax_regime', 'monotributo') ?? 'monotributo')));
        } catch (\Throwable $e) {
            $sellerTaxRegime = 'monotributo';
        }
        if ($sellerTaxRegime === '') {
            $sellerTaxRegime = 'monotributo';
        }

        // Factura (ARCA) â€” mÃ­nimo: permitir descarga si existe comprobante emitido
        $factura = null;
        $comprobantes = [];
        try {
            $cfRepo = new ComprobanteFiscalRepository(db());
$cf = $cfRepo->findPreferredInvoiceForOrder($id);
if ($cf && (string)($cf['status'] ?? '') === 'issued') {
    $factura = $cf;
}

// Historial de comprobantes del pedido (para mostrar al cliente)
$comprobantes = $cfRepo->schemaHasKind() ? $cfRepo->listByOrderId($id) : [];
        } catch (\Throwable $e) {
            $factura = null;
        }


        // Solicitud de Factura A (post-compra) â€” Etapa 1
        $fiscalRequest = null;
        $fiscalProfile = null;
        try {
            $reqRepo = new OrderFiscalRequestRepository();
            $fiscalRequest = $reqRepo->findByOrderId($id);
            $profileRepo = new UserFiscalProfileRepository();
            $fiscalProfile = $profileRepo->findByUserId($uid);
        } catch (\Throwable $e) {
            $fiscalRequest = null;
            $fiscalProfile = null;
        }

        render_page('cuenta/pedidos/show', [
            'order' => $orderView,
            'items' => $items,
            'shipment' => $shipment,
            'bank' => $bank,
            'seller_tax_regime' => $sellerTaxRegime,
            'receipts' => $receipts,
            'comprobantes' => $comprobantes,
            'factura' => $factura,
            'fiscal_request' => $fiscalRequest,
            'fiscal_profile' => $fiscalProfile,
            'provinces' => ProvinciasAR::all(),
            'pageId' => 'account_order_show',
        ]);
    }

    private function flashMercadoPagoPaymentFeedback(string $paymentId): void
    {
        if (trim($paymentId) === '') {
            flash('info', 'Estamos procesando tu pago. Actualizá en unos instantes si todavía no aparece.');
            return;
        }

        try {
            $repo = new \App\Repositories\Payments\PaymentRepository(db());
            $payment = $repo->findByProviderPaymentId('mercadopago', $paymentId);
            if (!$payment) {
                flash('info', 'Estamos procesando tu pago. Actualizá en unos instantes si todavía no aparece.');
                return;
            }

            $mapper = new MercadoPagoStatusMapper();
            $ui = $mapper->map($payment, [
                'payment_id' => $paymentId,
                'order_id' => (int)($payment['order_id'] ?? 0),
            ]);

            $tone = (string)($ui['tone'] ?? 'info');
            $title = trim((string)($ui['title'] ?? ''));
            $line = trim((string)($ui['lines'][0] ?? ''));
            $message = trim($title . '. ' . $line, ". \t\n\r\0\x0B");
            flash($tone === 'error' ? 'error' : 'info', $message !== '' ? $message : 'Estamos procesando tu pago. Actualizá en unos instantes si todavía no aparece.');
        } catch (\Throwable $e) {
            flash('info', 'Estamos procesando tu pago. Actualizá en unos instantes si todavía no aparece.');
        }
    }
    // POST /cuenta/pedidos/direccion/guardar
    public function updateShippingAddress(): void
    {
        auth_required('/cuenta/pedidos');
        verify_csrf_or_abort();
        $isAjax = (isset($_SERVER['HTTP_ACCEPT']) && str_contains((string)$_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] !== '');
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $orderId = (int)($_POST['order_id'] ?? $_POST['id'] ?? 0);
        if ($orderId <= 0) {
            if ($isAjax) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Pedido invalido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            flash('error', 'Pedido invalido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $repo = new OrderRepository(db());
        $order = $repo->findByIdForUser($orderId, $uid);
        if (!$order) {
            if ($isAjax) {
                http_response_code(404);
                echo json_encode(['ok' => false, 'error' => 'No encontramos ese pedido.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            flash('error', 'No encontramos ese pedido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $shipRepo = new OrderShipmentRepository(db());
        $shipment = $shipRepo->getByOrderId($orderId);
        $shipStatus = strtolower(trim((string)($shipment['status'] ?? '')));
        if (in_array($shipStatus, ['shipped', 'delivered', 'enviado', 'despachado', 'entregado'], true)) {
            if ($isAjax) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'error' => 'Este envio ya fue despachado y no se puede editar la direccion.'], JSON_UNESCAPED_UNICODE);
                return;
            }
            flash('error', 'Este envio ya fue despachado y no se puede editar la direccion.');
            redirect(url('/cuenta/pedidos/ver') . '?id=' . $orderId);
            return;
        }

        $fullName = trim((string)($_POST['full_name'] ?? ''));
        $phone = trim((string)($_POST['phone'] ?? ''));
        $street = trim((string)($_POST['street'] ?? ''));
        $number = trim((string)($_POST['number'] ?? ''));
        $floor = trim((string)($_POST['floor'] ?? ''));
        $apartment = trim((string)($_POST['apartment'] ?? ''));
        $zip = trim((string)($_POST['zip'] ?? ''));
        $city = trim((string)($_POST['city'] ?? ''));
        $state = trim((string)($_POST['state'] ?? ''));
        $notes = trim((string)($_POST['notes'] ?? ''));

        if ($fullName === '' || $phone === '' || $street === '' || $number === '' || $zip === '' || $city === '' || $state === '') {
            if ($isAjax) {
                http_response_code(422);
                echo json_encode([
                    'ok' => false,
                    'error' => 'Completa los campos obligatorios para guardar la direccion.',
                    'errors' => [
                        'full_name' => ($fullName === '' ? 'Requerido' : ''),
                        'phone' => ($phone === '' ? 'Requerido' : ''),
                        'street' => ($street === '' ? 'Requerido' : ''),
                        'number' => ($number === '' ? 'Requerido' : ''),
                        'zip' => ($zip === '' ? 'Requerido' : ''),
                        'city' => ($city === '' ? 'Requerido' : ''),
                        'state' => ($state === '' ? 'Requerido' : ''),
                    ],
                ], JSON_UNESCAPED_UNICODE);
                return;
            }
            flash('error', 'Completa los campos obligatorios para guardar la direccion.');
            redirect(url('/cuenta/pedidos/ver') . '?id=' . $orderId);
            return;
        }

        db()->prepare("
            UPDATE orders
               SET shipping_full_name = :full_name,
                   shipping_phone = :phone,
                   shipping_street = :street,
                   shipping_number = :number,
                   shipping_floor = :floor,
                   shipping_apartment = :apartment,
                   shipping_city = :city,
                   shipping_state = :state,
                   shipping_zip = :zip,
                   shipping_notes = :notes
             WHERE id = :id
               AND user_id = :uid
             LIMIT 1
        ")->execute([
            ':full_name' => $fullName,
            ':phone' => $phone,
            ':street' => $street,
            ':number' => $number,
            ':floor' => $floor,
            ':apartment' => $apartment,
            ':city' => $city,
            ':state' => $state,
            ':zip' => $zip,
            ':notes' => $notes,
            ':id' => $orderId,
            ':uid' => $uid,
        ]);

        if ($isAjax) {
            echo json_encode([
                'ok' => true,
                'id' => $orderId,
                'redirect_url' => url('/cuenta/pedidos/ver') . '?id=' . $orderId,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        flash('ok', 'Direccion actualizada.');
        redirect(url('/cuenta/pedidos/ver') . '?id=' . $orderId);
    }

    // GET /cuenta/pedidos/factura?id=... (order_id)
    public function facturaPdf(): void
    {
        auth_required('/cuenta');

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $orderId = (int)($_GET['id'] ?? $_GET['order_id'] ?? 0);
        if ($orderId <= 0) {
            flash('error', 'Pedido invÃ¡lido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $repo = new OrderRepository(db());
        $order = $repo->findByIdForUser($orderId, $uid);
        if (!$order) {
            flash('error', 'No encontramos ese pedido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $cfRepo = new ComprobanteFiscalRepository(db());
        $cf = $cfRepo->findPreferredInvoiceForOrder($orderId);
        if (!$cf || (string)($cf['status'] ?? '') !== 'issued') {
            flash('info', 'TodavÃ­a no hay una factura emitida para este pedido.');
            redirect(url('/cuenta/pedidos/ver') . '?id=' . $orderId);
            return;
        }

        $svc = new FacturaPdfService(db());
        $res = $svc->buildPdfForComprobanteId((int)($cf['id'] ?? 0));
        if (($res['ok'] ?? false) !== true) {
            flash('error', (string)($res['error'] ?? 'No se pudo generar la factura.'));
            redirect(url('/cuenta/pedidos/ver') . '?id=' . $orderId);
            return;
        }

        $filename = (string)($res['filename'] ?? 'factura.pdf');
        $bytes = (string)($res['bytes'] ?? '');
        $engine = (string)($res['engine'] ?? 'unknown');

        header('X-Content-Type-Options: nosniff');
        header('X-PDF-Engine: ' . $engine);
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . addslashes($filename) . '"');
        header('Content-Length: ' . (string)strlen($bytes));
        echo $bytes;
        exit;
    }


    // POST /cuenta/pedidos/factura-a/solicitar
    public function requestFacturaA(): void
    {
        auth_required('/cuenta/pedidos');
        verify_csrf_or_abort();
        $isAjax = (isset($_SERVER['HTTP_ACCEPT']) && str_contains((string)$_SERVER['HTTP_ACCEPT'], 'application/json'))
            || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && $_SERVER['HTTP_X_REQUESTED_WITH'] !== '');
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
        }


        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            flash('error', 'Pedido invÃ¡lido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $repo = new OrderRepository(db());
        if (!$repo->tableExists()) {
            flash('error', 'Pedidos no disponibles en este momento.');
            redirect('/cuenta');
            return;
        }

        $order = $repo->findByIdForUser($orderId, $uid);
        if (!$order) {
            flash('error', 'No encontramos ese pedido.');
            redirect('/cuenta/pedidos');
            return;
        }

        // Evitar duplicados (si ya hay una solicitud pendiente)
        try {
            $reqRepo = new OrderFiscalRequestRepository();
            $existing = $reqRepo->findByOrderId($orderId);
            $status = (string)($existing['status'] ?? '');
            if (in_array($status, ['pending','approved','done'], true)) {
                flash('info', 'Ya registramos tu solicitud de Factura A para este pedido.');
                redirect(url('/cuenta/pedidos/ver') . '?id=' . $orderId);
                return;
            }
        } catch (\Throwable $e) {
            // no bloquear
        }

        $taxCond = (string)($_POST['tax_condition'] ?? 'consumidor_final');
        $cuit = (string)($_POST['cuit'] ?? '');

        $svc = new FiscalRequestService(new UserFiscalProfileRepository(), new OrderFiscalRequestRepository(), db());
        $res = $svc->requestFacturaAWithPadron($uid, $orderId, $taxCond, $cuit);

        // Si es invÃ¡lida (DV, no RI, CUIT del emisor, etc) NO registramos solicitud.
        if (($res['ok'] ?? false) !== true) {
            $msg = (string)($res['message'] ?? 'No pudimos validar el CUIT.');
            if ($isAjax) {
                http_response_code(422);
                echo json_encode(['ok' => false, 'status' => 'invalid', 'message' => $msg], JSON_UNESCAPED_UNICODE);
                return;
            }
            flash('error', $msg);
            redirect(url('/cuenta/pedidos/ver') . '?id=' . $orderId . '&fa=1');
            return;
        }

        $msgOk = (string)($res['message'] ?? 'Solicitud registrada.');
        if ($isAjax) {
            echo json_encode([
                'ok' => true,
                'status' => (string)($res['status'] ?? 'pending'),
                'message' => $msgOk,
                'redirect' => url('/cuenta/pedidos/ver') . '?id=' . $orderId,
            ], JSON_UNESCAPED_UNICODE);
            return;
        }

        flash('success', $msgOk);
        redirect(url('/cuenta/pedidos/ver') . '?id=' . $orderId);
        return;
    }


    // POST /cuenta/pedidos/comprobante/subir
    public function uploadReceipt(): void
    {
        auth_required('/cuenta');
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            flash('error', 'Pedido invÃ¡lido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $repo = new OrderRepository(db());
        if (!$repo->tableExists()) {
            flash('error', 'Pedidos no disponibles en este momento.');
            redirect('/cuenta');
            return;
        }

        $order = $repo->findByIdForUser($orderId, $uid);
        if (!$order) {
            flash('error', 'No encontramos ese pedido.');
            redirect('/cuenta/pedidos');
            return;
        }

        // Solo aplica a pedidos por transferencia (evitamos ruido/abuso)
        $pm = (string)($order['payment_method'] ?? '');
        if (!in_array($pm, ['transferencia','transfer'], true)) {
            flash('error', 'Este pedido no requiere comprobante.');
            redirect(url('/cuenta/pedidos/ver').'?id='.$orderId);
            return;
        }

        $file = $_FILES['receipt'] ?? null;
        $svc = new TransferReceiptService();
        [$ok, $msg, $filename] = $svc->saveForOrder($orderId, is_array($file) ? $file : []);
        if (!$ok) {
            flash('error', $msg);
            redirect(url('/cuenta/pedidos/ver').'?id='.$orderId);
            return;
        }

        // AuditorÃ­a (evento Ãºtil para operaciÃ³n)
        try {
            Audit::log('transfer_receipt_uploaded', 'order', $orderId, [
                'file' => (string)$filename,
                'size' => (int)($file['size'] ?? 0),
            ]);
        } catch (\Throwable $e) {
            // no bloquear
        }

        flash('ok', $msg);
        redirect(url('/cuenta/pedidos/ver').'?id='.$orderId.'&comprobante=1');
    }

    // GET /cuenta/pedidos/comprobante/descargar?order_id=..&file=..
    public function downloadReceipt(): void
    {
        auth_required('/cuenta');

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $orderId = (int)($_GET['order_id'] ?? 0);
        $file = (string)($_GET['file'] ?? '');
        if ($orderId <= 0 || trim($file) === '') {
            flash('error', 'Comprobante invÃ¡lido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $repo = new OrderRepository(db());
        $order = $repo->findByIdForUser($orderId, $uid);
        if (!$order) {
            flash('error', 'No encontramos ese pedido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $svc = new TransferReceiptService();
        $path = $svc->resolvePath($orderId, $file);
        if (!$path) {
            flash('error', 'Comprobante no encontrado.');
            redirect(url('/cuenta/pedidos/ver').'?id='.$orderId);
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

    // GET /cuenta/pedidos/comprobante/ver?order_id=..&file=..
    public function viewReceipt(): void
    {
        auth_required('/cuenta');

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $orderId = (int)($_GET['order_id'] ?? 0);
        $file = (string)($_GET['file'] ?? '');
        if ($orderId <= 0 || trim($file) === '') {
            flash('error', 'Comprobante invÃ¡lido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $repo = new OrderRepository(db());
        $order = $repo->findByIdForUser($orderId, $uid);
        if (!$order) {
            flash('error', 'No encontramos ese pedido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $svc = new TransferReceiptService();
        $path = $svc->resolvePath($orderId, $file);
        if (!$path) {
            flash('error', 'Comprobante no encontrado.');
            redirect(url('/cuenta/pedidos/ver').'?id='.$orderId);
            return;
        }

        $name = basename($path);
        $mime = $svc->detectMime($path);
        if (!$svc->isPreviewableMime($mime)) {
            flash('error', 'Este comprobante no se puede previsualizar.');
            redirect(url('/cuenta/pedidos/ver').'?id='.$orderId);
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('Content-Type: ' . $mime);
        header('Content-Disposition: inline; filename="' . addslashes($name) . '"');
        header('Content-Length: ' . (string)filesize($path));
        readfile($path);
        exit;
    }
    // POST /cuenta/pedidos/repetir
    public function repeat(): void
    {
        auth_required('/cuenta');
        verify_csrf_or_abort();

        $uid = (int)($_SESSION['uid'] ?? 0);
        if ($uid <= 0) { redirect('/login'); return; }

        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            flash('error', 'Pedido invÃ¡lido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $repo = new OrderRepository(db());
        $order = $repo->findByIdForUser($orderId, $uid);
        if (!$order) {
            flash('error', 'No encontramos ese pedido.');
            redirect('/cuenta/pedidos');
            return;
        }

        $items = $repo->itemsForOrder($orderId);
        $repeatItems = [];
        $omittedVariantRequired = 0;
        foreach ($items as $it) {
            $pid = (int)($it['product_id'] ?? 0);
            $vid = (int)($it['variant_id'] ?? 0);
            if ($pid > 0 && $vid <= 0) {
                $variantSku = trim((string)($it['variant_sku'] ?? ''));
                if ($variantSku !== '') {
                    $resolvedVariantId = $repo->findActiveVariantIdBySku($pid, $variantSku);
                    if ($resolvedVariantId > 0) {
                        $vid = $resolvedVariantId;
                    }
                }
            }

            if ($pid > 0 && $vid <= 0 && $repo->requiresVariantSelection($pid)) {
                $omittedVariantRequired++;
                continue;
            }
            $qty = (int)($it['qty'] ?? 0);
            if ($pid > 0 && $qty > 0) {
                $repeatItems[] = [
                    'product_id' => $pid,
                    'variant_id' => max(0, $vid),
                    'quantity' => $qty,
                ];
            }
        }

        if (!$repeatItems) {
            flash('error', 'Este pedido no tiene productos para repetir.');
            redirect(url('/cuenta/pedidos/ver?id=' . $orderId));
            return;
        }

        $cartRead = new CartReadService(db());
        $_SESSION['repeat_order_items'] = $repeatItems;
        if ($omittedVariantRequired > 0) {
            flash('warn', 'Omitimos ' . $omittedVariantRequired . ' producto(s) porque ya no tienen una variante valida para repetir.');
        }
        if ($cartRead->hasItems()) {
            flash('warn', 'Ya tenias productos en el carrito. Vamos a sumar los del pedido.');
        }
        redirect('/carrito?repeat=1');
    }
}



