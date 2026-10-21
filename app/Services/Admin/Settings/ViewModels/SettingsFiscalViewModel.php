<?php
namespace App\Services\Admin\Settings\ViewModels;

final class SettingsFiscalViewModel
{
    public function build(array $cfg): array
    {
        $cfg = is_array($cfg) ? $cfg : [];

        $regime = strtolower(trim((string)($cfg['seller_tax_regime'] ?? 'monotributo')));
        if ($regime === '') {
            $regime = 'monotributo';
        }

        $regimeLabels = [
            'monotributo' => 'Monotributo (Factura C)',
            'responsable_inscripto' => 'Responsable Inscripto (Factura A/B)',
            'exento' => 'Exento',
            'no_facturar' => 'No facturar',
        ];

        $regimeOptions = [
            ['value' => 'monotributo', 'label' => 'Monotributo (Factura C)'],
            ['value' => 'responsable_inscripto', 'label' => 'Responsable Inscripto (Factura A/B)'],
            ['value' => 'exento', 'label' => 'Exento'],
            ['value' => 'no_facturar', 'label' => 'No facturar (desactivado)'],
        ];
        foreach ($regimeOptions as &$opt) {
            $opt['selected'] = ((string)$opt['value'] === $regime);
        }
        unset($opt);

        $trigger = strtolower(trim((string)($cfg['arca_auto_emit_trigger'] ?? 'paid')));
        if (!in_array($trigger, ['paid', 'shipped'], true)) {
            $trigger = 'paid';
        }

        $allStatuses = [
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

        $factSet = $this->csvToSet((string)($cfg['arca_facturable_statuses'] ?? ''));
        $noSet = $this->csvToSet((string)($cfg['arca_non_facturable_statuses'] ?? ''));

        $statusOptions = [];
        foreach ($allStatuses as $key => $label) {
            $statusOptions[] = [
                'key' => (string)$key,
                'label' => (string)$label,
                'facturable_checked' => isset($factSet[$key]),
                'non_facturable_checked' => isset($noSet[$key]),
            ];
        }

        return [
            'regime' => $regime,
            'regime_label' => (string)($regimeLabels[$regime] ?? $regime),
            'regime_options' => $regimeOptions,
            'is_no_facturar' => ($regime === 'no_facturar'),

            'seller_cuit' => (string)($cfg['seller_cuit'] ?? ''),
            'seller_business_name' => (string)($cfg['seller_business_name'] ?? ''),
            'seller_fiscal_address' => (string)($cfg['seller_fiscal_address'] ?? ''),

            'arca_env' => (string)($cfg['arca_env'] ?? 'homologacion'),
            'arca_pto_vta' => (string)($cfg['arca_pto_vta'] ?? '1'),
            'arca_ws_service' => (string)($cfg['arca_ws_service'] ?? 'wsfe'),
            'arca_auto_emit_checked' => ((string)($cfg['arca_auto_emit'] ?? '0') === '1'),
            'arca_auto_emit_trigger' => $trigger,

            'status_options' => $statusOptions,
        ];
    }

    private function csvToSet(string $csv): array
    {
        $csv = strtolower(trim($csv));
        if ($csv === '') {
            return [];
        }

        $parts = array_values(array_filter(array_map('trim', explode(',', $csv)), static fn($x) => $x !== ''));
        return array_fill_keys($parts, true);
    }
}

