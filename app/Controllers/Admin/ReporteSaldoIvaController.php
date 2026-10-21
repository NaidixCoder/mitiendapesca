<?php
namespace App\Controllers\Admin;

use App\Services\Billing\ReporteSaldoIvaService;
use App\Services\Admin\Facturacion\ViewModels\FacturacionReportesSaldoIvaViewModel;

final class ReporteSaldoIvaController
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

        $svc = new ReporteSaldoIvaService(db());
        $data = $svc->build($filters);
        $vm = new FacturacionReportesSaldoIvaViewModel();

        render_page('admin/facturacion/reportes/saldo_iva', $vm->build($data), [
            'pageId' => 'admin-facturacion-reporte-saldo-iva',
        ]);
    }

    public function export(): void
    {
        admin_required();

        $filters = $this->normalizeFilters($_GET);

        $svc = new ReporteSaldoIvaService(db());
        $data = $svc->build($filters);

        $rows = $data['rows'] ?? [];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="saldo_iva_mensual.csv"');

        $out = fopen('php://output', 'w');

        fputcsv($out, [
            'Periodo',
            'IVA débito (ventas)',
            'IVA crédito (compras)',
            'Saldo IVA',
            'Ventas total',
            'Compras total',
            'Ventas comprobantes',
            'Compras comprobantes',
        ]);

        foreach ($rows as $r) {
            fputcsv($out, [
                (string)($r['ym'] ?? ''),
                (string)($r['ventas_iva'] ?? 0),
                (string)($r['compras_iva'] ?? 0),
                (string)($r['saldo_iva'] ?? 0),
                (string)($r['ventas_total'] ?? 0),
                (string)($r['compras_total'] ?? 0),
                (string)($r['ventas_comprobantes'] ?? 0),
                (string)($r['compras_comprobantes'] ?? 0),
            ]);
        }

        fclose($out);
    }
}

