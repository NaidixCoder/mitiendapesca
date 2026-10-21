<?php
namespace App\Services\Billing;

use App\Repositories\Billing\ComprobanteFiscalRepository;
use App\Repositories\Orders\OrderFiscalRequestRepository;
use App\Repositories\Users\UserFiscalProfileRepository;
use App\Services\Audit\Audit;
use PDO;

/**
 * ETAPA 4 — Motor fiscal (Factura A post-compra)
 *
 * Flujo:
 * - Requiere solicitud aprobada.
 * - Si existe Factura B emitida con CAE: emite NC B total y luego Factura A.
 * - Si no existe factura emitida: emite Factura A directa.
 * - Idempotencia: si ya hay Factura A emitida, no hace nada.
 */
final class FacturaAWorkflowService
{
    public function __construct(private PDO $db) {}

    /** @return array{ok:bool,msg:string,skipped?:bool, invoice_id?:int, credit_note_id?:int} */
    public function emitirFacturaAPostCompra(int $adminUserId, int $orderId): array
    {
        if ($orderId <= 0) return ['ok'=>false,'msg'=>'Pedido inválido.'];

        $reqRepo = new OrderFiscalRequestRepository();
        $req = $reqRepo->findByOrderId($orderId);
        if (!$req) return ['ok'=>false,'msg'=>'No existe solicitud fiscal para este pedido.'];

        if ((string)($req['requested_type'] ?? 'A') !== 'A') {
            return ['ok'=>false,'msg'=>'La solicitud no corresponde a Factura A.'];
        }

        $status = (string)($req['status'] ?? '');
        if ($status !== 'approved' && $status !== 'done') {
            return ['ok'=>false,'msg'=>'La solicitud debe estar aprobada para emitir Factura A.'];
        }

        // Datos del receptor: preferimos profile del usuario si existe.
        $userId = (int)($req['user_id'] ?? 0);
        // Nota: order_fiscal_requests no tiene user_id en schema MVP; obtenemos desde pedido.
        if ($userId <= 0) {
            try {
                $st = $this->db->prepare('SELECT user_id FROM orders WHERE id=? LIMIT 1');
                $st->execute([$orderId]);
                $userId = (int)($st->fetchColumn() ?: 0);
            } catch (\Throwable $e) {
                $userId = 0;
            }
        }

        $taxCond = (string)($req['customer_tax_condition'] ?? '');
        $cuit    = (string)($req['customer_cuit'] ?? '');
        $legal   = (string)($req['customer_legal_name'] ?? '');

        if ($userId > 0) {
            $profileRepo = new UserFiscalProfileRepository();
            $p = $profileRepo->findByUserId($userId);
            if (is_array($p)) {
                if (!empty($p['tax_condition'])) $taxCond = (string)$p['tax_condition'];
                if (!empty($p['cuit'])) $cuit = (string)$p['cuit'];
                if (!empty($p['legal_name'])) $legal = (string)$p['legal_name'];
            }
        }

        $repo = new ComprobanteFiscalRepository($this->db);
        if (!$repo->tableExists() || !$repo->schemaOk() || !$repo->schemaHasKind()) {
            return ['ok'=>false,'msg'=>'Tabla comprobantes_fiscales no lista (schema viejo o inexistente).'];
        }

        $all = $repo->listByOrderId($orderId);
        foreach ($all as $row) {
            if ((string)($row['kind'] ?? '') !== 'invoice') continue;
            if ((int)($row['cbte_tipo'] ?? 0) !== 1) continue; // Factura A
            if ((string)($row['status'] ?? '') === 'issued' && !empty($row['cae'])) {
                // Idempotencia
                $reqRepo->updateStatus($orderId, 'done', 'Factura A ya emitida.');
                return ['ok'=>true,'skipped'=>true,'msg'=>'La Factura A ya está emitida para este pedido.','invoice_id'=>(int)($row['id'] ?? 0)];
            }
        }

        // Detectar Factura B emitida
        $invoiceB = null;
        foreach ($all as $row) {
            if ((string)($row['kind'] ?? '') !== 'invoice') continue;
            if ((int)($row['cbte_tipo'] ?? 0) !== 6) continue; // Factura B
            if ((string)($row['status'] ?? '') !== 'issued') continue;
            if (empty($row['cae'])) continue;
            $invoiceB = $row;
            break;
        }

        $svc = new FacturacionArcaService($this->db);

        $creditNoteId = null;
        if ($invoiceB) {
            $rNc = $svc->emitirNotaCreditoAutoDesdePedido($adminUserId, $orderId, 'factura_a_reemplazo', 'factura_a_workflow');
            if (!(($rNc['ok'] ?? false) === true) || !empty($rNc['skipped'])) {
                return ['ok'=>false,'msg'=>(string)($rNc['msg'] ?? 'No se pudo emitir la Nota de Crédito B total.')];
            }
            $creditNoteId = (int)($rNc['comprobante_id'] ?? 0);
        }

        $relatedInvoiceId = (int)($invoiceB['id'] ?? 0);
        $rA = $svc->emitirFacturaAParaSolicitud($adminUserId, $orderId, $taxCond, $cuit, $legal, $relatedInvoiceId > 0 ? $relatedInvoiceId : null);
        if (!(($rA['ok'] ?? false) === true)) {
            return ['ok'=>false,'msg'=>(string)($rA['msg'] ?? 'No se pudo emitir la Factura A.')];
        }

        $invoiceId = (int)($rA['comprobante_id'] ?? 0);
        $reqRepo->updateStatus($orderId, 'done', 'Factura A emitida.');

        Audit::log('order_fiscal_request_factura_a_issued', 'order', $orderId, [
            'invoice_id' => $invoiceId,
            'credit_note_id' => $creditNoteId,
            'related_invoice_b_id' => $relatedInvoiceId > 0 ? $relatedInvoiceId : null,
        ]);

        return ['ok'=>true,'msg'=>'Factura A emitida correctamente.','invoice_id'=>$invoiceId,'credit_note_id'=>$creditNoteId];
    }
}

