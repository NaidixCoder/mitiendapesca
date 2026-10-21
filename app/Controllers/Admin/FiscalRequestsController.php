<?php
namespace App\Controllers\Admin;

use App\Repositories\Orders\OrderFiscalRequestRepository;
use App\Repositories\Users\UserFiscalProfileRepository;
use App\Services\Billing\Arca\PadronValidationService;
use App\Services\Billing\FacturaAWorkflowService;
use App\Services\Audit\Audit;
use App\Services\Admin\AdminOrderService;

final class FiscalRequestsController
{
    // POST /admin/fiscal/requests/revalidate (order_id)
    public function revalidate(): void
    {
        admin_required('/admin/orders');
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (stripos($accept, 'application/json') !== false);

        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => 'Pedido inválido.']);
                return;
            }
            redirect(url('admin/orders?err=fiscal_request'));
            return;
        }

        // Resolver user_id desde el pedido (puede ser 0 si fue guest o si no está linkeado)
        $svc = new AdminOrderService(\db());
        [$orderView, $_items] = $svc->getDetailForAdmin($orderId);
        $userId = (int)($orderView['user_id'] ?? 0);

        $reqRepo = new OrderFiscalRequestRepository();
        $req = $reqRepo->findByOrderId($orderId);
        if (!$req) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(404);
                echo json_encode(['ok' => false, 'message' => 'No se encontró la solicitud fiscal del pedido.']);
                return;
            }
            redirect(url('admin/orders/show?id=' . $orderId . '&err=fiscal_request'));
            return;
        }

        $profileRepo = new UserFiscalProfileRepository();
        $profile = ($userId > 0) ? $profileRepo->findByUserId($userId) : null;

        $cuit = (string)((($profile['cuit'] ?? '') !== '') ? ($profile['cuit'] ?? '') : ($req['customer_cuit'] ?? ''));
        $digits = preg_replace('/\D+/', '', $cuit) ?? '';
        $digits = is_string($digits) ? $digits : '';

        // CUIT inválido: deja la solicitud en invalid (no intenta Padrón)
        if ($digits === '' || strlen($digits) !== 11) {
            if ($userId > 0) {
                $profileRepo->upsert($userId, [
                    'cuit' => $digits,
                    'legal_name' => '',
                    'validation_status' => 'invalid',
                    'validation_error'  => 'CUIT vacío o inválido.',
                    'validated_at' => null,
                ]);
            }

            $reqRepo->upsertForOrder($orderId, array_merge($req, [
                'status' => 'invalid',
                'customer_cuit' => $digits,
                'notes' => 'CUIT vacío o inválido.',
            ]));

            Audit::log('order_fiscal_request_revalidate_invalid_cuit', 'order', $orderId, ['user_id' => $userId, 'cuit' => $digits]);

            $redirectUrl = url('admin/orders/show?id=' . $orderId . '&fiscal=invalid');
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'status' => 'invalid', 'redirect' => $redirectUrl]);
                return;
            }
            redirect($redirectUrl);
            return;
        }

        // Admin: operativa.
        // "Quitar timeout" absoluto no existe (el socket/PHP/webserver siempre tiene algún límite),
        // pero para Admin dejamos una espera amplia para maximizar chances de respuesta.
        try {
            @set_time_limit(120);
            @ini_set('default_socket_timeout', '120');
        } catch (\Throwable $e) {
            // best-effort
        }

        $result = (new PadronValidationService(\db()))->validate($digits, [
            // 0 => no seteamos timeouts explícitos en SoapClient (usa default_socket_timeout)
            'padron_connection_timeout' => 0,
            'padron_timeout' => 0,
        ]);
        $st = (string)($result['status'] ?? 'pending');
        $legal = (string)($result['legal_name'] ?? '');
        $err = (string)($result['error'] ?? '');

        if ($userId > 0) {
            $profileRepo->upsert($userId, [
                'cuit' => $digits,
                'legal_name' => ($st === 'valid') ? $legal : '',
                'validation_status' => $st,
                'validation_error' => ($st !== 'valid') ? $err : '',
                'validated_at' => ($st === 'valid') ? date('Y-m-d H:i:s') : null,
            ]);
        }

        if ($st === 'valid') {
            // La solicitud sigue pendiente de aprobación/emisión (Etapa 3/4)
            $reqRepo->updateStatus($orderId, 'pending', null);
            Audit::log('order_fiscal_request_revalidated_valid', 'order', $orderId, ['user_id' => $userId, 'cuit' => $digits]);
            $redirectUrl = url('admin/orders/show?id=' . $orderId . '&fiscal=valid');
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'status' => 'valid', 'redirect' => $redirectUrl]);
                return;
            }
            redirect($redirectUrl);
            return;
        }

        if ($st === 'invalid') {
            $reqRepo->updateStatus($orderId, 'invalid', ($err !== '' ? $err : 'CUIT inválido o inexistente.'));
            Audit::log('order_fiscal_request_revalidated_invalid', 'order', $orderId, ['user_id' => $userId, 'cuit' => $digits, 'error' => $err]);
            $redirectUrl = url('admin/orders/show?id=' . $orderId . '&fiscal=invalid');
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['ok' => true, 'status' => 'invalid', 'redirect' => $redirectUrl]);
                return;
            }
            redirect($redirectUrl);
            return;
        }

        // pending
        $reqRepo->updateStatus($orderId, 'pending', 'Validación pendiente (padrón no disponible).');
        Audit::log('order_fiscal_request_revalidated_pending', 'order', $orderId, ['user_id' => $userId, 'cuit' => $digits]);

        $redirectUrl = url('admin/orders/show?id=' . $orderId . '&fiscal=pending');
        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['ok' => true, 'status' => 'pending', 'redirect' => $redirectUrl]);
            return;
        }
        redirect($redirectUrl);
    }

    // POST /admin/fiscal/requests/approve (order_id)
    public function approve(): void
    {
        admin_required('/admin/orders');
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            redirect(url('admin/orders?err=fiscal_request'));
            return;
        }

        $svc = new AdminOrderService(\db());
        [$orderView, $_items] = $svc->getDetailForAdmin($orderId);
        $userId = (int)($orderView['user_id'] ?? 0);
$profileRepo = new UserFiscalProfileRepository();
        $reqRepo = new OrderFiscalRequestRepository();
        $profile = $profileRepo->findByUserId($userId);
        $vp = (string)($profile['validation_status'] ?? '');

        if ($vp !== 'valid') {
            redirect(url('admin/orders/show?id=' . $orderId . '&err=fiscal_not_valid'));
            return;
        }

        $reqRepo->updateStatus($orderId, 'approved', null);
        Audit::log('order_fiscal_request_approved', 'order', $orderId, ['user_id' => $userId]);

        redirect(url('admin/orders/show?id=' . $orderId . '&ok=fiscal_approved'));
    }

    // POST /admin/fiscal/requests/reject (order_id, reason)
    public function reject(): void
    {
        admin_required('/admin/orders');
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $orderId = (int)($_POST['order_id'] ?? 0);
        $reason  = trim((string)($_POST['reason'] ?? ''));

        if ($orderId <= 0) {
            redirect(url('admin/orders?err=fiscal_request'));
            return;
        }

        $svc = new AdminOrderService(\db());
        [$orderView, $_items] = $svc->getDetailForAdmin($orderId);
        $userId = (int)($orderView['user_id'] ?? 0);
if ($reason === '') {
            $reason = 'Rechazado por el administrador.';
        }

        $reqRepo = new OrderFiscalRequestRepository();
        $reqRepo->updateStatus($orderId, 'rejected', mb_substr($reason, 0, 255));
        Audit::log('order_fiscal_request_rejected', 'order', $orderId, ['user_id' => $userId, 'reason' => $reason]);

        redirect(url('admin/orders/show?id=' . $orderId . '&ok=fiscal_rejected'));
    }


    // POST /admin/fiscal/requests/emit-a (order_id)
    public function emitA(): void
    {
        admin_required('/admin/orders');
        if (function_exists('verify_csrf_or_abort')) verify_csrf_or_abort();

        $accept = (string)($_SERVER['HTTP_ACCEPT'] ?? '');
        $isAjax = (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
              || (stripos($accept, 'application/json') !== false);

        $orderId = (int)($_POST['order_id'] ?? 0);
        if ($orderId <= 0) {
            if ($isAjax) {
                header('Content-Type: application/json; charset=utf-8');
                http_response_code(422);
                echo json_encode(['ok' => false, 'message' => 'Pedido inválido.']);
                return;
            }
            redirect(url('admin/orders?err=fiscal_request'));
            return;
        }

        // Espera amplia en admin (WSAA/WSFE puede tardar)
        try {
            @set_time_limit(180);
            @ini_set('default_socket_timeout', '180');
        } catch (\Throwable $e) {}

        $adminId = (int)($_SESSION['uid'] ?? 0);

        $r = (new FacturaAWorkflowService(\db()))->emitirFacturaAPostCompra($adminId, $orderId);
        $ok = (($r['ok'] ?? false) === true);
        $msg = (string)($r['msg'] ?? ($ok ? 'Factura A emitida.' : 'No se pudo emitir la Factura A.'));

        if ($isAjax) {
            header('Content-Type: application/json; charset=utf-8');
            if (!$ok) {
                http_response_code(422);
            }
            echo json_encode([
                'ok' => $ok,
                'message' => $msg,
                'redirect' => url('admin/orders/show?id=' . $orderId),
            ]);
            return;
        }

        if ($ok) {
            flash(!empty($r['skipped']) ? 'warn' : 'ok', $msg);
        } else {
            flash('error', $msg);
        }
        redirect(url('admin/orders/show?id=' . $orderId));
    }
}

