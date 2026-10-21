<?php

namespace App\Support\Admin;

use App\Repositories\Billing\ComprobanteFiscalRepository;
use App\Repositories\Settings\AppSettingsRepository;
use App\Services\Admin\AdminOrderService;
use PDO;

/**
 * Render server-side del bloque ARCA (HTML) para refrescar UI vía AJAX.
 *
 * Regla: views tontas. Este renderer prepara el contexto de datos y delega
 * el render a un partial único.
 */
final class ArcaBoxRenderer
{
    public function __construct(private PDO $db) {}

    public function render(int $orderId): string
    {
        $svc = new AdminOrderService($this->db);
        [$orderView] = $svc->getDetailForAdmin($orderId);

        $comprobante = null;
        $comprobantes = [];
        $creditNote = null;
        $comprobantesHasKind = false;

        try {
            $crepo = new ComprobanteFiscalRepository($this->db);
            $comprobante = $crepo->findByOrderId($orderId); // factura (kind=invoice)
            $comprobantesHasKind = $crepo->schemaHasKind();
            $comprobantes = $crepo->listByOrderId($orderId);

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
            $creditNote = null;
            $comprobantesHasKind = false;
        }

        // Snapshot de régimen fiscal del emisor desde app_settings (fuente de verdad).
        $settingsRepo = new AppSettingsRepository($this->db);
        $orderView['seller_tax_regime'] = (string)$settingsRepo->get('fiscal_seller_tax_regime', 'monotributo');

        // Config ARCA (para UI "inteligente" en Admin)
        try {
            $cfg = (new \App\Services\Billing\Arca\ArcaConfig($this->db))->get();
            $orderView['arca_auto_emit'] = (int)($cfg['auto_emit'] ?? false) ? 1 : 0;
            $orderView['arca_auto_emit_trigger'] = (string)($cfg['auto_emit_trigger'] ?? 'paid');
        } catch (\Throwable $e) {
            $orderView['arca_auto_emit'] = 0;
            $orderView['arca_auto_emit_trigger'] = 'paid';
        }

        ob_start();
        section('admin/orders/arca_box', [
            'orderView' => $orderView,
            'comprobante' => $comprobante,
            'creditNote' => $creditNote,
            'comprobantesHasKind' => (bool)$comprobantesHasKind,
        ]);
        return (string)ob_get_clean();
    }
}