<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionComprobantesShowViewModel
{
    /**
     * @param array<string,mixed> $input
     * @return array<string,mixed>
     */
    public function build(array $input): array
    {
        $row = is_array($input['row'] ?? null) ? $input['row'] : [];

        $id = (int)($row['id'] ?? 0);
        $status = (string)($row['status'] ?? '');
        $orderId = (int)($row['order_id'] ?? 0);
        $cbteTipo = (int)($row['cbte_tipo'] ?? 0);
        $pto = (int)($row['pto_vta'] ?? 0);
        $cbteNro = (int)($row['cbte_nro'] ?? 0);
        $impTotal = (int)($row['imp_total'] ?? 0);
        $impNeto = (int)($row['imp_neto'] ?? 0);
        $impIva = (int)($row['imp_iva'] ?? 0);

        $numeroFmt = ($pto > 0 && $cbteNro > 0)
            ? (str_pad((string)$pto, 4, '0', STR_PAD_LEFT) . '-' . str_pad((string)$cbteNro, 8, '0', STR_PAD_LEFT))
            : '-';

        $ivaDisplay = '$ ' . number_format($impIva, 0, ',', '.');
        if (in_array($cbteTipo, [11, 6], true)) {
            $impNeto = $impTotal;
            $ivaDisplay = 'No discriminado';
        }

        return [
            'crumbs' => [
                ['label' => 'Admin', 'href' => base_url('admin')],
                ['label' => 'Facturacion', 'href' => base_url('admin/facturacion/control')],
                ['label' => 'Comprobantes', 'href' => base_url('admin/facturacion/comprobantes')],
                ['label' => 'Detalle', 'href' => base_url('admin/facturacion/comprobantes/show?id=' . $id)],
            ],
            'id' => $id,
            'title' => $this->cbteLabel($cbteTipo) . ' - ' . $numeroFmt,
            'status_label' => fiscal_doc_status_label((string)($status ?: '')),
            'status_badge_class' => $this->statusBadgeClass($status),
            'env_label' => (string)($row['arca_env'] ?? '-'),
            'cae' => (string)($row['cae'] ?? ''),
            'cae_vto' => (string)($row['cae_vto'] ?? ''),
            'email_sent_at' => (string)($row['email_sent_at'] ?? ''),
            'email_error' => (string)($row['email_error'] ?? ''),
            'order_id' => $orderId,
            'order_url' => $orderId > 0 ? base_url('admin/orders/show?id=' . $orderId) : '',
            'created_at' => (string)($row['created_at'] ?? ''),
            'cbte_label' => $this->cbteLabel($cbteTipo),
            'cbte_tipo' => $cbteTipo,
            'pto_vta' => $pto,
            'numero_fmt' => $numeroFmt,
            'doc_tipo' => (string)($row['doc_tipo'] ?? ''),
            'doc_nro' => (string)($row['doc_nro'] ?? ''),
            'imp_total_label' => '$ ' . number_format($impTotal, 0, ',', '.'),
            'imp_neto_label' => '$ ' . number_format($impNeto, 0, ',', '.'),
            'imp_iva_label' => $ivaDisplay,
            'error_message' => (string)($row['error_message'] ?? ''),
            'request_pretty' => $this->prettyJson((string)($row['request_json'] ?? '')),
            'response_pretty' => $this->prettyJson((string)($row['response_json'] ?? '')),
            'observaciones_pretty' => $this->prettyJson((string)($row['observaciones_json'] ?? '')),
            'pdf_url' => base_url('admin/facturacion/comprobantes/pdf?id=' . $id),
            'back_url' => base_url('admin/facturacion/comprobantes'),
            'consultar_url' => base_url('admin/facturacion/comprobantes/consultar'),
            'retry_emit_url' => base_url('admin/facturacion/comprobantes/retry-emit'),
            'resend_email_url' => base_url('admin/facturacion/comprobantes/resend-email'),
            'show_retry' => in_array($status, ['pending', 'error'], true),
            'show_resend' => ($status === 'issued'),
            'is_issued' => ($status === 'issued'),
            'csrf' => csrf_token(),
        ];
    }

    private function cbteLabel(int $cbteTipo): string
    {
        if ($cbteTipo === 1) {
            return 'Factura A';
        }
        if ($cbteTipo === 6) {
            return 'Factura B';
        }
        if ($cbteTipo === 11) {
            return 'Factura C';
        }
        if ($cbteTipo === 8) {
            return 'Nota de Credito B';
        }
        if ($cbteTipo === 13) {
            return 'Nota de Credito C';
        }
        return 'Comprobante ' . $cbteTipo;
    }

    private function statusBadgeClass(string $status): string
    {
        if ($status === 'pending') {
            return 'bg-yellow-50 text-yellow-800 border-yellow-200';
        }
        if ($status === 'issued') {
            return 'bg-emerald-50 text-emerald-800 border-emerald-200';
        }
        if ($status === 'error') {
            return 'bg-red-50 text-red-800 border-red-200';
        }
        return 'bg-slate-50 text-slate-800 border-slate-200';
    }

    private function prettyJson(string $json): string
    {
        if ($json === '') {
            return '';
        }
        $decoded = json_decode($json, true);
        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return $json;
        }
        return (string)json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}



