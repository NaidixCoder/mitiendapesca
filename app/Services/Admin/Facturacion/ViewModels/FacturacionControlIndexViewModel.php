<?php
namespace App\Services\Admin\Facturacion\ViewModels;

final class FacturacionControlIndexViewModel
{
    /**
     * @param array<string,mixed> $alertsCounts
     * @param array<int,array<string,mixed>> $latestClosures
     * @return array<string,mixed>
     */
    public function build(array $alertsCounts, array $latestClosures): array
    {
        $loaded = (int)($alertsCounts['loaded_not_validated'] ?? 0);
        $missing = (int)($alertsCounts['purchases_missing_attachments'] ?? 0);
        $unclosed = (int)($alertsCounts['unclosed_periods'] ?? 0);
        $totalAlerts = $loaded + $missing + $unclosed;

        $lastClosure = '';
        if (!empty($latestClosures)) {
            $first = is_array($latestClosures[0] ?? null) ? $latestClosures[0] : [];
            $lastClosure = (string)($first['period'] ?? '');
        }

        $regime = strtolower(trim((string)app_setting('fiscal_seller_tax_regime', 'monotributo')));
        if ($regime === '') {
            $regime = 'monotributo';
        }
        $isRI = ($regime === 'responsable_inscripto');

        $quickLinks = [];
        if ($isRI) {
            $quickLinks[] = ['label' => 'Libro IVA Ventas', 'url' => base_url('admin/facturacion/libro-iva')];
            $quickLinks[] = ['label' => 'Libro IVA Compras', 'url' => base_url('admin/facturacion/libro-iva-compras')];
            $quickLinks[] = ['label' => 'Reporte mensual IVA', 'url' => base_url('admin/facturacion/reportes/mensual')];
        }
        $quickLinks[] = ['label' => 'Reportes', 'url' => base_url('admin/facturacion/reportes')];
        $quickLinks[] = ['label' => 'Alertas', 'url' => base_url('admin/facturacion/alertas')];

        return [
            'crumbs' => admin_crumbs('facturacion.control'),
            'loaded' => $loaded,
            'missing' => $missing,
            'unclosed' => $unclosed,
            'total_alerts' => $totalAlerts,
            'current_period' => date('Y-m'),
            'last_closure' => $lastClosure,
            'period_state' => $totalAlerts > 0 ? 'Con alertas' : 'Operativo',
            'period_pill_label' => $totalAlerts > 0 ? 'Periodo con alertas' : 'Periodo estable',
            'period_pill_dot_class' => $totalAlerts > 0 ? 'is-warning' : 'is-ok',
            'latest_closures' => $latestClosures,
            'quick_links' => $quickLinks,
        ];
    }
}

