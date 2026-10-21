<?php
namespace App\Services\Billing;

use App\Repositories\Billing\ComprobanteFiscalRepository;
use App\Services\Audit\Audit;
use App\Services\Billing\Arca\ArcaConfig;
use App\Services\Billing\Arca\WsaaService;
use App\Services\Billing\Arca\WsfeService;
use App\Mail\Orders\TransactionalMail;
use PDO;
use Throwable;

/**
 * Emisión ARCA (WSAA + WSFEv1) – MVP
 *
 * Objetivo ahora: Factura C (Monotributo) de forma confiable y auditable.
 * Más adelante: A/B (RI) con detalle de IVA.
 */
final class FacturacionArcaService
{
    public function __construct(private PDO $db) {}

    /**
     * Auto-emisión (post-compra) si está activada en Ajustes.
     *
     * Regla MVP (RI R1):
     * - Monotributo => Factura C
     * - Responsable Inscripto => Factura B (siempre)
     *
     * @return array{ok:bool, msg:string, skipped?:bool, kind?:string, comprobante_id?:int}
     */
    public function emitirAutoDesdePedido(int $actorUserId, int $orderId, string $source = 'auto'): array
    {
        if ($orderId <= 0) return ['ok'=>false,'msg'=>'Pedido inválido.'];

        $cfg = (new ArcaConfig($this->db))->get();
        if (empty($cfg['auto_emit'])) {
            return ['ok'=>true,'skipped'=>true,'msg'=>'Auto-emisión deshabilitada en Ajustes.'];
        }

        $repo = new ComprobanteFiscalRepository($this->db);
        $existing = $repo->findByOrderId($orderId);
        if ($existing && (string)($existing['status'] ?? '') === 'issued') {
            return ['ok'=>true,'skipped'=>true,'msg'=>'El pedido ya tiene un comprobante emitido.'];
        }

        $order = $this->loadOrderSnapshot($orderId);
        if (!$order) return ['ok'=>false,'msg'=>'Pedido no encontrado.'];

        $sellerRegime = $this->normalizeRegime((string)($order['seller_tax_regime'] ?? ''));
        // Decide el tipo a emitir
        $kind = 'C';
        if ($sellerRegime === 'monotributo') {
            $kind = 'C';
        } elseif ($sellerRegime === 'responsable_inscripto') {
            // RI R1: Factura B únicamente, sin CUIT obligatorio y sin Factura A.
            $kind = 'B';
        } else {
            return ['ok'=>true,'skipped'=>true,'msg'=>'Régimen del emisor no soportado para auto-emisión (por ahora).'];
        }

        $r = match ($kind) {
            'A' => $this->emitirFacturaADesdePedido($actorUserId, $orderId),
            'B' => $this->emitirFacturaBDesdePedido($actorUserId, $orderId),
            default => $this->emitirFacturaCDesdePedido($actorUserId, $orderId),
        };
        $r['kind'] = $kind;
        if (!isset($r['msg'])) $r['msg'] = 'Auto-emisión ejecutada.';
        return $r;
    }

    /**
     * Auto-emite una Nota de Crédito TOTAL cuando un pedido se reembolsa/chargeback,
     * siempre que exista una factura (kind=invoice) ya emitida y aún no exista NC emitida.
     *
     * MVP:
     * - Factura C (11) => NC C (13)
     * - Factura B (6)  => NC B (8)
     * - Factura A (1)  => NC A (3) (best-effort)
     *
     * @return array{ok:bool, msg:string, skipped?:bool, comprobante_id?:int}
     */
    public function emitirNotaCreditoAutoDesdePedido(int $actorUserId, int $orderId, string $reasonStatus, string $source = 'auto_nc'): array
    {
        if ($orderId <= 0) return ['ok'=>false,'msg'=>'Pedido inválido.'];

        $repo = new ComprobanteFiscalRepository($this->db);
        if (!$repo->tableExists() || !$repo->schemaOk() || !$repo->schemaHasKind()) {
            return ['ok'=>false,'msg'=>'Tabla comprobantes_fiscales no lista (schema viejo o inexistente).'];
        }

        // Necesitamos una factura emitida
        $invoice = $repo->findByOrderId($orderId);
        if (!$invoice || (string)($invoice['status'] ?? '') !== 'issued') {
            return ['ok'=>true,'skipped'=>true,'msg'=>'No hay factura emitida para este pedido.'];
        }
        if ((string)($invoice['kind'] ?? 'invoice') !== 'invoice') {
            return ['ok'=>true,'skipped'=>true,'msg'=>'El comprobante principal no es una factura.'];
        }

        // Idempotencia: si ya existe NC emitida (o pendiente) asociada a esta factura, no hacemos nada.
        $all = $repo->listByOrderId($orderId);
        $invoiceId = (int)($invoice['id'] ?? 0);
        foreach ($all as $row) {
            if ((string)($row['kind'] ?? '') !== 'credit_note') continue;
            if ((int)($row['related_comprobante_id'] ?? 0) !== $invoiceId) continue;
            $st = (string)($row['status'] ?? '');
            if (in_array($st, ['pending','issued'], true)) {
                return ['ok'=>true,'skipped'=>true,'msg'=>'Ya existe una Nota de Crédito para esta factura.','comprobante_id'=>(int)($row['id'] ?? 0)];
            }
        }

        $origTipo = (int)($invoice['cbte_tipo'] ?? 0);
        $ncTipo = match ($origTipo) {
            11 => 13, // NC C
            6 => 8,   // NC B
            1 => 3,   // NC A
            default => 0,
        };
        if ($ncTipo === 0) {
            return ['ok'=>true,'skipped'=>true,'msg'=>'Tipo de factura no soportado para Nota de Crédito automática.'];
        }

        $cfg = (new ArcaConfig($this->db))->get();
        $env = (string)$cfg['env'];
        $ptoVta = (int)($invoice['pto_vta'] ?? 0);
        if ($ptoVta <= 0) $ptoVta = (int)($cfg['pto_vta'] ?? 0);

        $docTipo = (int)($invoice['doc_tipo'] ?? 99);
        $docNro  = (int)($invoice['doc_nro'] ?? 0);
        $impTotal = (int)($invoice['imp_total'] ?? 0);
        $impNeto  = (int)($invoice['imp_neto'] ?? $impTotal);
        $impIva   = (int)($invoice['imp_iva'] ?? 0);

        // Para C, no discriminamos IVA
        if ($origTipo === 11) {
            $impIva = 0;
            $impNeto = $impTotal;
        }

        // Reutilizamos data del request original (CondicionIVAReceptorId + Iva) si existe.
        $origReq = [];
        try { $origReq = json_decode((string)($invoice['request_json'] ?? ''), true) ?: []; } catch (Throwable $e) { $origReq = []; }
        $condIvaReceptorId = (int)($origReq['FeDetReq']['FECAEDetRequest']['CondicionIVAReceptorId'] ?? 5);
        $ivaNode = $origReq['FeDetReq']['FECAEDetRequest']['Iva'] ?? null;

        $req = $this->buildNotaCreditoRequest(
            $ptoVta,
            $ncTipo,
            $docTipo,
            $docNro,
            $condIvaReceptorId,
            $impTotal,
            $impNeto,
            $impIva,
            $ivaNode,
            $origTipo,
            (int)($invoice['pto_vta'] ?? $ptoVta),
            (int)($invoice['cbte_nro'] ?? 0),
            (string)($cfg['cuit'] ?? '')
        );

        $id = $repo->insertPendingCreditNoteForOrder($orderId, $invoiceId, [
            'arca_env' => $env,
            'pto_vta' => $ptoVta,
            'cbte_tipo' => $ncTipo,
            'concepto' => 1,
            'doc_tipo' => $docTipo,
            'doc_nro' => $docNro,
            'imp_total' => $impTotal,
            'imp_neto' => $impNeto,
            'imp_iva' => $impIva,
            'moneda_id' => 'PES',
            'moneda_ctz' => '1.0000',
            'request_json' => json_encode($req, JSON_UNESCAPED_UNICODE),
        ]);

        if ($id <= 0) {
            return ['ok'=>false,'msg'=>'No se pudo preparar la Nota de Crédito (DB).'];
        }

        try {
            $wsaa = new WsaaService($cfg);
            $ta = $wsaa->getToken();
            $wsfe = new WsfeService($cfg);

            $ultimo = $wsfe->ultimoAutorizado(['token'=>$ta['token'],'sign'=>$ta['sign']], $ptoVta, $ncTipo);
            $next = $ultimo + 1;
            $req['FeDetReq']['FECAEDetRequest']['CbteDesde'] = $next;
            $req['FeDetReq']['FECAEDetRequest']['CbteHasta'] = $next;

            $resp = $wsfe->solicitarCAE(['token'=>$ta['token'],'sign'=>$ta['sign']], $req);
            $respJson = json_encode($this->normalizeRespForJson($resp), JSON_UNESCAPED_UNICODE);

            if (($resp['resultado'] ?? '') === 'A' && !empty($resp['cae'])) {
                $repo->markIssued($id, [
                    'cbte_nro' => (int)($resp['cbte_nro'] ?? $next),
                    'cae' => (string)($resp['cae'] ?? ''),
                    'cae_vto' => (string)($resp['cae_vto'] ?? ''),
                    'resultado' => (string)($resp['resultado'] ?? ''),
                    'observaciones_json' => json_encode($resp['observaciones'] ?? [], JSON_UNESCAPED_UNICODE),
                    'response_json' => $respJson,
                ]);

                Audit::log('arca_credit_note_issued', 'order', $orderId, [
                    'comprobante_id' => $id,
                    'related_comprobante_id' => $invoiceId,
                    'orig_cbte_tipo' => $origTipo,
                    'nc_cbte_tipo' => $ncTipo,
                    'reason_status' => $reasonStatus,
                    'source' => $source,
                ]);

                // Email con PDF adjunto (best-effort)
                $this->trySendIssuedComprobanteEmail($id);

                return ['ok'=>true,'msg'=>'Nota de Crédito emitida correctamente (CAE).','comprobante_id'=>$id];
            }

            $errs = $resp['errors'] ?? [];
            $obs = $resp['observaciones'] ?? [];
            $first = $errs[0] ?? ($obs[0] ?? null);
            $code = is_array($first) ? (string)($first['code'] ?? 'WSFE') : 'WSFE';
            $msg = is_array($first) ? (string)($first['msg'] ?? 'Rechazado') : 'Rechazado';
            $repo->markError($id, $code, $msg, $respJson);
            Audit::log('arca_credit_note_error', 'order', $orderId, [
                'comprobante_id' => $id,
                'related_comprobante_id' => $invoiceId,
                'code' => $code,
                'message' => $msg,
                'reason_status' => $reasonStatus,
                'source' => $source,
            ]);
            return ['ok'=>false,'msg'=>"ARCA rechazó la NC: {$code} – {$msg}", 'comprobante_id'=>$id];
        } catch (Throwable $e) {
            $repo->markError($id, 'EXCEPTION', $e->getMessage());
            Audit::log('arca_credit_note_exception', 'order', $orderId, [
                'comprobante_id' => $id,
                'related_comprobante_id' => $invoiceId,
                'msg' => $e->getMessage(),
                'reason_status' => $reasonStatus,
                'source' => $source,
            ]);
            return ['ok'=>false,'msg'=>'Error al emitir NC con ARCA: '.$e->getMessage(), 'comprobante_id'=>$id];
        }
    }

    /** @return array{ok:bool, msg:string, comprobante_id?:int} */
    public function emitirFacturaBDesdePedido(int $adminUserId, int $orderId): array
    {
        return $this->emitirFacturaABDesdePedido($adminUserId, $orderId, 6);
    }

    /** @return array{ok:bool, msg:string, comprobante_id?:int} */
    public function emitirFacturaADesdePedido(int $adminUserId, int $orderId): array
    {
        // RI R1: deshabilitado para emisión manual.
        // Factura A se habilita únicamente vía flujo post-compra (Etapa 4) usando emitirFacturaAParaSolicitud().
        return ['ok'=>false,'msg'=>'Factura A no se emite manualmente. Se emite solo por solicitud post-compra.'];
    }

    /**
     * ETAPA 4: Emite Factura A (cbte_tipo=1) usando datos del receptor que provienen de la solicitud post-compra.
     *
     * @return array{ok:bool, msg:string, comprobante_id?:int}
     */
    public function emitirFacturaAParaSolicitud(int $adminUserId, int $orderId, string $receiverTaxCondition, string $receiverCuit, string $receiverLegalName = '', ?int $relatedInvoiceId = null): array
    {
        if ($orderId <= 0) return ['ok'=>false,'msg'=>'Pedido inválido.'];

        $order = $this->loadOrderSnapshot($orderId);
        if (!$order) return ['ok'=>false,'msg'=>'Pedido no encontrado.'];

        $sellerRegime = $this->normalizeRegime((string)($order['seller_tax_regime'] ?? ''));
        if ($sellerRegime !== 'responsable_inscripto') {
            return ['ok'=>false,'msg'=>'Para emitir Factura A, el emisor debe ser Responsable Inscripto.'];
        }

        $taxCond = $this->normalizeRegime($receiverTaxCondition);
        $cuitDigits = preg_replace('/\D+/', '', (string)$receiverCuit) ?? '';
        $cuitDigits = is_string($cuitDigits) ? $cuitDigits : '';

        if ($taxCond !== 'responsable_inscripto') {
            return ['ok'=>false,'msg'=>'Factura A solo corresponde si el receptor es Responsable Inscripto.'];
        }
        if ($cuitDigits === '' || !$this->isValidCuit($cuitDigits)) {
            return ['ok'=>false,'msg'=>'Factura A requiere CUIT válido del receptor.'];
        }

        $docTipo = 80;
        $docNro  = (int)$cuitDigits;

        $cfg = (new ArcaConfig($this->db))->get();
        $env = (string)$cfg['env'];
        $ptoVta = (int)$cfg['pto_vta'];

        // IVA discriminado desde items
        $alics = $this->buildAlicIvaForOrder($orderId);
        $sumNeto = 0;
        $sumIva  = 0;
        foreach ($alics as $a) {
            $sumNeto += (int)($a['BaseImp'] ?? 0);
            $sumIva  += (int)($a['Importe'] ?? 0);
        }
        $impNeto = $sumNeto;
        $impIva  = $sumIva;
        $impTotal = $impNeto + $impIva;

        if ($impTotal <= 0) {
            $impTotal = (int)($order['total'] ?? 0);
            $impNeto  = (int)($order['total_net'] ?? $impTotal);
            $impIva   = (int)($order['total_vat'] ?? 0);
        }

        $condIvaReceptorId = $this->mapCondicionIvaReceptorId($receiverTaxCondition);

        $req = $this->buildFacturaABRequest($ptoVta, 1, $docTipo, $docNro, $condIvaReceptorId, $impTotal, $impNeto, $impIva, $alics);

        // Guardamos el nombre legal si lo tenemos (best-effort) para trazabilidad dentro del request
        if ($receiverLegalName !== '') {
            $req['ReceiverLegalName'] = $receiverLegalName;
        }

        $repo = new ComprobanteFiscalRepository($this->db);
        if (!$repo->tableExists()) {
            return ['ok'=>false,'msg'=>'Falta la tabla comprobantes_fiscales.'];
        }
        if (!$repo->schemaOk()) {
            return ['ok'=>false,'msg'=>'Tu tabla comprobantes_fiscales está desactualizada (schema viejo).'];
        }

        $id = 0;
        if ($repo->schemaHasKind()) {
            $id = $repo->insertPendingInvoiceForOrder($orderId, $relatedInvoiceId, [
                'arca_env' => $env,
                'pto_vta' => $ptoVta,
                'cbte_tipo' => 1,
                'concepto' => 1,
                'doc_tipo' => $docTipo,
                'doc_nro' => $docNro,
                'imp_total' => $impTotal,
                'imp_neto' => $impNeto,
                'imp_iva' => $impIva,
                'moneda_id' => 'PES',
                'moneda_ctz' => '1.0000',
                'request_json' => json_encode($req, JSON_UNESCAPED_UNICODE),
            ]);
        } else {
            // Legacy: no soporta múltiples comprobantes; usamos upsert (última factura prevalece)
            $id = $repo->upsertPendingForOrder($orderId, [
                'arca_env' => $env,
                'pto_vta' => $ptoVta,
                'cbte_tipo' => 1,
                'concepto' => 1,
                'doc_tipo' => $docTipo,
                'doc_nro' => $docNro,
                'imp_total' => $impTotal,
                'imp_neto' => $impNeto,
                'imp_iva' => $impIva,
                'moneda_id' => 'PES',
                'moneda_ctz' => '1.0000',
                'request_json' => json_encode($req, JSON_UNESCAPED_UNICODE),
            ]);
        }

        if ($id <= 0) {
            return ['ok'=>false,'msg'=>'No se pudo preparar la Factura A (DB).'];
        }

        try {
            $wsaa = new WsaaService($cfg);
            $ta = $wsaa->getToken();
            $wsfe = new WsfeService($cfg);

            $ultimo = $wsfe->ultimoAutorizado(['token'=>$ta['token'],'sign'=>$ta['sign']], $ptoVta, 1);
            $next = $ultimo + 1;
            $req['FeDetReq']['FECAEDetRequest']['CbteDesde'] = $next;
            $req['FeDetReq']['FECAEDetRequest']['CbteHasta'] = $next;

            // Importante: si insertamos, actualizamos el request_json con el nro de comprobante propuesto
            try { $repo->updateRequestJson($id, $req); } catch (Throwable $e) { /* best-effort */ }

            $resp = $wsfe->solicitarCAE(['token'=>$ta['token'],'sign'=>$ta['sign']], $req);
            $respJson = json_encode($this->normalizeRespForJson($resp), JSON_UNESCAPED_UNICODE);

            if (($resp['resultado'] ?? '') === 'A' && !empty($resp['cae'])) {
                $repo->markIssued($id, [
                    'cbte_nro' => (int)($resp['cbte_nro'] ?? $next),
                    'cae' => (string)($resp['cae'] ?? ''),
                    'cae_vto' => (string)($resp['cae_vto'] ?? ''),
                    'resultado' => (string)($resp['resultado'] ?? ''),
                    'observaciones_json' => json_encode($resp['observaciones'] ?? [], JSON_UNESCAPED_UNICODE),
                    'response_json' => $respJson,
                ]);

                Audit::log('arca_invoice_a_issued', 'order', $orderId, [
                    'comprobante_id' => $id,
                    'pto_vta' => $ptoVta,
                    'cbte_tipo' => 1,
                    'cbte_nro' => (int)($resp['cbte_nro'] ?? $next),
                    'cae_vto' => (string)($resp['cae_vto'] ?? ''),
                    'related_invoice_id' => $relatedInvoiceId,
                ]);

                $this->trySendIssuedComprobanteEmail($id);

                return ['ok'=>true,'msg'=>'Factura A emitida correctamente (CAE).','comprobante_id'=>$id];
            }

            $errs = $resp['errors'] ?? [];
            $obs = $resp['observaciones'] ?? [];
            $first = $errs[0] ?? ($obs[0] ?? null);
            $code = is_array($first) ? (string)($first['code'] ?? 'WSFE') : 'WSFE';
            $msg = is_array($first) ? (string)($first['msg'] ?? 'Rechazado') : 'Rechazado';
            $repo->markError($id, $code, $msg, $respJson);

            Audit::log('arca_invoice_a_error', 'order', $orderId, [
                'comprobante_id' => $id,
                'code' => $code,
                'message' => $msg,
                'related_invoice_id' => $relatedInvoiceId,
            ]);

            return ['ok'=>false,'msg'=>"ARCA rechazó la Factura A: {$code} – {$msg}", 'comprobante_id'=>$id];
        } catch (\Throwable $e) {
            $repo->markError($id, 'EXCEPTION', $e->getMessage());
            Audit::log('arca_invoice_a_exception', 'order', $orderId, [
                'comprobante_id' => $id,
                'msg' => $e->getMessage(),
                'related_invoice_id' => $relatedInvoiceId,
            ]);
            return ['ok'=>false,'msg'=>'Error al emitir Factura A con ARCA: '.$e->getMessage(), 'comprobante_id'=>$id];
        }
    }

    /**
     * Núcleo de emisión para Factura A/B (RI) con IVA discriminado.
     *
     * @param int $cbteTipo 1 (A) o 6 (B)
     * @return array{ok:bool, msg:string, comprobante_id?:int}
     */
    private function emitirFacturaABDesdePedido(int $adminUserId, int $orderId, int $cbteTipo): array
    {
        if ($orderId <= 0) return ['ok'=>false,'msg'=>'Pedido inválido.'];
        if (!in_array($cbteTipo, [1,6], true)) return ['ok'=>false,'msg'=>'Tipo de comprobante inválido.'];

        $order = $this->loadOrderSnapshot($orderId);
        if (!$order) return ['ok'=>false,'msg'=>'Pedido no encontrado.'];

        $sellerRegime = $this->normalizeRegime((string)($order['seller_tax_regime'] ?? ''));
        if ($sellerRegime !== 'responsable_inscripto') {
            return ['ok'=>false,'msg'=>'Para emitir Factura A/B, el emisor debe ser Responsable Inscripto.'];
        }

        $custCuit = preg_replace('/\D+/', '', (string)($order['customer_cuit'] ?? '')) ?? '';
        $custCond = $this->normalizeRegime((string)($order['customer_tax_condition'] ?? ''));
        $custCuitOk = ($custCuit !== '' && $this->isValidCuit($custCuit));

        // Reglas mínimas:
        // - Factura A: receptor RI + CUIT válido obligatorio
        if ($cbteTipo === 1) {
            if ($custCond !== 'responsable_inscripto') {
                return ['ok'=>false,'msg'=>'Factura A solo corresponde si el receptor es Responsable Inscripto.'];
            }
            if (!$custCuitOk) {
                return ['ok'=>false,'msg'=>'Factura A requiere CUIT válido del receptor.'];
            }
        }

        // DocTipo/DocNro receptor
        // - Factura B (6): si no hay condición fiscal/cuit válido -> Consumidor Final (99/0)
        // - Factura A (1): CUIT (80)
        if ($cbteTipo === 6) {
            if ($custCond === '' || $custCond === 'consumidor_final') {
                $docTipo = 99;
                $docNro  = 0;
            } elseif ($custCuitOk) {
                $docTipo = 80; // CUIT
                $docNro  = (int)$custCuit;
            } else {
                // Si el cliente indicó condición fiscal pero el CUIT no es válido, no forzamos DocTipo 80 (evita error 10015)
                $docTipo = 99;
                $docNro  = 0;
            }
        } else {
            $docTipo = 80;
            $docNro  = (int)$custCuit;
        }

        $cfg = (new ArcaConfig($this->db))->get();
        $env = (string)$cfg['env'];
        $ptoVta = (int)$cfg['pto_vta'];

        // IVA discriminado desde items
        $alics = $this->buildAlicIvaForOrder($orderId);
        $sumNeto = 0;
        $sumIva  = 0;
        foreach ($alics as $a) {
            $sumNeto += (int)($a['BaseImp'] ?? 0);
            $sumIva  += (int)($a['Importe'] ?? 0);
        }
        $impNeto = $sumNeto;
        $impIva  = $sumIva;
        $impTotal = $impNeto + $impIva;

        // Si por algún motivo no pudimos calcular alícuotas, caemos a total del pedido (sin IVA discriminado)
        if ($impTotal <= 0) {
            $impTotal = (int)($order['total'] ?? 0);
            $impNeto  = (int)($order['total_net'] ?? $impTotal);
            $impIva   = (int)($order['total_vat'] ?? 0);
        }

        $condIvaReceptorId = $this->mapCondicionIvaReceptorId((string)($order['customer_tax_condition'] ?? ''));

        $req = $this->buildFacturaABRequest($ptoVta, $cbteTipo, $docTipo, $docNro, $condIvaReceptorId, $impTotal, $impNeto, $impIva, $alics);

        $repo = new ComprobanteFiscalRepository($this->db);
        if (!$repo->tableExists()) {
            return ['ok'=>false,'msg'=>'Falta la tabla comprobantes_fiscales.'];
        }
        if (!$repo->schemaOk()) {
            return ['ok'=>false,'msg'=>'Tu tabla comprobantes_fiscales está desactualizada (schema viejo).'];
        }

        $id = $repo->upsertPendingForOrder($orderId, [
            'arca_env' => $env,
            'pto_vta' => $ptoVta,
            'cbte_tipo' => $cbteTipo,
            'concepto' => 1,
            'doc_tipo' => $docTipo,
            'doc_nro' => $docNro,
            'imp_total' => $impTotal,
            'imp_neto' => $impNeto,
            'imp_iva' => $impIva,
            'moneda_id' => 'PES',
            'moneda_ctz' => '1.0000',
            'request_json' => json_encode($req, JSON_UNESCAPED_UNICODE),
        ]);

        if ($id <= 0) {
            return ['ok'=>false,'msg'=>'No se pudo preparar el comprobante (DB).'];
        }

        try {
            $wsaa = new WsaaService($cfg);
            $ta = $wsaa->getToken();
            $wsfe = new WsfeService($cfg);

            $ultimo = $wsfe->ultimoAutorizado(['token'=>$ta['token'],'sign'=>$ta['sign']], $ptoVta, $cbteTipo);
            $next = $ultimo + 1;
            $req['FeDetReq']['FECAEDetRequest']['CbteDesde'] = $next;
            $req['FeDetReq']['FECAEDetRequest']['CbteHasta'] = $next;

            $resp = $wsfe->solicitarCAE(['token'=>$ta['token'],'sign'=>$ta['sign']], $req);
            $respJson = json_encode($this->normalizeRespForJson($resp), JSON_UNESCAPED_UNICODE);

            if (($resp['resultado'] ?? '') === 'A' && !empty($resp['cae'])) {
                $repo->markIssued($id, [
                    'cbte_nro' => (int)($resp['cbte_nro'] ?? $next),
                    'cae' => (string)($resp['cae'] ?? ''),
                    'cae_vto' => (string)($resp['cae_vto'] ?? ''),
                    'resultado' => (string)($resp['resultado'] ?? ''),
                    'observaciones_json' => json_encode($resp['observaciones'] ?? [], JSON_UNESCAPED_UNICODE),
                    'response_json' => $respJson,
                ]);

                Audit::log('arca_invoice_issued', 'order', $orderId, [
                    'comprobante_id' => $id,
                    'pto_vta' => $ptoVta,
                    'cbte_tipo' => $cbteTipo,
                    'cbte_nro' => (int)($resp['cbte_nro'] ?? $next),
                    'cae_vto' => (string)($resp['cae_vto'] ?? ''),
                ]);

                // Email con PDF adjunto (best-effort, no bloquea la emisión)
                $this->trySendIssuedComprobanteEmail($id);

                return ['ok'=>true,'msg'=>'Comprobante emitido correctamente (CAE).','comprobante_id'=>$id];
            }

            $errs = $resp['errors'] ?? [];
            $obs = $resp['observaciones'] ?? [];
            $first = $errs[0] ?? ($obs[0] ?? null);
            $code = is_array($first) ? (string)($first['code'] ?? 'WSFE') : 'WSFE';
            $msg = is_array($first) ? (string)($first['msg'] ?? 'Rechazado') : 'Rechazado';
            $repo->markError($id, $code, $msg, $respJson);

            Audit::log('arca_invoice_error', 'order', $orderId, [
                'comprobante_id' => $id,
                'code' => $code,
                'message' => $msg,
            ]);

            return ['ok'=>false,'msg'=>"ARCA rechazó la solicitud: {$code} – {$msg}", 'comprobante_id'=>$id];
        } catch (\Throwable $e) {
            $repo->markError($id, 'EXCEPTION', $e->getMessage());
            Audit::log('arca_invoice_exception', 'order', $orderId, ['comprobante_id'=>$id,'msg'=>$e->getMessage()]);
            return ['ok'=>false,'msg'=>'Error al emitir con ARCA: '.$e->getMessage(), 'comprobante_id'=>$id];
        }
    }

    /**
     * Emite o reintenta emitir el comprobante para un pedido.
     *
     * @return array{ok:bool, msg:string, comprobante_id?:int}
     */
    public function emitirFacturaCDesdePedido(int $adminUserId, int $orderId): array
    {
        if ($orderId <= 0) return ['ok'=>false,'msg'=>'Pedido inválido.'];

        // Cargar pedido + snapshot fiscal
        $st = $this->db->prepare("SELECT id,total,total_net,total_vat,currency_code,seller_tax_regime,customer_tax_condition,customer_cuit
                                  FROM orders WHERE id=? LIMIT 1");
        $st->execute([$orderId]);
        $order = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$order) return ['ok'=>false,'msg'=>'Pedido no encontrado.'];

        $sellerRegime = (string)($order['seller_tax_regime'] ?? 'monotributo');
        if ($sellerRegime !== 'monotributo') {
            return ['ok'=>false,'msg'=>'Por ahora este MVP emite solo Factura C (Monotributo).'];
        }

        $cfg = (new ArcaConfig($this->db))->get();
        $env = (string)$cfg['env'];
        $ptoVta = (int)$cfg['pto_vta'];

        // Factura C (cbte_tipo 11)
        $cbteTipo = 11;

        // DocTipo/DocNro receptor
        // Decisión operativa (MVP): Monotributo => siempre Consumidor Final (DocTipo 99 / DocNro 0).
        // No soportamos "Factura C a CUIT" en checkout por ahora.
        $docTipo = 99;
        $docNro  = 0;

        $total = (int)($order['total'] ?? 0);
        $totalNet = (int)($order['total_net'] ?? $total);
        $totalVat = (int)($order['total_vat'] ?? 0);
        // Monotributo: no discriminamos IVA (forzamos 0)
        $totalVat = 0;
        $totalNet = $total;

        // RG 5616 / WSFE: Condición frente al IVA del receptor (obligatorio)
        $condIvaReceptorId = $this->mapCondicionIvaReceptorId('consumidor_final');

        $req = $this->buildFacturaCRequest($ptoVta, $cbteTipo, $docTipo, $docNro, $condIvaReceptorId, $total, $totalNet, $totalVat);

        $repo = new ComprobanteFiscalRepository($this->db);
        if (!$repo->tableExists()) {
            return ['ok'=>false,'msg'=>'Falta la tabla comprobantes_fiscales. Importá database/schema.sql (ZIP vigente) o corré la migración correspondiente.'];
        }
        if (!$repo->schemaOk()) {
            return ['ok'=>false,'msg'=>'Tu tabla comprobantes_fiscales está desactualizada (schema viejo). Solución rápida en DEV: DROP TABLE comprobantes_fiscales y reimportar database/schema.sql del ZIP vigente.'];
        }

        $id = $repo->upsertPendingForOrder($orderId, [
            'arca_env' => $env,
            'pto_vta' => $ptoVta,
            'cbte_tipo' => $cbteTipo,
            'concepto' => 1,
            'doc_tipo' => $docTipo,
            'doc_nro' => $docNro,
            'imp_total' => $total,
            'imp_neto' => $totalNet,
            'imp_iva' => $totalVat,
            'moneda_id' => 'PES',
            'moneda_ctz' => '1.0000',
            'request_json' => json_encode($req, JSON_UNESCAPED_UNICODE),
        ]);

        if ($id <= 0) {
            return ['ok'=>false,'msg'=>'No se pudo preparar el comprobante (DB). Revisá schema/migraciones de comprobantes_fiscales.'];
        }

        try {
            $wsaa = new WsaaService($cfg);
            $ta = $wsaa->getToken();
            $wsfe = new WsfeService($cfg);

            // Numeración
            $ultimo = $wsfe->ultimoAutorizado(['token'=>$ta['token'],'sign'=>$ta['sign']], $ptoVta, $cbteTipo);
            $next = $ultimo + 1;

            $req['FeDetReq']['FECAEDetRequest']['CbteDesde'] = $next;
            $req['FeDetReq']['FECAEDetRequest']['CbteHasta'] = $next;

            $resp = $wsfe->solicitarCAE(['token'=>$ta['token'],'sign'=>$ta['sign']], $req);

            $respJson = json_encode($this->normalizeRespForJson($resp), JSON_UNESCAPED_UNICODE);

            if (($resp['resultado'] ?? '') === 'A' && !empty($resp['cae'])) {
                $repo->markIssued($id, [
                    'cbte_nro' => (int)($resp['cbte_nro'] ?? $next),
                    'cae' => (string)($resp['cae'] ?? ''),
                    'cae_vto' => (string)($resp['cae_vto'] ?? ''),
                    'resultado' => (string)($resp['resultado'] ?? ''),
                    'observaciones_json' => json_encode($resp['observaciones'] ?? [], JSON_UNESCAPED_UNICODE),
                    'response_json' => $respJson,
                ]);

                Audit::log('arca_invoice_issued', 'order', $orderId, [
                    'comprobante_id' => $id,
                    'pto_vta' => $ptoVta,
                    'cbte_tipo' => $cbteTipo,
                    'cbte_nro' => (int)($resp['cbte_nro'] ?? $next),
                    'cae_vto' => (string)($resp['cae_vto'] ?? ''),
                ]);

                // Email con PDF adjunto (best-effort, no bloquea la emisión)
                $this->trySendIssuedComprobanteEmail($id);

                return ['ok'=>true,'msg'=>'Factura emitida correctamente (CAE).','comprobante_id'=>$id];
            }

            // Rechazo/observaciones
            $errs = $resp['errors'] ?? [];
            $obs = $resp['observaciones'] ?? [];
            $first = $errs[0] ?? ($obs[0] ?? null);
            $code = is_array($first) ? (string)($first['code'] ?? 'WSFE') : 'WSFE';
            $msg = is_array($first) ? (string)($first['msg'] ?? 'Rechazado') : 'Rechazado';
            $repo->markError($id, $code, $msg, $respJson);

            Audit::log('arca_invoice_error', 'order', $orderId, [
                'comprobante_id' => $id,
                'code' => $code,
                'message' => $msg,
            ]);

            return ['ok'=>false,'msg'=>"ARCA rechazó la solicitud: {$code} – {$msg}", 'comprobante_id'=>$id];
        } catch (\Throwable $e) {
            $repo->markError($id, 'EXCEPTION', $e->getMessage());
            Audit::log('arca_invoice_exception', 'order', $orderId, ['comprobante_id'=>$id,'msg'=>$e->getMessage()]);
            return ['ok'=>false,'msg'=>'Error al emitir con ARCA: '.$e->getMessage(), 'comprobante_id'=>$id];
        }
    }

    /**
     * Consulta un comprobante emitido (WSFE FECompConsultar) y guarda un snapshot de la respuesta.
     * No re-emite.
     */
    public function consultarComprobante(int $comprobanteId): array
    {
        $repo = new ComprobanteFiscalRepository($this->db);
        $row = $repo->findById($comprobanteId);
        if (!$row) {
            return ['ok' => false, 'msg' => 'Comprobante no encontrado.'];
        }

        $ptoVta = (int)($row['pto_vta'] ?? 0);
        $cbteTipo = (int)($row['cbte_tipo'] ?? 0);
        $cbteNro = (int)($row['cbte_nro'] ?? 0);

        if ($ptoVta <= 0 || $cbteTipo <= 0 || $cbteNro <= 0) {
            return ['ok' => false, 'msg' => 'El comprobante no tiene PtoVta/CbteTipo/CbteNro para consultar.'];
        }

        try {
            $cfg = (new ArcaConfig($this->db))->get();
            $wsaa = new WsaaService($cfg);
            $ta = $wsaa->getToken();

            $wsfe = new WsfeService($cfg);
            $res = $wsfe->consultarComprobante(['token'=>$ta['token'],'sign'=>$ta['sign']], $ptoVta, $cbteTipo, $cbteNro);

            $repo->appendConsulta($comprobanteId, $this->normalizeRespForJson($res));
            return ['ok' => true, 'msg' => 'Consulta OK', 'consulta' => $res];
        } catch (\Throwable $e) {
            $repo->appendConsulta($comprobanteId, ['error' => $e->getMessage()]);
            return ['ok' => false, 'msg' => 'Error consultando en ARCA: ' . $e->getMessage()];
        }
    }

    /**
     * @return array<string,mixed>
     */
    private function buildFacturaCRequest(
        int $ptoVta,
        int $cbteTipo,
        int $docTipo,
        int $docNro,
        int $condIvaReceptorId,
        int $impTotal,
        int $impNeto,
        int $impIva
    ): array
    {
        $today = (int)gmdate('Ymd');

        return [
            'FeCabReq' => [
                'CantReg' => 1,
                'PtoVta' => $ptoVta,
                'CbteTipo' => $cbteTipo,
            ],
            'FeDetReq' => [
                'FECAEDetRequest' => [
                    'Concepto' => 1,
                    'DocTipo' => $docTipo,
                    'DocNro' => $docNro,
                    // RG 5616: obligatorio. Ver método FEParamGetCondicionIvaReceptor.
                    'CondicionIVAReceptorId' => $condIvaReceptorId,
                    'CbteDesde' => 0,
                    'CbteHasta' => 0,
                    'CbteFch' => $today,
                    'ImpTotal' => $impTotal,
                    'ImpTotConc' => 0,
                    'ImpNeto' => $impNeto,
                    'ImpOpEx' => 0,
                    'ImpTrib' => 0,
                    'ImpIVA' => $impIva,
                    'MonId' => 'PES',
                    'MonCotiz' => 1,
                ],
            ],
        ];
    }

    /**
     * Request para Factura A/B (RI) con IVA discriminado.
     *
     * @param array<int,array{Id:int,BaseImp:int,Importe:int}> $alics
     * @return array<string,mixed>
     */
    private function buildFacturaABRequest(
        int $ptoVta,
        int $cbteTipo,
        int $docTipo,
        int $docNro,
        int $condIvaReceptorId,
        int $impTotal,
        int $impNeto,
        int $impIva,
        array $alics
    ): array {
        $today = (int)gmdate('Ymd');

        // WSFE espera: Iva => { AlicIva => [ {Id, BaseImp, Importe}, ... ] }
        $ivaNode = null;
        if (!empty($alics)) {
            $ivaNode = ['AlicIva' => array_map(static function(array $a) {
                return [
                    'Id' => (int)($a['Id'] ?? 3),
                    'BaseImp' => (int)($a['BaseImp'] ?? 0),
                    'Importe' => (int)($a['Importe'] ?? 0),
                ];
            }, $alics)];
        }

        $det = [
            'Concepto' => 1,
            'DocTipo' => $docTipo,
            'DocNro' => $docNro,
            'CondicionIVAReceptorId' => $condIvaReceptorId,
            'CbteDesde' => 0,
            'CbteHasta' => 0,
            'CbteFch' => $today,
            'ImpTotal' => $impTotal,
            'ImpTotConc' => 0,
            'ImpNeto' => $impNeto,
            'ImpOpEx' => 0,
            'ImpTrib' => 0,
            'ImpIVA' => $impIva,
            'MonId' => 'PES',
            'MonCotiz' => 1,
        ];
        if ($ivaNode !== null) {
            $det['Iva'] = $ivaNode;
        }

        return [
            'FeCabReq' => [
                'CantReg' => 1,
                'PtoVta' => $ptoVta,
                'CbteTipo' => $cbteTipo,
            ],
            'FeDetReq' => [
                'FECAEDetRequest' => $det,
            ],
        ];
    }

    /**
     * Request WSFE para Nota de Crédito TOTAL.
     *
     * @param array<string,mixed>|null $ivaNode Nodo Iva ya armado (Iva=>AlicIva=>...). Se reutiliza del request de la factura.
     * @return array<string,mixed>
     */
    private function buildNotaCreditoRequest(
        int $ptoVta,
        int $ncCbteTipo,
        int $docTipo,
        int $docNro,
        int $condIvaReceptorId,
        int $impTotal,
        int $impNeto,
        int $impIva,
        $ivaNode,
        int $origCbteTipo,
        int $origPtoVta,
        int $origCbteNro,
        string $emisorCuit
    ): array {
        $today = (int)gmdate('Ymd');

        $det = [
            'Concepto' => 1,
            'DocTipo' => $docTipo,
            'DocNro' => $docNro,
            'CondicionIVAReceptorId' => $condIvaReceptorId,
            'CbteDesde' => 0,
            'CbteHasta' => 0,
            'CbteFch' => $today,
            'ImpTotal' => $impTotal,
            'ImpTotConc' => 0,
            'ImpNeto' => $impNeto,
            'ImpOpEx' => 0,
            'ImpTrib' => 0,
            'ImpIVA' => $impIva,
            'MonId' => 'PES',
            'MonCotiz' => 1,
        ];

        // Asociamos comprobante original
        if ($origCbteNro > 0) {
            $cuit = preg_replace('/\D+/', '', $emisorCuit) ?? '';
            $asoc = [
                'Tipo' => $origCbteTipo,
                'PtoVta' => $origPtoVta,
                'Nro' => $origCbteNro,
            ];
            if ($cuit !== '') {
                $asoc['Cuit'] = (int)$cuit;
            }
            $det['CbtesAsoc'] = ['CbteAsoc' => [$asoc]];
        }

        // Para A/B (y NC A/B), reusamos el IVA del request original si está.
        if (is_array($ivaNode) && !empty($ivaNode)) {
            $det['Iva'] = $ivaNode;
        }

        return [
            'FeCabReq' => [
                'CantReg' => 1,
                'PtoVta' => $ptoVta,
                'CbteTipo' => $ncCbteTipo,
            ],
            'FeDetReq' => [
                'FECAEDetRequest' => $det,
            ],
        ];
    }

    /**
     * Snapshot mínimo del pedido para emisión.
     * @return array<string,mixed>|null
     */
    private function loadOrderSnapshot(int $orderId): ?array
    {
        $st = $this->db->prepare("SELECT id,total,total_net,total_vat,currency_code,seller_tax_regime,customer_tax_condition,customer_cuit
                                  FROM orders WHERE id=? LIMIT 1");
        $st->execute([$orderId]);
        $row = $st->fetch(PDO::FETCH_ASSOC) ?: null;
        return $row ?: null;
    }

    /** Normaliza valores posibles del régimen (settings/UI). */
    private function normalizeRegime(string $raw): string
    {
        $v = trim(mb_strtolower($raw));
        if ($v === '') return '';
        if (str_contains($v, 'inscrip') || $v === 'ri') return 'responsable_inscripto';
        if (str_contains($v, 'mono')) return 'monotributo';
        if (str_contains($v, 'exento')) return 'exento';
        if (str_contains($v, 'consumidor')) return 'consumidor_final';
        return $v;
    }

    /** Valida CUIT (11 dígitos) por dígito verificador. */
    private function isValidCuit(string $cuit): bool
    {
        $cuit = preg_replace('/\D+/', '', $cuit) ?? '';
        if (strlen($cuit) !== 11) return false;
        if (!ctype_digit($cuit)) return false;

        $w = [5,4,3,2,7,6,5,4,3,2];
        $sum = 0;
        for ($i=0; $i<10; $i++) {
            $sum += ((int)$cuit[$i]) * $w[$i];
        }
        $mod = $sum % 11;
        $dv = 11 - $mod;
        if ($dv === 11) $dv = 0;
        if ($dv === 10) $dv = 9;
        return $dv === (int)$cuit[10];
    }

    /**
     * Construye AlicIva desde items del pedido.
     *
     * @return array<int,array{Id:int,BaseImp:int,Importe:int}>
     */
    private function buildAlicIvaForOrder(int $orderId): array
    {
        $st = $this->db->prepare("SELECT qty, price, vat_rate, line_net, line_vat FROM order_items WHERE order_id=?");
        $st->execute([$orderId]);

        $group = [];
        while ($r = $st->fetch(PDO::FETCH_ASSOC)) {
            $qty = (int)($r['qty'] ?? 0);
            $price = (int)($r['price'] ?? 0);
            $rate = (float)($r['vat_rate'] ?? 0);

            $lineNet = $r['line_net'] !== null ? (int)$r['line_net'] : null;
            $lineVat = $r['line_vat'] !== null ? (int)$r['line_vat'] : null;

            if ($lineNet === null || $lineVat === null) {
                $lineTotal = $qty * $price;
                if ($rate > 0) {
                    // total = neto * (1+rate)
                    $net = (int)round($lineTotal / (1 + $rate));
                    $vat = (int)round($lineTotal - $net);
                } else {
                    $net = (int)$lineTotal;
                    $vat = 0;
                }
                $lineNet = $net;
                $lineVat = $vat;
            }

            $id = $this->mapAlicIvaId($rate);
            if (!isset($group[$id])) {
                $group[$id] = ['Id'=>$id,'BaseImp'=>0,'Importe'=>0];
            }
            $group[$id]['BaseImp'] += (int)$lineNet;
            $group[$id]['Importe'] += (int)$lineVat;
        }

        // Si no hay items (o todo null), devolvemos al menos 0% para no romper WSFE.
        if (empty($group)) {
            $group[3] = ['Id'=>3,'BaseImp'=>0,'Importe'=>0];
        }

        return array_values($group);
    }

    /**
     * IDs WSFE AlicIva (mapeo mínimo).
     * 0% => 3, 10.5% => 4, 21% => 5, 27% => 6, 5% => 8, 2.5% => 9.
     */
    private function mapAlicIvaId(float $rate): int
    {
        $r = round($rate, 4);
        $tol = 0.0006;
        if (abs($r - 0.105) < $tol) return 4;
        if (abs($r - 0.21) < $tol) return 5;
        if (abs($r - 0.27) < $tol) return 6;
        if (abs($r - 0.05) < $tol) return 8;
        if (abs($r - 0.025) < $tol) return 9;
        return 3;
    }

    /**
     * Convertimos el objeto SOAP a algo JSON serializable.
     * @param array<string,mixed> $resp
     * @return array<string,mixed>
     */
    private function normalizeRespForJson(array $resp): array
    {
        $out = $resp;
        unset($out['raw']);
        return $out;
    }

    /**
     * Envía el comprobante por email (PDF adjunto) si corresponde.
     * Best-effort: nunca bloquea la emisión.
     *
     * @return array{ok:bool,skipped?:bool,msg?:string}
     */
    private function trySendIssuedComprobanteEmail(int $comprobanteId, bool $force = false): array
    {
        if ($comprobanteId <= 0) return ['ok'=>false,'msg'=>'comprobante_id inválido'];

        $repo = new ComprobanteFiscalRepository($this->db);
        if (!$repo->schemaOk()) return ['ok'=>false,'msg'=>'schema comprobantes_fiscales no disponible'];

        $cf = $repo->findById($comprobanteId);
        if (!$cf) return ['ok'=>false,'msg'=>'comprobante no encontrado'];
        if ((string)($cf['status'] ?? '') !== 'issued') return ['ok'=>true,'skipped'=>true,'msg'=>'El comprobante no está emitido'];

        // Idempotencia: si tenemos tracking, no reenviar (salvo force).
        if (!$force && $repo->schemaHasEmailTracking() && !empty($cf['email_sent_at'])) {
            return ['ok'=>true,'skipped'=>true,'msg'=>'Email ya enviado'];
        }

        $orderId = (int)($cf['order_id'] ?? 0);
        if ($orderId <= 0) return ['ok'=>false,'msg'=>'order_id inválido'];

        try {
            // Usuario destino
            $st = $this->db->prepare("SELECT user_id FROM orders WHERE id=:id LIMIT 1");
            $st->execute([':id' => $orderId]);
            $userId = (int)($st->fetchColumn() ?: 0);
            if ($userId <= 0) return ['ok'=>false,'msg'=>'No se encontró el usuario del pedido'];

            $st2 = $this->db->prepare("SELECT email FROM users WHERE id=:id LIMIT 1");
            $st2->execute([':id' => $userId]);
            $to = (string)($st2->fetchColumn() ?: '');
            if (trim($to) === '') return ['ok'=>false,'msg'=>'El usuario no tiene email'];

            // PDF
            $pdfSvc = new FacturaPdfService($this->db);
            $pdf = $pdfSvc->buildPdfForComprobanteId($comprobanteId);
            if (!($pdf['ok'] ?? false)) {
                $repo->markEmailError($comprobanteId, (string)($pdf['error'] ?? 'No se pudo generar el PDF'));
                return ['ok'=>false,'msg'=>(string)($pdf['error'] ?? 'No se pudo generar el PDF')];
            }

            $cbteTipo = (int)($cf['cbte_tipo'] ?? 0);
            $pto = (int)($cf['pto_vta'] ?? 0);
            $nro = (int)($cf['cbte_nro'] ?? 0);

            $invoiceLabel = match ($cbteTipo) {
                1 => 'Factura A',
                6 => 'Factura B',
                11 => 'Factura C',
                8 => 'Nota de Crédito B',
                13 => 'Nota de Crédito C',
                default => 'Comprobante ' . $cbteTipo,
            };

            $invoiceNumber = ($pto > 0 && $nro > 0)
                ? str_pad((string)$pto, 4, '0', STR_PAD_LEFT) . '-' . str_pad((string)$nro, 8, '0', STR_PAD_LEFT)
                : '';

            $tm = new TransactionalMail($this->db);
            $ok = $tm->sendInvoiceIssued($userId, $orderId, $invoiceLabel, $invoiceNumber, (string)($pdf['bytes'] ?? ''), (string)($pdf['filename'] ?? 'factura.pdf'));

            if ($ok) {
                $repo->markEmailSent($comprobanteId, $to);
                Audit::log('arca_invoice_email_sent', 'order', $orderId, [
                    'comprobante_id' => $comprobanteId,
                    'to' => $to,
                ]);
            } else {
                $repo->markEmailError($comprobanteId, 'mail() devolvió false');
                Audit::log('arca_invoice_email_error', 'order', $orderId, [
                    'comprobante_id' => $comprobanteId,
                    'to' => $to,
                    'error' => 'mail() devolvió false',
                ]);
            }

            return ['ok'=>(bool)$ok, 'msg'=>$ok ? 'Email enviado.' : 'No se pudo enviar el email.'];
        } catch (Throwable $e) {
            try { $repo->markEmailError($comprobanteId, $e->getMessage()); } catch (Throwable $e2) {}
            return ['ok'=>false,'msg'=>$e->getMessage()];
        }
    }

    /**
     * Reenvía el email del comprobante (PDF adjunto) aun si ya había sido enviado.
     * @return array{ok:bool,skipped?:bool,msg?:string}
     */
    public function reenviarEmailComprobante(int $actorUserId, int $comprobanteId, string $source = 'manual_resend'): array
    {
        $r = $this->trySendIssuedComprobanteEmail($comprobanteId, true);
        try {
            Audit::log('arca_comprobante_email_resend', 'comprobante_fiscal', $comprobanteId, [
                'actor_user_id' => $actorUserId,
                'source' => $source,
                'result' => $r,
            ]);
        } catch (Throwable $e) {
            // no bloquear
        }
        return $r;
    }

    /**
     * Reintenta la emisión de un comprobante (Factura o Nota de Crédito) que quedó pending/error.
     * Usa el request_json guardado y recalcula el próximo número autorizado.
     *
     * @return array{ok:bool,skipped?:bool,msg?:string,comprobante_id?:int}
     */
    public function reintentarEmisionComprobante(int $actorUserId, int $comprobanteId, string $source = 'manual_retry'): array
    {
        $repo = new ComprobanteFiscalRepository($this->db);
        $cf = $repo->findById($comprobanteId);
        if (!$cf) return ['ok'=>false,'msg'=>'Comprobante no encontrado.'];

        $status = (string)($cf['status'] ?? '');
        if ($status === 'issued') {
            return ['ok'=>true,'skipped'=>true,'msg'=>'El comprobante ya está emitido.','comprobante_id'=>$comprobanteId];
        }
        if (!in_array($status, ['pending','error'], true)) {
            return ['ok'=>true,'skipped'=>true,'msg'=>'El comprobante no está en estado pending/error.','comprobante_id'=>$comprobanteId];
        }

        $req = json_decode((string)($cf['request_json'] ?? '{}'), true);
        if (!is_array($req) || empty($req['FeDetReq']['FECAEDetRequest'])) {
            return ['ok'=>false,'msg'=>'request_json inválido o incompleto.','comprobante_id'=>$comprobanteId];
        }

        $cfg = (new \App\Services\Billing\Arca\ArcaConfig($this->db))->get();
        $ptoVta = (int)($cf['pto_vta'] ?? ($cfg['pto_vta'] ?? 1));
        $cbteTipo = (int)($cf['cbte_tipo'] ?? 0);
        if ($ptoVta <= 0 || $cbteTipo <= 0) {
            return ['ok'=>false,'msg'=>'pto_vta/cbte_tipo inválidos.','comprobante_id'=>$comprobanteId];
        }

        try {
            $wsaa = new WsaaService($cfg);
            $ta = $wsaa->getToken();
            $wsfe = new WsfeService($cfg);

            $ultimo = $wsfe->ultimoAutorizado(['token'=>$ta['token'],'sign'=>$ta['sign']], $ptoVta, $cbteTipo);
            $next = $ultimo + 1;
            $req['FeDetReq']['FECAEDetRequest']['CbteDesde'] = $next;
            $req['FeDetReq']['FECAEDetRequest']['CbteHasta'] = $next;

            // Guardamos el request actualizado para auditoría.
            $repo->updateRequestJson($comprobanteId, $req);

            $resp = $wsfe->solicitarCAE(['token'=>$ta['token'],'sign'=>$ta['sign']], $req);
            $respJson = json_encode($this->normalizeRespForJson($resp), JSON_UNESCAPED_UNICODE);

            if (($resp['resultado'] ?? '') === 'A' && !empty($resp['cae'])) {
                $repo->markIssued($comprobanteId, [
                    'cbte_nro' => (int)($resp['cbte_nro'] ?? $next),
                    'cae' => (string)($resp['cae'] ?? ''),
                    'cae_vto' => (string)($resp['cae_vto'] ?? ''),
                    'resultado' => (string)($resp['resultado'] ?? ''),
                    'observaciones_json' => json_encode($resp['observaciones'] ?? [], JSON_UNESCAPED_UNICODE),
                    'response_json' => $respJson,
                ]);

                Audit::log('arca_comprobante_retry_issued', 'comprobante_fiscal', $comprobanteId, [
                    'actor_user_id' => $actorUserId,
                    'order_id' => (int)($cf['order_id'] ?? 0),
                    'kind' => (string)($cf['kind'] ?? ''),
                    'source' => $source,
                ]);

                // Email (best-effort)
                $this->trySendIssuedComprobanteEmail($comprobanteId);

                return ['ok'=>true,'msg'=>'Reintento OK: comprobante emitido (CAE).','comprobante_id'=>$comprobanteId];
            }

            $errs = $resp['errors'] ?? [];
            $obs = $resp['observaciones'] ?? [];
            $first = $errs[0] ?? ($obs[0] ?? null);
            $code = is_array($first) ? (string)($first['code'] ?? 'WSFE') : 'WSFE';
            $msg = is_array($first) ? (string)($first['msg'] ?? 'Rechazado') : 'Rechazado';
            $repo->markError($comprobanteId, $code, $msg, $respJson);

            Audit::log('arca_comprobante_retry_error', 'comprobante_fiscal', $comprobanteId, [
                'actor_user_id' => $actorUserId,
                'order_id' => (int)($cf['order_id'] ?? 0),
                'kind' => (string)($cf['kind'] ?? ''),
                'code' => $code,
                'message' => $msg,
                'source' => $source,
            ]);

            return ['ok'=>false,'msg'=>"ARCA rechazó el reintento: {$code} – {$msg}",'comprobante_id'=>$comprobanteId];
        } catch (Throwable $e) {
            $repo->markError($comprobanteId, 'EXCEPTION', $e->getMessage());
            try {
                Audit::log('arca_comprobante_retry_exception', 'comprobante_fiscal', $comprobanteId, [
                    'actor_user_id' => $actorUserId,
                    'order_id' => (int)($cf['order_id'] ?? 0),
                    'kind' => (string)($cf['kind'] ?? ''),
                    'error' => $e->getMessage(),
                    'source' => $source,
                ]);
            } catch (Throwable $e2) {
                // no bloquear
            }
            return ['ok'=>false,'msg'=>$e->getMessage(),'comprobante_id'=>$comprobanteId];
        }
    }

    /**
     * WSFE (RG 5616): Condición frente al IVA del receptor.
     *
     * Mapeo mínimo a IDs del método FEParamGetCondicionIvaReceptor.
     * Si no coincide, cae en "Consumidor Final" (5), que es el caso más común.
     */
    private function mapCondicionIvaReceptorId(string $customerTaxCondition): int
    {
        $v = trim(mb_strtolower($customerTaxCondition));

        // Valores que hoy maneja el checkout (UI)
        return match ($v) {
            'responsable_inscripto', 'ri' => 1,
            'exento' => 4,
            'consumidor_final', 'cf' => 5,
            'monotributo', 'mono', 'monotributista' => 6,
            default => 5,
        };
    }
}

