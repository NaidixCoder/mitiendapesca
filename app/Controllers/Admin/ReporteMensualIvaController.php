<?php
namespace App\Controllers\Admin;

use App\Services\Billing\ReporteMensualIvaService;
use App\Services\Admin\Facturacion\ViewModels\FacturacionReportesMensualViewModel;

final class ReporteMensualIvaController
{
    private function normalizeFilters(array $src): array
    {
        return [
            'from_month' => (string)($src['from_month'] ?? ''),
            'to_month' => (string)($src['to_month'] ?? ''),
            'arca_env' => (string)($src['arca_env'] ?? ''),
        ];
    }

    public function index(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new ReporteMensualIvaService(db());
        $data = $svc->build($filters);
        $vm = new FacturacionReportesMensualViewModel();

        render_page('admin/facturacion/reportes/mensual', $vm->build($data), [
            'pageId' => 'admin-facturacion-reporte-mensual',
        ]);
    }

    public function export(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new ReporteMensualIvaService(db());
        $data = $svc->build($filters);

        $rows = $data['rows'] ?? [];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="reporte_mensual_iva.csv"');

        $out = fopen('php://output', 'w');

        fputcsv($out, [
            'Periodo',
            'Comprobantes',
            'Facturas',
            'Notas de credito',
            'Neto',
            'IVA',
            'Total',
            'Factura A Total',
            'Factura B Total',
            'Factura C Total',
            'NC A Total',
            'NC B Total',
            'NC C Total',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                (string)($r['ym'] ?? ''),
                (string)($r['comprobantes_count'] ?? 0),
                (string)($r['facturas_count'] ?? 0),
                (string)($r['notas_credito_count'] ?? 0),
                (string)($r['neto'] ?? 0),
                (string)($r['iva'] ?? 0),
                (string)($r['total'] ?? 0),
                (string)($r['fa_total'] ?? 0),
                (string)($r['fb_total'] ?? 0),
                (string)($r['fc_total'] ?? 0),
                (string)($r['nca_total'] ?? 0),
                (string)($r['ncb_total'] ?? 0),
                (string)($r['ncc_total'] ?? 0),
            ]);
        }

        fclose($out);
    }
}

